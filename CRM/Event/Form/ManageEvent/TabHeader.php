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
 * Helper class to build navigation links
 */
class CRM_Event_Form_ManageEvent_TabHeader {

    static function build( &$form ) {
        $tabs = $form->get( 'tabHeader' );
        if ( !$tabs || !CRM_Utils_Array::value('reset', $_GET) ) {
            $tabs =& self::process( $form );
            $form->set( 'tabHeader', $tabs );
        }
        $form->assign_by_ref( 'tabHeader', $tabs );
        $form->assign_by_ref( 'selectedTab', self::getCurrentTab($tabs) );
        return $tabs;
    }

    static function process( &$form ) {
        if ( $form->getVar( '_id' ) <= 0 ) {
            return null;
        }

        $tabs = array(
                      'EventInfo'    => array( 'title'  => ts( 'Info and Settings' ),
                                               'link'   => null,
                                               'valid'  => false,
                                               'active' => false,
                                               'current' => false,
                                               ),
                      'Location'     => array( 'title' => ts( 'Event Location' ),
                                               'link'   => null,
                                               'valid' => false,
                                               'active' => false,
                                               'current' => false,
                                               ),
                      'Fee'          => array( 'title' => ts( 'Fees' ),
                                               'link'   => null,
                                               'valid' => false,
                                               'active' => false,
                                               'current' => false,
                                               ),
                      'Registration' => array( 'title' => ts( 'Online Registration' ),
                                               'link'   => null,
                                               'valid' => false,
                                               'active' => false,
                                               'current' => false,
                                               ),
                      'Friend'       => array( 'title' => ts( 'Tell a Friend' ),
                                               'link'   => null,
                                               'valid' => false,
                                               'active' => false,
                                               'current' => false,
                                               ),
                      );

        $eventID = $form->getVar( '_id' );

        $fullName  = $form->getVar( '_name' );
        $className = CRM_Utils_String::getClassName( $fullName );

        // hack for tell a friend, since class name is different
        if ( $className == 'Event' ) {
            $className = 'Friend';
        }
        if ( array_key_exists( $className, $tabs ) ) {
            $tabs[$className]['current'] = true;
        }

        if ( $eventID ) {
            $reset = CRM_Utils_Array::value('reset', $_GET) ? 'reset=1&' : '';
            foreach ( $tabs as $key => $value ) {
                $tabs[$key]['link'] = CRM_Utils_System::url( 'civicrm/admin/event',
                                                             "{$reset}action=update&snippet=4&subPage={$key}&id={$eventID}" );
                $tabs[$key]['active'] = $tabs[$key]['valid'] = true;
            }
            
            // retrieve info about paid event, tell a friend and online reg
            $sql = "
SELECT     e.is_online_registration, e.is_monetary, taf.is_active
FROM       civicrm_event e
LEFT JOIN  civicrm_tell_friend taf ON ( taf.entity_table = 'civicrm_event' AND taf.entity_id = e.id )
WHERE      e.id = %1
";
            $params = array( 1 => array( $eventID, 'Integer' ) );
            $dao = CRM_Core_DAO::executeQuery( $sql, $params );
            if ( ! $dao->fetch( ) ) {
                CRM_Core_Error::fatal( );
            }

            if ( ! $dao->is_online_registration ) {
                $tabs['Registration']['valid'] = false;
            }
            
            if ( ! $dao->is_monetary ) {
                $tabs['Fee']['valid'] = false;
            }
        
            if ( ! $dao->is_active ) {
                $tabs['Friend']['valid'] = false;
            }
        }

        return $tabs;
    }

    static function reset( &$form ) {
        $tabs =& self::process( $form );
        $form->set( 'tabHeader', $tabs );
    }

    static function getNextSubPage( $form, $currentSubPage = 'EventInfo' ) {
        $tabs = self::build( $form );
        $flag = false;

        if ( is_array($tabs) ) {
            foreach ( $tabs as $subPage => $pageVal ) {
                if ( $flag && $pageVal['valid'] ) {
                    return $subPage;
                }
                if ( $subPage == $currentSubPage ) {
                    $flag = true;
                }
            }
        }
        return 'EventInfo';
    }

    static function getSubPageInfo( $form, $subPage, $info = 'title' ) {
        $tabs = self::build( $form );

        if ( is_array($tabs[$subPage]) && array_key_exists($info, $tabs[$subPage]) ) {
            return $tabs[$subPage][$info];
        }
        return false;
    }

    static function getCurrentTab( $tabs ) {
        static $current = false;

        if ( $current ) {
            return $current;
        }
        
        if ( is_array($tabs) ) {
            foreach ( $tabs as $subPage => $pageVal ) {
                if ( $pageVal['current'] === true ) {
                    $current = $subPage;
                    break;
                }
            }
        }
        
        $current = $current ? $current : 'EventInfo';
        return $current;
    }
}
