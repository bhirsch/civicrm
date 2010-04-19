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

require_once 'CRM/Activity/DAO/ActivityAssignment.php';

/**
 * This class is for activity assignment functions
 *
 */
class CRM_Activity_BAO_ActivityAssignment extends CRM_Activity_DAO_ActivityAssignment
{

    /**
     * class constructor
     */
    function __construct( ) 
    {
        parent::__construct( );
    }

    /**
     * Add activity assignment.
     *
     * @param array  $params       (reference ) an assoc array of name/value pairs
     * @param array  $ids          (reference ) the array that holds all the db ids
     *
     * @return object activity type of object that is added
     * @access public
     * 
     */
    public function create( &$params ) 
    {
        require_once 'CRM/Activity/BAO/ActivityAssignment.php';
        $assignment =& new CRM_Activity_BAO_ActivityAssignment();

        $assignment->copyValues( $params );
        return $assignment->save();
    }


    /**
     * Retrieve assignee_id by activity_id
     *
     * @param int    $id  ID of the activity
     * 
     * @return void
     * 
     * @access public
     * 
     */
    static function retrieveAssigneeIdsByActivityId( $activity_id ) 
    {
        $assigneeArray = array();
        require_once 'CRM/Utils/Rule.php';
        if ( ! CRM_Utils_Rule::positiveInteger( $activity_id ) ) {
            return $assigneeArray;
        }

        $assignment =& new CRM_Activity_BAO_ActivityAssignment( );
        $assignment->activity_id = $activity_id;
        $assignment->find();
        $count = 1;
        while ( $assignment->fetch( ) ) {
            $assigneeArray[$count] = $assignment->assignee_contact_id;
            $count++;
        }

        return $assigneeArray;
    }

    /**
     * Retrieve assignee names by activity_id
     *
     * @param int      $id             ID of the activity
     * @param boolean  $isDisplayName  if set returns display names of assignees
     * @param boolean  $skipDetails    if false returns all details of assignee contact.
     *
     * @return array
     * 
     * @access public
     * 
     */
    static function getAssigneeNames( $activity_id, $isDisplayName = false, $skipDetails = true ) 
    {
        $queryParam  = array();
        $whereClause = "";
        if ( !$skipDetails ) {
            $whereClause = "  AND ce.is_primary= 1";
        }
        
        $query = "SELECT contact_a.id, contact_a.sort_name, contact_a.display_name, ce.email   
                  FROM civicrm_contact contact_a 
                  LEFT JOIN civicrm_activity_assignment 
                         ON civicrm_activity_assignment.assignee_contact_id = contact_a.id
                  LEFT JOIN civicrm_email ce 
                         ON ce.contact_id = contact_a.id
                  WHERE civicrm_activity_assignment.activity_id = {$activity_id} 
                        {$whereClause}";

        $dao = CRM_Core_DAO::executeQuery($query,$queryParam);
        $assigneeNames = array();
        while ( $dao->fetch() ) {
            if ( !$isDisplayName ) {
                $assigneeNames[$dao->id] = $dao->sort_name;
            } else {
                if ( $skipDetails ) {
                    $assigneeNames[$dao->id] = $dao->display_name;   
                } else { 
                    $assigneeNames[$dao->id]['contact_id']   = $dao->id;
                    $assigneeNames[$dao->id]['display_name'] = $dao->display_name;
                    $assigneeNames[$dao->id]['sort_name']    = $dao->sort_name;
                    $assigneeNames[$dao->id]['email']        = $dao->email;
                    $assigneeNames[$dao->id]['role']         = ts('Activity Assignee');
                }
            }
        }
        return $assigneeNames;
    }
}

