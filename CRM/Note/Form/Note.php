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

/**
 * This class generates form components generic to note
 * 
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Note_Form_Note extends CRM_Core_Form
{
    /**
     * The table name, used when editing/creating a note
     *
     * @var string
     */
    protected $_entityTable;

    /**
     * The table id, used when editing/creating a note
     *
     * @var int
     */
    protected $_entityId;
    
    /**
     * The note id, used when editing the note
     *
     * @var int
     */
    protected $_id;

    function preProcess( ) {
        $this->_entityTable = $this->get( 'entityTable' );
        $this->_entityId    = $this->get( 'entityId'   );
        $this->_id          = $this->get( 'id'    );
    }

    /**
     * This function sets the default values for the form. Note that in edit/view mode
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    function setDefaultValues( ) {
        $defaults = array( );

        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            if ( isset( $this->_id ) ) {
                $defaults['note'] = CRM_Core_BAO_Note::getNoteText( $this->_id );
                $defaults['subject'] = CRM_Core_BAO_Note::getNoteSubject( $this->_id );
            }
        }

        return $defaults;
    }

    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) {
       
        if ($this->_action & CRM_Core_Action::DELETE ) { 
            $this->addButtons( array(
                                     array ( 'type'      => 'next',
                                             'name'      => ts('Delete'),
                                             'isDefault' => true   ),
                                     array ( 'type'       => 'cancel',
                                             'name'      => ts('Cancel') ),
                                     )
                               );
            return;
        }

        $this->add('text', 'subject' , ts('Subject:') , array('size' => 20));
        $this->add('textarea', 'note', ts('Notes'), CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_Note', 'note' ),true );
        
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Save'),
                                         'isDefault' => true   ),
                                 array ( 'type'       => 'cancel',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
        
    }

       
    /**
     *
     * @access public
     * @return None
     */
    public function postProcess( )
    {
        // store the submitted values in an array
        $params = $this->exportValues();
        
        $session =& CRM_Core_Session::singleton( );
        $params['contact_id'  ] = $session->get( 'userID' );
        $params['entity_table'] = $this->_entityTable;
        $params['entity_id'   ] = $this->_entityId;
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            CRM_Core_BAO_Note::del( $this->_id );
            return;
        } if ( $this->_action & CRM_Core_Action::UPDATE ) {
            $params['id'] = $this->_id;
        }
        
        $ids = array();
        require_once 'CRM/Core/BAO/Note.php';
        CRM_Core_BAO_Note::add( $params, $ids );
        CRM_Core_Session::setStatus( ts('Your Note has been saved.') );

    }//end of function
}


