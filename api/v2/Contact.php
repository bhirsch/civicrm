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
 * new version of civicrm apis. See blog post at
 * http://civicrm.org/node/131
 * @todo Write sth
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Contact
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: Contact.php 26284 2010-02-17 17:58:00Z shot $
 *
 */

/**
 * Include common API util functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Contact/BAO/Contact.php';
/**
 * @todo Write sth
 *
 * @param  array   $params           (reference ) input parameters
 *
 * Allowed @params array keys are:
 * {@schema Contact/Contact.xml}
 * {@schema Core/Address.xml}}
 *
 * @return array (reference )        contact_id of created or updated contact
 *
 * @static void
 * @access public
 */
function civicrm_contact_create( &$params ) {
    // call update and tell it to create a new contact
    $create_new = true;
    return civicrm_contact_update( $params, $create_new );
}

/**
 * @todo Write sth
 * @todo Serious FIXMES in the code! File issues.
 */
function civicrm_contact_update( &$params, $create_new = false ) {
    _civicrm_initialize( );
    require_once 'CRM/Utils/Array.php';
    $contactID = CRM_Utils_Array::value( 'contact_id', $params );

    $dupeCheck = CRM_Utils_Array::value( 'dupe_check', $params, false );
    $values    = civicrm_contact_check_params( $params, $dupeCheck );
    if ( $values ) {
        return $values;
    }
    
    if ( $create_new ) {
        // Make sure nothing is screwed up before we create a new contact
        if ( !empty( $contactID ) ) {
            return civicrm_create_error( 'Cannot create new contact when contact_id is present' );
        }
        if ( empty( $params[ 'contact_type' ] ) ) {
            return civicrm_create_error( 'Contact Type not specified' );
        }
        
        // If we get here, we're ready to create a new contact
        if ( ($email = CRM_Utils_Array::value( 'email', $params ) ) && !is_array( $params['email'] ) ) {
            require_once 'CRM/Core/BAO/LocationType.php';
            $defLocType = CRM_Core_BAO_LocationType::getDefault( );
            $params['email'] = array( 1 => array( 'email'            => $email,
                                                  'is_primary'       => 1, 
                                                  'location_type_id' => ($defLocType->id)?$defLocType->id:1
                                                  ),
                                      );
        }
    }

    // FIXME: Some legacy support cruft, should get rid of this in 3.1
    $change = array( 'individual_prefix' => 'prefix',
                     'prefix'            => 'prefix_id',
                     'individual_suffix' => 'suffix',
                     'suffix'            => 'suffix_id',
                     'gender'            => 'gender_id' );

    foreach ( $change as $field => $changeAs ) {
        if ( array_key_exists( $field, $params ) ) {
            $params[$changeAs] = $params[$field];
            unset( $params[$field] );
        }
    }
    // End legacy support cruft

    if ( isset( $params['suffix_id'] ) &&
         ! ( is_numeric( $params['suffix_id'] ) ) ) {
        $params['suffix_id'] = array_search( $params['suffix_id'] , CRM_Core_PseudoConstant::individualSuffix() );
    }

    if ( isset( $params['prefix_id'] ) &&
         ! ( is_numeric( $params['prefix_id'] ) ) ) {
        $params['prefix_id'] = array_search( $params['prefix_id'] , CRM_Core_PseudoConstant::individualPrefix() );
    }

         if ( isset( $params['gender_id'] )
              && ! ( is_numeric( $params['gender_id'] ) ) ) {
        $params['gender_id'] = array_search( $params['gender_id'] , CRM_Core_PseudoConstant::gender() );
    }

    $values   = array( );
    $entityId = CRM_Utils_Array::value( 'contact_id', $params, null );

    if ( ! CRM_Utils_Array::value('contact_type', $params) &&
         $entityId ) {
        $params['contact_type'] = CRM_Contact_BAO_Contact::getContactType( $entityId );
    }
    
    if ( ! ( $csType = CRM_Utils_Array::value('contact_sub_type', $params) ) &&
         $entityId ) {
        require_once 'CRM/Contact/BAO/Contact.php';
        $csType = CRM_Contact_BAO_Contact::getContactSubType( $entityId );
    }
    
    $customValue = civicrm_contact_check_custom_params( $params, $csType ); 

    if ( $customValue ) {
        return $customValue;
    }
    _civicrm_custom_format_params( $params, $values, $params['contact_type'], $entityId );

    $params = array_merge( $params, $values );

    $contact =& _civicrm_contact_update( $params, $contactID );

    if ( is_a( $contact, 'CRM_Core_Error' ) ) {
        return civicrm_create_error( $contact->_errors[0]['message'] );
    } else {
        $values = array( );
        $values['contact_id'] = $contact->id;
        $values['is_error']   = 0;
    }

    return $values;
}

