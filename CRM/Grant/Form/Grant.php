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

require_once 'CRM/Core/Form.php';
require_once "CRM/Custom/Form/CustomData.php";

/**
 * This class generates form components for processing a case
 * 
 */
class CRM_Grant_Form_Grant extends CRM_Core_Form
{
    /**
     * the id of the case that we are proceessing
     *
     * @var int
     * @protected
     */
    protected $_id;

    /**
     * the id of the contact associated with this contribution
     *
     * @var int
     * @protected
     */
    protected $_contactID;

    protected $_context;
    /** 
     * Function to set variables up before form is built 
     *                                                           
     * @return void 
     * @access public
     */ 
    public function preProcess()  
    {  
        $this->_contactID = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this );
        $this->_id        = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        $this->_context   = CRM_Utils_Request::retrieve( 'context', 'String', $this );
        
        $this->assign( 'action', $this->_action );
        $this->assign( 'context', $this->_context );
        
        //check permission for action.
        if ( !CRM_Core_Permission::checkActionPermission( 'CiviGrant', $this->_action ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );  
        }

        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return;
        }
        
        $this->_noteId =null;
        if ( $this->_id) {
            require_once 'CRM/Core/BAO/Note.php';
            $noteDAO               = & new CRM_Core_BAO_Note();
            $noteDAO->entity_table = 'civicrm_grant';
            $noteDAO->entity_id    = $this->_id;
            if ( $noteDAO->find(true) ) {
                $this->_noteId = $noteDAO->id;
            }
        }

		//build custom data
        CRM_Custom_Form_Customdata::preProcess( $this, null, null, 1, 'Grant', $this->_id );
    }
    
    function setDefaultValues( ) 
    {
        $defaults = array( );
        $defaults = parent::setDefaultValues();
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return $defaults;
        }
        
        $params['id'] =  $this->_id;
        if ( $this->_noteId ) {
            $defaults['note'] = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_Note', $this->_noteId, 'note' );
        }
        if ( $this->_id){
            CRM_Grant_BAO_Grant::retrieve( $params, $defaults);

            // fix the display of the monetary value, CRM-4038 
            require_once 'CRM/Utils/Money.php';
            if (isset($defaults['amount_total'])) {
                $defaults['amount_total'] = CRM_Utils_Money::format($defaults['amount_total'], null, '%a');
            }
            if (isset($defaults['amount_requested'])) {
                $defaults['amount_requested'] = CRM_Utils_Money::format($defaults['amount_requested'], null, '%a');
            }
            if (isset($defaults['amount_granted'])) {
                $defaults['amount_granted'] = CRM_Utils_Money::format($defaults['amount_granted'], null, '%a');
            }
            
            $dates = array( 'application_received_date',
                            'decision_date',
                            'money_transfer_date',
                            'grant_due_date' );
            
            foreach( $dates as $key ) {
                if ( $defaults[$key] ) {
                    list( $defaults[$key] ) = CRM_Utils_Date::setDateDefaults( $defaults[$key] );
                }
            }
        } else {
            require_once 'CRM/Utils/Date.php';
            list( $defaults['application_received_date'] ) = CRM_Utils_Date::setDateDefaults( );
        }
                
		// custom data set defaults
		$defaults += CRM_Custom_Form_Customdata::setDefaultValues( $this );
        return $defaults;
    }
    
    /** 
     * Function to build the form 
     * 
     * @return None 
     * @access public 
     */ 
    public function buildQuickForm( )  
    {         
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            $this->addButtons(array( 
                                    array ( 'type'      => 'next', 
                                            'name'      => ts('Delete'), 
                                            'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 
                                            'isDefault' => true   ), 
                                    array ( 'type'      => 'cancel', 
                                            'name'      => ts('Cancel') ), 
                                    ) 
                              );
            return;
        }
        
        require_once 'CRM/Core/OptionGroup.php';
        require_once 'CRM/Grant/BAO/Grant.php';
        $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_Grant');
        $grantType = CRM_Core_OptionGroup::values( 'grant_type' );
        $this->add('select', 'grant_type_id',  ts( 'Grant Type' ),
                   array( '' => ts( '- select -' ) ) + $grantType , true);

        $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
        $this->add('select', 'status_id',  ts( 'Grant Status' ),
                   array( '' => ts( '- select -' ) ) + $grantStatus , true);

        $this->addDate( 'application_received_date', ts('Application Received'), false, array( 'formatType' => 'custom') );
        $this->addDate( 'decision_date', ts('Grant Decision'), false, array( 'formatType' => 'custom') );
        $this->addDate( 'money_transfer_date', ts('Money Transferred'), false, array( 'formatType' => 'custom') );
        $this->addDate( 'grant_due_date', ts('Grant Report Due'), false, array( 'formatType' => 'custom') );

        $this->addElement('checkbox','grant_report_received', ts('Grant Report Received?'),null );
        $this->add('textarea', 'rationale', ts('Rationale'), $attributes['rationale']);
        $this->add( 'text', 'amount_total', ts('Amount Requested'), null, true );
        $this->addRule('amount_total', ts('Please enter a valid amount.'), 'money'); 
        
        $this->add( 'text', 'amount_granted', ts('Amount Granted') );         
        $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money'); 

        $this->add( 'text', 'amount_requested', ts('Amount Requested<br />(original currency)') );
        $this->addRule('amount_requested', ts('Please enter a valid amount.'), 'money'); 

        $noteAttrib = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
        $this->add( 'textarea', 'note', ts('Notes'), $noteAttrib['note'] );
        
        //build custom data
        CRM_Custom_Form_Customdata::buildQuickForm( $this );
        
        // add attachments part
        require_once 'CRM/Core/BAO/File.php';
        CRM_Core_BAO_File::buildAttachment( $this,
                                            'civicrm_grant',
                                            $this->_id );

        // make this form an upload since we dont know if the custom data injected dynamically
        // is of type file etc $uploadNames = $this->get( 'uploadNames' );
        $this->addButtons(array( 
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save'), 
                                        'isDefault' => true   ), 
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save and New'), 
                                        'js'        => array( 'onclick' => "return verify( );" ),
                                        'subName'   => 'new' ),
                                array ( 'type'      => 'cancel', 
                                        'name'      => ts('Cancel') ), 
                                ) 
                          );
        
        if ( $this->_context == 'standalone' ) {
            require_once 'CRM/Contact/Form/NewContact.php';
            CRM_Contact_Form_NewContact::buildQuickForm( $this );
        }
    }
    
    /**  
     * global form rule  
     *  
     * @param array $fields  the input form values  
     * @param array $files   the uploaded files if any  
     * @param array $options additional user data  
     *  
     * @return true if no errors, else array of errors  
     * @access public  
     * @static  
     */  
    static function formRule( &$fields, &$files, $self ) {  
        $errors = array( ); 
        
        if ( isset( $fields['contact_select_id'] ) && !$fields['contact_select_id'] ) {
            $errors['contact'] = ts('Please select a contact or create new contact');
        }
        
        return $errors;
    }
    
    
    /** 
     * Function to process the form 
     * 
     * @access public 
     * @return None 
     */ 
    public function postProcess( )  
    { 
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            require_once 'CRM/Grant/BAO/Grant.php';
            CRM_Grant_BAO_Grant::del( $this->_id );
            return;
        }
        
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            $ids['grant'] = $this->_id ;
        }
        
        // get the submitted form values.  
        $params = $this->controller->exportValues( $this->_name );

        if (!$params['grant_report_received']) {
            $params['grant_report_received'] = "null";
        } 
        // set the contact, when contact is selected
        if ( CRM_Utils_Array::value('contact_select_id', $params ) ) {
            $this->_contactID = CRM_Utils_Array::value('contact_select_id', $params);
        }
        
        $params['contact_id'] = $this->_contactID;

        $dates = array( 'application_received_date',
                        'decision_date',
                        'money_transfer_date',
                        'grant_due_date' );
        
        foreach ( $dates as $d ) {
            $params[$d] = CRM_Utils_Date::processDate( $params[$d], null, true );
        }
     
        $ids['note'] = array( );
        if ( $this->_noteId ) {
            $ids['note']['id']   = $this->_noteId;
        }

		// process custom data
        $customFields = CRM_Core_BAO_CustomField::getFields( 'Grant' );
        $params['custom'] = CRM_Core_BAO_CustomField::postProcess( $params,
                                                                   $customFields,
                                                                   $this->_id,
                                                                   'Grant' );

        // add attachments as needed
        CRM_Core_BAO_File::formatAttachment( $params,
                                             $params,
                                             'civicrm_grant',
                                             $this->_id );

        require_once 'CRM/Grant/BAO/Grant.php';
        $grant = CRM_Grant_BAO_Grant::create($params, $ids);

        $buttonName = $this->controller->getButtonName( );
        $session =& CRM_Core_Session::singleton( );
        if ( $this->_context == 'standalone' ) {
            if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
                $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/add', 
                                                                   'reset=1&action=add&context=standalone') );
            } else {
                $session->replaceUserContext(CRM_Utils_System::url( 'civicrm/contact/view',
                                                                    "reset=1&cid={$this->_contactID}&selectedChild=grant" ) );
            }
        } else if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/grant', 
                                                               "reset=1&action=add&context=grant&cid={$this->_contactID}") );
        }
    }
}


