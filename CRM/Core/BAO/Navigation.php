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

require_once 'CRM/Core/DAO/Navigation.php';

class CRM_Core_BAO_Navigation extends CRM_Core_DAO_Navigation 
{
    /**
     * class constructor
     */
    function __construct( ) 
    {
        parent::__construct( );
    }

    /**
     * update the is_active flag in the db
     *
     * @param int      $id        id of the database record
     * @param boolean  $is_active value we want to set the is_active field
     *
     * @return Object             DAO object on sucess, null otherwise
     * 
     * @access public
     * @static
     */
    static function setIsActive( $id, $is_active ) 
    {
        return CRM_Core_DAO::setFieldValue( 'CRM_Core_DAO_Navigation', $id, 'is_active', $is_active );
    }

    /**
     * Function to get existing / build navigation for CiviCRM Admin Menu
     *
     * @static
     * @return array associated array
     */
    static function getMenus( ) 
    {
        $menus = array( );

        require_once "CRM/Core/DAO/Menu.php";
        $menu  =& new CRM_Core_DAO_Menu( );
        $menu->domain_id = CRM_Core_Config::domainID( );
        $menu->find();

        while ( $menu->fetch() ) {
            if ( $menu->title ) {
                $menus[$menu->path] = $menu->title;
            }
        }    
        return $menus;        
    }
    
