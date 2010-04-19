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

require_once 'CRM/Core/DAO/Mapping.php';
require_once 'CRM/Core/DAO/MappingField.php';
require_once 'CRM/Contact/DAO/RelationshipType.php';

require_once 'CRM/Core/BAO/LocationType.php';

require_once 'CRM/Import/Parser/Contact.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Import_Form_MapField extends CRM_Core_Form 
{

    /**
     * cache of preview data values
     *
     * @var array
     * @access protected
     */
    protected $_dataValues;

    /**
     * mapper fields
     *
     * @var array
     * @access protected
     */
    protected $_mapperFields;

    /**
     * loaded mapping ID
     *
     * @var int
     * @access protected
     */
    protected $_loadedMappingId;

    /**
     * number of columns in import data
     *
     * @var int
     * @access protected
     */
    protected $_columnCount;


    /**
     * column names, if we have them
     *
     * @var array
     * @access protected
     */
    protected $_columnNames;

    /**
     * an array of booleans to keep track of whether a field has been used in
     * form building already.
     *
     * @var array
     * @access protected
     */
    protected $_fieldUsed;
    
    /**
     * an array of all contact fields with 
     * formatted custom field names.
     *
     * @var array
     * @access protected
     */
    protected static $_formattedFieldNames;
    
    /**
     * on duplicate
     *
     * @var int
     */
    public $_onDuplicate;

    protected $_dedupeFields;
        
    /**
     * Attempt to resolve a column name with our mapper fields
     *
     * @param columnName
     * @param mapperFields
     * @return string
     * @access public
     */
    public function defaultFromColumnName($columnName, &$patterns) 
    {
        foreach ($patterns as $key => $re) {
            /* skip empty patterns */
            if ( empty( $re ) or $re == '//' ) {
                continue;
            }

            if (preg_match($re, $columnName)) {
                $this->_fieldUsed[$key] = true;
                return $key;
            }
        }
        return '';
    }

    /**
     * Guess at the field names given the data and patterns from the schema
     *
     * @param patterns
     * @param index
     * @return string
     * @access public
     */
    public function defaultFromData(&$patterns, $index) 
    {
        $best = '';
        $bestHits = 0;
        $n = count($this->_dataValues);
        
        foreach ($patterns as $key => $re) {
            if (empty($re)) continue;

            /* Take a vote over the preview data set */
            $hits = 0;
            for ($i = 0; $i < $n; $i++) {
                if (preg_match($re, $this->_dataValues[$i][$index])) {
                    $hits++;
                }
            }

            if ($hits > $bestHits) {
                $bestHits = $hits;
                $best = $key;
            }
        }
    
        if ($best != '') {
            $this->_fieldUsed[$best] = true;
        }
        return $best;
    }

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess()
    {
        $dataSource             = $this->get( 'dataSource' );
        $skipColumnHeader       = $this->get( 'skipColumnHeader' );
        $this->_mapperFields    = $this->get( 'fields' );
        $this->_importTableName = $this->get( 'importTableName' );        
        $this->_onDuplicate     = $this->get( 'onDuplicate' );
        $highlightedFields   = array();
        $highlightedFields[] = 'email';
        $highlightedFields[] = 'external_identifier';
        //format custom field names, CRM-2676
        switch ( $this->get( 'contactType' ) ) {
        case CRM_Import_Parser::CONTACT_INDIVIDUAL :
            $contactType = 'Individual';
            $highlightedFields[] = 'first_name';
            $highlightedFields[] = 'last_name';
            break;
        case CRM_Import_Parser::CONTACT_HOUSEHOLD :
            $contactType = 'Household';
            $highlightedFields[] = 'household_name'; 
            break;
        case CRM_Import_Parser::CONTACT_ORGANIZATION :
            $contactType = 'Organization';
            $highlightedFields[] = 'organization_name'; 
            break;
        }
        $this->_contactType = $contactType;
        if ( $this->_onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP ) {
            unset($this->_mapperFields['id']);  
        } else {
            $highlightedFields[] = 'id';            
        }

        if ( $this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK ) {
            //Mark Dedupe Rule Fields as required, since it's used in matching contact
            require_once 'CRM/Dedupe/BAO/Rule.php';
            foreach ( array( 'Individual','Household','Organization' ) as $cType ) {
                $ruleParams = array(
                                    'contact_type' => $cType,
                                    'level'        => 'Strict'
                                    );
                $this->_dedupeFields[$cType] = CRM_Dedupe_BAO_Rule::dedupeRuleFields( $ruleParams );
            }

            //Modify mapper fields title if fields are present in dedupe rule
            if ( is_array( $this->_dedupeField[$contactType] ) ) {
                foreach ( $this->_dedupeFields[$contactType] as $val ) {
                    if ( $valTitle = CRM_Utils_Array::value( $val, $this->_mapperFields ) ) {
                        $this->_mapperFields[$val]  = $valTitle . ' (match to contact)';
                    }
                }
            }
        }

        $this->assign( 'highlightedFields', $highlightedFields );
        $this->_formattedFieldNames[$contactType] = $this->_mapperFields =
            array_merge( $this->_mapperFields, $this->formatCustomFieldName( $this->_mapperFields ) );
        
        $columnNames = array( );
        //get original col headers from csv if present.
        if ( $dataSource == 'CRM_Import_DataSource_CSV' && $skipColumnHeader ) {
            $columnNames = $this->get( 'originalColHeader' );
        } else {
            // get the field names from the temp. DB table
            $dao = new CRM_Core_DAO();
            $db = $dao->getDatabaseConnection();
            
            $columnsQuery = "SHOW FIELDS FROM $this->_importTableName
                         WHERE Field NOT LIKE '\_%'";
            $columnsResult = $db->query($columnsQuery);
            while ( $row = $columnsResult->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $columnNames[] = $row['Field'];
            }
        }
        
        $showColNames = true;
        if ( $dataSource == 'CRM_Import_DataSource_CSV' && !$skipColumnHeader ) {
            $showColNames = false;
        }
        $this->assign( 'showColNames', $showColNames );
        
        $this->_columnCount = count( $columnNames );
        $this->_columnNames = $columnNames;
        $this->assign( 'columnNames', $columnNames );
        //$this->_columnCount = $this->get( 'columnCount' );
        $this->assign( 'columnCount' , $this->_columnCount );
        $this->_dataValues = $this->get( 'dataValues' );
        $this->assign( 'dataValues'  , $this->_dataValues );
        $this->assign( 'rowDisplayCount', 2 );
    }

    /**
     * Function to actually build the form
     *
     * @return void
     * @access public
     */
    public function buildQuickForm()
    {
        require_once "CRM/Core/BAO/Mapping.php";
        require_once "CRM/Core/OptionGroup.php";        
        //to save the current mappings
        if ( !$this->get('savedMapping') ) {
            $saveDetailsName = ts('Save this field mapping');
            $this->applyFilter('saveMappingName', 'trim');
            $this->add('text','saveMappingName',ts('Name'));
            $this->add('text','saveMappingDesc',ts('Description'));
        } else {
            $savedMapping = $this->get('savedMapping');

            list ($mappingName, $mappingContactType, $mappingLocation, $mappingPhoneType, $mappingImProvider, $mappingRelation  ) = CRM_Core_BAO_Mapping::getMappingFields($savedMapping);
            
            //get loaded Mapping Fields
            $mappingName        = CRM_Utils_Array::value( 1, $mappingName );
            $mappingContactType = CRM_Utils_Array::value( 1, $mappingContactType );
            $mappingLocation    = CRM_Utils_Array::value( 1, $mappingLocation );
            $mappingPhoneType   = CRM_Utils_Array::value( 1, $mappingPhoneType );
            $mappingImProvider  = CRM_Utils_Array::value( 1, $mappingImProvider );
            $mappingRelation    = CRM_Utils_Array::value( 1, $mappingRelation );
           
            $this->assign('loadedMapping', $savedMapping);
            $this->set('loadedMapping', $savedMapping);

            $params = array('id' => $savedMapping);
            $temp   = array ();
            $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

            $this->assign('savedName', $mappingDetails->name);

            $this->add('hidden','mappingId',$savedMapping);

            $this->addElement('checkbox','updateMapping',ts('Update this field mapping'), null);
            $saveDetailsName = ts('Save as a new field mapping');
            $this->add('text','saveMappingName',ts('Name'));
            $this->add('text','saveMappingDesc',ts('Description'));
        }
        
        $this->addElement('checkbox','saveMapping',$saveDetailsName, null, array('onclick' =>"showSaveDetails(this)"));
        
        $this->addFormRule( array( 'CRM_Import_Form_MapField', 'formRule' ) );

        //-------- end of saved mapping stuff ---------

        $defaults = array( );
        $mapperKeys      = array_keys( $this->_mapperFields );
        $hasColumnNames      = !empty($this->_columnNames);
        $columnPatterns  = $this->get( 'columnPatterns' );
        $dataPatterns    = $this->get( 'dataPatterns' );
        $hasLocationTypes = $this->get( 'fieldTypes' );

        $this->_location_types  =& CRM_Core_PseudoConstant::locationType();

        $defaultLocationType =& CRM_Core_BAO_LocationType::getDefault();

        /* FIXME: dirty hack to make the default option show up first.  This
         * avoids a mozilla browser bug with defaults on dynamically constructed
         * selector widgets. */
        if ($defaultLocationType) {
            $defaultLocation = $this->_location_types[$defaultLocationType->id];
            unset($this->_location_types[$defaultLocationType->id]);
            $this->_location_types = 
                array($defaultLocationType->id => $defaultLocation) + 
                $this->_location_types;
        }

        /* Initialize all field usages to false */
        foreach ($mapperKeys as $key) {
            $this->_fieldUsed[$key] = false;
        }

        $sel1     = $this->_mapperFields;
        $sel2[''] = null;

        $phoneTypes = CRM_Core_PseudoConstant::phoneType();
        $imProviders = CRM_Core_PseudoConstant::IMProvider();
        foreach ($this->_location_types as $key => $value) {
            $sel3['phone'][$key] =& $phoneTypes;
            //build array for IM service provider type for contact
            $sel3['im'][$key]    =& $imProviders;
        }
        
        $sel4 = null;

        // store and cache all relationship types
        $contactRelation =& new CRM_Contact_DAO_RelationshipType();
        $contactRelation->find( );
        while ( $contactRelation->fetch( ) ) {
            $contactRelationCache[$contactRelation->id] = array( );
            $contactRelationCache[$contactRelation->id]['contact_type_a']     = $contactRelation->contact_type_a;
            $contactRelationCache[$contactRelation->id]['contact_sub_type_a'] = $contactRelation->contact_sub_type_a;
            $contactRelationCache[$contactRelation->id]['contact_type_b']     = $contactRelation->contact_type_b;
            $contactRelationCache[$contactRelation->id]['contact_sub_type_b'] = $contactRelation->contact_sub_type_b;
        }
        $highlightedFields = $highlightedRelFields = array();
        
        $highlightedFields['email']               = 'All';
        $highlightedFields['external_identifier'] = 'All';
        $highlightedFields['first_name']          = 'Individual';
        $highlightedFields['last_name']           = 'Individual';
        $highlightedFields['household_name']      = 'Household';
        $highlightedFields['organization_name']   = 'Organization'; 

        foreach ($mapperKeys as $key) {
            // check if there is a _a_b or _b_a in the key
            if ( strpos( $key, '_a_b' ) || strpos( $key, '_b_a' ) ) {
                list($id, $first, $second) = explode('_', $key);
            } else {
                $id = $first = $second = null;
            }
            if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                $cType = $contactRelationCache[$id]["contact_type_{$second}"];
                
                //CRM-5125 for contact subtype specific relationshiptypes
                $cSubType = null;
                if ( CRM_Utils_Array::value("contact_sub_type_{$second}", $contactRelationCache[$id]) ) {
                    $cSubType =  $contactRelationCache[$id]["contact_sub_type_{$second}"];   
                }

                if ( ! $cType ) {
                    $cType = 'All';
                }
                
                $relatedFields = array();
                require_once 'CRM/Contact/BAO/Contact.php'; 
                $relatedFields =& CRM_Contact_BAO_Contact::importableFields( $cType );
                unset($relatedFields['']);
                $values = array();
                foreach ($relatedFields as $name => $field ) {
                    $values[$name] = $field['title'];
                    if (isset ( $hasLocationTypes[$name] ) ) {
                        $sel3[$key][$name] = $this->_location_types;
                    } else {
                        $sel3[$name] = null;
                    }
                }
                
                //fix to append custom group name to field name, CRM-2676
                if ( !CRM_Utils_Array::value( $cType, $this->_formattedFieldNames ) || $cType == $this->_contactType ) {
                    $this->_formattedFieldNames[$cType] = $this->formatCustomFieldName( $values );
                }

                $this->_formattedFieldNames[$cType] = array_merge( $values, $this->_formattedFieldNames[$cType] );

                //Modified the Relationship fields if the fields are
                //present in dedupe rule
                if ( $this->_onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK && 
                     is_array( $this->_dedupeField[$cType] ) ) {
                    static $cTypeArray = array();
                    if ( $cType != $this->_contactType && !in_array( $cType, $cTypeArray ) ) {
                        foreach ( $this->_dedupeFields[$cType] as $val ) {
                            if ( $valTitle = CRM_Utils_Array::value( $val, $this->_formattedFieldNames[$cType] ) ) {
                                $this->_formattedFieldNames[$cType][$val]  = $valTitle . ' (match to contact)';
                            }
                        }
                        $cTypeArray[] = $cType;
                    }
                }

                foreach ($highlightedFields as $k => $v ) {
                    if ( $v == $cType || $v == 'All' ) {
                        $highlightedRelFields[$key][] = $k;
                    }
                }
                $this->assign( 'highlightedRelFields', $highlightedRelFields );
                $sel2[$key] = $this->_formattedFieldNames[$cType];

                if ( !empty($cSubType) ) { 
                    //custom fields for sub type
                    $subTypeFields = CRM_Core_BAO_CustomField::getFieldsForImport( $cSubType );
                                        
                    if ( !empty($subTypeFields) ) {
                        $subType = null;
                        foreach($subTypeFields as $customSubTypeField => $details ) {
                            $subType[$customSubTypeField] = $details['title'];
                            $sel2[$key] = array_merge( $sel2[$key], $this->formatCustomFieldName($subType) );
                        }  
                    }
                }
                
                foreach ($this->_location_types as $k => $value) {
                    $sel4[$key]['phone'][$k] =& $phoneTypes;
                    //build array of IM service provider for related contact 
                    $sel4[$key]['im'][$k]    =& $imProviders;
                }
                
            } else {
                if ($hasLocationTypes[$key]) {
                    $sel2[$key] = $this->_location_types;
                } else {
                    $sel2[$key] = null;
                }
            }
        }

        $js = "<script type='text/javascript'>\n";
        $formName = 'document.forms.' . $this->_name;
        
        //used to warn for mismatch column count or mismatch mapping      
        $warning = 0;
        for ( $i = 0; $i < $this->_columnCount; $i++ ) {
            $sel =& $this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', array(1 => $i)), null);
            $jsSet = false;
            if( $this->get('savedMapping') ) {                                              
                if ( isset($mappingName[$i]) ) {
                    if ( $mappingName[$i] != ts('- do not import -')) {                                
                        
                        if ( isset($mappingRelation[$i]) ) {
                            // relationship mapping
                            switch ($this->get('contactType')) {
                            case CRM_Import_Parser::CONTACT_INDIVIDUAL :
                                $contactType = 'Individual';
                                break;
                            case CRM_Import_Parser::CONTACT_HOUSEHOLD :
                                $contactType = 'Household';
                                break;
                            case CRM_Import_Parser::CONTACT_ORGANIZATION :
                                $contactType = 'Organization';
                            }
                            //CRM-5125
                            $contactSubType = null;
                            if ( $this->get('contactSubType') ) {
                                $contactSubType = $this->get('contactSubType');
                            }
                            
                            $relations = CRM_Contact_BAO_Relationship::getContactRelationshipType( null, null, null, $contactType, 
                                                                                                   false, 'label', true, $contactSubType );
                            
                            foreach ($relations as $key => $var) {
                                if ( $key == $mappingRelation[$i]) {
                                    $relation = $key;
                                    break;
                                }
                            }

                            $contactDetails = strtolower(str_replace(" ", "_",$mappingName[$i]));
                            $locationId = isset($mappingLocation[$i])? $mappingLocation[$i] : 0;
                            $phoneType = isset($mappingPhoneType[$i]) ? $mappingPhoneType[$i] : null;
                            //get provider id from saved mappings
                            $imProvider = isset($mappingImProvider[$i]) ? $mappingImProvider[$i] : null;
                           
                            // default for IM/phone when mapping with relation is true
                            $typeId = null;
                            if ( isset($phoneType) ) {
                                $typeId = $phoneType;                                   
                            } else if ( isset($imProvider) ) {
                                $typeId = $imProvider;
                            }
                            
                            // fix for edge cases, CRM-4954
                            if ( $contactDetails == 'home_url' || $contactDetails == 'image_url' ) {
                                $contactDetails = str_replace( 'url', 'URL', $contactDetails );
                            }
                            
                            $defaults["mapper[$i]"] = array( $relation, $contactDetails, $locationId, $typeId
                                                             );
                            if ( ! $contactDetails ) {
                                $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
                            }
                            if ( ! $locationId ) {
                                $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
                            }
                            if ( ( ! $phoneType ) && ( ! $imProvider ) ) {
                                $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
                            }
                            //$js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
                            $jsSet = true;
                        } else {
                            $mappingHeader = array_keys($this->_mapperFields, $mappingName[$i]);
                            $locationId = isset($mappingLocation[$i])? $mappingLocation[$i] : 0;
                            $phoneType = isset($mappingPhoneType[$i]) ? $mappingPhoneType[$i] : null;
                            // get IM service provider id
                            $imProvider = isset($mappingImProvider[$i]) ? $mappingImProvider[$i] : null;
                            
                            if ( ! $locationId ) {
                                $js .= "{$formName}['mapper[$i][1]'].style.display = 'none';\n";
                            }

                            if ( ( ! $phoneType ) && ( ! $imProvider ) ) {
                                $js .= "{$formName}['mapper[$i][2]'].style.display = 'none';\n";
                            }
                            
                            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
                            
                            //default for IM/phone without related contact 
                            $typeId = null;
                            if( isset($phoneType) ) {
                                $typeId = $phoneType;
                            } else if ( isset($imProvider) ) {
                                $typeId = $imProvider;
                            }
                            $defaults["mapper[$i]"] = array( $mappingHeader[0], $locationId, $typeId );
                            
                            $jsSet = true;
                        }                    
                    } else {
                        $defaults["mapper[$i]"] = array();
                    }                          
                    if ( ! $jsSet ) {
                        for ( $k = 1; $k < 4; $k++ ) {
                            $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n"; 
                        }
                    }
                } else {
                    // this load section to help mapping if we ran out of saved columns when doing Load Mapping
                    $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";
                    
                    if ($hasColumnNames) {
                        $defaults["mapper[$i]"] = array( $this->defaultFromColumnName($this->_columnNames[$i],$columnPatterns) );
                    } else {
                        $defaults["mapper[$i]"] = array( $this->defaultFromData($dataPatterns, $i) );
                    }                    
                } //end of load mapping
            } else {
                $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_0_');\n";
                if ($hasColumnNames) {
                    // Infer the default from the column names if we have them
                    $defaults["mapper[$i]"] = array(
                                                           $this->defaultFromColumnName($this->_columnNames[$i], 
                                                                                    $columnPatterns),
                                                           0
                                                           );
                    
                } else {
                    // Otherwise guess the default from the form of the data
                    $defaults["mapper[$i]"] = array(
                                                           $this->defaultFromData($dataPatterns, $i),
                                                           //                     $defaultLocationType->id
                                                           0
                                                           );
                }
            }
            $sel->setOptions(array($sel1, $sel2, $sel3, $sel4));
        }
        
        $js .= "</script>\n";
        $this->assign('initHideBoxes', $js);

        //set warning if mismatch in more than 
        if ( isset( $mappingName ) &&
             ( $this->_columnCount != count( $mappingName ) ) ) {
            $warning++;            
        }

        if ( $warning != 0 && $this->get('savedMapping') ) {
            $session =& CRM_Core_Session::singleton( );
            $session->setStatus( ts( 'The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.' ) );
        } else {
            $session =& CRM_Core_Session::singleton( );
            $session->setStatus( null ); 
        }

        $this->setDefaults( $defaults );       

        $this->addButtons( array(
                                 array ( 'type'      => 'back',
                                         'name'      => ts('<< Previous') ),
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Continue >>'),
                                         'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'cancel',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

    /**
     * global validation rules for the form
     *
     * @param array $fields posted values of the form
     *
     * @return array list of errors to be posted back to the form
     * @static
     * @access public
     */
    static function formRule( &$fields ) 
    {
        $errors  = array( );
        if ( CRM_Utils_Array::value( 'saveMapping', $fields ) ) {
            $nameField = CRM_Utils_Array::value( 'saveMappingName', $fields );
            if ( empty( $nameField ) ) {
                $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
            } else {
                $mappingTypeId = CRM_Core_OptionGroup::getValue( 'mapping_type', 'Import Contact', 'name' );
                if ( CRM_Core_BAO_Mapping::checkMapping( $nameField, $mappingTypeId ) ) {
                    $errors['saveMappingName'] = ts('Duplicate Import Mapping Name');
                }
            }
        }
        $template =& CRM_Core_Smarty::singleton( );
        if ( CRM_Utils_Array::value('saveMapping', $fields) ) {
            $template->assign('isCheked', true ); 
        }
        
        if ( !empty($errors) ) {
            $_flag = 1;
            require_once 'CRM/Core/Page.php';
            $assignError =& new CRM_Core_Page(); 
            $assignError->assign('mappingDetailsError', $_flag);
            return $errors;
        } else {
            return true;
        }
    }

    /**
     * Process the mapped fields and map it into the uploaded file
     * preview the file and extract some summary statistics
     *
     * @return void
     * @access public
     */
    public function postProcess()
    {
        $params = $this->controller->exportValues( 'MapField' );

        //reload the mapfield if load mapping is pressed
        if( !empty($params['savedMapping']) ) {            
            $this->set('savedMapping', $params['savedMapping']);
            $this->controller->resetPage( $this->_name );
            return;
        }

        $mapperKeys = array( );
        $mapper     = array( );
        $mapperKeys = $this->controller->exportValue( $this->_name, 'mapper' );
        $mapperKeysMain     = array();
        $mapperLocType      = array();
        $mapperPhoneType    = array();
        $mapperImProvider   = array();
        
        $locations = array();
        
        $phoneTypes = CRM_Core_PseudoConstant::phoneType();
        $imProviders = CRM_Core_PseudoConstant::IMProvider();

        for ( $i = 0; $i < $this->_columnCount; $i++ ) {
            $mapper[$i]     = $this->_mapperFields[$mapperKeys[$i][0]];
            $mapperKeysMain[$i] = $mapperKeys[$i][0];

            if ( isset( $mapperKeys[$i][1] ) &&
                 is_numeric( $mapperKeys[$i][1] ) ) {
                $mapperLocType[$i] = $mapperKeys[$i][1];
            } else {
                $mapperLocType[$i] = null;
            }

            $locations[$i]  =   isset($mapperLocType[$i])
                            ?   $this->_location_types[$mapperLocType[$i]]
                            :   null;
            // to store phone_type id and provider id seperately, CRM-3140
            if ( CRM_Utils_Array::value($i,$mapperKeysMain) == 'phone' ) {
                $mapperPhoneType[$i]  = $phoneTypes[$mapperKeys[$i][2]];
                $mapperImProvider[$i] = null;
            } else if ( CRM_Utils_Array::value($i,$mapperKeysMain) == 'im' ) {
                $mapperImProvider[$i] = $imProviders[$mapperKeys[$i][2]];
                $mapperPhoneType[$i]  = null;
            } else {
                $mapperPhoneType[$i]  = null;
                $mapperImProvider[$i] = null;
            }

            //relationship info
            if ( isset( $mapperKeys[$i] ) &&
                 isset( $mapperKeys[$i][0] ) ) {
                list($id, $first, $second) = CRM_Utils_System::explode( '_', $mapperKeys[$i][0], 3);
            } else {
                list($id, $first, $second) = array( null, null, null );
            }
            if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                $related[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
                $relatedContactLocType[$i] = isset($mapperKeys[$i][1]) ? $this->_location_types[$mapperKeys[$i][2]] : null;
                //$relatedContactPhoneType[$i] = !is_numeric($mapperKeys[$i][2]) ? $mapperKeys[$i][3] : null;
                // to store phoneType id and provider id seperately for ralated contact, CRM-3140
                if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'phone' ) {
                    $relatedContactPhoneType[$i] = isset($mapperKeys[$i][3]) ? $phoneTypes[$mapperKeys[$i][3]] : null;
                    $relatedContactImProvider[$i] = null;
                } else if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'im' ) {
                    $relatedContactImProvider[$i] = isset($mapperKeys[$i][3]) ? $imProviders[$mapperKeys[$i][3]] : null;
                    $relatedContactPhoneType[$i] = null;
                }
                $relationType =& new CRM_Contact_DAO_RelationshipType();
                $relationType->id = $id;
                $relationType->find(true);
                eval( '$relatedContactType[$i] = $relationType->contact_type_'.$second.';');
                $relatedContactDetails[$i] = $this->_formattedFieldNames[$relatedContactType[$i]][$mapperKeys[$i][1]];
            } else {
                $related[$i] = null;
                $relatedContactType[$i] = null;
                $relatedContactDetails[$i] = null;
                $relatedContactLocType[$i] = null;                
                $relatedContactPhoneType[$i] = null;
                $relatedContactImProvider[$i] = null;
            }            
        }
        
        $this->set( 'mapper'    , $mapper     );
        $this->set( 'locations' , $locations  );
        $this->set( 'phones', $mapperPhoneType);
        $this->set( 'ims' , $mapperImProvider );
        $this->set( 'columnNames', $this->_columnNames);
        
        //relationship info
        $this->set( 'related', $related );
        $this->set( 'relatedContactType',$relatedContactType );
        $this->set( 'relatedContactDetails',$relatedContactDetails );
        $this->set( 'relatedContactLocType',$relatedContactLocType );
        $this->set( 'relatedContactPhoneType',$relatedContactPhoneType );
        $this->set( 'relatedContactImProvider',$relatedContactImProvider );
        
        // store mapping Id to display it in the preview page 
        $this->set('loadMappingId', CRM_Utils_Array::value( 'mappingId', $params ) );
        
        //Updating Mapping Records
        if ( CRM_Utils_Array::value('updateMapping', $params)) {
            
            $locationTypes =& CRM_Core_PseudoConstant::locationType();            

            $mappingFields =& new CRM_Core_DAO_MappingField();
            $mappingFields->mapping_id = $params['mappingId'];
            $mappingFields->find( );
            
            $mappingFieldsId = array();                
            while($mappingFields->fetch()) {
                if ( $mappingFields->id ) {
                    $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
                }
            }
                
            for ( $i = 0; $i < $this->_columnCount; $i++ ) {
                $updateMappingFields =& new CRM_Core_DAO_MappingField();
                $updateMappingFields->id = $mappingFieldsId[$i];
                $updateMappingFields->mapping_id = $params['mappingId'];
                $updateMappingFields->column_number = $i;

                list($id, $first, $second) = explode('_', $mapperKeys[$i][0]);
                if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                    $updateMappingFields->relationship_type_id = $id;
                    $updateMappingFields->relationship_direction = "{$first}_{$second}";
                    $updateMappingFields->name = ucwords(str_replace("_", " ",$mapperKeys[$i][1]));
                    $updateMappingFields->location_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null;  
                    // get phoneType id and provider id separately
                    // before updating mappingFields of phone and IM for related contact, CRM-3140
                    if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'phone' ) {               
                        $updateMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : null;                  
                    } else if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'im' ) {
                        $updateMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : null;                  
                    }
                } else {
                    $updateMappingFields->name = $mapper[$i];
                    $updateMappingFields->relationship_type_id = 'NULL';
                    $updateMappingFields->relationship_type_direction = 'NULL';
                    $location = array_keys($locationTypes, $locations[$i]);
                    $updateMappingFields->location_type_id = isset($location) ? $location[0] : null;                    
                    // to store phoneType id and provider id seperately
                    // before updating mappingFields for phone and IM, CRM-3140
                    if ( CRM_Utils_Array::value( '0', $mapperKeys[$i] ) == 'phone' ) {
                        $updateMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null; 
                    } else if ( CRM_Utils_Array::value( '0', $mapperKeys[$i] ) == 'im' ) {
                        $updateMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null; 
                    }
                }
                $updateMappingFields->save();                
            }
        }
        
        //Saving Mapping Details and Records
        if ( CRM_Utils_Array::value('saveMapping', $params) ) {
            $mappingParams = array('name'            => $params['saveMappingName'],
                                   'description'     => $params['saveMappingDesc'],
                                   'mapping_type_id' => CRM_Core_OptionGroup::getValue( 'mapping_type',
                                                                                        'Import Contact',
                                                                                        'name' ) );
            
            $saveMapping = CRM_Core_BAO_Mapping::add( $mappingParams );
            
            $locationTypes =& CRM_Core_PseudoConstant::locationType();
            $contactType = $this->get('contactType');
            switch ($contactType) {
            case CRM_Import_Parser::CONTACT_INDIVIDUAL :
                $cType = 'Individual';
                break;
            case CRM_Import_Parser::CONTACT_HOUSEHOLD :
                $cType = 'Household';
                break;
            case CRM_Import_Parser::CONTACT_ORGANIZATION :
                $cType = 'Organization';
            }

            for ( $i = 0; $i < $this->_columnCount; $i++ ) {                  
                $saveMappingFields =& new CRM_Core_DAO_MappingField();
                $saveMappingFields->mapping_id    = $saveMapping->id;
                $saveMappingFields->contact_type  = $cType;
                $saveMappingFields->column_number = $i;                             
                
                list($id, $first, $second) = explode('_', $mapperKeys[$i][0]);
                if ( ($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a') ) {
                    $saveMappingFields->name = ucwords(str_replace("_", " ",$mapperKeys[$i][1]));
                    $saveMappingFields->relationship_type_id = $id;
                    $saveMappingFields->relationship_direction = "{$first}_{$second}";
                    // to get phoneType id and provider id seperately
                    // before saving mappingFields of phone and IM for related contact, CRM-3140
                    if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'phone' ) {
                        $saveMappingFields->phone_type_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : null;
                    } else if ( CRM_Utils_Array::value( '1', $mapperKeys[$i] ) == 'im' ) {
                        $saveMappingFields->im_provider_id = isset($mapperKeys[$i][3]) ? $mapperKeys[$i][3] : null;
                    }
                    $saveMappingFields->location_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null;
                } else {
                    $saveMappingFields->name = $mapper[$i];
                    $location_id = array_keys($locationTypes, $locations[$i]);
                    $saveMappingFields->location_type_id = isset($location_id[0]) ? $location_id[0] : null;
                    // to get phoneType id and provider id seperately
                    // before saving mappingFields of phone and IM, CRM-3140
                    if ( CRM_Utils_Array::value( '0', $mapperKeys[$i] ) == 'phone' ) {
                        $saveMappingFields->phone_type_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null;
                    } else if ( CRM_Utils_Array::value( '0', $mapperKeys[$i] ) == 'im' ) {
                        $saveMappingFields->im_provider_id = isset($mapperKeys[$i][2]) ? $mapperKeys[$i][2] : null;
                    }
                    $saveMappingFields->relationship_type_id = null;
                }
                $saveMappingFields->save();
            }
            $this->set( 'savedMapping', $saveMappingFields->mapping_id );
        }
        
        $parser =& new CRM_Import_Parser_Contact(  $mapperKeysMain, $mapperLocType, $mapperPhoneType, 
                                                   $mapperImProvider, $related, $relatedContactType, 
                                                   $relatedContactDetails, $relatedContactLocType, 
                                                   $relatedContactPhoneType, $relatedContactImProvider );
       
                                         
        $primaryKeyName = $this->get( 'primaryKeyName' );
        $statusFieldName = $this->get( 'statusFieldName' );
        $parser->run( $this->_importTableName, $mapper,
                      CRM_Import_Parser::MODE_PREVIEW,
                      $this->get('contactType'),
                      $primaryKeyName, $statusFieldName, $this->_onDuplicate, 
                      null, null, false, CRM_Import_Parser::DEFAULT_TIMEOUT, $this->get('contactSubType') );
        
        // add all the necessary variables to the form
        $parser->set( $this );        
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle()
    {
        return ts('Match Fields');
    }

    /**
     * format custom field name.
     * combine group and field name to avoid conflict.
     *
     * @return void
     * @access public
     */
    function formatCustomFieldName( &$fields ) 
    {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        $fieldIds = $formattedFieldNames = array( );
        foreach ( $fields as $key => $value ) {
            require_once 'CRM/Core/BAO/CustomField.php';
            if ( $customFieldId = CRM_Core_BAO_CustomField::getKeyID( $key ) ) {
                $fieldIds[] = $customFieldId;
            }
        }
        
        if ( !empty( $fieldIds ) && is_array( $fieldIds ) ) {
            require_once 'CRM/Core/BAO/CustomGroup.php';
            $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles( $fieldIds );
            
            if ( !empty( $groupTitles ) ) {
                foreach ( $groupTitles as $fId => $values ) {
                    $key = "custom_{$fId}";
                    $groupTitle = $values['groupTitle'];
                    $formattedFieldNames[$key] = $fields[$key] . ' :: ' . $groupTitle;
                }
            }
        }
        
        return $formattedFieldNames;
    }
    
}
