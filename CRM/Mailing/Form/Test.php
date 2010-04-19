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

/**
 * Form to send test mail
 */
class CRM_Mailing_Form_Test extends CRM_Core_Form 
{
    /**
     * This function sets the default values for the form.
     * 
     * @access public
     * @return None
     */
    function setDefaultValues( ) 
    {
        $count = $this->get('count');
        $this->assign('count',$count);    
    }

    public function buildQuickForm() 
    {
        $session =& CRM_Core_Session::singleton();
        $this->add('text', 'test_email', ts('Send to This Address'));
        $defaults['test_email'] = $session->get('ufUniqID');
        $qfKey = $this->get('qfKey');
        
        $this->add('select',
                   'test_group',
                   ts('Send to This Group'),
                   array( '' => ts( '- none -' ) ) + CRM_Core_PseudoConstant::group( 'Mailing' ) );
        $this->setDefaults($defaults);

        $this->add('submit', 'sendtest', ts('Send a Test Mailing'));
        
        //FIXME : currently we are hiding save an continue later when
        //search base mailing, we should handle it when we fix CRM-3876
        $buttons = array( array(  'type'  => 'back',
                                  'name'  => '<< Previous'),
                          array(  'type'  => 'next',
                                  'name'  => ts('Next >>'),
                                  'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
                                  'isDefault' => true ),
                          array ( 'type'      => 'submit',
                                  'name'      => ts('Save & Continue Later') ),
                          array(  'type'  => 'cancel',
                                  'name'  => ts('Cancel') ),
                          );
        if ( $this->get( 'context' ) == 'search' && $this->get( 'ssID' ) ) {
            $buttons = array( array(  'type'  => 'back',
                                      'name'  => '<< Previous'),
                              array(  'type'  => 'next',
                                      'name'  => ts('Next >>'),
                                      'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
                                      'isDefault' => true ),
                              array(  'type'  => 'cancel',
                                      'name'  => ts('Cancel') ),
                              );
        }
        $this->addButtons( $buttons );
        
        $mailingID = $this->get('mailing_id' );
        $textFile = $this->get('textFile');
        $htmlFile = $this->get('htmlFile');
        $subject = $this->get('subject');
        $this->assign('subject', $subject);

        $this->addFormRule(array('CRM_Mailing_Form_Test', 'testMail'), $this );
        $preview = array();
        if ($textFile) {
            $preview['text_link'] = CRM_Utils_System::url('civicrm/mailing/preview', "type=text&qfKey=$qfKey");
        }
        if ($htmlFile) {
            $preview['html_link'] = CRM_Utils_System::url('civicrm/mailing/preview', "type=html&qfKey=$qfKey");
        }

        require_once 'CRM/Core/BAO/File.php';
        $preview['attachment'] = CRM_Core_BAO_File::attachmentInfo( 'civicrm_mailing',
                                                                    $mailingID );
        $this->assign('preview', $preview);
    }
    
