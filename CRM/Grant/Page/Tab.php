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

require_once 'CRM/Contact/Page/View.php';

/**
 * This class handle grant related functions
 *
 */
class CRM_Grant_Page_Tab extends CRM_Contact_Page_View 
{
    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     * @static
     */
    static $_links = null;
   
    /**
     * This function is called when action is browse
     *
     * return null
     * @access public
     */
    function browse( )
    {
         $controller =& new CRM_Core_Controller_Simple( 'CRM_Grant_Form_Search', ts('Grants'), $this->_action );
         $controller->setEmbedded( true );
         $controller->reset( );
         $controller->set( 'cid'  , $this->_contactId );
         $controller->set( 'context', 'grant' ); 
         $controller->process( );
         $controller->run( );
    }

    /** 
     * This function is called when action is view
     *  
     * return null 
     * @access public 
     */ 
    function view( ) 
    { 
        $controller =& new CRM_Core_Controller_Simple( 'CRM_Grant_Form_GrantView', 'View Grant', $this->_action ); 
        $controller->setEmbedded( true );  
        $controller->set( 'id' , $this->_id );  
        $controller->set( 'cid', $this->_contactId );  
        
        return $controller->run( ); 
    }
    
    /**
     * This function is called when action is update or new
     * 
     * return null
     * @access public
     */
    function edit( ) 
    { 
        $controller =& new CRM_Core_Controller_Simple( 'CRM_Grant_Form_Grant', 'Create grant', $this->_action );
        $controller->setEmbedded( true ); 
        $controller->set( 'id' , $this->_id ); 
        $controller->set( 'cid', $this->_contactId ); 
        
        return $controller->run( );
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
        $contactID  = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullArray );
        $context = CRM_Utils_Request::retrieve('context', 'String', $this );

        if ( $context == 'standalone' && !$contactID ) {
            $this->_action = CRM_Core_Action::ADD;
            $this->assign('action', $this->_action );     
        } else {
            // we should call contact view, preprocess only for grant in contact summary
            $this->preProcess( );           
        } 

        $this->setContext( );
        if ( $this->_action & CRM_Core_Action::VIEW ) { 
            $this->view( ); 
        } else if ( $this->_action & ( CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE ) ) {
            $this->edit( );
        } else {
            $this->browse( ); 
        }
        return parent::run( );
    }

    function setContext( ) 
    {
        $context   = CRM_Utils_Request::retrieve( 'context', 'String', $this );
        $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this );
        $session   =& CRM_Core_Session::singleton( ); 
        
        switch ( $context ) {
            
        case 'search':
            $url = CRM_Utils_System::url('civicrm/grant/search','reset=1&force=1');
            break;
            
        case 'dashboard':
            $url = CRM_Utils_System::url('civicrm/grant','reset=1');
            break;
            
        case 'edit':
            $url = CRM_utils_System::url('civicrm/contact/view/grant','reset=1&id='.$this->_id.'&cid='.$this->_contactId.'&action=view&context=grant&selectedChild=grant');
            break;

        case 'grant':
            $url = CRM_Utils_System::url('civicrm/contact/view', 'action=browse&selectedChild=grant&cid=' . $this->_contactId );
            break;

        case 'standalone':
            $url = CRM_Utils_System::url( 'civicrm/dashboard', 'reset=1' );
            break;

        default:
            $cid = null;
            if ( $this->_contactId ) {
                $cid = '&cid=' . $this->_contactId;
            }
            $url = CRM_Utils_System::url( 'civicrm/grant/search', 'reset=1&force=1' . $cid );
            break;
        }
        $session->pushUserContext( $url );

        if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
                                        CRM_Core_DAO::$_nullObject )) {
            require_once 'CRM/Grant/BAO/Grant.php';
            CRM_Grant_BAO_Grant::del( $this->_id );
            CRM_Utils_System::redirect($url);
        }
    }
    
}


