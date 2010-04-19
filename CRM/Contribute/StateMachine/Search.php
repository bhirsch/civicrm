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

require_once 'CRM/Core/StateMachine.php';
require_once 'CRM/Core/Action.php';
require_once 'CRM/Contribute/Task.php';

class CRM_Contribute_StateMachine_Search extends CRM_Core_StateMachine 
{
    /**
     * The task that the wizard is currently processing
     *
     * @var string
     * @protected
     */
    protected $_task;

    /**
     * class constructor
     */
    function __construct( $controller, $action = CRM_Core_Action::NONE ) 
    {
        parent::__construct( $controller, $action );

        $this->_pages = array( );

        $this->_pages['CRM_Contribute_Form_Search'] = null;
        list( $task, $result ) = $this->taskName( $controller, 'Search' );
        $this->_task    = $task;

        if ( is_array( $task ) ) {
            foreach ( $task as $t ) {
                $this->_pages[$t] = null;
            }
        } else {
            $this->_pages[$task] = null;
        }

        $this->addSequentialPages( $this->_pages, $action );
    }

    /**
     * Determine the form name based on the action. This allows us
     * to avoid using  conditional state machine, much more efficient
     * and simpler
     *
     * @param CRM_Core_Controller $controller the controller object
     *
     * @return string the name of the form that will handle the task
     * @access protected
     */
    function taskName( $controller, $formName = 'Search' ) 
    {
        // total hack, check POST vars and then session to determine stuff
        // fix value if print button is pressed
        if ( CRM_Utils_Array::value( '_qf_' . $formName . '_next_print', $_POST ) ) {
            $value = CRM_Contribute_Task::PRINT_CONTRIBUTIONS;
        } else {
            $value = CRM_Utils_Array::value( 'task', $_POST );
        }
        if ( ! isset( $value ) ) {
            $value = $this->_controller->get( 'task' );
        }
        $this->_controller->set( 'task', $value );
        $result = false;
        switch ( $value ) {
        case CRM_Contribute_Task::DELETE_CONTRIBUTIONS:
            $task   = 'CRM_Contribute_Form_Task_Delete';
            break;

        case CRM_Contribute_Task::EXPORT_CONTRIBUTIONS:
            $task   = array('CRM_Export_Form_Select',
                            'CRM_Export_Form_Map');
            break;

        case CRM_Contribute_Task::EMAIL_CONTACTS:
            $task   = array('CRM_Contribute_Form_Task_Email',
                            'CRM_Contribute_Form_Task_Result');
            break;

        case CRM_Contribute_Task::UPDATE_STATUS:
            $task   = array('CRM_Contribute_Form_Task_Status',
                            'CRM_Contribute_Form_Task_Result');
            break;

        case CRM_Contribute_Task::BATCH_CONTRIBUTIONS:
            $task   = array( 'CRM_Contribute_Form_Task_PickProfile',
                             'CRM_Contribute_Form_Task_Batch',
                             'CRM_Contribute_Form_Task_Result');
            break;

        case CRM_Contribute_Task::PDF_RECEIPT:
            $task = 'CRM_Contribute_Form_Task_PDF';
            break;

        default: // the print task is the default and catch=all task
            $task = 'CRM_Contribute_Form_Task_Print';
            break;
        }

        return array( $task, $result );
    }

    /**
     * return the form name of the task
     *
     * @return string
     * @access public
     */
    function getTaskFormName( ) 
    {
        return CRM_Utils_String::getClassName( $this->_task );
    }

}


