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
 * This class is used to build address block
 */
class CRM_Contact_Form_Edit_Address
{
    /**
     * build form for address input fields 
     *
     * @param object $form - CRM_Core_Form (or subclass)
     * @param array reference $location - location array
     * @param int $locationId - location id whose block needs to be built.
     * @return none
     *
     * @access public
     * @static
     */
    static function buildQuickForm( &$form ) 
    {
        $blockId    = ( $form->get( 'Address_Block_Count' ) ) ? $form->get( 'Address_Block_Count' ) : 1;
        
        $config =& CRM_Core_Config::singleton( );
        $countryDefault = $config->defaultContactCountry;
        
        $form->applyFilter('__ALL__','trim');
   
        $js = array( 'onChange' => 'checkLocation( this.id );');
        $form->addElement('select',
                          "address[$blockId][location_type_id]",
                          ts( 'Location Type' ),
                          array( '' => ts( '- select -' ) ) + CRM_Core_PseudoConstant::locationType( ), $js );
        
        $js = array( 'id' => "Address_".$blockId."_IsPrimary", 'onClick' => 'singleSelect( this.id );');
        $form->addElement(
                          'checkbox', 
                          "address[$blockId][is_primary]", 
                          ts('Primary location for this contact'),  
                          ts('Primary location for this contact'), 
                          $js );
        
        $js = array( 'id' => "Address_".$blockId."_IsBilling", 'onClick' => 'singleSelect( this.id );');
        $form->addElement(
                          'checkbox', 
                          "address[$blockId][is_billing]", 
                          ts('Billing location for this contact'),  
                          ts('Billing location for this contact'), 
                          $js );
        
        require_once 'CRM/Core/BAO/Preferences.php';
        $addressOptions = CRM_Core_BAO_Preferences::valueOptions( 'address_options', true, null, true );
        $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');
        
        $elements = array( 
                          'address_name'           => array( ts('Address Name')      ,  $attributes['address_name'], null ),
                          'street_address'         => array( ts('Street Address')    ,  $attributes['street_address'], null ),
                          'supplemental_address_1' => array( ts('Addt\'l Address 1') ,  $attributes['supplemental_address_1'], null ),
                          'supplemental_address_2' => array( ts('Addt\'l Address 2') ,  $attributes['supplemental_address_2'], null ),
                          'city'                   => array( ts('City')              ,  $attributes['city'] , null ),
                          'postal_code'            => array( ts('Zip / Postal Code') ,  $attributes['postal_code'], null ),
                          'postal_code_suffix'     => array( ts('Postal Code Suffix'),  array( 'size' => 4, 'maxlength' => 12 ), null ),
                          'county_id'              => array( ts('County')            ,  $attributes['county_id'], 'county' ),
                          'state_province_id'      => array( ts('State / Province')  ,  $attributes['state_province_id'],null ),
                          'country_id'             => array( ts('Country')           ,  $attributes['country_id'], null ), 
                          'geo_code_1'             => array( ts('Latitude') ,  array( 'size' => 9, 'maxlength' => 10 ), null ),
                          'geo_code_2'             => array( ts('Longitude'),  array( 'size' => 9, 'maxlength' => 10 ), null ),
                          'street_number'          => array( ts('Street Number')       , $attributes['street_number'], null ),
                          'street_name'            => array( ts('Street Name')         , $attributes['street_name'], null ),
                          'street_unit'            => array( ts('Apt/Unit/Suite')         , $attributes['street_unit'], null )
                          );

        $stateCountryMap = array( );
        foreach ( $elements as $name => $v ) {
            list( $title, $attributes, $select ) = $v;

            $nameWithoutID = strpos( $name, '_id' ) !== false ? substr( $name, 0, -3 ) : $name;
            if ( ! CRM_Utils_Array::value( $nameWithoutID, $addressOptions ) ) {
                $continue = true;
                if ( in_array( $nameWithoutID, array('street_number', 'street_name', 'street_unit' ) ) &&
                     CRM_Utils_Array::value( 'street_address_parsing', $addressOptions ) ) {
                    $continue = false;
                }
                if ( $continue ) {
                    continue;
                }
            }
            
            if ( ! $attributes ) {
                $attributes = $attributes[$name];
            }
            
            //build normal select if country is not present in address block
            if ( $name == 'state_province_id' && ! $addressOptions['country'] ) {
                $select = 'stateProvince';
            }
            
            if ( ! $select ) {
                if ( $name == 'country_id' || $name == 'state_province_id' ) {
                    if ( $name == 'country_id' ) {
                        $stateCountryMap[$blockId]['country'] = "address_{$blockId}_{$name}";
                        $selectOptions = array('' => ts('- select -')) + 
                            CRM_Core_PseudoConstant::country( );
                    } else {
                        $stateCountryMap[$blockId]['state_province'] = "address_{$blockId}_{$name}";
                        if ( $countryDefault ) {
                            $selectOptions = array('' => ts('- select -')) +
                                CRM_Core_PseudoConstant::stateProvinceForCountry( $countryDefault );
                        } else {
                            $selectOptions = array( '' => ts( '- select a country -' ) );
                        }
                    }
                    $form->addElement( 'select',
                                       "address[$blockId][$name]",
                                       $title,
                                       $selectOptions );
                } else {
                    if ( $name == 'address_name' ) {
                        $name = "name";
                    }
                    
                    $form->addElement( 'text',
                                       "address[$blockId][$name]",
                                       $title,
                                       $attributes );
                }
            } else {
                $form->addElement( 'select',
                                   "address[$blockId][$name]",
                                   $title,
                                   array('' => ts('- select -')) + CRM_Core_PseudoConstant::$select( ) );
            }
        }
        
        require_once 'CRM/Core/BAO/Address.php';
        CRM_Core_BAO_Address::addStateCountryMap( $stateCountryMap );
    }
    
