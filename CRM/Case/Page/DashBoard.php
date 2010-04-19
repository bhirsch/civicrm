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

/**
 * This page is for Case Dashboard
 */
class CRM_Case_Page_DashBoard extends CRM_Core_Page 
{
    /** 
     * Heart of the viewing process. The runner gets all the meta data for 
     * the contact and calls the appropriate type of page to view. 
     * 
     * @return void 
     * @access public 
     * 
     */ 
    function preProcess( ) 
    {
        // Make sure case types have been configured for the component
        require_once 'CRM/Core/OptionGroup.php';        
        $caseType = CRM_Core_OptionGroup::values('case_type');
        if ( empty( $caseType ) ){
            $this->assign('notConfigured', 1);
            return;
        }

        $session = & CRM_Core_Session::singleton();
        $allCases = CRM_Utils_Request::retrieve( 'all', 'Positive', $session );
        
        CRM_Utils_System::setTitle( ts('CiviCase Dashboard') );
        
        $userID  = $session->get('userID');
               
        if ( ! $allCases ) {
            $this->assign('myCases', true );
        } else {
            $this->assign('myCases', false );
        }
        
        $this->assign('newClient', false );
        if ( CRM_Core_Permission::check('add contacts')) {
            $this->assign('newClient', true );
        }
        require_once 'CRM/Case/BAO/Case.php';
        $summary  = CRM_Case_BAO_Case::getCasesSummary( $allCases, $userID );
        $upcoming = CRM_Case_BAO_Case::getCases( $allCases, $userID, 'upcoming');
        $recent   = CRM_Case_BAO_Case::getCases( $allCases, $userID, 'recent');
        
        $this->assign('casesSummary',  $summary);
        if( !empty( $upcoming ) ) {
            $this->assign('upcomingCases', $upcoming);
        }
        if( !empty( $recent ) ) {
            $this->assign('recentCases',   $recent);
        }
    }
    
    /** 
     * This function is the main function that is called when the page loads, 
     * it decides the which action has to be taken for the page. 
     *                                                          
     * return null        
     * @access public 
     */                                                          
    function run( ) 
    {
        $this->preProcess( );
        
        return parent::run( );
    }
}