    /**
     * Form rule to send out a test mailing.
     *
     * @param array $params     Array of the form values
     * @param array $files      Any files posted to the form
     * @param array $self       an current this object
     * @return boolean          true on succesful SMTP handoff
     * @access public
     */
    public function &testMail($testParams, &$files, &$self) 
    {
        $error = null;
        
        $urlString = "civicrm/mailing/send";
        $urlParams = "_qf_Test_display=true&qfKey={$testParams['qfKey']}";
        
        $ssID    = $self->get( 'ssID' );
        $context = $self->get( 'context' );
        if ( $ssID && $context == 'search' ) {
            if ( $self->_action == CRM_Core_Action::BASIC ) {
                $fragment = 'search';
            } else if ( $self->_action == CRM_Core_Action::PROFILE ) {
                $fragment = 'search/builder';
            } else if ( $self->_action == CRM_Core_Action::ADVANCED ) {
                $fragment = 'search/advanced';
            } else {
                $fragment = 'search/custom';
            }
            $urlString = "civicrm/contact/" . $fragment;
        }
        $emails = null;
        if ( CRM_Utils_Array::value( 'sendtest', $testParams ) ) {
            if (!($testParams['test_group'] || $testParams['test_email'] )) {
                CRM_Core_Session::setStatus( ts("Your did not provided any email address or selected any group. No test mail is sent.") );
                $error = true;
            }

            if ( $testParams['test_email'] ) {
                $emailAdd = explode( ',', $testParams['test_email'] );
                foreach ( $emailAdd as $key => $value ) {
                    $email = trim($value);
                    $testParams['emails'][] = $email;
                    $emails .= $emails?",'$email'":"'$email'";
                    if ( !CRM_Utils_Rule::email($email) ) {
                        CRM_Core_Session::setStatus( ts("Please enter valid email addresses only") );
                        $error = true;
                    }
                }
            }
            
            if ($error) {
                $url = CRM_Utils_System::url( $urlString, $urlParams );
                CRM_Utils_System::redirect($url);
                return true;
            }
        } 
        
        if ( CRM_Utils_Array::value( '_qf_Test_submit', $testParams ) ) {
            //when user perform mailing from search context 
            //redirect it to search result CRM-3711.
            if ( $ssID && $context == 'search' ) {
                $draftURL = CRM_Utils_System::url( 'civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1' );
                $status = ts("Your mailing has been saved. You can continue later by clicking the 'Continue' action to resume working on it.<br /> From <a href='%1'>Draft and Unscheduled Mailings</a>.", array( 1 => $draftURL ) );
                CRM_Core_Session::setStatus( $status );
                
                //replace user context to search.
                $urlParams = "force=1&reset=1&ssID={$ssID}";
                $url = CRM_Utils_System::url( $urlString, $urlParams );
                CRM_Utils_System::redirect( $url );
            } else { 
                $status = ts("Your mailing has been saved. Click the 'Continue' action to resume working on it.");
                CRM_Core_Session::setStatus( $status );
                $url = CRM_Utils_System::url( 'civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1' );
                CRM_Utils_System::redirect($url);
            }
        }
        
        if ( CRM_Utils_Array::value( '_qf_Import_refresh', $_POST ) || 
             CRM_Utils_Array::value( '_qf_Test_next', $testParams ) ||
             !CRM_Utils_Array::value( 'sendtest', $testParams ) ) {
            return true;
        }
        
        require_once 'CRM/Mailing/BAO/Job.php';
        $job =& new CRM_Mailing_BAO_Job();
        $job->mailing_id = $self->get('mailing_id' );
        $job->is_test    = true;
        $job->save( );
        $newEmails  = null;
        $session    =& CRM_Core_Session::singleton();
        if ( !empty($testParams['emails']) ) {
            $query = "
                      SELECT id, contact_id, email  
                      FROM civicrm_email  
                      WHERE civicrm_email.email IN ($emails)";
            
            $dao =& CRM_Core_DAO::executeQuery( $query );
            $emailDetail = array( );
            // fetch contact_id and email id for all existing emails
            while ( $dao->fetch( ) ) {
                $emailDetail[$dao->email] = array(
                                                  'contact_id' => $dao->contact_id,
                                                  'email_id'   => $dao->id
                                                  );
            }
            
            $dao->free( );
            foreach ( $testParams['emails'] as $key => $email ) {
                $email = trim($email);
                $contactId = $emailId = null;
                if ( array_key_exists( $email, $emailDetail) ) {
                    $emailId   = $emailDetail[$email]['email_id'];
                    $contactId = $emailDetail[$email]['contact_id'];
                }
                
                if ( !$contactId ) {
                    //create new contact.
                    $params = array( 'contact_type' => 'Individual',
                                     'email'        => array( 1 => array( 'email'            => $email,
                                                                          'is_primary'       => 1,
                                                                          'location_type_id' => 1 ) ) );
                    require_once 'CRM/Contact/BAO/Contact.php';
                    $contact   = CRM_Contact_BAO_Contact::create( $params );
                    $emailId   = $contact->email[0]->id;
                    $contactId = $contact->id;
                    $contact->free( );
                }
                $params = array(
                                'job_id'        => $job->id,
                                'email_id'      => $emailId,
                                'contact_id'    => $contactId
                                );
                require_once 'CRM/Mailing/Event/BAO/Queue.php';
                CRM_Mailing_Event_BAO_Queue::create($params);
            }
        }
        
        $testParams['job_id'] = $job->id;
        $isComplete = false;
        while (!$isComplete) {
            $isComplete = CRM_Mailing_BAO_Job::runJobs($testParams);
        }
       
        if ( CRM_Utils_Array::value( 'sendtest', $testParams ) ) {
            CRM_Core_Session::setStatus( ts("Your test message has been sent. Click 'Next' when you are ready to Schedule or Send your live mailing (you will still have a chance to confirm or cancel sending this mailing on the next page).") );
            $url = CRM_Utils_System::url( $urlString, $urlParams );
            CRM_Utils_System::redirect($url);
        }
        
        return true;
    }
    
    /**
     * Display Name of the form
     *
     * @access public
     * @return string
     */
    public function getTitle( ) {
        return ts( 'Test' );
    }

}


