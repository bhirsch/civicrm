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

require_once 'CRM/Admin/Form.php';
require_once 'CRM/Core/BAO/OptionValue.php';
require_once 'CRM/Core/BAO/OptionGroup.php';

/**
 * This class generates form components for Options
 * 
 */
class CRM_Admin_Form_Options extends CRM_Admin_Form
{

    /**
     * The option group name
     *
     * @var array
     * @static
     */
    protected $_gName;

    /**
     * The option group name in display format (capitalized, without underscores...etc)
     *
     * @var array
     * @static
     */
    protected $_GName;

    /**
     * Function to pre-process
     *
     * @return None
     * @access public
     */
    public function preProcess( ) 
    {
        parent::preProcess( );
        $session =& CRM_Core_Session::singleton( );
        if ( ! $this->_gName ) {
            $this->_gName = CRM_Utils_Request::retrieve('group','String', $this, false, 0);
            $this->_gid   = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_OptionGroup',
                                                         $this->_gName,
                                                         'id',
                                                         'name');
        }
        if ($this->_gName) {
            $this->set( 'gName', $this->_gName );
        } else {
            $this->_gName = $this->get( 'gName' );
        }
        $this->_GName = ucwords(str_replace('_', ' ', $this->_gName));
        $url = "civicrm/admin/options/{$this->_gName}";
        $params = "group={$this->_gName}&reset=1";
        $session->pushUserContext( CRM_Utils_System::url( $url, $params ) );
        $this->assign('id', $this->_id);
        
