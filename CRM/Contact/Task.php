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
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
require_once 'CRM/Contact/BAO/ContactType.php';

class CRM_Contact_Task {
    const
        GROUP_CONTACTS        =     1,
        REMOVE_CONTACTS       =     2,
        TAG_CONTACTS          =     3,
        REMOVE_TAGS           =     4,
        EXPORT_CONTACTS       =     5,
        EMAIL_CONTACTS        =     6,
        SMS_CONTACTS          =     7,
        DELETE_CONTACTS       =     8,
        HOUSEHOLD_CONTACTS    =     9,
        ORGANIZATION_CONTACTS =    10,
        RECORD_CONTACTS       =    11,
        MAP_CONTACTS          =    12,
        SAVE_SEARCH           =    13,
        SAVE_SEARCH_UPDATE    =    14,
        PRINT_CONTACTS        =    15,
        LABEL_CONTACTS        =    16,
        BATCH_UPDATE          =    17,
        ADD_EVENT             =    18,
        PRINT_FOR_CONTACTS    =    19;



    /**
     * the task array
     *
     * @var array
     * @static
     */
    static $_tasks = null;

    /**
     * the optional task array
     *
     * @var array
     * @static
     */
    static $_optionalTasks = null;

    static function initTasks( ) {
        if ( ! self::$_tasks ) {
            self::$_tasks = array(
                                  1     => array( 'title'  => ts( 'Add Contacts to Group'         ),
                                                  'class'  => 'CRM_Contact_Form_Task_AddToGroup',
                                                  'result' => true ),
                                  2     => array( 'title'  => ts( 'Remove Contacts from Group'    ),
                                                  'class'  => 'CRM_Contact_Form_Task_RemoveFromGroup',
                                                  'result' => true ),
                                  3     => array( 'title'  => ts( 'Tag Contacts (assign tags)'    ),
                                                  'class'  => 'CRM_Contact_Form_Task_AddToTag',
                                                  'result' => true ),
                                  4     => array( 'title'  => ts( 'Untag Contacts (remove tags)'  ),  
                                                  'class'  => 'CRM_Contact_Form_Task_RemoveFromTag',
                                                  'result' => true ),
                                  5     => array( 'title'  => ts( 'Export Contacts'               ),
                                                  'class'  => array( 'CRM_Export_Form_Select',
                                                                     'CRM_Export_Form_Map' ),
                                                  'result' => false ),
                                  6     => array( 'title'  => ts( 'Send Email to Contacts'        ),
                                                  'class'  => 'CRM_Contact_Form_Task_Email',
                                                  'result' => false ),
                                  7     => array( 'title'  => ts( 'Send SMS to Contacts'          ),
                                                  'class'  => 'CRM_Contact_Form_Task_SMS',
                                                  'result' => true ),
                                  8     => array( 'title'  => ts( 'Delete Contacts'               ),
                                                  'class'  => 'CRM_Contact_Form_Task_Delete',
                                                  'result' => false ),
                                  
                                  11    => array( 'title'  => ts( 'Record Activity for Contacts'  ),
                                                  'class'  => 'CRM_Activity_Form_Activity',
                                                  'result' => true ),
                                  13    => array( 'title'  => ts( 'New Smart Group'               ),
                                                  'class'  => 'CRM_Contact_Form_Task_SaveSearch',
                                                  'result' => true ),
                                  14    => array( 'title'  => ts( 'Update Smart Group'            ),
                                                  'class'  => 'CRM_Contact_Form_Task_SaveSearch_Update',
                                                  'result' => true ),
                                  15    => array( 'title'  => ts( 'Print Contacts'                ),
                                                  'class'  => 'CRM_Contact_Form_Task_Print',
                                                  'result' => false ),
                                  16    => array( 'title'  => ts( 'Mailing Labels'       ),
                                                  'class'  => 'CRM_Contact_Form_Task_Label',
                                                  'result' => true ),
                                  17    => array( 'title'  => ts( 'Batch Update via Profile'       ),
                                                  'class'  => array( 'CRM_Contact_Form_Task_PickProfile',
                                                                     'CRM_Contact_Form_Task_Batch' ),
                                                  'result' => true ),
                                  19    => array( 'title'  => ts( 'Print PDF Letter for Contacts' ),
                                                  'class'  => 'CRM_Contact_Form_Task_PDF',
                                                  'result' => true ),
                                  21    => array( 'title'  => ts( 'Merge Contacts' ),
                                                  'class'  => 'CRM_Contact_Form_Task_Merge',
                                                  'result' => true ),
                                  );
            if( CRM_Contact_BAO_ContactType::isActive( 'Household' ) ) {
                $label = CRM_Contact_BAO_ContactType::getLabel( 'Household' );
                self::$_tasks[9] = array( 'title'  => ts( 'Add Contacts to %1',
                                                          array( 1=> $label ) ) ,
                                          'class'  => 'CRM_Contact_Form_Task_AddToHousehold',
                                          'result' => true
                                          );
            }
            if( CRM_Contact_BAO_ContactType::isActive( 'Organization' ) ) {
                $label = CRM_Contact_BAO_ContactType::getLabel( 'Organization' );
                self::$_tasks[10] = array( 'title'  => ts( 'Add Contacts to %1',
                                                           array( 1=> $label ) ) ,
                                           'class'  => 'CRM_Contact_Form_Task_AddToOrganization',
                                           'result' => true
                                           );
            }
            //CRM-4418, check for delete 
            if ( !CRM_Core_Permission::check( 'delete contacts' ) ) {
                unset( self::$_tasks[8] );
            }

            //show map action only if map provider and key is set
            $config =& CRM_Core_Config::singleton( );

            if ( $config->mapProvider && $config->mapAPIKey ) {
                self::$_tasks[12] = array( 'title'  => ts( 'Map Contacts'),
                                           'class'  => 'CRM_Contact_Form_Task_Map',
                                           'result' => false );
            }

 
            if ( CRM_Core_Permission::access( 'CiviEvent' ) ) {
                self::$_tasks[18] = array( 'title'  => ts( 'Add Contacts to Event' ),
                                           'class'  => 'CRM_Event_Form_Participant',
                                           'result' => true );
            }
            
            if ( CRM_Core_Permission::access( 'CiviMail' ) ) { 
                self::$_tasks[20] = array( 'title'  => ts( 'Schedule/Send a Mass Mailing' ),
                                           'class'  => array( 'CRM_Mailing_Form_Group',
                                                              'CRM_Mailing_Form_Settings',
                                                              'CRM_Mailing_Form_Upload',
                                                              'CRM_Mailing_Form_Test',
                                                              'CRM_Mailing_Form_Schedule'
                                                              ),
                                           'result' => false
                                           );
            }
            
            self::$_tasks += CRM_Core_Component::taskList( );

            require_once 'CRM/Utils/Hook.php';
            CRM_Utils_Hook::searchTasks( 'contact', self::$_tasks );

            asort(self::$_tasks);
        }
    }

