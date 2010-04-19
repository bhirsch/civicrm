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
 * form helper class for an Email object
 */
class CRM_Contact_Form_Edit_Email 
{
    /**
     * build the form elements for an email object
     *
     * @param CRM_Core_Form $form       reference to the form object
     * @param array         $location   the location object to store all the form elements in
     * @param int           $locationId the locationId we are dealing with
     * @param int           $count      the number of blocks to create
     *
     * @return void
     * @access public
     * @static
     */
    static function buildQuickForm( &$form ) 
    {        
        $blockId = ( $form->get( 'Email_Block_Count' ) ) ? $form->get( 'Email_Block_Count' ) : 1;

        $form->applyFilter('__ALL__','trim');

        //Email box
        $form->addElement('text',"email[$blockId][email]", ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'));
        $form->addRule( "email[$blockId][email]", ts('Email is not valid.'), 'email' );
        if ( isset( $form->_contactType ) ) {
            //Block type
            $form->addElement('select',"email[$blockId][location_type_id]", '' , CRM_Core_PseudoConstant::locationType());

            //On-hold checkbox
            $form->addElement('advcheckbox', "email[$blockId][on_hold]",null);

            //Bulkmail checkbox
            $js = array( 'id' => "Email_".$blockId."_IsBulkmail", 'onClick' => 'singleSelect( this.id );');
            $form->addElement('advcheckbox', "email[$blockId][is_bulkmail]", null, '', $js);

            //is_Primary radio
            $js = array( 'id' => "Email_".$blockId."_IsPrimary", 'onClick' => 'singleSelect( this.id );');
            $form->addElement( 'radio', "email[$blockId][is_primary]", '', '', '1', $js );
        }
    }
}