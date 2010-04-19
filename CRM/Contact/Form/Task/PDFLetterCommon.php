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
 * This class provides the common functionality for sending email to
 * one or a group of contact ids. This class is reused by all the search
 * components in CiviCRM (since they all have send email as a task)
 */
class CRM_Contact_Form_Task_PDFLetterCommon
{
    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    static function preProcess( &$form ) 
    {
        require_once 'CRM/Core/BAO/MessageTemplates.php';
        $messageText    = array( );
        $messageSubject = array( );
        $dao =& new CRM_Core_BAO_MessageTemplates( );
        $dao->is_active= 1;
        $dao->find();
        while ( $dao->fetch() ){
            $messageText   [$dao->id] = $dao->msg_text;
            $messageSubject[$dao->id] = $dao->msg_subject;
        }

        $form->assign( 'message'       , $messageText    );
        $form->assign( 'messageSubject', $messageSubject );


    }

    static function preProcessSingle( &$form, $cid )
    {
        $form->_contactIds = array( $cid );
    }


    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    static function buildQuickForm( &$form )
    {
        $form->assign('totalSelectedContacts',count($form->_contactIds));

        require_once "CRM/Mailing/BAO/Mailing.php";
        CRM_Mailing_BAO_Mailing::commonLetterCompose( $form );
        
        $form->addDefaultButtons( ts('Make PDF Letters') );
        
        $form->addFormRule( array( 'CRM_Contact_Form_Task_PDFLetterCommon', 'formRule' ), $form );
    }

    /** 
     * form rule  
     *  
     * @param array $fields    the input form values  
     * @param array $dontCare   
     * @param array $self      additional values form 'this'  
     *  
     * @return true if no errors, else array of errors
     * @access public  
     * 
     */  
    static function formRule($fields, $dontCare, &$self) 
    {
        $errors = array();
        $template =& CRM_Core_Smarty::singleton( );

        //Added for CRM-1393
        if( CRM_Utils_Array::value('saveTemplate',$fields) && empty($fields['saveTemplateName']) ){
            $errors['saveTemplateName'] = ts("Enter name to save message template");
        }
        return empty($errors) ? true : $errors;
    }
    
    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    static function postProcess( &$form ) 
    {
        $formValues = $form->controller->exportValues( $form->getName( ) );

        // process message template
        require_once 'CRM/Core/BAO/MessageTemplates.php';
        if ( CRM_Utils_Array::value( 'saveTemplate', $formValues ) || CRM_Utils_Array::value( 'updateTemplate', $formValues ) ) {
            $messageTemplate = array( 'msg_text'    => NULL,
                                      'msg_html'    => $formValues['html_message'],
                                      'msg_subject' => NULL,
                                      'is_active'   => true );

            if ( $formValues['saveTemplate'] ) {
                $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
                CRM_Core_BAO_MessageTemplates::add( $messageTemplate );
            }

            if ( $formValues['template'] && $formValues['updateTemplate']  ) {
                $messageTemplate['id'] = $formValues['template'];
                unset($messageTemplate['msg_title']);
                CRM_Core_BAO_MessageTemplates::add( $messageTemplate );
            }
        }



        require_once 'dompdf/dompdf_config.inc.php';
        $html = '<html><head><style>body { margin: 56px; }</style></head><body>';
        require_once 'api/v2/Contact.php';
        require_once 'CRM/Utils/Token.php';

        $tokens = array( );
        CRM_Utils_Hook::tokens( $tokens );
        $categories = array_keys( $tokens );        
				
		$html_message = $formValues['html_message'];
        
        require_once 'CRM/Activity/BAO/Activity.php';
		$messageToken = CRM_Activity_BAO_Activity::getTokens( $html_message );  

		$returnProperties = array();
        if( isset( $messageToken['contact'] ) ) { 
            foreach ( $messageToken['contact'] as $key => $value ) {
                $returnProperties[$value] = 1; 
            }
        }
                    
        require_once 'CRM/Mailing/BAO/Mailing.php';
        $mailing = & new CRM_Mailing_BAO_Mailing();
		
        $first = TRUE;

        foreach ($form->_contactIds as $item => $contactId) {
            $params  = array( 'contact_id'  => $contactId );

			list( $contact ) = $mailing->getDetails($params, $returnProperties, false );
            
            if ( civicrm_error( $contact ) ) {
                $notSent[] = $contactId;
                continue;
            }
	
			$tokenHtml    = CRM_Utils_Token::replaceContactTokens( $html_message, $contact[$contactId], true       , $messageToken);
            $tokenHtml    = CRM_Utils_Token::replaceHookTokens   ( $tokenHtml, $contact[$contactId]   , $categories, true         );
            
            if ( $first == TRUE ) {
              $first = FALSE;
              $html .= $tokenHtml;
            } else {
              $html .= "<div STYLE='page-break-after: always'></div>$tokenHtml";
            }

        }
        
        $html .= '</body></html>';
        
        require_once 'CRM/Utils/PDF/Utils.php';
        CRM_Utils_PDF_Utils::html2pdf( $html, "CiviLetter.pdf", 'portrait' ); 
        exit(1);
    }//end of function
}


