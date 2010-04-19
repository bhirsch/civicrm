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

require_once 'CRM/Core/Page.php';

class CRM_Case_Page_CaseDetails extends CRM_Core_Page
{

    /**
     * This function is the main function that is called when the page loads, 
     * it decides the which action has to be taken for the page.
     * 
     * return null
     * @access public
     */
    function run( ) 
    {
        $this->_action  = CRM_Utils_Request::retrieve('action', 'String', $this, false, 'browse');
        $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this ) ;

        $this->assign( 'action', $this->_action );
        $this->assign( 'context', $this->_context );
        
        $this->_contactId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this );

        $caseId = CRM_Utils_Request::retrieve( 'caseId', 'Positive', $this );
       
        require_once 'CRM/Case/Page/Tab.php';
        CRM_Case_Page_Tab::setContext( );
            
        require_once 'CRM/Case/BAO/Case.php';
        $params = array( 'date_range' => 0 );
       
        $caseDetails = CRM_Case_BAO_Case::getCaseActivity( $caseId, $params, $this->_contactId );

        $this->assign( 'rows'     , $caseDetails );
        $this->assign( 'caseId' , $caseId );
        $this->assign( 'contactId', $this->_contactId );
            
        // check is the user has view/edit signer permission
        $permission = 'view';
        if ( CRM_Core_Permission::check( 'edit cases' ) ) {
            $permission = 'edit';
        }
        $this->assign( 'permission', $permission );

        return parent::run();
    }

}


