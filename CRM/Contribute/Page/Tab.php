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

class CRM_Contribute_Page_Tab extends CRM_Core_Page
{
    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     * @static
     */
    static $_links = null;
    
    
    /**
     * This method returns the links that are given for honor search row.
     * currently the links added for each row are 
     * 
     * - View
     * - Edit
     *
     * @return array
     * @access public
     *
     */
    static function &honorLinks()
    {
        if (!(self::$_links)) {
            self::$_links = array(
                                  CRM_Core_Action::VIEW   => array(
                                                                   'name'     => ts('View'),
                                                                   'url'      => 'civicrm/contact/view/contribution',
                                                                   'qs'       => 'reset=1&id=%%id%%&cid=%%cid%%&honorId=%%honorId%%&action=view&context=%%cxt%%&selectedChild=contribute',
                                                                   'title'    => ts('View Contribution'),
                                                                   ),
                                  CRM_Core_Action::UPDATE => array(
                                                                   'name'     => ts('Edit'),
                                                                   'url'      => 'civicrm/contact/view/contribution',
                                                                   'qs'       => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&honorId=%%honorId%%&context=%%cxt%%&subType=%%contributionType%%',
                                                                   'title'    => ts('Edit Contribution'),
                                                                   ),
                                  CRM_Core_Action::DELETE => array(
                                                                   'name'     => ts('Delete'),
                                                                   'url'      => 'civicrm/contact/view/contribution',
                                                                   'qs'       => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&honorId=%%honorId%%&context=%%cxt%%',
                                                                   'title'    => ts('Delete Contribution'),
                                                                   ),
                                  );
        }
        return self::$_links;
    } //end of function
    
    /**
     * This function is called when action is browse
     * 
     * return null
     * @access public
     */
    function browse( ) 
    {
        require_once 'CRM/Contribute/BAO/Contribution.php';

        // add annual contribution
        $annual = array( );
        list( $annual['count'],
              $annual['amount'],
              $annual['avg'] ) =
            CRM_Contribute_BAO_Contribution::annual( $this->_contactId );
        $this->assign( 'annual', $annual );

        $controller =& new CRM_Core_Controller_Simple( 'CRM_Contribute_Form_Search', ts('Contributions'), $this->_action );
        $controller->setEmbedded( true );
        $controller->reset( );
        $controller->set( 'cid'  , $this->_contactId );
        $controller->set( 'id' , $this->_id ); 
        $controller->set( 'context', 'contribution' ); 
        $controller->process( );
        $controller->run( );
        
        //add honor block
        // form all action links	
        $action = array_sum(array_keys($this->honorLinks( )));	    
        
        $params = array( );
        $params =  CRM_Contribute_BAO_Contribution::getHonorContacts( $this->_contactId );        
        if ( ! empty($params) ) {
            foreach( $params as $ids => $honorId ) {
                $params[$ids]['action'] = CRM_Core_Action::formLink(self::honorLinks( ), $action, 
                                                                    array('cid'              => $honorId['honorId'],
                                                                          'id'               => $ids,
                                                                          'cxt'              => 'contribution',
                                                                          'contributionType' => $honorId['type_id'],
                                                                          'honorId'          => $this->_contactId )
                                                                    );
            }
            // assign vars to templates
            $this->assign('action', $this->_action);
            $this->assign('honorRows', $params);
            $this->assign('honor', true);
        }

        //enable/disable soft credit records for test contribution
        $isTest = 0;
        if ( CRM_Utils_Request::retrieve( 'isTest', 'Positive', $this ) ) {
            $isTest = 1;
        }
        $this->assign( 'isTest', $isTest ); 
       
        $softCreditList = CRM_Contribute_BAO_Contribution::getSoftContributionList( $this->_contactId, $isTest );
               
        if( !empty( $softCreditList ) ) {
            $softCreditTotals = array();
            
            list( $softCreditTotals['amount'],
                  $softCreditTotals['avg'],
                  $softCreditTotals['currency'] ) =
                  CRM_Contribute_BAO_Contribution::getSoftContributionTotals( $this->_contactId, $isTest );        
                
            $this->assign('softCredit', true);
            $this->assign('softCreditRows', $softCreditList );
            $this->assign('softCreditTotals', $softCreditTotals );
        }

    }


    /** 
     * This function is called when action is view
     *  
     * return null 
     * @access public 
     */ 
    function view( ) 
    {
        $controller =& new CRM_Core_Controller_Simple( 'CRM_Contribute_Form_ContributionView',  
                                                       'View Contribution',  
                                                       $this->_action ); 
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
        // set https for offline cc transaction        
        $mode = CRM_Utils_Request::retrieve( 'mode', 'String', $this );
        if ( $mode == 'test' || $mode == 'live' ) {
            CRM_Utils_System::redirectToSSL( );
        }

        $controller =& new CRM_Core_Controller_Simple( 'CRM_Contribute_Form_Contribution', 
                                                       'Create Contribution', 
                                                       $this->_action );
        $controller->setEmbedded( true ); 
        $controller->set( 'id' , $this->_id ); 
        $controller->set( 'cid', $this->_contactId ); 
        
        return $controller->run( );
    }