/**
 * Add or update a contact. If a dupe is found, check for
 * ignoreDupe flag to ignore or return error
 *
 * @deprecated deprecated since version 2.2.3; use civicrm_contact_create or civicrm_contact_update instead
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        contact_id of created or updated contact
 * @static void
 * @access public
 */
function &civicrm_contact_add( &$params ) {
    _civicrm_initialize( );

    $contactID = CRM_Utils_Array::value( 'contact_id', $params );
    
    if ( !empty($contactID) ) {
        $result = civicrm_contact_update($params);
    } else {
        $result = civicrm_contact_create($params);
    }
    return $result;
}

/**
 * Retrieve one or more contacts, given a set of search params
 *
 * @param  mixed[]  (reference ) input parameters
 * @param  bool  follow the pre-2.2.3 behavior of this function
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function civicrm_contact_get( &$params, $deprecated_behavior = false ) {
    _civicrm_initialize( );
    
    if ($deprecated_behavior) {
        return _civicrm_contact_get_deprecated($params);
    }

    $inputParams      = array( );
    $returnProperties = array( );
    $otherVars = array( 'sort', 'offset', 'rowCount', 'smartGroupCache' );

    $sort            = null;
    $offset          = 0;
    $rowCount        = 25;
    $smartGroupCache = false;
    foreach ( $params as $n => $v ) {
        if ( substr( $n, 0, 6 ) == 'return' ) {
            $returnProperties[ substr( $n, 7 ) ] = $v;
        } elseif ( in_array( $n, $otherVars ) ) {
            $$n = $v;
        } else {
            $inputParams[$n] = $v;
        }
    }

    if ( empty( $returnProperties ) ) {
        $returnProperties = null;
    }

    require_once 'CRM/Contact/BAO/Query.php';
    $newParams =& CRM_Contact_BAO_Query::convertFormValues( $inputParams );
    list( $contacts, $options ) = CRM_Contact_BAO_Query::apiQuery( $newParams,
                                                                   $returnProperties,
                                                                   null,
                                                                   $sort,
                                                                   $offset,
                                                                   $rowCount,
                                                                   $smartGroupCache );
    return $contacts;
}

/**
 * Retrieve a specific contact, given a set of search params
 *
 * @deprecated deprecated since version 2.2.3
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function _civicrm_contact_get_deprecated( &$params ) {
    $values = array( );
    if ( empty( $params ) ) {
        return civicrm_create_error( ts( 'No input parameters present' ) );
    }

    if ( ! is_array( $params ) ) {
        return civicrm_create_error( ts( 'Input parameters is not an array' ) );
    }

    $contacts =& civicrm_contact_search( $params );
    if ( civicrm_error( $contacts ) ) {
        return $contacts;
    }

    if ( count( $contacts ) != 1 &&
         ! CRM_Utils_Array::value( 'returnFirst', $params ) ) {
        return civicrm_create_error( ts( '%1 contacts matching input params', array( 1 => count( $contacts ) ) ) );
    } else if ( count( $contacts ) == 0 ) {
        return civicrm_create_error( ts( 'No contacts match the input params' ) );
    }

    $contacts = array_values( $contacts );
    return $contacts[0];
}

/**
 * Delete a contact with given contact id
 *
 * @param  array   	  $params (reference ) input parameters, contact_id element required
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 */
function civicrm_contact_delete( &$params ) {
    require_once 'CRM/Contact/BAO/Contact.php';

    $contactID = CRM_Utils_Array::value( 'contact_id', $params );
    if ( ! $contactID ) {
        return civicrm_create_error( ts( 'Could not find contact_id in input parameters' ) );
    }

    if ( CRM_Contact_BAO_Contact::deleteContact( $contactID ) ) {
        return civicrm_create_success( );
    } else {
        return civicrm_create_error( ts( 'Could not delete contact' ) );
    }
}