        require_once 'CRM/Core/OptionGroup.php';
        if ( $this->_id && in_array( $this->_gName, CRM_Core_OptionGroup::$_domainIDGroups ) ) {
            $domainID = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_OptionValue', $this->_id, 'domain_id', 'id' ) ;
            if( CRM_Core_Config::domainID( ) != $domainID ) {
                CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) ); 
            }
        }
    }
    
    /**
     * This function sets the default values for the form. 
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    function setDefaultValues( ) 
    {
        $defaults = parent::setDefaultValues( );
       
        if (! isset($defaults['weight']) || ! $defaults['weight']) {
            $fieldValues = array('option_group_id' => $this->_gid);
            $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
        }
        
        //setDefault of contact types for email greeting, postal greeting, addressee, CRM-4575
        if ( in_array( $this->_gName, array( 'email_greeting', 'postal_greeting', 'addressee' ) ) ) {
            $defaults['contactOptions'] = ( CRM_Utils_Array::value( 'filter', $defaults) ) ? $defaults['filter'] : null;
        }
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
        parent::buildQuickForm( );
        if ($this->_action & CRM_Core_Action::DELETE ) { 
            return;
        }

        $this->applyFilter('__ALL__', 'trim');
        
        $this->add('text',
                   'label',
                   ts('Label'),
                   CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_OptionValue', 'label' ),
                   true );
        $this->addRule( 'label',
                        ts('This Label already exists in the database for this option group. Please select a different Value.'),
                        'optionExists',
                        array( 'CRM_Core_DAO_OptionValue', $this->_id, $this->_gid, 'label' ) );
        
        $required = false;
        if ( $this->_gName == 'custom_search' ) {
            $required = true;
        } elseif ( $this->_gName == 'redaction_rule' ) {
            $this->add( 'text', 
                        'value', 
                        ts('Value'), 
                        CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_OptionValue', 'value' ),
                        true );
            $this->add( 'checkbox', 
                        'filter', 
                        ts('Regular Expression?'));
        }
        if ( $this->_gName == 'participant_listing' ) {
            $this->add('text', 
                       'description', 
                       ts('Description'), 
                       CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_OptionValue', 'description' ) );
            
        } else {
            // Hard-coding attributes here since description is still stored as varchar and not text in the schema. dgg
            $this->addWysiwyg( 'description',
                               ts('Description'),
                               array( 'rows' => 4, 'cols' => 80),
                               $required );
        }
        $this->add('text',
                   'weight',
                   ts('Weight'),
                   CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'),
                   true);
        $this->addRule('weight', ts('is a numeric field') , 'numeric');

        $isReserved = false;
        if ($this->_id) {
            $isReserved = (bool) CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'is_reserved');
        }

        // If CiviCase enabled AND "Add" mode OR "edit" mode for non-reserved activities, only allow user to pick Core or CiviCase component.
        // FIXME: Each component should define whether adding new activity types is allowed.
        require_once 'CRM/Core/Config.php';
        $config =& CRM_Core_Config::singleton( );
        if ($this->_gName == 'activity_type' && in_array("CiviCase", $config->enableComponents) &&
            ( ($this->_action & CRM_Core_Action::ADD) || ! $isReserved ) ) {
                require_once 'CRM/Core/Component.php';
                $caseID = CRM_Core_Component::getComponentID('CiviCase');
                $components   = array( '' => ts( 'Contact' ), $caseID => 'CiviCase' );
                $this->add( 'select',
                            'component_id',
                            ts( 'Component' ),
                            $components, false );
        }

        $enabled = $this->add('checkbox', 'is_active', ts('Enabled?'));
        
        if ($isReserved) {
            $enabled->freeze();
        }
        
        //fix for CRM-3552, CRM-4575
        if ( in_array( $this->_gName, array('email_greeting', 'postal_greeting', 'addressee', 'from_email_address') ) ) {
            $this->assign( 'showDefault', true );
            $this->add('checkbox', 'is_default', ts('Default Option?'));
        }
        
         //get contact type for which user want to create a new greeting/addressee type, CRM-4575
        if ( in_array( $this->_gName, array( 'email_greeting', 'postal_greeting', 'addressee' ) ) && ! $isReserved ) {
            $values = array( 1 => ts('Individual'), 2 => ts('Household') );
            if ( $this->_gName == 'addressee' ) {
                $values[] =  ts('Organization'); 
            }
            $this->add( 'select', 'contactOptions', ts('Contact Type'),array('' => '-select-' ) + $values, true );
            $this->assign( 'showContactFilter', true );
        }
        
        if ($this->_gName == 'participant_status') {
            // For Participant Status options, expose the 'filter' field to track which statuses are "Counted", and the Visibility field
            $element = $this->add('checkbox', 'filter', ts('Counted?'));
            require_once "CRM/Core/PseudoConstant.php";
            $this->add( 'select', 'visibility_id', ts('Visibility'), CRM_Core_PseudoConstant::visibility( ) );
        }
        if ( $this->_gName == 'participant_role') {
            // For Participant Role options, expose the 'filter' field to track which statuses are "Counted"
            $this->add( 'checkbox', 'filter', ts('Counted?') );
        }
        
        $this->addFormRule( array( 'CRM_Admin_Form_Options', 'formRule' ), $this );
    }
    
    /**  
     * global form rule  
     *  
     * @param array $fields the input form values  
     * @param array $files  the uploaded files if any  
     * @param array $self   current form object. 
     *  
     * @return array array of errors / empty array.   
     * @access public  
     * @static  
     */  
    static function formRule( &$fields, &$files, $self ) 
    {
        $errors = array( );
        if ( $self->_gName == 'from_email_address' ) {
            require_once 'CRM/Utils/Mail.php';
            $formEmail = CRM_Utils_Mail::pluckEmailFromHeader( $fields['label'] );
            if ( !CRM_Utils_Rule::email( $formEmail ) ) {
                $errors['label'] = ts( 'Please enter the valid email address.' );
            }
            
            $formName = explode('"', $fields['label'] );
            if ( !CRM_Utils_Array::value( 1, $formName ) || count( $formName ) != 3 ) {
                $errors['label'] = ts( 'Please follow the proper format for From Email Address' ); 
            }
        }
                
        return $errors;
    }
    
    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        if($this->_action & CRM_Core_Action::DELETE) {
            $fieldValues = array('option_group_id' => $this->_gid);
            $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);
            
            if( CRM_Core_BAO_OptionValue::del($this->_id) ) {
                if ( $this->_gName == 'phone_type' ) {
                    require_once 'CRM/Core/BAO/Phone.php';
                    CRM_Core_BAO_Phone::setOptionToNull( CRM_Utils_Array::value( 'value', $this->_defaultValues) );
                }
                
                CRM_Core_Session::setStatus( ts('Selected %1 type has been deleted.', array(1 => $this->_GName)) );
            } else {
                CRM_Core_Session::setStatus( ts('Selected %1 type has not been deleted.', array(1 => $this->_GName)) );
                CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $fieldValues);
            }
        } else {
            $params = $ids = array( );
            $params = $this->exportValues();
           
            // allow multiple defaults within group.
            $allowMultiDefaults = array('email_greeting', 'postal_greeting', 'addressee', 'from_email_address');
            if ( CRM_Utils_Array::value( 'is_default', $params ) && 
                 in_array( $this->_gName,  $allowMultiDefaults ) ) {
                if ( $this->_gName == 'from_email_address' ) {
                    $params['reset_default_for'] = array( 'domain_id' => CRM_Core_Config::domainID( ) );
                } else if ( $filter = CRM_Utils_Array::value( 'contactOptions', $params ) )  {
                    $params['filter'] = $filter;
                    $params['reset_default_for'] = array( 'filter' => "0, ". $params['filter'] );
                }
            }
            
            $groupParams = array( 'name' => ($this->_gName) );
            require_once 'CRM/Core/OptionValue.php';
            $optionValue = CRM_Core_OptionValue::addOptionValue($params, $groupParams, $this->_action, $this->_id);
            
            CRM_Core_Session::setStatus( ts('The %1 \'%2\' has been saved.', array(1 => $this->_GName, 2 => $optionValue->label)) );
        }
    }
}


