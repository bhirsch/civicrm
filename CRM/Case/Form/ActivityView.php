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

require_once "CRM/Core/Form.php";
require_once "CRM/Activity/BAO/Activity.php";
require_once "CRM/Activity/BAO/ActivityTarget.php";

/**
 * This class does pre processing for viewing an activity or their revisions
 * 
 */
class CRM_Case_Form_ActivityView extends CRM_Core_Form
{
    /**
     * Function to process the view
     *
     * @access public
     * @return None
     */
    public function preProcess() 
    {
        $contactID  = CRM_Utils_Request::retrieve( 'cid' , 'Integer', $this, true );
        $activityID = CRM_Utils_Request::retrieve( 'aid' , 'Integer', $this, true );
        $revs       = CRM_Utils_Request::retrieve( 'revs', 'Boolean', CRM_Core_DAO::$_nullObject );
        $caseID     = CRM_Utils_Request::retrieve( 'caseID', 'Boolean', CRM_Core_DAO::$_nullObject );
        $activitySubject =  CRM_Core_DAO::getFieldValue( 'CRM_Activity_DAO_Activity', 
                                                         $activityID,
                                                         'subject' );
        $this->assign('contactID', $contactID );
        $this->assign('caseID', $caseID );

        require_once 'CRM/Case/XMLProcessor/Report.php';
        $xmlProcessor = new CRM_Case_XMLProcessor_Report( );
        $report       = $xmlProcessor->getActivityInfo( $contactID, $activityID, true );
        
        require_once 'CRM/Core/BAO/File.php';
        $attachmentUrl = CRM_Core_BAO_File::attachmentInfo( 'civicrm_activity', $activityID );
        if ( $attachmentUrl ) {
            $report['fields'][] = array ( 'label' => 'Attachment(s)',
                                          'value' => $attachmentUrl,
                                          'type'  => 'Link'
                                          );
        }  
        
        $this->assign('report', $report );

        $latestRevisionID = CRM_Activity_BAO_Activity::getLatestActivityId( $activityID );

        if ( $revs ) {
            $this->assign('revs',$revs);
            
            $priorActivities = CRM_Activity_BAO_Activity::getPriorAcitivities( $activityID );

            $this->assign( 'result' , $priorActivities );
            $this->assign( 'subject', $activitySubject );
            
            $this->assign( 'latestRevisionID', $latestRevisionID );
        } else {
            $countPriorActivities = CRM_Activity_BAO_Activity::getPriorCount( $activityID );

            if ( $countPriorActivities >= 1 ) {
                $this->assign( 'activityID', $activityID ); 
            }

            if ( $latestRevisionID != $activityID ) {
                $this->assign( 'latestRevisionID', $latestRevisionID );
            }
        }

        $parentID =  CRM_Activity_BAO_Activity::getParentActivity( $activityID );
        if ( $parentID ) { 
            $this->assign( 'parentID', $parentID );
        }

        //viewing activity should get diplayed in recent list.CRM-4670 
        $activityTypeID = CRM_Core_DAO::getFieldValue( 'CRM_Activity_DAO_Activity', $activityID, 'activity_type_id' );
        
        $activityTargetContacts = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId( $activityID ); 
        if (!empty( $activityTargetContacts ) ) {
            $recentContactId = $activityTargetContacts[1];
        } else {
            $recentContactId = $contactID; 
        }

        if ( !isset( $caseID ) ) {
            $caseID = CRM_Core_DAO::getFieldValue( 'CRM_Case_DAO_CaseActivity', $activityID, 'case_id', 'activity_id' );
        }
        
        require_once 'CRM/Utils/Recent.php';
        $url = CRM_Utils_System::url( 'civicrm/case/activity/view', 
                                      "reset=1&aid={$activityID}&cid={$recentContactId}&caseID={$caseID}&context=home" );

        require_once 'CRM/Contact/BAO/Contact.php';
        $recentContactDisplay = CRM_Contact_BAO_Contact::displayName( $recentContactId );
        // add the recently created Activity
        $activityTypes = CRM_Core_Pseudoconstant::activityType( true, true );
        
        $title = "";
        if ( isset($activitySubject) ) {
            $title = $activitySubject . ' - ';
        }
        
        $title =  $title . $recentContactDisplay .' (' . $activityTypes[$activityTypeID] . ')';
        
        CRM_Utils_Recent::add( $title,
                               $url,
                               $activityID,
                               'Activity',
                               $recentContactId,
                               $recentContactDisplay
                               );
        
    }
}