/**
 * Retrieve a set of contacts, given a set of input params
 *
 * @deprecated deprecated since version 2.2.3
 *
 * @param  array   $params           (reference ) input parameters
 * @param array    $returnProperties Which properties should be included in the
 *                                   returned Contact object. If NULL, the default
 *                                   set of properties will be included.
 *
 * @return array (reference )        array of contacts, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_contact_search( &$params ) {
    _civicrm_initialize( );

    $inputParams      = array( );
    $returnProperties = array( );
    $otherVars = array( 'sort', 'offset', 'rowCount', 'smartGroupCache' );
    
    $sort            = null;
    $offset          = 0;
    $rowCount        = 25;
    $smartGroupCache = false;
    foreach ( $params as $n => $v ) {
        if ( substr( $n, 0, 6 ) == 'return' ) {
            $returnProperties[ substr( $n, 7 ) ] = $v;
        } elseif ( in_array( $n, $otherVars ) ) {
            $$n = $v;
        } else {
            $inputParams[$n] = $v;
        }
    }

    if ( empty( $returnProperties ) ) {
        $returnProperties = null;
    }

    require_once 'CRM/Contact/BAO/Query.php';
    $newParams =& CRM_Contact_BAO_Query::convertFormValues( $inputParams );
    list( $contacts, $options ) = CRM_Contact_BAO_Query::apiQuery( $newParams,
                                                                   $returnProperties,
                                                                   null,
                                                                   $sort,
                                                                   $offset,
                                                                   $rowCount,
                                                                   $smartGroupCache );
    return $contacts;
}

/**
 * Ensure that we have the right input parameters
 *
 * @todo We also need to make sure we run all the form rules on the params list
 *       to ensure that the params are valid
 *
 * @param array   $params          Associative array of property name/value
 *                                 pairs to insert in new contact.
 * @param boolean $dupeCheck       Should we check for duplicate contacts
 * @param boolean $dupeErrorArray  Should we return values of error
 *                                 object in array foramt
 * @param boolean $requiredCHeck   Should we check if required params
 *                                 are present in params array
 *
 * @return null on success, error message otherwise
 * @access public
 */
function civicrm_contact_check_params( &$params, $dupeCheck = true, $dupeErrorArray = false, $requiredCheck = true ) {
    if ( $requiredCheck ) {
        $required = array(
                          'Individual'   => array(
                                                  array( 'first_name', 'last_name' ),
                                                  'email',
                                                  ),
                          'Household'    => array(
                                                  'household_name',
                                                  ),
                          'Organization' => array(
                                                  'organization_name',
                                                  ),
                          );
        
        // cannot create a contact with empty params
        if ( empty( $params ) ) {
            return civicrm_create_error( 'Input Parameters empty' );
        }
        
        if ( ! array_key_exists( 'contact_type', $params ) ) {
            return civicrm_create_error( 'Contact Type not specified' );
        }
        
        // contact_type has a limited number of valid values
        $fields = CRM_Utils_Array::value( $params['contact_type'], $required );
        if ( $fields == null ) {
            return civicrm_create_error( "Invalid Contact Type: {$params['contact_type']}" );
        }
        
        if ( $csType = CRM_Utils_Array::value('contact_sub_type', $params) ) {
            if ( !(CRM_Contact_BAO_ContactType::isExtendsContactType($csType, $params['contact_type'])) ) {
                return civicrm_create_error( "Invalid or Mismatched Contact SubType: {$csType}" );
            }
        }

        if ( !CRM_Utils_Array::value( 'contact_id', $params ) ) { 
            $valid = false;
            $error = '';
            foreach ( $fields as $field ) {
                if ( is_array( $field ) ) {
                    $valid = true;
                    foreach ( $field as $element ) {
                        if ( ! CRM_Utils_Array::value( $element, $params ) ) {
                            $valid = false;
                            $error .= $element; 
                            break;
                        }
                    }
                } else {
                    if ( CRM_Utils_Array::value( $field, $params ) ) {
                        $valid = true;
                    }
                }
                if ( $valid ) {
                    break;
                }
            }
            
            if ( ! $valid ) {
                return civicrm_create_error( "Required fields not found for {$params['contact_type']} : $error" );
            }
        }
    }
    
    if ( $dupeCheck ) {
        // check for record already existing
        require_once 'CRM/Dedupe/Finder.php';
        $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);
        $ids = implode(',', CRM_Dedupe_Finder::dupesByParams($dedupeParams, $params['contact_type']));
        
        if ( $ids != null ) {
            if ( $dupeErrorArray ) {
                $error = CRM_Core_Error::createError( "Found matching contacts: $ids",
                                                      CRM_Core_Error::DUPLICATE_CONTACT, 
                                                      'Fatal', $ids );
                return civicrm_create_error( $error->pop( ) );
            }
            
            return civicrm_create_error( "Found matching contacts: $ids", $ids );
        }
    }
    
    return null;
}