    function preProcess( ) {
        $context       = CRM_Utils_Request::retrieve('context', 'String', $this );
        $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, false, 'browse');
        $this->_id     = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        
        if ( $context == 'standalone' ) {
            $this->_action = CRM_Core_Action::ADD;
        } else {
            $this->_contactId = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, true );
            $this->assign( 'contactId', $this->_contactId );

            // check logged in url permission
            require_once 'CRM/Contact/Page/View.php';
            CRM_Contact_Page_View::checkUserPermission( $this );
        }      

        $this->assign('action', $this->_action );     
        
        if ( $this->_permission == CRM_Core_Permission::EDIT && ! CRM_Core_Permission::check( 'edit contributions' ) ) {
            $this->_permission = CRM_Core_Permission::VIEW; // demote to view since user does not have edit contrib rights
            $this->assign( 'permission', 'view' );
        }
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
        $this->preProcess( );
        
        // check if we can process credit card contribs
        $processors = CRM_Core_PseudoConstant::paymentProcessor( false, false,
                                                                 "billing_mode IN ( 1, 3 )" );
        if ( count( $processors ) > 0 ) {
            $this->assign( 'newCredit', true );
        } else {
            $this->assign( 'newCredit', false );
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
        $context = CRM_Utils_Request::retrieve( 'context', 'String',
                                                $this, false, 'search' );
        $session =& CRM_Core_Session::singleton( ); 
       
        switch ( $context ) {

        case 'user':
            $url = CRM_Utils_System::url( 'civicrm/user', 'reset=1' );
            break;
            
        case 'dashboard':
            $url = CRM_Utils_System::url( 'civicrm/contribute',
                                          'reset=1' );
            break;

        case 'pledgeDashboard':
            $url = CRM_Utils_System::url( 'civicrm/pledge',
                                          'reset=1' );
            break;
            
        case 'contribution':
            $honorId = CRM_Utils_Request::retrieve( 'honorId', 'Positive', $this, false );
            
            if ($honorId) {
                $cid = $honorId;
            } else {
                $cid = $this->_contactId;
            }
            
            $url = CRM_Utils_System::url( 'civicrm/contact/view',
                                          "reset=1&force=1&cid={$cid}&selectedChild=contribute" );
            break;
            
        case 'search':
            $url = CRM_Utils_System::url( 'civicrm/contribute/search', 'force=1' );
            break;

        case 'home':
            $url = CRM_Utils_System::url( 'civicrm/dashboard', 'reset=1' );
            break;

        case 'activity':
            $url = CRM_Utils_System::url( 'civicrm/contact/view',
                                          "reset=1&force=1&cid={$this->_contactId}&selectedChild=activity" );
            break;
            
        case 'membership':
            $componentId     =  CRM_Utils_Request::retrieve( 'compId', 'Positive', $this);
            $componentAction =  CRM_Utils_Request::retrieve( 'compAction', 'Integer', $this );

            if ( $componentAction & CRM_Core_Action::VIEW ) {
                $action = 'view';
            } else {
                $action = 'update';
            } 
            $url = CRM_Utils_System::url( 'civicrm/contact/view/membership',
                                          "reset=1&action={$action}&cid={$this->_contactId}&id={$componentId}&context=membership&selectedChild=member" );
            break; 
            
        case 'participant':
            $componentId     =  CRM_Utils_Request::retrieve( 'compId', 'Positive', $this );
            $componentAction =  CRM_Utils_Request::retrieve( 'compAction', 'Integer', $this );
            
            if ( $componentAction == CRM_Core_Action::VIEW ) {
                $action = 'view';
            } else {
                $action = 'update';
            } 
            $url = CRM_Utils_System::url( 'civicrm/contact/view/participant',
                                          "reset=1&action={$action}&id={$componentId}&cid={$this->_contactId}&context=participant&selectedChild=event" );
            break;
            
        case 'pledge':
            $url = CRM_Utils_System::url( 'civicrm/contact/view',
                                         "reset=1&force=1&cid={$this->_contactId}&selectedChild=pledge" );
            break;
       
        case 'standalone':
            $url = CRM_Utils_System::url( 'civicrm/dashboard', 'reset=1' );
            break;
            
        default:
            $cid = null;
            if ( $this->_contactId ) {
                $cid = '&cid=' . $this->_contactId;
            }
            $url = CRM_Utils_System::url( 'civicrm/contribute/search', 
                                          'reset=1&force=1' . $cid );
            break;
        }

        $session =& CRM_Core_Session::singleton( ); 
        $session->pushUserContext( $url );
    }
}

