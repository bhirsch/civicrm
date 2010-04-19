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
require_once 'CRM/Contact/Form/Location.php';
require_once 'CRM/Custom/Form/CustomData.php';
require_once 'CRM/Contact/BAO/ContactType.php';

/**
 * This class generates form components generic to all the contact types.
 * 
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contact_Form_Contact extends CRM_Core_Form
{
    /**
     * The contact type of the form
     *
     * @var string
     */
    public $_contactType;
    
    /**
     * The contact type of the form
     *
     * @var string
     */
    public $_contactSubType;
    
    /**
     * The contact id, used when editing the form
     *
     * @var int
     */
    public $_contactId;
    
    /**
     * the default group id passed in via the url
     *
     * @var int
     */
    public $_gid;
    
    /**
     * the default tag id passed in via the url
     *
     * @var int
     */
    public $_tid;
    
    /**
     * name of de-dupe button
     *
     * @var string
     * @access protected
     */
    protected $_dedupeButtonName;
    
    /**
     * name of optional save duplicate button
     *
     * @var string
     * @access protected
     */
    protected $_duplicateButtonName;
    
    protected $_editOptions = array( );
    
    public $_blocks;
    
    public $_values = array( );
    
    public $_action;
    /**
     * The array of greetings with option group and filed names
     *
     * @var array
     */
    public $_greetings;
    
    /**
     * Do we want to parse street address. 
     */
    private $_parseStreetAddress; 
    
    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    function preProcess( )
    {
        $this->_action  = CRM_Utils_Request::retrieve('action', 'String',$this, false, 'add' );
        
        $this->_dedupeButtonName    = $this->getButtonName( 'refresh', 'dedupe'    );
        $this->_duplicateButtonName = $this->getButtonName( 'upload',  'duplicate' );
        
        $session = & CRM_Core_Session::singleton( );
        if ( $this->_action == CRM_Core_Action::ADD ) {
            // check for add contacts permissions
            require_once 'CRM/Core/Permission.php';
            if ( ! CRM_Core_Permission::check( 'add contacts' ) ) {
                CRM_Utils_System::permissionDenied( );
                exit;
            }
            $this->_contactType = CRM_Utils_Request::retrieve( 'ct', 'String',
                                                               $this, true, null, 'REQUEST' );
            if ( ! in_array( $this->_contactType,
                             array( 'Individual', 'Household', 'Organization' ) ) ) {
                CRM_Core_Error::statusBounce( ts('Could not get a contact_id and/or contact_type') );
            }
            
            $this->_contactSubType = CRM_Utils_Request::retrieve( 'cst','String', $this );

            $this->_gid = CRM_Utils_Request::retrieve( 'gid', 'Integer',
                                                       CRM_Core_DAO::$_nullObject,
                                                       false, null, 'GET' );
            $this->_tid = CRM_Utils_Request::retrieve( 'tid', 'Integer',
                                                       CRM_Core_DAO::$_nullObject,
                                                       false, null, 'GET' );
            $typeLabel = 
                CRM_Contact_BAO_ContactType::contactTypePairs( true, $this->_contactSubType ? 
                                                               $this->_contactSubType : $this->_contactType );
            CRM_Utils_System::setTitle( ts( 'New %1', array( 1 => $typeLabel ) ) );
            $session->pushUserContext(CRM_Utils_System::url('civicrm/dashboard', 'reset=1'));
            $this->_contactId = null;
        } else {
            //update mode
            if ( ! $this->_contactId ) {
                $this->_contactId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true );
            }
            
            if ( $this->_contactId ) {
                require_once 'CRM/Contact/BAO/Contact.php';
                $contact =& new CRM_Contact_DAO_Contact( );
                $contact->id = $this->_contactId;
                if ( ! $contact->find( true ) ) {
                    CRM_Core_Error::statusBounce( ts('contact does not exist: %1', array(1 => $this->_contactId)) );
                }
                $this->_contactType = $contact->contact_type;
                $this->_contactSubType = $contact->contact_sub_type;
                
                // check for permissions
                require_once 'CRM/Contact/BAO/Contact/Permission.php';
                if ( ! CRM_Contact_BAO_Contact_Permission::allow( $this->_contactId, CRM_Core_Permission::EDIT ) ) {
                    CRM_Core_Error::statusBounce( ts('You do not have the necessary permission to edit this contact.') );
                }
                
                list( $displayName, $contactImage ) = CRM_Contact_BAO_Contact::getDisplayAndImage( $this->_contactId );
                
                CRM_Utils_System::setTitle( $displayName, $contactImage . ' ' . $displayName ); 
                $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid='. $this->_contactId ));
                
                $values = $this->get( 'values');
                // get contact values.
                if ( !empty( $values ) ) {
                    $this->_values = $values;
                } else {
                    $params = array( 'id'         => $this->_contactId,
                                     'contact_id' => $this->_contactId ) ;
                    $contact = CRM_Contact_BAO_Contact::retrieve( $params, $this->_values, true );
                    $this->set( 'values', $this->_values );
                }
            } else {
                CRM_Core_Error::statusBounce( ts('Could not get a contact_id and/or contact_type') );
            }
        }
        
        // parse street address, CRM-5450
        require_once 'CRM/Core/BAO/Preferences.php';
        $this->_parseStreetAddress = $this->get( 'parseStreetAddress' );
        if ( !isset( $this->_parseStreetAddress ) ) { 
            $addressOptions = CRM_Core_BAO_Preferences::valueOptions( 'address_options' );
            $this->_parseStreetAddress = false;
            if ( CRM_Utils_Array::value( 'street_address', $addressOptions ) &&
                 CRM_Utils_Array::value( 'street_address_parsing', $addressOptions ) ) {
                $this->_parseStreetAddress = true;
            }
            $this->set( 'parseStreetAddress', $this->_parseStreetAddress );
        }
        $this->assign( 'parseStreetAddress', $this->_parseStreetAddress );
        
        $this->_editOptions = $this->get( 'contactEditOptions' ); 
        if ( CRM_Utils_System::isNull( $this->_editOptions ) ) {
            $this->_editOptions  = CRM_Core_BAO_Preferences::valueOptions( 'contact_edit_options', true, null, 
                                                                           false, 'name', true, 'AND v.filter = 0' );
            $this->set( 'contactEditOptions', $this->_editOptions );
        }
        
        // build demographics only for Individual contact type
        if ( $this->_contactType != 'Individual' &&
             array_key_exists( 'Demographics', $this->_editOptions ) ) {
            unset( $this->_editOptions['Demographics'] );
        }
        
        // in update mode don't show notes
        if ( $this->_contactId && array_key_exists( 'Notes', $this->_editOptions ) ) {
            unset( $this->_editOptions['Notes'] );
        }
        
        
        $this->assign( 'editOptions',    $this->_editOptions );
        $this->assign( 'contactType',    $this->_contactType );
        $this->assign( 'contactSubType', $this->_contactSubType );
        
        // get the location blocks.
        $this->_blocks = $this->get( 'blocks' );
        if ( CRM_Utils_System::isNull( $this->_blocks ) ) {
            $this->_blocks = CRM_Core_BAO_Preferences::valueOptions( 'contact_edit_options', true, null, 
                                                                     false, 'name', true, 'AND v.filter = 1' );
            $this->set( 'blocks', $this->_blocks );
        }
        $this->assign( 'blocks', $this->_blocks );
        
        if ( array_key_exists( 'CustomData', $this->_editOptions ) ) {
            //only custom data has preprocess hence directly call it
            CRM_Custom_Form_CustomData::preProcess( $this, null, $this->_contactSubType, 
                                                    1, $this->_contactType, $this->_contactId );
        }
        
        // this is needed for custom data.
        $this->assign( 'entityID', $this->_contactId );
        
        // also keep the convention.
        $this->assign( 'contactId', $this->_contactId );
        
        // location blocks.
        CRM_Contact_Form_Location::preProcess( $this );
    }
    
    /**
     * This function sets the default values for the form. Note that in edit/view mode
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    function setDefaultValues( ) 
    {
        $defaults = $this->_values;
        $params   = array( );
        
        if ( $this->_action & CRM_Core_Action::ADD ) {
            if ( array_key_exists( 'TagsAndGroups', $this->_editOptions ) ) {
                // set group and tag defaults if any
                if ( $this->_gid ) {
                    $defaults['group'][$this->_gid] = 1;
                }
                if ( $this->_tid ) {
                    $defaults['tag'][$this->_tid] = 1;
                }
            }
            if ( $this->_contactSubType ) {
                $defaults['contact_sub_type'] = $this->_contactSubType;
            }
        } else {
            if ( isset( $this->_elementIndex[ "shared_household" ] ) ) {
                $sharedHousehold = $this->getElementValue( "shared_household" );
                if ( $sharedHousehold ) {
                    $this->assign('defaultSharedHousehold', $sharedHousehold );
                } elseif ( CRM_Utils_Array::value('mail_to_household_id', $defaults) ) {
                    $defaults['use_household_address'] = true;
                    $this->assign('defaultSharedHousehold', $defaults['mail_to_household_id'] );
                }
                $defaults['shared_household_id'] = CRM_Utils_Array::value( 'mail_to_household_id', $defaults );
                if ( array_key_exists(1, $defaults['address']) ) {
                    $this->assign( 'sharedHouseholdAddress', $defaults['address'][1]['display'] );
                }
            }
            require_once 'CRM/Contact/BAO/Relationship.php';
            $currentEmployer = CRM_Contact_BAO_Relationship::getCurrentEmployer( array( $this->_contactId ) );
            $defaults['current_employer_id'] = CRM_Utils_Array::value( 'org_id', $currentEmployer[$this->_contactId] );
            $this->assign( 'currentEmployer', $defaults['current_employer_id'] );            
        }
        
        // set defaults for blocks ( custom data, address, communication preference, notes, tags and groups )
        foreach( $this->_editOptions as $name => $label ) {                
            if ( !in_array( $name, array( 'Address', 'Notes' ) ) ) {
                require_once(str_replace('_', DIRECTORY_SEPARATOR, "CRM_Contact_Form_Edit_" . $name ) . ".php");
                eval( 'CRM_Contact_Form_Edit_' . $name . '::setDefaultValues( $this, $defaults );' );
            }
        }
        
        // build street address, CRM-5450.
        $addressValues = array( );
        if ( $this->_parseStreetAddress ) {
            if ( is_array( $defaults['address'] ) && 
                 !CRM_Utils_system::isNull( $defaults['address'] ) ) {
                $parseFields = array( 'street_address', 'street_number', 'street_name', 'street_unit' );
                foreach ( $defaults['address'] as $cnt => &$address ) {
                    $streetAddress = null;
                    foreach ( array( 'street_number', 'street_number_suffix', 'street_name', 'street_unit' ) as $fld ) {
                        if ( in_array( $fld, array( 'street_name', 'street_unit' ) ) ) { 
                            $streetAddress .= ' ';
                        }
                        $streetAddress .= CRM_Utils_Array::value( $fld, $address );
                    }
                    $streetAddress = trim( $streetAddress );
                    if ( !empty( $streetAddress ) ) {
                        $address['street_address'] = $streetAddress;
                    }
                    $address['street_number'] .= CRM_Utils_Array::value( 'street_number_suffix', $address ); 
                    
                    // build array for set default.
                    foreach ( $parseFields as $field ) {
                        $addressValues["{$field}_{$cnt}"] = CRM_Utils_Array::value( $field, $address ); 
                    }
                    
                    // don't load fields, use js to populate.
                    foreach ( array( 'street_number', 'street_name', 'street_unit' ) as $f ) {
                        if ( isset( $address[$f] ) ) unset( $address[$f] );
                    }
                }
            }
            $this->assign( 'allAddressFieldValues', json_encode( $addressValues ) );
            
            //hack to handle show/hide address fields.
            $parsedAddress = array( );
            if ( $this->_contactId &&
                 CRM_Utils_Array::value( 'address', $_POST ) 
                 && is_array( $_POST['address'] ) ) {
                foreach ( $_POST['address'] as $cnt => $values ) {
                    $showField = 'streetAddress';
                    foreach ( array( 'street_number', 'street_name', 'street_unit' ) as $fld ) {
                        if ( CRM_Utils_Array::value( $fld, $values ) ) {
                            $showField = 'addressElements';
                            break;
                        }
                    }
                    $parsedAddress[$cnt] = $showField;
                }
            }
            $this->assign( 'showHideAddressFields',     $parsedAddress );
            $this->assign( 'loadShowHideAddressFields', empty( $parsedAddress  ) ? false : true  );
        }
        
        //set location type and country to default for each block
        $this->blockSetDefaults( $defaults );
        
        return $defaults;
    }
    
    /*
     * do the set default related to location type id, 
     * primary location,  default country
     *
     */
    function blockSetDefaults( &$defaults ) {
        $locationTypeKeys = array_filter(array_keys( CRM_Core_PseudoConstant::locationType() ), 'is_int' );
        sort( $locationTypeKeys );
        
        // get the default location type
        require_once 'CRM/Core/BAO/LocationType.php';
        
        $locationType = CRM_Core_BAO_LocationType::getDefault( );
        
        // unset primary location type
        $primaryLocationTypeIdKey = CRM_Utils_Array::key( $locationType->id, $locationTypeKeys );
        unset( $locationTypeKeys[ $primaryLocationTypeIdKey ] );
        
        // reset the array sequence
        $locationTypeKeys = array_values( $locationTypeKeys );
        
        // get default phone and im provider id.
        require_once 'CRM/Core/OptionGroup.php';
        $defPhoneTypeId  = key( CRM_Core_OptionGroup::values( 'phone_type', false, false, false, ' AND is_default = 1' ) );
        $defIMProviderId = key( CRM_Core_OptionGroup::values( 'instant_messenger_service', 
                                                              false, false, false, ' AND is_default = 1' ) );
        
        $allBlocks = $this->_blocks;
        if ( array_key_exists( 'Address', $this->_editOptions ) ) {
            $allBlocks['Address'] = $this->_editOptions['Address'];
        }
        
        $config =& CRM_Core_Config::singleton( );
        foreach ( $allBlocks as $blockName => $label ) {
            $name = strtolower( $blockName );
            $hasPrimary = $updateMode = false;
            
            // user is in update mode. 
            if ( array_key_exists( $name, $defaults ) && 
                 !CRM_Utils_System::isNull( $defaults[$name] ) ) {
                $updateMode = true;
            }
            
            for ( $instance = 1; $instance <= $this->get( $blockName ."_Block_Count" ); $instance++ ) {
                
                // make we require one primary block, CRM-5505
                if ( $updateMode ) {
                    if ( !$hasPrimary ) {
                        $hasPrimary = CRM_Utils_Array::value( 'is_primary', $defaults[$name][$instance] );
                    }
                    continue;
                }
                
                //set location to primary for first one.
                if ( $instance == 1 ) {
                    $hasPrimary = true;
                    $defaults[$name][$instance]['is_primary']       = true;
                    $defaults[$name][$instance]['location_type_id'] = $locationType->id;
                } else {
                    $locTypeId = isset( $locationTypeKeys[$instance-1] )?$locationTypeKeys[$instance-1]:$locationType->id;
                    $defaults[$name][$instance]['location_type_id'] = $locTypeId; 
                }
                
                //set default country
                if ( $name == 'address' && $config->defaultContactCountry ) {
                    $defaults[$name][$instance]['country_id'] = $config->defaultContactCountry;
                }
                
                //set default phone type.
                if ( $name == 'phone' && $defPhoneTypeId ) {
                    $defaults[$name][$instance]['phone_type_id'] = $defPhoneTypeId;
                }
                
                //set default im provider.
                if ( $name == 'im' && $defIMProviderId ) {
                    $defaults[$name][$instance]['provider_id'] = $defIMProviderId;
                }
            }
            
            if ( !$hasPrimary ) {
                $defaults[$name][1]['is_primary'] = true;
            }
        }
        
        // set defaults for country-state widget
        if ( CRM_Utils_Array::value( 'address', $defaults ) && is_array( $defaults['address'] ) ) {
            require_once 'CRM/Contact/Form/Edit/Address.php';
            foreach ( $defaults['address'] as $blockId => $values ) {
                CRM_Contact_Form_Edit_Address::fixStateSelect( $this,
                                                               "address[$blockId][country_id]",
                                                               "address[$blockId][state_province_id]",
                                                               CRM_Utils_Array::value( 'country_id',
                                                                                       $values, $config->defaultContactCountry ) );
                
            }
        }
        
    }
    
    /**
     * This function is used to add the rules (mainly global rules) for form.
     * All local rules are added near the element
     *
     * @return None
     * @access public
     * @see valid_date
     */
    function addRules( )
    {
        // skip adding formRules when custom data is build
        if ( $this->_addBlockName || ($this->_action & CRM_Core_Action::DELETE) ) {
			return;
		}
        
        $this->addFormRule( array( 'CRM_Contact_Form_Edit_'. $this->_contactType,   'formRule' ), $this->_contactId );
        if ( array_key_exists('CommunicationPreferences', $this->_editOptions) ) {
            $this->addFormRule( array( 'CRM_Contact_Form_Edit_CommunicationPreferences','formRule' ), $this );
        }
    }
    
    /**
     * global validation rules for the form
     *
     * @param array $fields     posted values of the form
     * @param array $errors     list of errors to be posted back to the form
     * @param int   $contactId  contact id if doing update.
     *
     * @return $primaryID emal/openId
     * @static
     * @access public
     */
    static function formRule( &$fields, &$errors, $contactId = null )
    {
        $config =& CRM_Core_Config::singleton( );
        if ( $config->civiHRD && ! isset( $fields['tag'] ) ) {
            $errors["tag"] = ts('Please select at least one tag.');
        }
        
        // validations.
        //1. for each block only single value can be marked as is_primary = true.
        //2. location type id should be present if block data present.
        //3. check open id across db and other each block for duplicate.
        //4. at least one location should be primary.
        //5. also get primaryID from email or open id block.
        
        // take the location blocks.
        $blocks = CRM_Core_BAO_Preferences::valueOptions( 'contact_edit_options', true, null, 
                                                          false, 'name', true, 'AND v.filter = 1' );
        $otherEditOptions = CRM_Core_BAO_Preferences::valueOptions( 'contact_edit_options', true, null,
                                                                    false, 'name', true, 'AND v.filter = 0');
        //get address block inside.
        if ( array_key_exists( 'Address', $otherEditOptions ) ) {
            $blocks['Address'] = $otherEditOptions['Address'];
        }
        
        $openIds = array( );
        $primaryID = false;
        foreach ( $blocks as $name => $label ) {
            $hasData = $hasPrimary = array( );
            $name = strtolower( $name );
            if ( CRM_Utils_Array::value( $name, $fields ) && is_array( $fields[$name] ) ) {
                foreach ( $fields[$name] as $instance => $blockValues ) {
                    $dataExists = self::blockDataExists( $blockValues );
                    if ( !$dataExists && $name == 'address' &&  $instance == 1 ) {
                        $dataExists = CRM_Utils_Array::value( 'use_household_address', $fields );
                    }
                    
                    if ( $dataExists ) {
                        $hasData[] = $instance;
                        if ( CRM_Utils_Array::value( 'is_primary', $blockValues ) ) {
                            $hasPrimary[] = $instance;
                            if ( !$primaryID && 
                                 in_array( $name, array( 'email', 'openid' ) ) && 
                                 CRM_Utils_Array::value( $name, $blockValues ) ) {
                                $primaryID = $blockValues[$name];
                            }
                        }
                        
                        if ( !CRM_Utils_Array::value( 'location_type_id', $blockValues ) ) {
                            $errors["{$name}[$instance][location_type_id]"] = 
                                ts('The Location Type should be set if there is  %1 information.', array( 1=> $label ) );
                        }
                    }
                    
                    if ( $name == 'openid' && CRM_Utils_Array::value( $name, $blockValues ) ) {
                        require_once 'CRM/Core/DAO/OpenID.php';
                        require_once 'Auth/OpenID.php';
                        $oid =& new CRM_Core_DAO_OpenID( );
                        $oid->openid = $openIds[$instance] = Auth_OpenID::normalizeURL( CRM_Utils_Array::value( $name, $blockValues ) );
                        $cid = isset($contactId) ? $contactId : 0;
                        if ( $oid->find(true) && ($oid->contact_id != $cid) ) {
                            $errors["{$name}[$instance][openid]"] = ts('%1 already exist.', array( 1 => $blocks['OpenID'] ) );
                        }
                    }
                }
                
                if ( empty( $hasPrimary ) && !empty( $hasData ) ) {
                    $errors["{$name}[1][is_primary]"] = ts('One %1 should be marked as primary.', array( 1 => $label ) );
                }
                
                if ( count( $hasPrimary ) > 1 ) {
                    $errors["{$name}[".array_pop($hasPrimary)."][is_primary]"] = ts( 'Only one %1 can be marked as primary.', 
                                                                                     array( 1 => $label ) );  
                }
            }
        }
        
        //do validations for all opend ids they should be distinct.
        if ( !empty( $openIds ) && ( count( array_unique($openIds) ) != count($openIds) ) ) {
            foreach ( $openIds as $instance => $value ) {
                if ( !array_key_exists( $instance, array_unique($openIds) ) ) {
                    $errors["openid[$instance][openid]"] = ts('%1 already used.', array( 1 => $blocks['OpenID'] ) );
                }
            }
        }
        
        // street number should be digit + suffix, CRM-5450
        $parseStreetAddress = CRM_Utils_Array::value( 'street_address_parsing', 
                                                      CRM_Core_BAO_Preferences::valueOptions( 'address_options' ) );
        if ( $parseStreetAddress ) {
            if ( is_array( $fields['address'] ) ) {  
                $invalidStreetNumbers = array( );
                foreach ( $fields['address'] as $cnt => $address ) {
                    if ( $streetNumber = CRM_Utils_Array::value( 'street_number', $address ) ) {
                        $parsedAddress = CRM_Core_BAO_Address::parseStreetAddress( $address['street_number'] );
                        if ( !CRM_Utils_Array::value( 'street_number', $parsedAddress ) ) {
                            $invalidStreetNumbers[] = $cnt;
                        }
                    }
                }
                
                if ( !empty( $invalidStreetNumbers ) ) {
                    $first = $invalidStreetNumbers[0];
                    foreach ( $invalidStreetNumbers as &$num ) $num = CRM_Contact_Form_Contact::ordinalNumber( $num );
                    $errors["address[$first][street_number]"] = ts('The street number you entered for the %1 address block(s) is not in an expected format. Street numbers may include numeric digit(s) followed by other characters. You can still enter the complete street address (unparsed) by clicking "Edit Complete Street Address".', array(1 => implode(', ', $invalidStreetNumbers)));
                }
            }
        }
        
        return $primaryID;
    }
    
    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        //load form for child blocks
        if ( $this->_addBlockName ) {
            require_once( str_replace('_', DIRECTORY_SEPARATOR, "CRM_Contact_Form_Edit_" . $this->_addBlockName ) . ".php");
            return eval( 'CRM_Contact_Form_Edit_' . $this->_addBlockName . '::buildQuickForm( $this );' );
        }
        
        //build contact type specific fields
        require_once(str_replace('_', DIRECTORY_SEPARATOR, "CRM_Contact_Form_Edit_" . $this->_contactType) . ".php");
        eval( 'CRM_Contact_Form_Edit_' . $this->_contactType . '::buildQuickForm( $this, $this->_action );' );
        
        // subtype is a common field. lets keep it here
        $typeLabel = CRM_Contact_BAO_ContactType::getLabel( $this->_contactType );
        $subtypes  = CRM_Contact_BAO_ContactType::subTypePairs( $this->_contactType );
        $subtypeElem =& $this->addElement( 'select', 'contact_sub_type', 
                                           ts('Contact Type'), array( '' => $typeLabel ) + $subtypes );

        $allowEditSubType = true;
        if ( $this->_contactId && $this->_contactSubType ) {
            $allowEditSubType = CRM_Contact_BAO_ContactType::isAllowEdit( $this->_contactId, $this->_contactSubType );
        }
        if ( !$allowEditSubType ) {
            $subtypeElem->freeze( );
        }
        
        // build edit blocks ( custom data, demographics, communication preference, notes, tags and groups )
        foreach( $this->_editOptions as $name => $label ) {                
            if ( $name == 'Address' ) {
                $this->_blocks['Address'] = $this->_editOptions['Address'];
                continue;
            }
            require_once(str_replace('_', DIRECTORY_SEPARATOR, "CRM_Contact_Form_Edit_" . $name ) . ".php");
            eval( 'CRM_Contact_Form_Edit_' . $name . '::buildQuickForm( $this );' );
        }
        
        // build location blocks.
        CRM_Contact_Form_Location::buildQuickForm( $this );
        
        // add the dedupe button
        $this->addElement('submit', 
                          $this->_dedupeButtonName,
                          ts( 'Check for Matching Contact(s)' ) );
        $this->addElement('submit', 
                          $this->_duplicateButtonName,
                          ts( 'Save Matching Contact' ) );
        $this->addElement('submit', 
                          $this->getButtonName( 'next', 'sharedHouseholdDuplicate' ),
                          ts( 'Save With Duplicate Household' ) );
        
        $this->addButtons( array(
                                 array ( 'type'      => 'upload',
                                         'name'      => ts('Save'),
                                         'subName'   => 'view',
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'upload',
                                         'name'      => ts('Save and New'),
                                         'subName'   => 'new' ),
                                 array ( 'type'       => 'cancel',
                                         'name'      => ts('Cancel') ) ) );
    }
    
    /**
     * Form submission of new/edit contact is processed.
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        // check if dedupe button, if so return.
        $buttonName = $this->controller->getButtonName( );
        if ( $buttonName == $this->_dedupeButtonName ) {
            return;
        }
        
        //get the submitted values in an array
        $params = $this->controller->exportValues( $this->_name );
        
        //get the related id for shared / current employer
        if ( CRM_Utils_Array::value( 'shared_household_id',$params ) ) {
            $params['shared_household'] = $params['shared_household_id'];
        }
        if ( is_numeric( CRM_Utils_Array::value( 'current_employer_id', $params ) ) 
             && CRM_Utils_Array::value( 'current_employer', $params ) ) { 
			$params['current_employer'] = $params['current_employer_id'];
        }
        
        // don't carry current_employer_id field,
        // since we don't want to directly update DAO object without
        // handling related business logic ( eg related membership )
        if ( isset( $params['current_employer_id'] ) ) unset( $params['current_employer_id'] ); 
        
        $params['contact_type'] = $this->_contactType;
        if ( $this->_contactId ) {
            $params['contact_id'] = $this->_contactId;
        }
        
        //make deceased date null when is_deceased = false
        if ( $this->_contactType == 'Individual' && 
             CRM_Utils_Array::value( 'Demographics',  $this->_editOptions ) &&
             !CRM_Utils_Array::value( 'is_deceased', $params ) ) {
            $params['is_deceased']        = false;
            $params['deceased_date'] = null;
        }
        
        if ( $this->_contactSubType && ($this->_action & CRM_Core_Action::ADD) ) {
            $params['contact_sub_type'] = $this->_contactSubType;
        }

        // action is taken depending upon the mode
        require_once 'CRM/Utils/Hook.php';
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            CRM_Utils_Hook::pre( 'edit', $params['contact_type'], $params['contact_id'], $params );
        } else {
            CRM_Utils_Hook::pre( 'create', $params['contact_type'], null, $params );
        }
        
        require_once 'CRM/Core/BAO/CustomField.php';
        $customFields     = 
            CRM_Core_BAO_CustomField::getFields( $params['contact_type'], false, true );

        //CRM-5143
        //if subtype is set, send subtype as extend to validate subtype customfield 
        $customFieldExtends = (CRM_Utils_Array::value('contact_sub_type', $params)) ? $params['contact_sub_type'] : $params['contact_type'];  
            
        $params['custom'] = CRM_Core_BAO_CustomField::postProcess( $params, 
                                                                   $customFields, 
                                                                   $this->_contactId,
                                                                   $customFieldExtends, 
                                                                   true );
        
        if ( array_key_exists( 'CommunicationPreferences',  $this->_editOptions ) ) {
            // this is a chekbox, so mark false if we dont get a POST value
            $params['is_opt_out'] = CRM_Utils_Array::value( 'is_opt_out', $params, false );
        }
        
        // copy household address, if use_household_address option (for individual form) is checked
        if ( $this->_contactType == 'Individual' ) {
            if ( CRM_Utils_Array::value( 'use_household_address', $params ) && 
                 CRM_Utils_Array::value( 'shared_household',$params ) ) {
                if ( is_numeric( $params['shared_household'] ) ) {
                    CRM_Contact_Form_Edit_Individual::copyHouseholdAddress( $params );
                }
                CRM_Contact_Form_Edit_Individual::createSharedHousehold( $params );
            } else { 
                $params['mail_to_household_id'] = 'null';
            }
        } else {
            $params['mail_to_household_id'] = 'null';
        }
        
        if ( ! array_key_exists( 'TagsAndGroups', $this->_editOptions ) ) {
            unset($params['group']);
        }

        if ( CRM_Utils_Array::value( 'contact_id', $params ) && ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            // cleanup unwanted location blocks
            require_once 'CRM/Core/BAO/Location.php';
            CRM_Core_BAO_Location::cleanupContactLocations( $params );

            // figure out which all groups are intended to be removed
            if ( ! empty($params['group']) ) {
                $contactGroupList =& CRM_Contact_BAO_GroupContact::getContactGroup( $params['contact_id'], 'Added' );
                if ( is_array($contactGroupList) ) {
                    foreach ( $contactGroupList as $key ) {
                        if ( $params['group'][$key['group_id']] != 1 ) {
                            $params['group'][$key['group_id']] = -1;
                        }
                    }
                }
            }
        }
        
        // parse street address, CRM-5450
        $parseStatusMsg = null;
        if ( $this->_parseStreetAddress ) {
            $parseResult    = $this->parseAddress( $params );
            $parseStatusMsg = $this->parseAddressStatusMsg( $parseResult );
        }
        
        require_once 'CRM/Contact/BAO/Contact.php';
        $contact =& CRM_Contact_BAO_Contact::create( $params, true,false );

        // set the contact ID
        $this->_contactId = $contact->id;

        if ( $this->_contactType == 'Individual' && ( CRM_Utils_Array::value( 'use_household_address', $params )) &&
             CRM_Utils_Array::value( 'mail_to_household_id',$params ) ) {
            // add/edit/delete the relation of individual with household, if use-household-address option is checked/unchecked.
            CRM_Contact_Form_Edit_Individual::handleSharedRelation($contact->id , $params );
        }
        
        if ( $this->_contactType == 'Household' && ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            //TO DO: commented because of schema changes
            require_once 'CRM/Contact/Form/Edit/Household.php';
            CRM_Contact_Form_Edit_Household::synchronizeIndividualAddresses( $contact->id );
        }
        
        if ( array_key_exists( 'TagsAndGroups', $this->_editOptions ) ) {
            //add contact to tags
            require_once 'CRM/Core/BAO/EntityTag.php';
            CRM_Core_BAO_EntityTag::create( $params['tag'], $params['contact_id'] );
        }
        
        $statusMsg = ts('Your %1 contact record has been saved.', array( 1 => $contact->contact_type_display ) );
        if ( $parseStatusMsg ) {
            $statusMsg =  "$statusMsg <br > $parseStatusMsg";
        }
        $session =& CRM_Core_Session::singleton( );
        CRM_Core_Session::setStatus( $statusMsg );

        require_once 'CRM/Utils/Recent.php';
        // add the recently viewed contact
        $displayName = CRM_Contact_BAO_Contact::displayName( $contact->id );
        CRM_Utils_Recent::add( $displayName,
                               CRM_Utils_System::url( 'civicrm/contact/view', 'reset=1&cid=' . $contact->id ),
                               $contact->id,
                               $this->_contactType,
                               $contact->id,
                               $displayName );
        
        // here we replace the user context with the url to view this contact
        $buttonName = $this->controller->getButtonName( );
        if ( $buttonName == $this->getButtonName( 'upload', 'new' )  ) {
            $resetStr  = "reset=1&ct={$contact->contact_type}";
            $resetStr .= $this->_contactSubType ? "&cst={$this->_contactSubType}" : '';
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/add', $resetStr ) );
        } else {
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $contact->id));
        }
        
        // now invoke the post hook
        if ($this->_action & CRM_Core_Action::UPDATE) {
            CRM_Utils_Hook::post( 'edit', $params['contact_type'], $contact->id, $contact );
        } else {
            CRM_Utils_Hook::post( 'create', $params['contact_type'], $contact->id, $contact );
        }
    }
    
    /**
     * is there any real significant data in the hierarchical location array
     *
     * @param array $fields the hierarchical value representation of this location
     *
     * @return boolean true if data exists, false otherwise
     * @static
     * @access public
     */
    static function blockDataExists( &$fields ) {
        if ( !is_array( $fields ) ) return false;
        
        static $skipFields = array( 'location_type_id', 'is_primary', 'phone_type_id', 'provider_id', 'country_id' );
        foreach ( $fields as $name => $value ) {
            $skipField = false;
            foreach ( $skipFields as $skip ) {
                if ( strpos( "[$skip]", $name ) !== false ) {
                    if($name == 'phone') continue;
                    $skipField = true;
                    break;
                }
            }
            if ( $skipField ) {
                continue;
            }
            if ( is_array( $value ) ) {
                if ( self::blockDataExists( $value ) ) {
                    return true;
                }
            } else {
                if ( ! empty( $value ) ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Function to that checks for duplicate contacts
     *  
     *  @param array  $fields      fields array which are submitted
     *  @param array  $error       error message array
     *  @param int    $contactID   contact id
     *  @param string $contactType contact type  
     */
     static function checkDuplicateContacts( &$fields, &$errors, $contactID, $contactType ) {
         // if this is a forced save, ignore find duplicate rule
         if ( ! CRM_Utils_Array::value( '_qf_Contact_upload_duplicate', $fields ) ) {
   
             require_once 'CRM/Dedupe/Finder.php';
             $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, $contactType);
             $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $contactType, 'Fuzzy', array( $contactID ) );
             if ( $ids ) {
                 require_once 'CRM/Contact/BAO/Contact/Utils.php';
                 
                 $contactLinks = CRM_Contact_BAO_Contact_Utils::formatContactIDSToLinks( $ids, true, true, $contactID );

                 $duplicateContactsLinks = '<div class="matching-contacts-found">';
                 $duplicateContactsLinks .= ts('One matching contact was found. ', array('count' => count($contactLinks['rows']), 'plural' => '%count matching contacts were found.<br />'));
                 if ( $contactLinks['msg'] == 'view') {
                     $duplicateContactsLinks .= ts('You can View the existing contact', array('count' => count($contactLinks['rows']), 'plural' => 'You can View the existing contacts'));                 
                 } else {
                     $duplicateContactsLinks .= ts('You can View or Edit the existing contact', array('count' => count($contactLinks['rows']), 'plural' => 'You can View or Edit the existing contacts'));
                 }
                 if  ( $contactLinks['msg'] == 'merge' ) {
                     // We should also get a merge link if this is for an existing contact
                     $duplicateContactsLinks .= ts(', or Merge this contact with an existing contact');
                 }
                 $duplicateContactsLinks .= '.';
                 $duplicateContactsLinks .= '</div>';
                 $duplicateContactsLinks .= '<table class="matching-contacts-actions">';

                 for ($i=0; $i < count($contactLinks['rows']); $i++) {                 
            	   $row .='  <tr>	 ';
            	   $row .='  	<td class="matching-contacts-name"> ';
            	   $row .=  		$contactLinks['rows'][$i]['display_name'];
            	   $row .='  	</td>';
            	   $row .='  	<td class="matching-contacts-email"> ';
            	   $row .=  		$contactLinks['rows'][$i]['primary_email'];
            	   $row .='  	</td>';            	   
            	   $row .='  	<td class="action-items"> ';
            	   $row .=  		$contactLinks['rows'][$i]['view'];
            	   $row .=  		$contactLinks['rows'][$i]['edit'];
            	   $row .=  		$contactLinks['rows'][$i]['merge'];
            	   $row .='  	</td>';
            	   $row .='  </tr>	 ';
                 }

                 $duplicateContactsLinks .= $row.'</table>';
                 $duplicateContactsLinks .= "If you're sure this record is not a duplicate, click the 'Save Matching Contact' button below.";
                 
				 $errors['_qf_default'] = $duplicateContactsLinks;
                 
                 

                 // let smarty know that there are duplicates
                 $template =& CRM_Core_Smarty::singleton( );
                 $template->assign( 'isDuplicate', 1 );
             } else if ( CRM_Utils_Array::value( '_qf_Contact_refresh_dedupe', $fields ) ) {
                 // add a session message for no matching contacts
                 CRM_Core_Session::setStatus(ts('No matching contact found.'));
             }
         }
     }   

    function getTemplateFileName() {
        if ( $this->_contactSubType ) {
            $templateFile = "CRM/Contact/Form/Edit/{$this->_contactSubType}.tpl";
            $template     =& CRM_Core_Form::getTemplate( );
            if ( $template->template_exists( $templateFile ) ) {
                return $templateFile;
            }
        }
        return parent::getTemplateFileName( );
    }
    
    /* Parse all address blocks present in given params
     * and return parse result for all address blocks,
     * This function either parse street address in to child 
     * elements or build street address from child elements.
     *
     * @params $params an array of key value consist of address  blocks.
     *
     * @return $parseSuccess as array of sucess/fails for every address block.
     */
    function parseAddress( &$params ) 
    {
        $parseSuccess = array( );
        if ( !is_array( $params['address'] ) || 
             CRM_Utils_System::isNull( $params['address'] ) ) {
            return $parseSuccess;
        }
        
        require_once 'CRM/Core/BAO/Address.php';
        
        $buildStreetAddress = false;
        foreach ( $params['address'] as $instance => &$address ) {
            $parseFieldName = 'street_address';
            foreach ( array( 'street_number', 'street_name', 'street_unit' ) as $fld ) {
                if ( CRM_Utils_Array::value( $fld, $address )  ) {
                    $parseFieldName     = 'street_number';
                    $buildStreetAddress = true;
                    break;
                }
            }
            
            // main parse string.
            $parseString = CRM_Utils_Array::value( $parseFieldName, $address );
            
            // parse address field.
            $parsedFields = CRM_Core_BAO_Address::parseStreetAddress( $parseString );
            
            if ( $buildStreetAddress ) {
                //hack to ignore spaces between number and suffix.
                //here user gives input as street_number so it has to
                //be street_number and street_number_suffix, but
                //due to spaces though preg detect string as street_name
                //consider it as 'street_number_suffix'.
                $suffix = $parsedFields['street_number_suffix'];
                if ( !$suffix ) {
                    $suffix = $parsedFields['street_name'];
                }
                $address['street_number_suffix'] = $suffix;
                $address['street_number']        = $parsedFields['street_number'];
                
                $streetAddress = null;
                foreach ( array( 'street_number', 'street_number_suffix', 'street_name', 'street_unit' ) as $fld ) {
                    if ( in_array( $fld, array( 'street_name', 'street_unit') ) ) {
                        $streetAddress .= ' ';
                    }
                    $streetAddress .= CRM_Utils_Array::value( $fld, $address );
                }
                $address['street_address'] = trim( $streetAddress );
                $parseSuccess[$instance]   = true;
            } else {
                $success = true;
                // consider address is automatically parseable,
                // when we should found street_number and street_name
                if ( ! CRM_Utils_Array::value( 'street_name', $parsedFields ) ||
                     ! CRM_Utils_Array::value( 'street_number', $parsedFields ) ) {
                    $success = false;
                }
                
                // check for original street address string.
                if ( empty( $parseString ) ) {
                    $success = true;
                }
                
                $parseSuccess[$instance] = $success;
                
                // reset element values.
                if ( !$success ) {
                    $parsedFields = array_fill_keys( array_keys($parsedFields), '' );
                }
                
                // merge parse address in to main address block.
                $address = array_merge( $address, $parsedFields );
            }
        }
        
        return $parseSuccess;
    }
    
    /* check parse result and if some address block fails then this
     * function return the status message for all address blocks.
     * 
     * @param  $parseResult an array of address blk instance and its status.
     *
     * @return $statusMsg   string status message for all address blocks. 
     */
    function parseAddressStatusMsg( $parseResult ) 
    {
        $statusMsg = null;
        if ( !is_array( $parseResult ) || empty( $parseResult ) ) {
            return $statusMsg;
        }
        
        $parseFails = array( );
        foreach ( $parseResult as $instance => $success ) {
            if ( !$success ) $parseFails[] = $this->ordinalNumber( $instance );
        }
        
        if ( !empty( $parseFails ) ) {
            $statusMsg = ts( "Complete street address(es) have been saved. However we were unable to split the address in the %1 address block(s) into address elements (street number, street name, street unit) due to an unrecognized address format. You can set the address elements manually by clicking 'Edit Address Elements' next to the Street Address field while in edit mode.",
                             array( 1 =>  implode( ', ', $parseFails ) ) );
        }
        
        return $statusMsg;
    }
    
    /* 
     * Convert normal number to ordinal number format.
     * like 1 => 1st, 2 => 2nd and so on...
     *
     * @param  $number int number to convert in to ordinal number.
     *
     * @return ordinal number for given number.
     */
    function ordinalNumber( $number ) 
    {
        if ( empty( $number )  ) {
            return null;
        }
        
        $str = 'th';
        switch( floor( $number/10 ) % 10 ) {
        case 1:            
        default:
            switch( $number % 10 ) {
            case 1: 
                $str = 'st';
                break;
            case 2:
                $str = 'nd';
                break;
            case 3: 
                $str = 'rd';
                break;
            }
        }
        
        return "$number$str";
    }
    
}


