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

require_once 'CRM/Contact/Page/View/UserDashBoard.php';

/**
 * This class is for building membership block on user dashboard
 */
class CRM_Member_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard 
{
    /**
     * Function to list memberships for the UF user
     * 
     * return null
     * @access public
     */
    function listMemberships( ) 
    {
        $idList = array('membership_type' => 'MembershipType',
                        'status'          => 'MembershipStatus',
                      );

        $membership = array( );
        require_once "CRM/Member/BAO/Membership.php";
        $dao =& new CRM_Member_DAO_Membership( );
        $dao->contact_id = $this->_contactId;
        $dao->is_test    = 0;
        $dao->find();
        
        while ($dao->fetch()) {
            $membership[$dao->id] = array( );
            CRM_Core_DAO::storeValues( $dao, $membership[$dao->id]);
            foreach ( $idList as $name => $file ) {
                if ( $membership[$dao->id][$name .'_id'] ) {
                    $membership[$dao->id][$name] = 
                        CRM_Core_DAO::getFieldValue( "CRM_Member_DAO_$file", 
                                                     $membership[$dao->id][$name .'_id'] );
                }
            }
            if ( $dao->status_id ) {
                $active =
                    CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
                                                $dao->status_id,
                                                'is_current_member');
                if ( $active ) {
                    $membership[$dao->id]['active'] = $active;
                }
            }

            $membership[$dao->id]['renewPageId'] = CRM_Member_BAO_Membership::getContributionPageId( $dao->id );
        }
        
        $activeMembers   = CRM_Member_BAO_Membership::activeMembers( $membership );
        $inActiveMembers = CRM_Member_BAO_Membership::activeMembers( $membership, 'inactive' );

        $this->assign('activeMembers', $activeMembers);
        $this->assign('inActiveMembers', $inActiveMembers);
    }

    /**
     * This function is the main function that is called when the page
     * loads, it decides the which action has to be taken for the page.
     * 
     * return null
     * @access public
     */
    function run( ) 
    {
        parent::preProcess( );
        $this->listMemberships( );
    }
}


