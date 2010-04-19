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
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'CRM/Core/BAO/CustomOption.php';

require_once 'CRM/Utils/Recent.php';

require_once 'CRM/Contact/BAO/Contact.php';
require_once 'CRM/Core/BAO/UFMatch.php';
require_once 'CRM/Core/Menu.php';

/**
 * Main page for viewing contact.
 *
 */
class CRM_Contact_Page_View extends CRM_Core_Page {
    /**
     * the id of the object being viewed (note/relationship etc)
     *
     * @int
     * @access protected
     */
    protected $_id;

    /**
     * the contact id of the contact being viewed
     *
     * @int
     * @access protected
     */
    protected $_contactId;

    /**
     * The action that we are performing
     *
     * @string
     * @access protected
     */
    protected $_action;

    /**
     * The permission we have on this contact
     *
     * @string
     * @access protected
     */
    protected $_permission;

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
        // process url params
        $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        $this->assign( 'id', $this->_id );
        
        // retrieve the group contact id, so that we can get contact id
        $gcid = CRM_Utils_Request::retrieve( 'gcid', 'Positive', $this );
        
        if ( !$gcid ) {
            $this->_contactId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true );
        } else {
            $this->_contactId = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_GroupContact', $gcid, 'contact_id' );
        }

        if ( ! $this->_contactId ) {
            CRM_Core_Error::statusBounce( ts( 'We could not find a contact id.' ) );
        }
        $this->assign( 'contactId', $this->_contactId );
        
        $path = CRM_Utils_System::url( 'civicrm/contact/view', 'reset=1&cid=' . $this->_contactId );
        CRM_Utils_System::appendBreadCrumb( array( array( 'title' => ts('View Contact'),
                                                          'url'   => $path ) ) );
        CRM_Utils_System::appendBreadCrumb( array( array( 'title' => ts('Search Results'),
                                                          'url'   => self::getSearchURL( ) ) ) );

        // also store in session for future use
        $session =& CRM_Core_Session::singleton( );
        $session->set( 'view.id', $this->_contactId );

        $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, false, 'browse');
        $this->assign( 'action', $this->_action);

        // check logged in url permission
        self::checkUserPermission( $this );
        
        list( $displayName, $contactImage, $contactType, $contactSubtype, $contactImageUrl ) = $this->getContactDetails();
        $this->assign( 'displayName', $displayName );
        
        $this->set( 'contactType',    $contactType );
        $this->set( 'contactSubtype', $contactSubtype );

        // see if other modules want to add a link activtity bar
        require_once 'CRM/Utils/Hook.php';
        $hookLinks = CRM_Utils_Hook::links( 'view.contact.activity', 'Contact', $this->_contactId );
        if ( is_array( $hookLinks ) ) {
            $this->assign( 'hookLinks', $hookLinks );
        }

        // set page title
        CRM_Utils_System::setTitle( $displayName, $contactImage . ' ' . $displayName );
        
        // add to recently viewed block
        CRM_Utils_Recent::add( $displayName,
                               CRM_Utils_System::url( 'civicrm/contact/view', 'reset=1&cid=' . $this->_contactId ),
                               $this->_contactId,
                               $contactType,
                               $this->_contactId,
                               $displayName,
                               $contactImageUrl,
                               $contactSubtype );
                
        $config = CRM_Core_Config::singleton( );
        require_once 'CRM/Core/BAO/UFMatch.php';
        if ( $uid = CRM_Core_BAO_UFMatch::getUFId( $this->_contactId ) ) {
            if ($config->userFramework == 'Drupal') {
                $url = CRM_Utils_System::url( 'user/' . $uid );
            } else if ( $config->userFramework == 'Joomla' ) {
                $url = $config->userFrameworkBaseURL . 
                    'index2.php?option=com_users&view=user&task=edit&cid[]=' . $uid;
            } else {
                $url = null;
            }
            $this->assign( 'url', $url );
        }
    
        if ( CRM_Core_Permission::check( 'access Contact Dashboard' ) ) {
            $dashboardURL = CRM_Utils_System::url( 'civicrm/user',
                                                   "reset=1&id={$this->_contactId}" );
            $this->assign( 'dashboardURL', $dashboardURL );
        }
        
        if ( defined( 'CIVICRM_MULTISITE' ) && 
             CIVICRM_MULTISITE              && 
             $contactType == 'Organization' &&
             CRM_Core_Permission::check( 'administer Multiple Organizations' ) ) {
            require_once 'CRM/Contact/BAO/GroupOrganization.php';
            //check is any relationship between the organization and groups
            $groupOrg = CRM_Contact_BAO_GroupOrganization::hasGroupAssociated( $this->_contactId );
            if ( $groupOrg ) {
                $groupOrganizationUrl = CRM_Utils_System::url( 'civicrm/group',
                                                               "reset=1&oid={$this->_contactId}" ); 
                $this->assign( 'groupOrganizationUrl', $groupOrganizationUrl );
            }
        }
    }


    /**
     * Get meta details of the contact.
     *
     * @return void
     * @access public
     */
    function getContactDetails( ) {
        return list( $displayName, 
                     $contactImage, 
                     $contactType, 
                     $contactSubtype, 
                     $contactImageUrl ) = CRM_Contact_BAO_Contact::getDisplayAndImage( $this->_contactId,
                                                                                       true,
                                                                                       true );
    }

    function getSearchURL( ) {
        $session = CRM_Core_Session::singleton();
        $isAdvanced = $session->get('isAdvanced');
        
        if ( $isAdvanced == '1' ) {
            return CRM_Utils_System::url( 'civicrm/contact/search/advanced', 'force=1' );
        } else if ( $isAdvanced == '2' ) {
            return CRM_Utils_System::url( 'civicrm/contact/search/builder', 'force=1' );
        } else if ( $isAdvanced == '3' ) {
            return CRM_Utils_System::url( 'civicrm/contact/search/custom', 'force=1' );
        }
        return CRM_Utils_System::url( 'civicrm/contact/search/basic', 'force=1' );
    }

    static function checkUserPermission( $page ) {
        // check for permissions
        $page->_permission = null;

        // automatically grant permissin for users on their own record. makes 
        // things easier in dashboard
        $session = CRM_Core_Session::singleton( );
        
        require_once 'CRM/Contact/BAO/Contact/Permission.php';
        if ( $session->get( 'userID' ) == $page->_contactId ) {
            $page->assign( 'permission', 'edit' );
            $page->_permission = CRM_Core_Permission::EDIT;
        } else if ( CRM_Contact_BAO_Contact_Permission::allow( $page->_contactId, CRM_Core_Permission::EDIT ) ) {
            $page->assign( 'permission', 'edit' );
            $page->_permission = CRM_Core_Permission::EDIT;            
        } else if ( CRM_Contact_BAO_Contact_Permission::allow( $page->_contactId, CRM_Core_Permission::VIEW ) ) {
            $page->assign( 'permission', 'view' );
            $page->_permission = CRM_Core_Permission::VIEW;
        } else {
            $session->pushUserContext( CRM_Utils_System::url('civicrm', 'reset=1' ) );
            CRM_Core_Error::statusBounce( ts('You do not have the necessary permission to view this contact.') );
        }
    }
}