/**
 * @todo What does this do? If it's still useful, figure out where it should live and what it should be named.
 *
 * @deprecated deprecated since version 2.2.3
 */
function civicrm_replace_contact_formatted($contactId, &$params, &$fields) {
    //$contact = civcrm_get_contact(array('contact_id' => $contactId));
    
    $delContact = array( 'contact_id' => $contactId );
    
    civicrm_contact_delete($delContact);
    
    $cid = CRM_Contact_BAO_Contact::createProfileContact( $params, $fields, 
                                                          null, null, null, 
                                                          $params['contact_type'] );
    return civicrm_create_success( $cid );
}


/** 
 * Takes an associative array and creates a contact object and all the associated 
 * derived objects (i.e. individual, location, email, phone etc) 
 * 
 * @param array $params (reference ) an assoc array of name/value pairs 
 * @param  int     $contactID        if present the contact with that ID is updated
 * 
 * @return object CRM_Contact_BAO_Contact object  
 * @access public 
 * @static 
 */ 
function _civicrm_contact_update( &$params, $contactID = null )
{
    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction( );

    if ( $contactID ) {
        $params['contact_id'] = $contactID;
    }
    require_once 'CRM/Contact/BAO/Contact.php';
    
    $contact = CRM_Contact_BAO_Contact::create( $params );

    $transaction->commit( );

    return $contact;
}

/**
 * @todo Move this to ContactFormat.php 
 */
function civicrm_contact_format_create( &$params ) {
    _civicrm_initialize( );

    CRM_Core_DAO::freeResult( );

    // return error if we have no params
    if ( empty( $params ) ) {
        return civicrm_create_error( 'Input Parameters empty' );
    }

    $error = _civicrm_required_formatted_contact($params);
    if (civicrm_error( $error, 'CRM_Core_Error')) {
        return $error;
    }
    
    $error = _civicrm_validate_formatted_contact($params);
    if (civicrm_error( $error, 'CRM_Core_Error')) {
        return $error;
    }

    //get the prefix id etc if exists
    require_once 'CRM/Contact/BAO/Contact.php';
    CRM_Contact_BAO_Contact::resolveDefaults($params, true);

    require_once 'CRM/Import/Parser.php';
    if ( $params['onDuplicate'] != CRM_Import_Parser::DUPLICATE_NOCHECK) {
        CRM_Core_Error::reset( );
        $error = _civicrm_duplicate_formatted_contact($params);
        if (civicrm_error( $error, 'CRM_Core_Error')) {
            return $error;
        }
    }
    
    $contact = CRM_Contact_BAO_Contact::create( $params, 
                                                CRM_Utils_Array::value( 'fixAddress',  $params ) );
    
    _civicrm_object_to_array($contact, $contactArray);
    return $contactArray;
}

/** 
 * Returns the number of Contact objects which match the search criteria specified in $params.
 *
 * @deprecated deprecated since version 2.2.3; civicrm_contact_get now returns a record_count value
 *
 * @param array  $params
 *
 * @return int
 * @access public
 */
function civicrm_contact_search_count( &$params ) {
    // convert the params to new format
    require_once 'CRM/Contact/Form/Search.php';
    $newP =& CRM_Contact_BAO_Query::convertFormValues( $params );
    $query =& new CRM_Contact_BAO_Query( $newP );
    return $query->searchQuery( 0, 0, null, true );
}

/**
 * Ensure that we have the right input parameters for custom data
 *
 * @param array   $params          Associative array of property name/value
 *                                 pairs to insert in new contact.
 * @param string  $csType          contact subtype if exists/passed.
 *
 * @return null on success, error message otherwise
 * @access public
 */
function civicrm_contact_check_custom_params( $params, $csType = null ) {
    
    empty($csType) ? $onlyParent = true : $onlyParent = false;
    
    require_once 'CRM/Core/BAO/CustomField.php';
    $customFields = CRM_Core_BAO_CustomField::getFields( $params['contact_type'], false, false, $csType, null, $onlyParent );
    
    foreach ($params as $key => $value) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
            /* check if it's a valid custom field id */
            if ( !array_key_exists($customFieldID, $customFields)) {

                $errorMsg = ts("Invalid Custom Field Contact Type: {$params['contact_type']}");
                if ( $csType ) {
                    $errorMsg .= ts(" or Mismatched SubType: {$csType}.");  
                }
                return civicrm_create_error( $errorMsg );  
            }
        }
    }
}

