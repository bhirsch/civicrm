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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */




function civicrm_setup( $filesDirectory ) {
    global $crmPath, $sqlPath, $pkgPath, $tplPath;
    global $compileDir;

    $pkgPath = $crmPath . DIRECTORY_SEPARATOR . 'packages';
    set_include_path( $crmPath . PATH_SEPARATOR .
                      $pkgPath . PATH_SEPARATOR .
                      get_include_path( ) );

    $sqlPath = $crmPath . DIRECTORY_SEPARATOR . 'sql';
    $tplPath = $crmPath . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'CRM' . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR;

    if ( ! is_dir( $filesDirectory ) ) {
        mkdir( $filesDirectory, 0777 );
        chmod( $filesDirectory, 0777 );
    }

    $scratchDir   = $filesDirectory . DIRECTORY_SEPARATOR . 'civicrm';
    if ( ! is_dir( $scratchDir ) ) {
        mkdir( $scratchDir, 0777 );
    }
    
    $compileDir        = $scratchDir . DIRECTORY_SEPARATOR . 'templates_c' . DIRECTORY_SEPARATOR;
    if ( ! is_dir( $compileDir ) ) {
        mkdir( $compileDir, 0777 );
    }
    $compileDir = addslashes( $compileDir );
}

function civicrm_write_file( $name, &$buffer ) {
    $fd  = fopen( $name, "w" );
    if ( ! $fd ) {
        die( "Cannot open $name" );

    }
    fputs( $fd, $buffer );
    fclose( $fd );
}

function civicrm_main( &$config ) {
    global $sqlPath, $crmPath, $installType;

    if ( $installType == 'drupal' ) {
        global $cmsPath;
        $siteDir = getSiteDir( $cmsPath, $_SERVER['SCRIPT_FILENAME'] );

        civicrm_setup( $cmsPath . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 
                       $siteDir . DIRECTORY_SEPARATOR . 'files' );
    } elseif ( $installType == 'standalone' ) {
        $filesDirectory = $crmPath . DIRECTORY_SEPARATOR . 'standalone' . DIRECTORY_SEPARATOR . 'files';
        civicrm_setup( $filesDirectory );
    }

    $dsn = "mysql://{$config['mysql']['username']}:{$config['mysql']['password']}@{$config['mysql']['server']}/{$config['mysql']['database']}?new_link=true";

    civicrm_source( $dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm.mysql'   );

    if ( isset( $config['loadGenerated'] ) &&
         $config['loadGenerated'] ) {
        civicrm_source( $dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_generated.mysql', true );
    } else {
        if (isset($config['seedLanguage'])
            and preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $config['seedLanguage'])
            and file_exists($sqlPath . DIRECTORY_SEPARATOR . "civicrm_data.{$config['seedLanguage']}.mysql")
            and file_exists($sqlPath . DIRECTORY_SEPARATOR . "civicrm_acl.{$config['seedLanguage']}.mysql" )) {
            civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . "civicrm_data.{$config['seedLanguage']}.mysql");
            civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . "civicrm_acl.{$config['seedLanguage']}.mysql" );
        } else {
            civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_data.mysql');
            civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_acl.mysql' );
        }
    }
    
    // generate backend settings file
    if ( $installType == 'drupal' ) {
        $siteDir    = getSiteDir( $cmsPath, $_SERVER['SCRIPT_FILENAME'] );
        $configFile =
            $cmsPath  . DIRECTORY_SEPARATOR .
            'sites'   . DIRECTORY_SEPARATOR .
            $siteDir  . DIRECTORY_SEPARATOR .
            'civicrm.settings.php';
    } elseif ( $installType == 'standalone' ) {
        $configFile =
            $crmPath     . DIRECTORY_SEPARATOR .
            'standalone' . DIRECTORY_SEPARATOR .
            'civicrm.settings.php';
    }

    $string = civicrm_config( $config );
    civicrm_write_file( $configFile,
                        $string );
}

