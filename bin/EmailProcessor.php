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

define( 'EMAIL_ACTIVITY_TYPE_ID', null  );
define( 'MAIL_BATCH_SIZE'       , 50    );

class EmailProcessor {

    /**
     * Process the mailbox for all the settings from civicrm_mail_settings
     *
     * @param string $civiMail  if true, processing is done in CiviMail context, or Activities otherwise.
     * @return void
     */
    static function process( $civiMail = true ) {
        require_once 'CRM/Core/OptionGroup.php';
        $emailActivityTypeId = 
            ( defined('EMAIL_ACTIVITY_TYPE_ID') && EMAIL_ACTIVITY_TYPE_ID )  ? 
            EMAIL_ACTIVITY_TYPE_ID : CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                                     'Inbound Email', 
                                                                     'name' );
        if ( ! $emailActivityTypeId ) {
            CRM_Core_Error::fatal( ts( 'Could not find a valid Activity Type ID for Inbound Email' ) );
        }


        require_once 'CRM/Core/DAO/MailSettings.php';
        $dao = new CRM_Core_DAO_MailSettings;
        $dao->domain_id = CRM_Core_Config::domainID( );
        $dao->find( );
        
        while ( $dao->fetch() ) {
            // FIXME: legacy regexen to handle CiviCRM 2.1 address patterns, with domain id and possible VERP part
            $commonRegex = '/^' . preg_quote($dao->localpart) . '(b|bounce|c|confirm|o|optOut|r|reply|re|e|resubscribe|u|unsubscribe)\.(\d+)\.(\d+)\.(\d+)\.([0-9a-f]{16})(-.*)?@' . preg_quote($dao->domain) . '$/';
            $subscrRegex = '/^' . preg_quote($dao->localpart) . '(s|subscribe)\.(\d+)\.(\d+)@' . preg_quote($dao->domain) . '$/';
            
            // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
            $regex = '/^' . preg_quote($dao->localpart) . '(b|c|e|o|r|u)\.(\d+)\.(\d+)\.([0-9a-f]{16})@' . preg_quote($dao->domain) . '$/';

            // retrieve the emails
            require_once 'CRM/Mailing/MailStore.php';
            $store = CRM_Mailing_MailStore::getStore($dao->name);
            
            require_once 'api/Mailer.php';
            
            // process fifty at a time, CRM-4002
            while ($mails = $store->fetchNext(MAIL_BATCH_SIZE)) {
                foreach ($mails as $key => $mail) {
                    
                    // for every addressee: match address elements if it's to CiviMail
                    $matches = array();

                    if ( $civiMail ) {
                        foreach ($mail->to as $address) {
                            if (preg_match($regex, $address->email, $matches)) {
                                list($match, $action, $job, $queue, $hash) = $matches;
                                break;
                                // FIXME: the below elseifs should be dropped when we drop legacy support
                            } elseif (preg_match($commonRegex, $address->email, $matches)) {
                                list($match, $action, $_, $job, $queue, $hash) = $matches;
                                break;
                            } elseif (preg_match($subscrRegex, $address->email, $matches)) {
                                list($match, $action, $_, $job) = $matches;
                                break;
                            }
                        }
                    } else {
                        // if its the activities that needs to be processed ..
                        require_once 'CRM/Utils/Mail/Incoming.php';
                        $mailParams = CRM_Utils_Mail_Incoming::parseMailingObject( $mail );

                        require_once 'api/v2/Activity.php';
                        $params = _civicrm_activity_buildmailparams( $mailParams, $emailActivityTypeId );
                        $result = civicrm_activity_create( $params );

                        if ( $result['is_error'] ) {
                            $matches = false;
                            echo "Failed Processing: {$mail->subject}. Reason: {$result['error_message']}\n";
                        } else {
                            $matches = true;
                            echo "Processed: {$mail->subject}\n";
                        }
                    }
                    
                    // if $matches is empty, this email is not CiviMail-bound
                    if (!$matches) {
                        $store->markIgnored($key);
                        continue;
                    }
                    
                    // get $replyTo from either the Reply-To header or from From
                    // FIXME: make sure it works with Reply-Tos containing non-email stuff
                    $replyTo = $mail->getHeader('Reply-To') ? $mail->getHeader('Reply-To') : $mail->from->email;
                    
                    // handle the action by passing it to the proper API call
                    // FIXME: leave only one-letter cases when dropping legacy support
					if (! empty($action)) {
	                    switch ($action) {
	                    case 'b':
	                    case 'bounce':
	                        $text = '';
	                        if ($mail->body instanceof ezcMailText) {
	                            $text = $mail->body->text;
	                        } elseif ($mail->body instanceof ezcMailMultipart) {
	                            foreach ($mail->body->getParts() as $part) {
	                                if (isset($part->subType) and $part->subType == 'plain') {
	                                    $text = $part->text;
	                                    break;
	                                }
	                            }
	                        }
	                        crm_mailer_event_bounce($job, $queue, $hash, $text);
	                        break;
	                    case 'c':
	                    case 'confirm':
	                        crm_mailer_event_confirm($job, $queue, $hash);
	                        break;
	                    case 'o':
	                    case 'optOut':
	                        crm_mailer_event_domain_unsubscribe($job, $queue, $hash);
	                        break;
	                    case 'r':
	                    case 'reply':
	                        // instead of text and HTML parts (4th and 6th params) send the whole email as the last param
	                        crm_mailer_event_reply($job, $queue, $hash, null, $replyTo, null, $mail->generate());
	                        break;
	                    case 'e':
	                    case 're':
	                    case 'resubscribe':
	                        crm_mailer_event_resubscribe($job, $queue, $hash);
	                        break;
	                    case 's':
	                    case 'subscribe':
	                        crm_mailer_event_subscribe($mail->from->email, $job);
	                        break;
	                    case 'u':
	                    case 'unsubscribe':
	                        crm_mailer_event_unsubscribe($job, $queue, $hash);
	                        break;
	                    }
					}
					                    
                    $store->markProcessed($key);
                }
            }
        }
    }
}

// bootstrap the environment and run the processor
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(true);

require_once 'CRM/Core/Lock.php';
$lock = new CRM_Core_Lock('EmailProcessor');

if ($lock->isAcquired()) {
    // try to unset any time limits
    if (!ini_get('safe_mode')) set_time_limit(0);

    // check if the script is being used for civimail processing or email to 
    // activity processing.
    $isCiviMail = CRM_Utils_Array::value( 'emailtoactivity', $_REQUEST ) ? false : true;
    EmailProcessor::process($isCiviMail);
} else {
    throw new Exception('Could not acquire lock, another EmailProcessor process is running');
}

$lock->release();
