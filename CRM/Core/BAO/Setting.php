<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * file contains functions used in civicrm configuration
 * 
 */
class CRM_Core_BAO_Setting 
{
    /**
     * Function to add civicrm settings
     *
     * @params array $params associated array of civicrm variables
     *
     * @return null
     * @static
     */
    static function add(&$params) 
    {
        CRM_Core_BAO_Setting::fixParams($params);

        require_once "CRM/Core/DAO/Domain.php";
        $domain = new CRM_Core_DAO_Domain();
        $domain->id = CRM_Core_Config::domainID( );
        $domain->find(true);
        if ($domain->config_backend) {
            $values = unserialize($domain->config_backend);
            CRM_Core_BAO_Setting::formatParams($params, $values);
        }

        // unset any of the variables we read from file that should not be stored in the database
        // the username and certpath are stored flat with _test and _live
        // check CRM-1470
        $skipVars = array( 'dsn', 'templateCompileDir',
                           'userFrameworkDSN', 
                           'userFrameworkBaseURL', 'userFrameworkClass', 'userHookClass',
                           'userPermissionClass', 'userFrameworkURLVar',
                           'qfKey', 'gettextResourceDir', 'cleanURL' );
        foreach ( $skipVars as $var ) {
            unset( $params[$var] );
        }
                           
        $domain->config_backend = serialize($params);
        $domain->save();
    }

    /**
     * Function to fix civicrm setting variables
     *
     * @params array $params associated array of civicrm variables
     *
     * @return null
     * @static
     */
    static function fixParams(&$params) 
    {
        // in our old civicrm.settings.php we were using ISO code for country and
        // province limit, now we have changed it to use ids

        $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode( );
        
        $specialArray = array('countryLimit', 'provinceLimit');
        
        foreach($params as $key => $value) {
            if ( in_array($key, $specialArray) && is_array($value) ) {
                foreach( $value as $k => $val ) {
                    if ( !is_numeric($val) ) {
                        $params[$key][$k] = array_search($val, $countryIsoCodes); 
                    }
                }
            } else if ( $key == 'defaultContactCountry' ) {
                if ( !is_numeric($value) ) {
                    $params[$key] =  array_search($value, $countryIsoCodes); 
                }
            }
        }
    }

    /**
     * Function to format the array containing before inserting in db
     *
     * @param  array $params associated array of civicrm variables(submitted)
     * @param  array $values associated array of civicrm variables stored in db
     *
     * @return null
     * @static
     */
    static function formatParams(&$params, &$values) 
    {
        if ( empty( $params ) ||
             ! is_array( $params ) ) {
            $params = $values;
        } else {
            foreach ($params as $key => $val) {
                if ( array_key_exists($key, $values)) {
                    unset($values[$key]);
                }
            }
            $params = array_merge($params, $values);
        }
    }