function civicrm_source( $dsn, $fileName, $lineMode = false ) {
    global $crmPath;

    require_once 'DB.php';

    $db  =& DB::connect( $dsn );
    if ( PEAR::isError( $db ) ) {
        die( "Cannot open $dsn: " . $db->getMessage( ) );
    }

    if ( ! $lineMode ) {
        $string = file_get_contents( $fileName );

        // change \r\n to fix windows issues
        $string = ereg_replace("\r\n", "\n", $string );

        //get rid of comments starting with # and --
        $string = ereg_replace("\n#[^\n]*\n", "\n", $string );
        $string = ereg_replace("\n\-\-[^\n]*\n", "\n", $string );
        
        $queries  = preg_split('/;$/m', $string);
        foreach ( $queries as $query ) {
            $query = trim( $query );
            if ( ! empty( $query ) ) {
                $res =& $db->query( $query );
                if ( PEAR::isError( $res ) ) {
                    die( "Cannot execute $query: " . $res->getMessage( ) );
                }
            }
        }
    } else {
        $fd = fopen( $fileName, "r" );
        while ( $string = fgets( $fd ) ) {
            $string = ereg_replace("\n#[^\n]*\n", "\n", $string );
            $string = ereg_replace("\n\-\-[^\n]*\n", "\n", $string );
            $string = trim( $string );
            if ( ! empty( $string ) ) {
                $res =& $db->query( $string );
                if ( PEAR::isError( $res ) ) {
                    die( "Cannot execute $string: " . $res->getMessage( ) );
                }
            }
        }
    }

}

function civicrm_config( &$config ) {
    global $crmPath, $comPath;
    global $compileDir;
    global $tplPath;
    global $installType;

    $params = array(
                    'crmRoot' => $crmPath,
                    'templateCompileDir' => $compileDir,
                    'frontEnd' => 0,
                    'dbUser' => $config['mysql']['username'],
                    'dbPass' => $config['mysql']['password'],
                    'dbHost' => $config['mysql']['server'],
                    'dbName' => $config['mysql']['database'],
                    );
    
    if ( $installType == 'drupal' ) {
        $params['cms']        = 'Drupal';
        $params['baseURL']    = civicrm_cms_base( );
        $params['CMSdbUser']  = $config['drupal']['username'];
        $params['CMSdbPass']  = $config['drupal']['password'];
        $params['CMSdbHost']  = $config['drupal']['server'];
        $params['CMSdbName']  = $config['drupal']['database'];
    } elseif ( $installType == 'standalone' ) {
        $params['cms']            = 'Standalone';
        $params['baseURL']        = civicrm_cms_base( )  . 'standalone/';
    }

    $str = file_get_contents( $tplPath . 'civicrm.settings.php.tpl' );
    foreach ( $params as $key => $value ) { 
        $str = str_replace( '%%' . $key . '%%', $value, $str ); 
    } 
    return trim( $str );
}

function civicrm_cms_base( ) {
    global $installType;

    // for drupal
    $numPrevious = 6;

    // for standalone
    if ( $installType == 'standalone' ) {
        $numPrevious = 2;
    }

    if ( ! isset( $_SERVER['HTTPS'] ) ||
         strtolower( $_SERVER['HTTPS'] )  == 'off' ) {
        $url = 'http://' . $_SERVER['HTTP_HOST'];
    } else {
        $url = 'https://' . $_SERVER['HTTP_HOST'];
    }

    $baseURL = $_SERVER['SCRIPT_NAME'];

    for ( $i = 1; $i <= $numPrevious; $i++ ) {
        $baseURL = dirname( $baseURL );
    }

    // remove the last directory separator string from the directory
    if ( substr( $baseURL, -1, 1 ) == DIRECTORY_SEPARATOR ) {
        $baseURL = substr( $baseURL, 0, -1 );
    }

    // also convert all DIRECTORY_SEPARATOR to the forward slash for windoze
    $baseURL = str_replace( DIRECTORY_SEPARATOR, '/', $baseURL );

    if ( $baseURL != '/' ) {
        $baseURL .= '/';
    }

    return $url . $baseURL;
}

function civicrm_home_url( ) {
    $drupalURL = civicrm_cms_base( );
    return $drupalURL . 'index.php?q=civicrm';
}
