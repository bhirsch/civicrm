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
 * Definition of the Tag of the CRM API. 
 * More detailed documentation can be found 
 * {@link http://objectledge.org/confluence/display/CRM/CRM+v1.0+Public+APIs
 * here}
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * Files required for this package
 */
require_once 'api/utils.php'; 

/**
 * Create a file 
 *  
 * This API is used for creating a file
 * 
 * @param   array  $params  an associative array of name/value property values of civicrm_file
 * @return array of newly created file property values.
 * @access public
 */
function crm_create_file($params) 
{
    if ( ! is_array($params) ) {
        return _crm_error('Params is not an array.');
    }
    
    if ( ! isset($params['file_type_id']) ) {
        return _crm_error('Required parameter missing.');
    }
    
    if ( !isset($params['upload_date']) ) {
        $params['upload_date'] = date("Ymd");
    }
    
    require_once 'CRM/Core/DAO/File.php';
    
    $fileDAO = new CRM_Core_DAO_File();
    $properties = array('id', 'file_type_id', 'mime_type', 'uri', 'document', 'description', 'upload_date');
    
    foreach ($properties as $name) {
        if (array_key_exists($name, $params)) {
            $fileDAO->$name = $params[$name];
        }
    }
    
    $fileDAO->save();
    
    $file = array();
    _crm_object_to_array($fileDAO, $file);
    
    return $file;
}

/**
 * Get a file.
 * 
 * This api is used for finding an existing file.
 * Required parameters : id OR file_type_id of a file
 * 
 * @param  array $params  an associative array of name/value property values of civicrm_file
 *
 * @return  Array of all found file object property values.
 * @access public
 */
function crm_get_file($params) 
{
    if ( ! is_array($params) ) {
        return _crm_error('params is not an array.');
    }
    
    if ( ! isset($params['id']) && ! isset($params['file_type_id']) ) {
        return _crm_error('Required parameters missing.');
    }
    
    require_once 'CRM/Core/DAO/File.php';
    $fileDAO = new CRM_Core_DAO_File();
    
    $properties = array('id', 'file_type_id', 'mime_type', 'uri', 'document', 'description', 'upload_date');
    
    foreach ( $properties as $name) {
        if (array_key_exists($name, $params)) {
            $fileDAO->$name = $params[$name];
        }
    }
    
    if ( $fileDAO->find() ) {
        $file = array();
        while ( $fileDAO->fetch() ) {
            _crm_object_to_array( clone($fileDAO), $file );
            $files[$fileDAO->id] = $file;
        }
    } else {
        return _crm_error('Exact match not found');
    }
    return $files;
}

/**
 * Update an existing file
 *
 * This api is used for updating an existing file.
 * Required parrmeters : id of a file
 * 
 * @param  Array   $params  an associative array of name/value property values of civicrm_file
 * 
 * @return array of updated file object property values
 * @access public
 */
function &crm_update_file( &$params ) {
    if ( !is_array( $params ) ) {
        return _crm_error( 'Params is not an array' );
    }
    
    if ( !isset($params['id']) ) {
        return _crm_error( 'Required parameter missing' );
    }
    
    require_once 'CRM/Core/DAO/File.php';
    $fileDAO =& new CRM_Core_DAO_File( );
    $fileDAO->id = $params['id'];
    if ($fileDAO->find(true)) {
        $fileDAO->copyValues( $params );
        if ( !$params['upload_date'] && !$fileDAO->upload_date) {
            $fileDAO->upload_date = date("Ymd");
        }
        $fileDAO->save();
    }
    $file = array();
    _crm_object_to_array( clone($fileDAO), $file );
    return $file;
}

/**
 * Deletes an existing file
 * 
 * This API is used for deleting a file
 * Required parameters : id of a file
 * 
 * @param  Int  $fileId  Id of the file to be deleted
 * 
 * @return null if successfull, object of CRM_Core_Error otherwise
 * @access public
 */
function &crm_delete_file( $fileId ) {
    if ( empty($fileId) ) {
        return _crm_error( 'Required parameter missing' );
    }
    
    $check = false;
    
    require_once 'CRM/Core/DAO/EntityFile.php';
    $entityFileDAO =& new CRM_Core_DAO_EntityFile( );
    $entityFileDAO->file_id = $fileId;
    if ($entityFileDAO->find()) {
        $check = $entityFileDAO->delete();
    }
    
    require_once 'CRM/Core/DAO/File.php';
    $fileDAO =& new CRM_Core_DAO_File( );
    $fileDAO->id = $fileId;
    if ($fileDAO->find(true)) {
        $check = $fileDAO->delete();
    }
    
    return $check ? null : _crm_error('Error while deleting a file.');
}

