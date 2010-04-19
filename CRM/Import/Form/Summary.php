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
 * This class summarizes the import results
 */
class CRM_Import_Form_Summary extends CRM_Core_Form {

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess( ) {
        // set the error message path to display
        $errorFile = $this->assign('errorFile', $this->get('errorFile') );
        
        $totalRowCount     = $this->get('totalRowCount');
        $relatedCount      = $this->get('relatedCount');
        $totalRowCount    += $relatedCount;
        
        $invalidRowCount   = $this->get('invalidRowCount');
        $conflictRowCount  = $this->get('conflictRowCount');
        $duplicateRowCount = $this->get('duplicateRowCount');
        $onDuplicate       = $this->get('onDuplicate');
        $mismatchCount     = $this->get('unMatchCount');
        if ($duplicateRowCount > 0) {
            $this->set('downloadDuplicateRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=3'));
        }else if($mismatchCount) {
            $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', 'type=4'));
        } else {
            $duplicateRowCount = 0;
            $this->set('duplicateRowCount', $duplicateRowCount);
        }

        $this->assign('dupeError', false);
        
        if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
            $dupeActionString = 
                ts('These records have been updated with the imported data.');   
        } else if ($onDuplicate == CRM_Import_Parser::DUPLICATE_REPLACE) {
            $dupeActionString =
                ts('These records have been replaced with the imported data.');
        } else if ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
            $dupeActionString =
                ts('These records have been filled in with the imported data.');
        } else {
            /* Skip by default */
            $dupeActionString = 
                ts('These records have not been imported.');

            $this->assign('dupeError', true);
        }
        //now we also create relative contact in update and fill mode
        $this->set('validRowCount', $totalRowCount - $invalidRowCount -
                   $conflictRowCount - $duplicateRowCount - $mismatchCount);
        
        $this->assign('dupeActionString', $dupeActionString);
        
        $properties = array( 'totalRowCount', 'validRowCount', 'invalidRowCount', 'conflictRowCount', 'downloadConflictRecordsUrl', 'downloadErrorRecordsUrl', 'duplicateRowCount', 'downloadDuplicateRecordsUrl','downloadMismatchRecordsUrl', 'groupAdditions', 'tagAdditions', 'unMatchCount');
        foreach ( $properties as $property ) {
            $this->assign( $property, $this->get( $property ) );
        }

        $session =& CRM_Core_Session::singleton( );
        $session->pushUserContext( CRM_Utils_System::url('civicrm/import/contact', 'reset=1') );
    }

    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) {
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Done'),
                                         'isDefault' => true   ),
                                 )
                           );
    }
    
    /**
    * Clean up the import table we used
    *
    * @return None
    * @access public
    */
    public function postProcess( ) {
        $dao = new CRM_Core_DAO( );
        $db = $dao->getDatabaseConnection( );
        
        $importTableName = $this->get( 'importTableName' );
        // do a basic sanity check here
        if (strpos( $importTableName, 'civicrm_import_job_' ) === 0) {
            $query = "DROP TABLE IF EXISTS $importTableName";
            $db->query( $query );
        }
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle( ) {
        return ts('Summary');
    }

}