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

class CRM_Utils_ReCAPTCHA {

    protected $_captcha = null;

    protected $_name    = null;

    protected $_url     = null;

    protected $_phrase  = null;

    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;

    /**
     * singleton function used to manage this object
     *
     * @param string the key to permit session scope's
     *
     * @return object
     * @static
     *
     */
    static function &singleton( ) {
        if (self::$_singleton === null ) {
            self::$_singleton =& new CRM_Utils_ReCAPTCHA( );
        }
        return self::$_singleton;
    }

    function __construct( ) {
    }

    /**
     * Add element to form
     *
     */
    function add( &$form ) {
        $error  = null;
        $config =& CRM_Core_Config::singleton( );
        $useSSL = false;
        require_once 'packages/recaptcha/recaptchalib.php';
      
        // See if we are using SSL
        if ( isset( $_SERVER['HTTPS'] ) &&
            strtolower( $_SERVER['HTTPS'] ) != 'off' ) {     
            $useSSL = true;
        }       
        $html = recaptcha_get_html( $config->recaptchaPublicKey, $error, $useSSL );

        $form->assign( 'recaptchaHTML', $html );
        $form->add( 'text',
                    'recaptcha_challenge_field',
                    null,
                    null,
                    true );
        $form->add( 'hidden',
                    'recaptcha_response_field',
                    'manual_challenge' );

        $form->registerRule( 'recaptcha', 'callback', 'validate', 'CRM_Utils_ReCAPTCHA' );
        $form->addRule( 'recaptcha_challenge_field',
                        ts( 'Input text must match the phrase in the image. Please review the image and re-enter matching text.' ), 
                        'recaptcha',
                        $form );

    }

    function validate( $value, &$form ) {
        $config =& CRM_Core_Config::singleton( );

        $resp = recaptcha_check_answer( $config->recaptchaPrivateKey,
                                        $_SERVER['REMOTE_ADDR'],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"] );
        return $resp->is_valid;
    }

}