    /**
     * Function to add/update navigation record
     *
     * @param array associated array of submitted values
     *
     * @return object navigation object
     * @static
     */
    static function add( &$params ) 
    {
        require_once "CRM/Core/DAO/Navigation.php";
        $navigation  =& new CRM_Core_DAO_Navigation( );
        
        $params['is_active'    ] = CRM_Utils_Array::value( 'is_active', $params, false );
        $params['has_separator'] = CRM_Utils_Array::value( 'has_separator', $params, false );
        
        if ( !isset( $params['id'] ) || 
             ( $params['parent_id'] !=  CRM_Utils_Array::value( 'current_parent_id', $params ) ) ) {
            /* re/calculate the weight, if the Parent ID changed OR create new menu */
            if ( $navName = CRM_Utils_Array::value('name', $params) ) {
                $params['name'] = $navName;  
            } else {
                $params['name'] = $params['label'];  
            }
          
            $params['weight'] = self::calculateWeight( $params['parent_id'] );
        }
        
        if( is_array( $params['permission'] )) {           
            $params['permission'] = implode( ',', $params['permission'] );
        }
        
        $navigation->copyValues( $params );

        $navigation->domain_id = CRM_Core_Config::domainID( );
        
        $navigation->save();
        return $navigation;
    } 
      
    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. Typically the valid params are only
     * contact_id. We'll tweak this function to be more full featured over a period
     * of time. This is the inverse function of create. It also stores all the retrieved
     * values in the default array
     *
     * @param array $params   (reference ) an assoc array of name/value pairs
     * @param array $defaults (reference ) an assoc array to hold the flattened values
     *
     * @return object CRM_Core_BAO_Navigation object on success, null otherwise
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults ) 
    {
        $navigation =& new CRM_Core_DAO_Navigation( );
        $navigation->copyValues( $params );

        $navigation->domain_id = CRM_Core_Config::domainID( );
        
        if ( $navigation->find( true ) ) {
            CRM_Core_DAO::storeValues( $navigation, $defaults );
            return $navigation;
        }
        return null;
    }
      
    /**
     * Calculate navigation weight
     *
     * @param $parentID parent_id of a menu
     * @param $menuID  menu id
     *
     * @return $weight string
     * @static
     */
    static function calculateWeight( $parentID = null, $menuID = null ) 
    {
        $domainID = CRM_Core_Config::domainID( );

        $weight = 1;
        // we reset weight for each parent, i.e we start from 1 to n
        // calculate max weight for top level menus, if parent id is absent
        if ( !$parentID ) {
            $query = "SELECT max(weight) as weight FROM civicrm_navigation WHERE parent_id IS NULL AND domain_id = $domainID";
        } else {
            // if parent is passed, we need to get max weight for that particular parent
            $query = "SELECT max(weight) as weight FROM civicrm_navigation WHERE parent_id = {$parentID} AND domain_id = $domainID";
        }

        $dao = CRM_Core_DAO::executeQuery( $query );
        $dao->fetch();
        return $weight = $weight + $dao->weight;
    }
       
    /**
     * Get formatted menu list
     * 
     * @return array $navigations returns associated array
     * @static
     */
    static function getNavigationList( ) 
    {
        $cacheKeyString = "navigationList ";
        $whereClause    = '';

        $config = CRM_Core_Config::singleton( );
        if ( $config->userFramework == 'Joomla' ) {
            $whereClause = " AND name NOT IN ('Access Control') ";
            $cacheKeyString .= "_1";
        }

        // check if we can retrieve from database cache
        require_once 'CRM/Core/BAO/Cache.php'; 
        $navigations =& CRM_Core_BAO_Cache::getItem( 'navigation', $cacheKeyString );

        if ( ! $navigations ) {
            $domainID  = CRM_Core_Config::domainID( );
            $query     = "
SELECT id, label, parent_id, weight, is_active, name 
FROM civicrm_navigation WHERE domain_id = $domainID {$whereClause} ORDER BY parent_id, weight ASC";
            $result    = CRM_Core_DAO::executeQuery( $query );

            $pidGroups = array( );
            while ( $result->fetch( ) ) {
                $pidGroups[$result->parent_id][$result->label] = $result->id;
            }
            
            foreach ( $pidGroups[''] as $label => $val ) {
                $pidGroups[''][$label] = self::_getNavigationValue($val, $pidGroups);
            }
            
            $navigations = array();
            self::_getNavigationLabel( $pidGroups[''], $navigations );
            
            CRM_Core_BAO_Cache::setItem( $navigations, 'navigation', $cacheKeyString );
        }
        return $navigations;
    }

    // helper function for getNavigationList( )
    static function _getNavigationLabel( $list, &$navigations, $separator = '' ) {
        foreach ( $list as $label => $val ) {
            if ( $label == 'navigation_id' ) continue;
            $navigations[is_array( $val ) ? $val['navigation_id'] : $val] = "{$separator}{$label}";
            if ( is_array( $val ) ) {
                self::_getNavigationLabel( $val, $navigations, $separator . '&nbsp;&nbsp;&nbsp;&nbsp;' );
            }
        }
    }

    // helper function for getNavigationList( )
    static function _getNavigationValue( $val, &$pidGroups ) {
        if ( array_key_exists( $val, $pidGroups ) ) {
            $list = array( 'navigation_id' => $val );
            foreach ( $pidGroups[$val] as $label => $id ) {
                $list[$label] = self::_getNavigationValue( $id, $pidGroups );
            }
            unset($pidGroups[$val]);
            return $list;
        } else {
            return $val;
        }
    }

    /**
     * Function to build navigation tree
     * 
     * @param array $navigationTree nested array of menus
     * @param int   $parentID       parent id 
     *
     * @return array $navigationTree nested array of menus
     * @static
     */
    static function buildNavigationTree( &$navigationTree, $parentID ) 
    {
        $whereClause = " parent_id IS NULL";

        if (  $parentID ) {
            $whereClause = " parent_id = {$parentID}"; 
        }

        $domainID = CRM_Core_Config::domainID( );

        // get the list of menus
        $query = "
SELECT id, label, url, permission, permission_operator, has_separator, parent_id, is_active, name 
FROM civicrm_navigation 
WHERE {$whereClause}
AND domain_id = $domainID
ORDER BY parent_id, weight";

        $navigation = CRM_Core_DAO::executeQuery( $query );
        $config = CRM_Core_Config::singleton( );
        while ( $navigation->fetch() ) {
            // CRM-5336
            if ( $config->userFramework == 'Joomla' &&  $navigation->name == 'Access Control' ) {
                continue;
            }
             
            // for each menu get their children
            $navigationTree[$navigation->id] = array( 'attributes' => array( 'label'      => $navigation->label,
                                                                             'url'        => $navigation->url,
                                                                             'permission' => $navigation->permission,
                                                                             'operator'   => $navigation->permission_operator,
                                                                             'separator'  => $navigation->has_separator,
                                                                             'parentID'   => $navigation->parent_id,
                                                                             'navID'      => $navigation->id,
                                                                             'active'     => $navigation->is_active ));
            self::buildNavigationTree( $navigationTree[$navigation->id]['child'], $navigation->id );
        }

        return $navigationTree;
    }
        
    /**
     * Function to build menu 
     * 
     * @param boolean $json by default output is html
     * 
     * @return returns html or json object
     * @static
     */
    static function buildNavigation( $json = false ) 
    {
        $navigations = array( );
        self::buildNavigationTree( $navigations, $parent = NULL );
        $navigationString = null;

        //skip children menu item if user don't have access to parent menu item
        $skipMenuItems = array( );
        foreach( $navigations as $key => $value ) {
            if ( $json ) {
                if ( $navigationString ) {
                    $navigationString .= '},';
                }
                $data = $value['attributes']['label'];
                $class = '';
                if ( !$value['attributes']['active'] ) {
                    $class = ', attributes: { "class" : "disabled"} ';
                }
                $navigationString .= ' { attributes: { "id" : "node_'.$key.'"}, data: { title:"'. $data. '"' .$class.'}';
            } else {
                $name = self::getMenuName( $value, $skipMenuItems );
                if ( $name ) { 
                    $navigationString .= '<li class="menumain">' . $name;
                }
            }
            
            self::recurseNavigation( $value, $navigationString, $json, $skipMenuItems );
        }
        
        if ( $json ) {
            $navigationString = '[' .$navigationString . '}]';
        } else {
            // clean up - Need to remove empty <ul>'s, this happens when user don't have 
            // permission to access parent
            $navigationString = str_replace( '<ul></ul></li>', '', $navigationString );
        }

        return $navigationString;
    }
         
    /**
     * Recursively check child menus
     */
    function recurseNavigation(&$value, &$navigationString, $json, $skipMenuItems ) 
    {
        if ( $json ) {
            if ( !empty( $value['child'] ) ) {
                $navigationString .= ', children : [ ';
            } else {
                return $navigationString ;
            }

            if ( !empty( $value['child'] ) ) {
                $appendComma = false;
                foreach($value['child'] as $k => $val ) {
                    $appendComma = true; 
                    $data = $val['attributes']['label'];
                    $class = '';
                    if ( !$val['attributes']['active'] ) {
                        $class = ', attributes: { "class" : "disabled"} ';
                    }                      
                    $navigationString .= ' { attributes: { "id" : "node_'.$k.'"}, data: { title:"'. $data. '"' .$class.'}';
                    self::recurseNavigation($val, $navigationString, $json, $skipMenuItems );
                    if ( $appendComma ) {
                        $navigationString .= ' },';
                    }
                }
            }

            if ( !empty( $value['child'] ) ) {
                $navigationString .= ' ]';
            }
            
        } else {
            if ( !empty( $value['child'] ) ) {
                $navigationString .= '<ul>';  
            } else {
                $navigationString .= '</li>';
                if ( isset( $value['attributes']['separator'] ) && $value['attributes']['separator'] ) {
                    $navigationString .= '<li class="menu-separator"></li>';
                } 
            }

            if ( !empty( $value['child'] ) ) {
                foreach($value['child'] as $val ) {
                    $name = self::getMenuName( $val, $skipMenuItems );
                    if ( $name ) { 
                        $navigationString .= '<li>' . $name;
                        self::recurseNavigation($val, $navigationString, $json, $skipMenuItems );
                    }
                }
            }
            if ( !empty( $value['child'] ) ) {
                $navigationString .= '</ul></li>';
            }
        }
        return $navigationString;
    }

    /**
     *  Get Menu name
     */
    function getMenuName( &$value, &$skipMenuItems ) {
        // we need to localise the menu labels (CRM-5456) and don’t
        // want to use ts() as it would throw the ts-extractor off
        $i18n =& CRM_Core_I18n::singleton();

        $name       = $i18n->crm_translate($value['attributes']['label']);
        $url        = str_replace('&', '&amp;', $value['attributes']['url']);
        $permission = $value['attributes']['permission'];
        $operator   = $value['attributes']['operator'];
        $parentID   = $value['attributes']['parentID'];
        $navID      = $value['attributes']['navID'];
        $active     = $value['attributes']['active'];
        
        if ( in_array( $parentID, $skipMenuItems ) || !$active ) {
            $skipMenuItems[] = $navID;
            return false;
        }
        
        $makeLink = false;
        if ( isset( $url ) && $url) {
            if ( substr( $url, 0, 4 ) === 'http' ) {
                $url = $url;
            } else {
                $url = CRM_Utils_System::url( $url );
            }
            $makeLink = true;
        }
                    
        if ( isset( $permission) && $permission ) {
            $permissions = explode(',', $permission ); 
            $config  =& CRM_Core_Config::singleton( );
            
            $hasPermission = false;    
            foreach ( $permissions as $key ) {
                $showItem = true;
                //hack to determine if it's a component related permission
                if ( $key != 'access CiviCRM' && substr( $key, 0, 6 ) === 'access' ) {
                    $componentName = trim(substr( $key, 6 ));
                    if ( !in_array( $componentName, $config->enableComponents ) || 
                         !CRM_Core_Permission::check( $key ) ) {
                        $showItem = false;
                        if ( $operator == 'AND' ) {
                            $skipMenuItems[] = $navID;
                            return $showItem;
                        }
                    } else {
                        $hasPermission = true;
                    }
               } else if ( !CRM_Core_Permission::check( $key ) ) {
                     $showItem = false;
                     if ( $operator == 'AND' ) {
                         $skipMenuItems[] = $navID;
                         return $showItem;
                     }
                } else {
                    $hasPermission = true;
                }
            }
            
            if ( !$showItem && !$hasPermission ) {
                $skipMenuItems[] = $navID;
                return false;
            }   
        }
              
        if ( $makeLink ) {
            return $name = "<a href=\"{$url}\">{$name}</a>";
        }
 
        return $name;
    }
          
    /**
     * Function to create navigation for CiviCRM Admin Menu
     * 
     * @param int $contactID contact id
     *
     * @return string $navigation returns navigation html
     * @static
     */
    static function createNavigation( $contactID ) 
    {
        if ( !$contactID || 
             !CRM_Core_DAO::checkFieldExists( 'civicrm_preferences', 'navigation' ) ) {
            return;
        }

        $config =& CRM_Core_Config::singleton();
        // For Joomla front end user, there is no need to create
        // navigation menu items, CRM-5349
        if ($config->userFramework == 'Joomla' && $config->userFrameworkFrontend ) {
            return "<!-- $config->lcMessages -->";
        }

        $navParams = array( 'contact_id' => $contactID );
        if ( CRM_Core_DAO::checkFieldExists('civicrm_preferences', 'domain_id') ) {
            // FIXME: if() condition check was required especially for upgrade 
            // cases (2.2.x -> 3.0.x), CRM-5203
            $navParams['domain_id'] = CRM_Core_Config::domainID( );
        }

        CRM_Core_DAO::commonRetrieve( 'CRM_Core_DAO_Preferences', $navParams, $navParams );
        $navigation = array_key_exists('navigation', $navParams) ? $navParams['navigation'] : false;

        // FIXME: hack for CRM-5027: we need to prepend the navigation string with
        // (HTML-commented-out) locale info so that we rebuild menu on locale changes
        if (!$navigation or substr($navigation, 0, 14) != "<!-- $config->lcMessages -->") {
            //retrieve navigation if it's not cached.       
            require_once 'CRM/Core/BAO/Navigation.php';
            $navigation = self::buildNavigation( );
            
            //add additional navigation items
            $logoutURL       = CRM_Utils_System::url( 'civicrm/logout', 'reset=1');
            $appendSring     = "<li id=\"menu-logout\" class=\"menumain\"><a href=\"{$logoutURL}\">" . ts('Logout') . "</a></li>";

            $homeURL       = CRM_Utils_System::url( 'civicrm/dashboard', 'reset=1');

            if ( ( $config->userFramework == 'Drupal' ) && 
                 function_exists( 'module_exists' ) &&
                 module_exists('admin_menu') ) {
                $prepandString = "<li class=\"menumain\">" . ts('Home') . "<ul id=\"civicrm-home\"><li><a href=\"{$homeURL}\">" . ts('CiviCRM Home') . "</a></li><li><a href=\"#\" onclick=\"cj.Menu.closeAll( );cj('#civicrm-menu').toggle( );\">" . ts('Drupal Menu') . "</a></li></ul></li>";
            } else {
                $prepandString = "<li class=\"menumain\"><a href=\"{$homeURL}\" title=\"" . ts('CiviCRM Home') . "\">" . ts('Home') . "</a></li>";
            }

            // prepend the navigation with locale info for CRM-5027
            $navigation = "<!-- $config->lcMessages -->" . $prepandString . $navigation . $appendSring;
            
            // before inserting check if contact id exists in db
            // this is to handle wierd case when contact id is in session but not in db
            require_once 'CRM/Contact/DAO/Contact.php';
            $contact =& new CRM_Contact_DAO_Contact( );
            $contact->id = $contactID;
            if ( $contact->find(true ) ) {
                // save in preference table for this particular user
                require_once 'CRM/Core/DAO/Preferences.php';
                $preference =& new CRM_Core_DAO_Preferences();
                $preference->contact_id = $contactID;
                $preference->domain_id  = CRM_Core_Config::domainID( );
                $preference->find(true);
                $preference->navigation = $navigation;
                $preference->save();
            }
        }
        return $navigation;
    }

    /**
     * Reset navigation for all contacts
     */
    static function resetNavigation( ) 
    {
        $query = "UPDATE civicrm_preferences SET navigation = NULL WHERE contact_id IS NOT NULL";
        CRM_Core_DAO::executeQuery( $query );

        require_once 'CRM/Core/BAO/Cache.php';
        CRM_Core_BAO_Cache::deleteGroup( 'navigation' );
    }          

    /**
     * Function to process navigation
     *
     * @param array $params associated array, $_GET 
     *
     * @return void
     * @static
     */
     static function processNavigation( &$params ) 
     {
         $nodeID      = (int)str_replace("node_","",$params['id']);
         $referenceID = (int)str_replace("node_","",$params['ref_id']);
         $moveType    = $params['move_type'];
         $type        = $params['type'];
         $label       = $params['data'];
         
         switch ( $type ) {
             case "move":
                self::processMove( $nodeID, $referenceID, $moveType );
                break;
             case "rename":
                self::processRename( $nodeID, $label );
                break;
             case "delete":
                self::processDelete( $nodeID );
                break;
         }
         
         //reset navigation menus
         self::resetNavigation( );
         exit();
     }
     
     /**
      * Function to process move action
      */
      static function processMove( $nodeID, $referenceID, $moveType ) 
      {
          //check if it's a valid move
          if ( !in_array($moveType, array("after", "before", "inside") ) ) {
              return false;    
          }
          
          // get the details of reference node
          $referenInfo = self::getNavigationInfo( $referenceID );

          // determine new parent and weight
          if ( $moveType == "inside" ) {
              $newParentID = $referenceID;
              $newWeight   = 1;
          } else {
              $newParentID =  $referenInfo['parent_id'];
              if ( $moveType == "before" )  {
                  $newWeight = $referenInfo['weight'];    
              } else if ( $moveType == "after" ) {
                  $newWeight = $referenInfo['weight'] + 1; 
              }    
          }
          
          // get the details of current node
          $nodeInfo = self::getNavigationInfo( $nodeID ); 
          $oldParentID  = $nodeInfo['parent_id'];
          $oldWeight    = $nodeInfo['weight'];
          
          $oldParentClause = " parent_id = {$oldParentID}";
          // if no parent means these are top menus
          if ( !$oldParentID ) {
              $oldParentClause = " parent_id IS NULL";
          }
          
          $newParentClause = " parent_id = {$newParentID}";
          if ( !$newParentID ) {
              $newParentClause = " parent_id IS NULL";
              $newParentID = 'NULL';
          }
          
          // since we need to do multiple updates lets build sql array and then fire all with transaction
          $sql = array( );
          
          // reorder was made, since parent are same
          if ( $oldParentID == $newParentID ) {
              if ( $newWeight > $oldWeight ) {
                  $newWeight = $newWeight - 1;
                  $sql[] = "UPDATE civicrm_navigation SET weight = weight - 1 
                            WHERE {$oldParentClause}  AND weight BETWEEN {$oldWeight} + 1 AND {$newWeight}";
              }
              
              if ( $newWeight < $oldWeight ) {
                  $sql[] = "UPDATE civicrm_navigation SET weight = weight + 1 
                            WHERE {$oldParentClause} AND weight BETWEEN {$newWeight} AND {$oldWeight} - 1";
              }
          } else {
              // 1. fix old parent (move siblings up)                  
              $sql[] = "UPDATE civicrm_navigation SET weight = weight - 1 
                        WHERE {$oldParentClause} AND weight > {$oldWeight}";
              
              // 2. set new parent (move sibling down)
              $weightOperator = '>';
              if ( $moveType != "after" ) {
                  $weightOperator = '>=';
              }
              
              $sql[] = "UPDATE civicrm_navigation SET weight = weight + 1 
                        WHERE {$newParentClause} AND weight {$weightOperator} $newWeight";
          }
          
          // finally set the weight of current node
          $sql[] = "UPDATE civicrm_navigation SET weight = {$newWeight}, parent_id = {$newParentID} WHERE id = {$nodeID}";
          
          // now execute all the sql's
          require_once 'CRM/Core/Transaction.php';
          $transaction = new CRM_Core_Transaction( );
          
          foreach ( $sql as $query ) {
              CRM_Core_DAO::executeQuery( $query );
          }
          
          $transaction->commit( );
      }
      
      /**
       *  Function to process rename action for tree
       *
       */
       static function processRename( $nodeID, $label ) 
       {
           CRM_Core_DAO::setFieldValue( 'CRM_Core_DAO_Navigation', $nodeID, 'label', $label );
       }

      /**
       *  Function to process delete action for tree
       *
       */
       static function processDelete( $nodeID ) 
       {
           $query = "DELETE FROM civicrm_navigation WHERE id = {$nodeID}";
           CRM_Core_DAO::executeQuery( $query );
       }
       
      /**
      * Function to get the info on navigation item
      * 
      * @param int $navigationID  navigation id
      *
      * @return array associated array
      * @static
      */
      static function getNavigationInfo( $navigationID ) 
      {
          $query  = "SELECT parent_id, weight FROM civicrm_navigation WHERE id = %1";
          $params = array( $navigationID, 'Integer' );
          $dao =& CRM_Core_DAO::executeQuery( $query, array( 1 => $params ) );
          $dao->fetch();            
          return array( 'parent_id' => $dao->parent_id,
                        'weight'    => $dao->weight );
      }

      /**
       * Function to update menu 
       * 
       * @param array  $params  
       * @param array  $newParams new value of params
       * @static
       */
      static function processUpdate( $params, $newParams ) 
      {
          $dao = new CRM_Core_DAO_Navigation( );
          $dao->copyValues( $params );
          if( $dao->find( true ) ) {
              $dao->copyValues( $newParams );
              $dao->save( );
          }
      } 
}