    /**
     * check for correct state / country mapping.
     *
     * @param array reference $fields - submitted form values.
     * @param array reference $errors - if any errors found add to this array. please.
     * @return true if no errors
     *         array of errors if any present.
     *
     * @access public
     * @static
     */
    static function formRule( &$fields, &$errors )
    {
        // check for state/county match if not report error to user.
        if ( is_array( $fields['address'] ) ) {
            foreach ( $fields['address'] as $instance => $addressValues ) {
                if ( CRM_Utils_System::isNull( $addressValues ) ) {
                    continue;
                }
                
                if ( $countryId = CRM_Utils_Array::value( 'country_id', $addressValues ) ) {
                    if ( !array_key_exists( $countryId, CRM_Core_PseudoConstant::country( ) ) ) { 
                        $countryId = null;
                        $errors["address[$instance][country_id]"] = ts('Enter a valid country name.');
                    }
                } 
                
                if ( $stateProvinceId = CRM_Utils_Array::value( 'state_province_id', $addressValues  ) ) {
                    // hack to skip  - type first letter(s) - for state_province
                    // CRM-2649
                    if ( $stateProvinceId != ts('- type first letter(s) -') ) {
                        if ( !array_key_exists( $stateProvinceId, CRM_Core_PseudoConstant::stateProvince( false, false ) ) ) {
                            $stateProvinceId = null;
                            $errors["address[$instance][state_province_id]"] = "Please select a valid State/Province name.";
                        }
                    }
                }
                
                //do check for mismatch countries 
                if ( $stateProvinceId && $countryId ) {
                    $stateProvinceDAO =& new CRM_Core_DAO_StateProvince();
                    $stateProvinceDAO->id = $stateProvinceId;
                    $stateProvinceDAO->find(true);
                    if ( $stateProvinceDAO->country_id != $countryId ) {
                        // countries mismatch hence display error
                        $stateProvinces = CRM_Core_PseudoConstant::stateProvince( );
                        $countries =& CRM_Core_PseudoConstant::country( );
                        $errors["address[$instance][state_province_id]"] = "State/Province " . $stateProvinces[$stateProvinceId] . " is not part of ". $countries[$countryId] . ". It belongs to " . $countries[$stateProvinceDAO->country_id] . "." ;
                    }
                }
                
                $countyId = CRM_Utils_Array::value( 'county_id', $addressValues ); 
                
                //state county validation
                if ( $stateProvinceId && $countyId ) {
                    $countyDAO =& new CRM_Core_DAO_County();
                    $countyDAO->id = $countyId;
                    $countyDAO->find(true);
                    
                    if ( $countyDAO->state_province_id != $stateProvinceId ) {
                        $counties =& CRM_Core_PseudoConstant::county();
                        $errors["address[$instance][county_id]"] = "County " . $counties[$countyId] . " is not part of ". $stateProvinces[$stateProvinceId] . ". It belongs to " . $stateProvinces[$countyDAO->state_province_id] . "." ;
                    }
                }
            }
        }
    }

    static function fixStateSelect( &$form,
                                    $countryElementName,
                                    $stateElementName,
                                    $countryDefaultValue ) {
        $countryID = null;
        if ( isset( $form->_elementIndex[$countryElementName] ) ) {
            //get the country id to load states -
            //first check for submitted value,
            //then check for user passed value.
            //finally check for element default val.
            $submittedVal = $form->getSubmitValue( $countryElementName );
            if ( $submittedVal ) {
                $countryID = $submittedVal;
            } else if ( $countryDefaultValue ) {
                $countryID = $countryDefaultValue;
            } else {
                $countryID = CRM_Utils_Array::value( 0, $form->getElementValue( $countryElementName ) );
            }
        }
        
        $stateTitle = ts( 'State/Province' );
        if ( isset( $form->_fields[$stateElementName]['title'] ) ) {
            $stateTitle = $form->_fields[$stateElementName]['title'];
        }
            
        if ( $countryID &&
             isset( $form->_elementIndex[$stateElementName] ) ) {
            $form->addElement( 'select',
                               $stateElementName,
                               $stateTitle,
                               array( '' => ts( '- select -' ) ) +
                               CRM_Core_PseudoConstant::stateProvinceForCountry( $countryID ) );
        }
    }

}