    /**
     * Function to retrieve the settings values from db
     *
     * @return array $defaults  
     * @static
     */
    static function retrieve(&$defaults) 
    {
        require_once "CRM/Core/DAO/Domain.php";
        $domain = new CRM_Core_DAO_Domain();
        $domain->selectAdd( );

        if ( CRM_Utils_Array::value( 'q', $_GET ) == 'civicrm/upgrade' ) {
            $domain->selectAdd( 'config_backend' );
        } else {
            $domain->selectAdd( 'config_backend, locales' );
        }
        
        $domain->id = CRM_Core_Config::domainID( );
        $domain->find(true);
        if ($domain->config_backend) {
            $defaults   = unserialize($domain->config_backend);

            // set proper monetary formatting, falling back to en_US and C (CRM-2782)
            setlocale(LC_MONETARY, $defaults['lcMonetary'].'.utf8', $defaults['lcMonetary'], 'en_US.utf8', 'en_US', 'C');

            $skipVars = array( 'dsn', 'templateCompileDir',
                               'userFrameworkDSN', 
                               'userFrameworkBaseURL', 'userFrameworkClass', 'userHookClass',
                               'userPermissionClass', 'userFrameworkURLVar',
                               'qfKey', 'gettextResourceDir', 'cleanURL' );
            foreach ( $skipVars as $skip ) {
                if ( array_key_exists( $skip, $defaults ) ) {
                    unset( $defaults[$skip] );
                }
            }
            
            // since language field won't be present before upgrade.
            if ( CRM_Utils_Array::value( 'q', $_GET ) == 'civicrm/upgrade' ) {
                return;
            }

            // are we in a multi-language setup?
            $multiLang = $domain->locales ? true : false;

            // set the current language
            $lcMessages = null;

            $session =& CRM_Core_Session::singleton();

            // on multi-lang sites based on request and civicrm_uf_match
            if ($multiLang) {
                require_once 'CRM/Utils/Request.php';
                $lcMessagesRequest = CRM_Utils_Request::retrieve('lcMessages', 'String', $this);
                $languageLimit = array( ); 
                if ( array_key_exists( 'languageLimit', $defaults ) && is_array( $defaults['languageLimit'] ) ) {
                    $languageLimit = $defaults['languageLimit'];
                }
                
                if ( in_array($lcMessagesRequest, array_keys( $languageLimit ) ) ) {
                    $lcMessages = $lcMessagesRequest;
                } else {
                    $lcMessagesRequest = null;
                }

                if (!$lcMessagesRequest) {
                    $lcMessagesSession = $session->get('lcMessages');
                    if ( in_array( $lcMessagesSession, array_keys( $languageLimit ) ) ) {
                        $lcMessages = $lcMessagesSession;
                    } else {
                        $lcMessagesSession = null;
                    }
                }

                if ($lcMessagesRequest) {
                    require_once 'CRM/Core/DAO/UFMatch.php';
                    $ufm = new CRM_Core_DAO_UFMatch();
                    $ufm->contact_id = $session->get('userID');
                    if ($ufm->find(true)) {
                        $ufm->language = $lcMessages;
                        $ufm->save();
                    }
                    $session->set('lcMessages', $lcMessages);
                }
                
                if (!$lcMessages and $session->get('userID')) {
                    require_once 'CRM/Core/DAO/UFMatch.php';
                    $ufm = new CRM_Core_DAO_UFMatch();
                    $ufm->contact_id = $session->get('userID');
                    if ( $ufm->find( true ) && 
                         in_array( $ufm->language, array_keys( $languageLimit ) ) ) {
                        $lcMessages = $ufm->language;
                    }
                    $session->set('lcMessages', $lcMessages);
                }
            }

            // if unset and the install is so configured, try to inherit the language from the hosting CMS
            if ($lcMessages === null and CRM_Utils_Array::value( 'inheritLocale', $defaults ) ) {
                require_once 'CRM/Utils/System.php';
                $lcMessages = CRM_Utils_System::getUFLocale();
                require_once 'CRM/Core/BAO/CustomOption.php';
                if ($domain->locales and !in_array($lcMessages, explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $domain->locales))) {
                    $lcMessages = null;
                }
            }
            
            if ( $lcMessages ) {
                // update config lcMessages - CRM-5027 fixed.
                $defaults['lcMessages'] = $lcMessages;
            } else {
                // if a single-lang site or the above didn't yield a result, use default
                $lcMessages = $defaults['lcMessages'];
            }
            
            // set suffix for table names - use views if more than one language
            global $dbLocale;
            $dbLocale = $multiLang ? "_{$lcMessages}" : '';

            // FIXME: an ugly hack to fix CRM-4041
            global $tsLocale;
            $tsLocale = $lcMessages;

            // FIXME: as goo^W bad place as any to fix CRM-5428 (to be moved to a sane location along with the above)
            if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
        }
    }


    static function getConfigSettings( ) {
        $config =& CRM_Core_Config::singleton( );

        $url = $dir = $siteName = null;
        if ( $config->userFramework == 'Joomla' ) {
            $url = preg_replace( '|administrator/components/com_civicrm/civicrm/|',
                                 '',
                                 $config->userFrameworkResourceURL );

            // lets use imageUploadDir since we dont mess around with its values
            // in the config object, lets kep it a bit generic since folks
            // might have different values etc
            $dir =  preg_replace( '|/media/civicrm/.*$|',
                                  '/files/',
                                  $config->imageUploadDir );
        } else {
            $url = preg_replace( '|sites/[\w\.\-\_]+/modules/civicrm/|',
                                 '',
                                 $config->userFrameworkResourceURL );
            
            // lets use imageUploadDir since we dont mess around with its values
            // in the config object, lets kep it a bit generic since folks
            // might have different values etc
            $dir =  preg_replace( '|/files/civicrm/.*$|',
                                  '/files/',
                                  $config->imageUploadDir );

            $matches = array( );
            if ( preg_match( '|/sites/([\w\.\-\_]+)/|',
                             $config->imageUploadDir,
                             $matches ) ) {
                $siteName = $matches[1];
                if ( $siteName ) {
                    $siteName = "/sites/$siteName/";
                }
            }
        }


        return array( $url, $dir, $siteName );
    }

    static function getBestGuessSettings( ) {
        $config =& CRM_Core_Config::singleton( );

        $url = $config->userFrameworkBaseURL;
        $dir = preg_replace( '|civicrm/templates_c/.*$|',
                             '',
                             $config->templateCompileDir );

        $siteName = null;
        if ( $config->userFramework != 'Joomla' ) {
            $matches = array( );
            if ( preg_match( '|/sites/([\w\.\-\_]+)/|',
                             $config->templateCompileDir,
                             $matches ) ) {
                $siteName = $matches[1];
                if ( $siteName ) {
                    $siteName = "/sites/$siteName/";
                }
            }
        }
        
        return array( $url, $dir, $siteName );
    }

    static function doSiteMove( ) {
        // get the current and guessed values
        list( $oldURL, $oldDir, $oldSiteName ) = self::getConfigSettings( );
        list( $newURL, $newDir, $newSiteName ) = self::getBestGuessSettings( );
    
        require_once 'CRM/Utils/Request.php';

        // retrieve these values from the argument list 
        $variables = array( 'URL', 'Dir', 'SiteName', 'Val_1', 'Val_2', 'Val_3' );
        $states     = array( 'old', 'new' );
        foreach ( $variables as $varSuffix ) {
            foreach ( $states as $state ) {
                $var = "{$state}{$varSuffix}";
                if ( ! isset( $$var ) ) {
                    $$var = null;
                }
                $$var = CRM_Utils_Request::retrieve( $var,
                                                     'String',
                                                     CRM_Core_DAO::$_nullArray,
                                                     false,
                                                     $$var,
                                                     'REQUEST' );
            }
        }

        $from = $to = array( );
        foreach ( $variables as $varSuffix ) {
            $oldVar = "old{$varSuffix}";
            $newVar = "new{$varSuffix}";
            if ( $$oldVar && $$newVar ) {
                $from[]  = $$oldVar;
                $to[]    = $$newVar;
            }
        }

        $sql = "
SELECT config_backend
FROM   civicrm_domain
WHERE  id = %1
";
        $params = array( 1 => array( CRM_Core_Config::domainID( ), 'Integer' ) );
        $configBackend = CRM_Core_DAO::singleValueQuery( $sql, $params );
        if ( ! $configBackend ) {
            CRM_Core_Error::fatal( );
        }
        $configBackend = unserialize( $configBackend );

        $configBackend = str_replace( $from,
                                      $to  ,
                                      $configBackend );

        $configBackend = serialize( $configBackend );
        $sql = "
UPDATE civicrm_domain
SET    config_backend = %2
WHERE  id = %1
";
        $params[2] = array( $configBackend, 'String' );
        CRM_Core_DAO::executeQuery( $sql, $params );

        $config =& CRM_Core_Config::singleton( );

        // clear the template_c and upload directory also
        $config->cleanup( 3, true );
    
        // clear all caches
        CRM_Core_Config::clearDBCache( );

        $resetSessionTable = CRM_Utils_Request::retrieve( 'resetSessionTable',
                                                          'Boolean',
                                                          CRM_Core_DAO::$_nullArray,
                                                          false,
                                                          false,
                                                          'REQUEST' );
        if ( $config->userFramework == 'Drupal' &&
             $resetSessionTable ) {
            db_query("DELETE FROM {sessions} WHERE 1");
        } else {
            $session =& CRM_Core_Session::singleton( );
            $session->reset( 2 );
        }

    }

}