/**
 * Assigns an entity to a file
 *
 * @param object  $file            id of a file
 * @param object  $entity          id of a entity
 * @param string  $entity_table    
 *
 * @return array of newly created entity-file object properties
 * @access public
 */
function crm_create_entity_file(&$fileID, &$entityID, $entity_table = 'civicrm_contact')
{
    require_once 'CRM/Core/DAO/EntityFile.php';
    
    if ( ! $fileID || ! $entityID ) {
        return _crm_error('Required parameters missing');
    }
    
    $params = array('entity_id'    => $entityID,
                    'file_id'      => $fileID,
                    'entity_table' => $entity_table
                    );
    
    $entityFileDAO =& new CRM_Core_DAO_EntityFile( );
    $entityFileDAO->copyValues( $params );
    $entityFileDAO->save( );
    
    $entityFile = array();
    _crm_object_to_array( $entityFileDAO, $entityFile );
    
    return $entityFile;
}

/**
 * Attach a file to a given entity
 *
 * @param string   $name          filename
 * @param object   $entityID      id of the supported entity.
 * @param string   $entity_table   
 *
 * @access public
 */
function crm_add_file_by_entity( $name, $entityID, $entityTable = 'civicrm_contact', $params ) {
    require_once 'CRM/Core/BAO/File.php';

    CRM_Core_BAO_File::filePostProcess( $name, null, $entityTable, $entityID, null, false, $params );
}

/**
 * Returns all files assigned to a single entity instance.
 *
 * @param object $entityID         id of the supported entity.
 * @param string $entity_table   
 *
 * @return array   nested array of entity-file property values.
 * @access public
 */
function crm_get_files_by_entity($entityID, $entityTable = 'civicrm_contact', $fileID = null)
{
    if ( ! $entityID ) {
        return _crm_error('Required parameters missing');
    }
    
    require_once 'CRM/Core/DAO/EntityFile.php';
    require_once 'CRM/Core/DAO/File.php';
    
    $entityFileDAO =& new CRM_Core_DAO_EntityFile();
    $entityFileDAO->entity_table = $entityTable;
    $entityFileDAO->entity_id = $entityID;
    if ( $fileID ) {
        $entityFileDAO->file_id   = $fileID;
    }
    if ( $entityFileDAO->find() ) {
        $entityFile = array();
        while ($entityFileDAO->fetch()) {
            _crm_object_to_array( $entityFileDAO, $entityFile );
            $files[$entityFileDAO->file_id] = $entityFile;
            
            if ( array_key_exists( 'file_id', $files[$entityFileDAO->file_id] ) ) {
                $fileDAO =& new CRM_Core_DAO_File();
                $fileDAO->id = $entityFile['file_id'];
                $fileDAO->find(true);
                _crm_object_to_array( $fileDAO, $files[$entityFileDAO->file_id] );
            }
            
            if ( CRM_Utils_Array::value( 'file_type_id', $files[$entityFileDAO->file_id] ) ) {
                $files[$entityFileDAO->file_id]['file_type'] =
                    CRM_Core_OptionGroup::getLabel( 'file_type',
                                                    $files[$entityFileDAO->file_id]['file_type_id'] );
            }
        }
    } else {
        return _crm_error('Exact match not found');
    }
    
    return $files;
}

/**
 * Deletes an existing entity file assignment.
 * Required parameters : 1.  id of an entity-file
 *                       2.  entity_id and entity_table of an entity-file
 *
 * @param   array $params   an associative array of name/value property values of civicrm_entity_file.
 * 
 * @return  null if successfull, object of CRM_Core_Error otherwise
 * @access public
 */
function crm_delete_entity_file(&$params)
{
    require_once 'CRM/Core/DAO/EntityFile.php';
    
    //if ( ! isset($params['id']) && ( !isset($params['entity_id']) || !isset($params['entity_file']) ) ) {
    if ( ! isset($params['id']) && ( !isset($params['entity_id']) || !isset($params['entity_table']) ) ) {
        return _crm_error('Required parameters missing');
    }
    
    $entityFileDAO =& new CRM_Core_DAO_EntityFile( );
    
    $properties = array( 'id', 'entity_id', 'entity_table', 'file_id' );
    foreach ( $properties as $name) {
        if ( array_key_exists($name, $params) ) {
            $entityFileDAO->$name = $params[$name];
        }
    }
    
    return $entityFileDAO->delete() ? null : _crm_error('Error while deleting');
}

