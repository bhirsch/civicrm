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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/BAO/UFGroup.php';

/**
 *  This class is for UF Group
 */
class CRM_UF_Form_Group extends CRM_Core_Form 
{

    /**
     * the form id saved to the session for an update
     *
     * @var int
     * @access protected
     */
    protected $_id;
    
    /**
     * the title for group
     *
     * @var int
     * @access protected
     */
    protected $_title;
    protected $_groupElement;
    protected $_group;
    protected $_allPanes;

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess()
    {
        // current form id 
        $this->_id = $this->get('id');
        $this-> assign('gid',$this->_id);
        $this->_group    =& CRM_Core_PseudoConstant::group( ); 
        
        // setting title for html page
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            $title = CRM_Core_BAO_UFGroup::getTitle($this->_id);
            CRM_Utils_System::setTitle( ts( 'Profile Settings' ) . " - $title" );
        } elseif ( $this->_action & (CRM_Core_Action::DISABLE | CRM_Core_Action::DELETE) ) { 
            $ufGroup['module'] = implode( ' , ', CRM_Core_BAO_UFGroup::getUFJoinRecord( $this->_id, true ));
            $status = 0;
            $status = CRM_Core_BAO_UFGroup::usedByModule($this->_id); 
            if ($this->_action & (CRM_Core_Action::DISABLE) ) {
                if ( $status ) {
                    $message='This profile is currently used for '. $ufGroup['module'].'. If you disable the profile - it will be removed from these forms and/or modules. Do you want to continue?';
                } else {            
                    $message='Are you sure you want to disable this Profile?';
                }
            } else{
                if ( $status ) {
                    $message='This profile is currently used for '. $ufGroup['module'].'. If you delete the profile - it will be removed from these forms and/or modules. This action cannot be undone. Do you want to continue?';
                } else {            
                    $message='Are you sure you want to delete this Profile? This action cannot be undone.';
                }  
            }
            $this->assign('message',$message);
        } else {
            CRM_Utils_System::setTitle( ts('New CiviCRM Profile') );
        }
    }
    
    /**
     * Function to actually build the form
     *
     * @return void
     * @access public
     */
    public function buildQuickForm()
    {
        if ( $this->_action & (CRM_Core_Action::DISABLE | CRM_Core_Action::DELETE) ) {
            if( $this->_action & (CRM_Core_Action::DISABLE ) ) {
                $display = 'Disable Profile';
            } else {
                $display = 'Delete Profile';
            }
            $this->addButtons(array(
                                    array ( 'type'      => 'next',
                                            'name'      => $display,
                                            'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                            'isDefault' => true   ),
                                    array ( 'type'      => 'cancel',
                                            'name'      => ts('Cancel') ),
                                    )
                              );
            return;
        }
        $this->applyFilter('__ALL__', 'trim');
        
        // title
        $this->add('text', 'title', ts('Profile Name'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'title'), true);
        
        if ( isset ($ufgroupId ) ){
            if( !( $this->_action & CRM_Core_Action::UPDATE ) ) {
                $this->addRule( 'title', ts('Profile Title is already exist in Database.'), 'objectExists', 
                                array( 'CRM_Core_DAO_UFGroup', $ufgroupId, 'title' ) );
            }
        }
        //add checkboxes
        $uf_group_type = array();
        $UFGroupType = CRM_Core_SelectValues::ufGroupTypes( );
        foreach ($UFGroupType as $key => $value ) {
            $uf_group_type[] = HTML_QuickForm::createElement('checkbox', $key, null, $value);
        }
        $this->addGroup($uf_group_type, 'uf_group_type', ts('Used For'), '&nbsp;');

        // help text
        $this->addWysiwyg( 'help_pre',  ts('Pre-form Help'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'help_post'));
        $this->addWysiwyg( 'help_post', ts('Post-form Help'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFGroup', 'help_post'));

        // weight
        $this->add('text', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_UFJoin', 'weight'), true);
        $this->addRule('weight', ts('is a numeric field') , 'numeric');

        // is this group active ?
        $this->addElement('checkbox', 'is_active', ts('Is this CiviCRM Profile active?') );
        
        require_once 'CRM/UF/Form/AdvanceSetting.php';
        $paneNames =  array ( 'Advanced Settings'  => 'buildAdvanceSetting' );
        
        foreach ( $paneNames as $name => $type ) {
            if ( $this->_id ) {
                $dataURL = "&reset=1&action=update&id={$this->_id}&snippet=4&formType={$type}";  
            } else {
                $dataURL = "&reset=1&action=add&snippet=4&formType={$type}";
            }
            
            $allPanes[$name] = array( 'url'  => CRM_Utils_System::url( 'civicrm/admin/uf/group/setting',
                                                                       $dataURL ),
                                      'open' => 'false',
                                      'id'   => $type,
                                      );
            
            eval( 'CRM_UF_Form_AdvanceSetting::' . $type . '( $this );' );
        }
        
        $this->addButtons(array(
                                array ( 'type'      => 'next',
                                        'name'      => ts('Save'),
                                        'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                        'isDefault' => true   ),
                                array ( 'type'      => 'cancel',
                                        'name'      => ts('Cancel') ),
                                )
                          );

        // views are implemented as frozen form
        if ($this->_action & CRM_Core_Action::VIEW) {
            $this->freeze();
            $this->addElement('button', 'done', ts('Done'), array('onclick' => "location.href='civicrm/admin/uf/group?reset=1&action=browse'"));
        }
    }

    /**
     * This function sets the default values for the form. Note that in edit/view mode
     * the default values are retrieved from the database
     *
     * @access public
     * @return void
     */
    function setDefaultValues()
    {
        $defaults = array();
        require_once 'CRM/Core/ShowHideBlocks.php';
        $showHide =& new CRM_Core_ShowHideBlocks( );

        if ($this->_action == CRM_Core_Action::ADD) {
            $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_UFJoin');
        }
        
        //id fetched for Dojo Pane
        $pId = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        if ( isset( $pId )){
            $this->_id = $pId;
        }

        if( ( isset($this->_id ) ) ) {
            
            $defaults['weight'] = CRM_Core_BAO_UFGroup::getWeight( $this->_id );
          
            $params = array('id' => $this->_id);
            CRM_Core_BAO_UFGroup::retrieve($params, $defaults);
            $defaults['group'] = CRM_Utils_Array::value('limit_listings_group_id',$defaults);
            $defaults['add_contact_to_group'] = CRM_Utils_Array::value('add_to_group_id',$defaults);
            //get the uf join records for current uf group
            $ufJoinRecords = CRM_Core_BAO_UFGroup::getUFJoinRecord( $this->_id );
            foreach ($ufJoinRecords as $key => $value ) {
                $checked[$value] = 1;
            }
            $defaults['uf_group_type'] = isset($checked) ? $checked : "";            
            
            //get the uf join records for current uf group other than default modules
            $otherModules = array( );
            $otherModules = CRM_Core_BAO_UFGroup::getUFJoinRecord( $this->_id, true, true );
            if (!empty($otherModules)) {
                $otherModuleString = null;
                foreach($otherModules as $key) {
                    $otherModuleString .= " [ x ] <label>" . $key . "</label>";
                }
                $this-> assign('otherModuleString',$otherModuleString);
            }
            
            $showAdvanced = 0;
            $advFields = array('group', 'post_URL', 'cancel_URL',
                               'add_captcha', 'is_map', 'is_uf_link', 'is_edit_link',
                               'is_update_dupe', 'is_cms_user');
            foreach($advFields as $key) {
                if ( !empty($defaults[$key]) ) {
                    $showAdvanced = 1;
                    $this->_allPanes['Advanced Settings']['open'] = 'true';
                    break;
                }
            }
            
        } else {
            $defaults['is_active'     ] = 1;
            $defaults['is_map'        ] = 0;
            $defaults['is_update_dupe'] = 0;
            $defaults['uf_group_type[Profile]'] = 1;
            
        }
        // Don't assign showHide elements to template in DELETE mode (fields to be shown and hidden don't exist)
        if ( !( $this->_action & CRM_Core_Action::DELETE )&& !( $this->_action & CRM_Core_Action::DISABLE )  ) {
            $showHide->addToTemplate( );
        }
        $this->assign( 'allPanes', $this->_allPanes );
        return $defaults;
    }

    /**
     * Process the form
     *
     * @return void
     * @access public
     */
    public function postProcess( )
    {
        if( $this->_action & CRM_Core_Action::DELETE ) {
            $title = CRM_Core_BAO_UFGroup::getTitle($this->_id);        
            CRM_Core_BAO_UFGroup::del($this->_id);
            CRM_Core_Session::setStatus(ts("Your CiviCRM Profile '%1' has been deleted.", array(1 => $title)));
        } else if( $this->_action & CRM_Core_Action::DISABLE ) {
            $ufJoinParams = array('uf_group_id' => $this->_id);
            CRM_Core_BAO_UFGroup::delUFJoin($ufJoinParams);

            require_once "CRM/Core/BAO/UFGroup.php";
            CRM_Core_BAO_UFGroup::setIsActive($this->_id, 0);
        } else {
            // get the submitted form values.
            $params = $ids = array( );
            $params = $this->controller->exportValues( $this->_name );

            if ( ! array_key_exists( 'is_active', $params ) ){
                $params['is_active'] = 0;
            }

            if ( $this->_action & ( CRM_Core_Action::UPDATE) ) {
                $ids['ufgroup'] = $this->_id;
                // CRM-5284
                // lets skip trying to mess around with profile weights and allow the user to do as needed.
            } else if ( $this->_action & CRM_Core_Action::ADD ) {
                $session =& CRM_Core_Session::singleton( );
                $params['created_id']   = $session->get( 'userID' );
                $params['created_date'] = date('YmdHis');
            }
            
            // create uf group
            $ufGroup = CRM_Core_BAO_UFGroup::add($params, $ids);

            if ( CRM_Utils_Array::value( 'is_active', $params ) ) {
                //make entry in uf join table
                CRM_Core_BAO_UFGroup::createUFJoin($params, $ufGroup->id);
            } else {
                // this profile has been set to inactive, delete all corresponding UF Join's
                $ufJoinParams = array('uf_group_id' => $this->_id);
                CRM_Core_BAO_UFGroup::delUFJoin($ufJoinParams);
            }
        
            if ( $this->_action & CRM_Core_Action::UPDATE ) {
                CRM_Core_Session::setStatus(ts("Your CiviCRM Profile '%1' has been saved.", array(1 => $ufGroup->title)));
            } else {
                $url = CRM_Utils_System::url( 'civicrm/admin/uf/group/field', 'reset=1&action=add&gid=' . $ufGroup->id);
                CRM_Core_Session::setStatus(ts('Your CiviCRM Profile \'%1\' has been added. You can add fields to this profile now.',
                                               array(1 => $ufGroup->title)));
                $session =& CRM_Core_Session::singleton( );
                $session->replaceUserContext($url);
            }
        }

        // update cms integration with registration / my account
        require_once 'CRM/Utils/System.php';
        CRM_Utils_System::updateCategories( );
    }

}