    /**
     * These tasks are the core set of tasks that the user can perform
     * on a contact / group of contacts
     *
     * @return array the set of tasks for a group of contacts
     * @static
     * @access public
     */
    static function &taskTitles()
    {
        self::initTasks( );

        $titles = array( );
        foreach ( self::$_tasks as $id => $value ) {
            $titles[$id] = $value['title'];
        }

        // hack unset update saved search and print contacts
        unset( $titles[14] );
        unset( $titles[15] );

        $config =& CRM_Core_Config::singleton( );

        require_once 'CRM/Utils/Mail.php';
        if ( !CRM_Utils_Mail::validOutBoundMail() ) { 
            unset( $titles[6 ] );
            unset( $titles[20] );
        }
        
        if ( ! in_array( 'CiviSMS', $config->enableComponents ) ) {
            unset( $titles[7] );
        }

        return $titles;
    }

    /**
     * show tasks selectively based on the permission level
     * of the user
     *
     * @param int $permission
     *
     * @return array set of tasks that are valid for the user
     * @access public
     */
    static function &permissionedTaskTitles( $permission ) {
        $tasks = array( );
        if ( $permission == CRM_Core_Permission::EDIT ) {
            $tasks = self::taskTitles( );
        } else {
            $tasks = array( 
                           5  => self::$_tasks[ 5]['title'],
                           6  => self::$_tasks[ 6] ['title'],
                           12 => self::$_tasks[12]['title'],
                           16 => self::$_tasks[16]['title'],
                           );
            if ( ! self::$_tasks[12]['title'] ) {
                //usset it, No edit permission and Map provider info
                //absent, drop down shows blank space
                unset( $tasks[12] );
            }
            //CRM-4418, lets keep delete for View and Edit so user can tweak ACL
            if ( CRM_Core_Permission::check( 'delete contacts' ) ) {
                $tasks[8] = self::$_tasks[8]['title']; 
            }
        }

        return $tasks;
    }

    /**
     * These tasks get added based on the context the user is in
     *
     * @return array the set of optional tasks for a group of contacts
     * @static
     * @access public
     */
    static function &optionalTaskTitle()
    {
        $tasks = array(
                       14 => self::$_tasks[14]['title'],
                       );
        return $tasks;
    }

    static function getTask( $value ) {
        self::initTasks( );
        
        if ( ! CRM_Utils_Array::value( $value, self::$_tasks ) ) {
            $value = 15; // make it the print task by default
        }
        return array( self::$_tasks[$value]['class' ],
                      self::$_tasks[$value]['result'] );
    }

}


