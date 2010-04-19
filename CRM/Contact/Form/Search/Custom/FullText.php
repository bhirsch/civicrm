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

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class CRM_Contact_Form_Search_Custom_FullText
   implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;

    protected $_columns;

    protected $_text  = null;
    
    protected $_textID  = null;

    protected $_table = null;

    protected $_tableName = null;

    protected $_entityIDTableName = null;

    protected $_tableFields = null;

    protected $_limitClause = null;
    
    protected $_limitNumber = 10;

    function __construct( &$formValues ) {
        $this->_formValues =& $formValues;
       
        $this->_text   = CRM_Utils_Array::value( 'text',
                                                 $formValues );
        $this->_table = CRM_Utils_Array::value( 'table',
                                                 $formValues );

        if ( ! $this->_text ) {
            $this->_text   = CRM_Utils_Request::retrieve( 'text', 'String',
                                                          CRM_Core_DAO::$_nullObject );
            if ( $this->_text ) {
                $this->_text        = trim($this->_text);
                $formValues['text'] = $this->_text; 
            }
        }
    
        if ( ! $this->_table ) {
            $this->_table   = CRM_Utils_Request::retrieve( 'table', 'String',
                                                          CRM_Core_DAO::$_nullObject );
            if ( $this->_table ) {
                $formValues['table'] = $this->_table;
            }
        }
        

        // fix text to include wild card characters at begining and end
        if ( $this->_text ) {
            if ( is_numeric( $this->_text ) ) {
                $this->_textID = $this->_text;
            } 

            $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
            $this->_text = $strtolower( CRM_Core_DAO::escapeString( $this->_text ) );
            if ( strpos( $this->_text, '%' ) === false ) {
                $this->_text = "'%{$this->_text}%'";
            } else {
                $this->_text = "'{$this->_text}'";
            }

        } else {
            $this->_text = "'%'";
        }

        if ( ! $this->_table ) {
            $this->_limitClause = " LIMIT {$this->_limitNumber}";
        }

        $this->buildTempTable( );
        
        $this->fillTable( );

    }

    function __destruct( ) {
    }

    function buildTempTable( ) {
        $randomNum = md5( uniqid( ) );
        $this->_tableName = "civicrm_temp_custom_details_{$randomNum}";

        $this->_tableFields =
            array(
                  'id'                        => 'int unsigned NOT NULL AUTO_INCREMENT',
                  'table_name'                => 'varchar(16)',
                  'contact_id'                => 'int unsigned',
                  'display_name'              => 'varchar(128)',
                  'assignee_contact_id'       => 'int unsigned',
                  'assignee_display_name'     => 'varchar(128)',
                  'target_contact_id'         => 'int unsigned',
                  'target_display_name'       => 'varchar(128)',
                  'activity_id'               => 'int unsigned',
                  'activity_type_id'          => 'int unsigned',
                  'case_id'                   => 'int unsigned',
                  'case_start_date'           => 'datetime',
                  'case_end_date'             => 'datetime',
                  'subject'                   => 'varchar(255)',
                  'details'                   => 'varchar(255)',
                  'contribution_id'           => 'int unsigned',
                  'contribution_type'         => 'varchar(255)',
                  'contribution_page'         => 'varchar(255)',
                  'contribution_receive_date' => 'datetime',
                  'contribution_total_amount' => 'decimal(20,2)',
                  'contribution_trxn_Id'      => 'varchar(255)',
                  'contribution_source'       => 'varchar(255)',
                  'contribution_status'       => 'varchar(255)',
                  'contribution_check_number' => 'varchar(255)',
                  'participant_id'            => 'int unsigned',
                  'event_title'               => 'varchar(255)',
                  'participant_fee_level'     => 'varchar(255)',
                  'participant_fee_amount'    => 'int unsigned',
                  'participant_source'        => 'varchar(255)',
                  'participant_register_date' => 'datetime',
                  'participant_status'        => 'varchar(255)',
                  'participant_role'          => 'varchar(255)',
                  'membership_id'             => 'int unsigned',
                  'membership_fee'            => 'int unsigned',
                  'membership_type'           => 'varchar(255)',
                  'membership_start_date'     => 'datetime',
                  'membership_end_date'       => 'datetime',
                  'membership_source'         => 'varchar(255)',
                  'membership_status'         => 'varchar(255)'
                  );
                  
        $sql = "
CREATE TEMPORARY TABLE {$this->_tableName} (
";

        foreach ( $this->_tableFields as $name => $desc ) {
            $sql .= "$name $desc,\n"; 
        }

        $sql .= "
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
";
        CRM_Core_DAO::executeQuery( $sql );

        $this->_entityIDTableName = "civicrm_temp_custom_entityID_{$randomNum}";
        $sql = "
CREATE TEMPORARY TABLE {$this->_entityIDTableName} (
  id int unsigned NOT NULL AUTO_INCREMENT,
  entity_id int unsigned NOT NULL,
  
  UNIQUE INDEX unique_entity_id ( entity_id ),
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
";
        CRM_Core_DAO::executeQuery( $sql );
    }

    function fillTable( ) {
        require_once 'CRM/Core/Permission.php';
        $config =& CRM_Core_Config::singleton( );
       
        if ( ( ! $this->_table ||
               $this->_table == 'Contact') ) {
            $this->fillContact( );
        }

        if ( ( ! $this->_table ||
               $this->_table == 'Activity') && CRM_Core_Permission::check('view all activities') ) {
            $this->fillActivity( );
        }

        if ( ( ! $this->_table ||
               $this->_table == 'Case') && in_array( 'CiviCase', $config->enableComponents ) ) {
            $this->fillCase( );
        }

        if ( ( ! $this->_table ||
               $this->_table == 'Contribution') && in_array( 'CiviContribute', $config->enableComponents ) ) {
            $this->fillContribution( );
        }

        if ( ( ! $this->_table ||
               $this->_table == 'Participant') &&
             (in_array('CiviEvent', $config->enableComponents) && CRM_Core_Permission::check('view event participants')) ) {
            $this->fillParticipant( );
        }

        if ( ( ! $this->_table ||
               $this->_table == 'Membership') && in_array( 'CiviMember', $config->enableComponents ) ) {
            $this->fillMembership( );
        }

        $this->filterACLContacts( );
    }

    function filterACLContacts( ) {
        if ( CRM_Core_Permission::check( 'view all contacts' ) ) {
            return;
        }

        $session = CRM_Core_Session::singleton( );
        $contactID =  $session->get( 'userID' );
        if ( ! $contactID ) {
            $contactID = 0;
        }

        require_once 'CRM/Contact/BAO/Contact/Permission.php';
        CRM_Contact_BAO_Contact_Permission::cache( $contactID );

        $params = array( 1 => array( $contactID, 'Integer' ) );

        $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND t.contact_id = c.contact_id )
";
        CRM_Core_DAO::executeQuery( $sql, $params );

        $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.target_contact_id = c.contact_id OR t.target_contact_id IS NULL ) )
";
        CRM_Core_DAO::executeQuery( $sql, $params );

        $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.assignee_contact_id = c.contact_id OR t.assignee_contact_id IS NULL ) )
";
        CRM_Core_DAO::executeQuery( $sql, $params );
    }


    function fillCustomInfo( &$tables,
                             $extends ) {
        
        $sql = "
SELECT     cg.table_name, cf.column_name
FROM       civicrm_custom_group cg
INNER JOIN civicrm_custom_field cf ON cf.custom_group_id = cg.id
WHERE      cg.extends IN $extends
AND        cg.is_active = 1
AND        cf.is_active = 1
AND        cf.is_searchable = 1
AND        cf.html_type IN ( 'Text', 'TextArea', 'RichTextEditor' )
";

        $dao = CRM_Core_DAO::executeQuery( $sql );
        while ( $dao->fetch( ) ) {
            if ( ! array_key_exists( $dao->table_name,
                                     $tables ) ) {
                $tables[$dao->table_name] = array( 'id' => 'entity_id',
                                                   'fields' => array( ) );
            }
            $tables[$dao->table_name]['fields'][$dao->column_name] = null;
        }
    }

    function runQueries( &$tables ) {
        $sql = "TRUNCATE {$this->_entityIDTableName}";
        CRM_Core_DAO::executeQuery( $sql );

        foreach ( $tables as $tableName => $tableValues ) {
            if ( $tableName == 'sql' ) {
                foreach ( $tableValues as $sqlStatement ) {
                    $sql = "
REPLACE INTO {$this->_entityIDTableName} ( entity_id )
$sqlStatement
{$this->_limitClause}
";
                    CRM_Core_DAO::executeQuery( $sql );
                }
            } else {
                $clauses = array( );

                foreach ( $tableValues['fields'] as $fieldName => $fieldType ) {
                    if ( $fieldType == 'Int') {
                        if ( $this->_textID ) {
                            $clauses[] = "$fieldName = {$this->_textID}";
                        }
                    } else {
                        $clauses[] = "$fieldName LIKE {$this->_text}";
                    } 
                }
                
                if ( empty( $clauses ) ) {
                    continue;
                }
                
                $whereClause = implode( ' OR ', $clauses );

                //resolve conflict between entity tables.
                if ( $tableName == 'civicrm_note' && 
                     $entityTable = CRM_Utils_Array::value( 'entity_table', $tableValues ) ) {
                    $whereClause .= " AND entity_table = '{$entityTable}'";
                }

                $sql = "
REPLACE INTO {$this->_entityIDTableName} ( entity_id )
SELECT  {$tableValues['id']}
FROM    $tableName
WHERE   ( $whereClause )
AND     {$tableValues['id']} IS NOT NULL
{$this->_limitClause}
";
                CRM_Core_DAO::executeQuery( $sql );
            }
        }
    }

    function fillContactIDs( ) {
        $tables = 
            array( 'civicrm_contact' => array( 'id' => 'id',
                                               'fields' => array( 'display_name' => null,
                                                                  'nick_name'    => null ) ),
                   'civicrm_address' => array( 'id' => 'contact_id',
                                               'fields' => array( 'street_address' => null,
                                                                  'city' => null,
                                                                  'postal_code' => null ) ),
                   'civicrm_email'   => array( 'id' => 'contact_id',
                                               'fields' => array( 'email' => null ) ),
                   'civicrm_phone'   => array( 'id' => 'contact_id',
                                               'fields' => array( 'phone' => null ) ),
                   'civicrm_note'    => array( 'id'           => 'entity_id',
                                               'entity_table' => 'civicrm_contact',
                                               'fields'       => array( 'subject' => null,
                                                                        'note' => null ) ),
                   );
        
        // get the custom data info
        $this->fillCustomInfo( $tables,
                               "( 'Contact', 'Individual', 'Organization', 'Household' )" );
         
        $this->runQueries( $tables );
   }

    function fillContact( ) {

        $this->fillContactIDs( );
        
        //move data from entity table to detail table.
        $this->moveEntityToDetail( 'Contact' );
    }

    function fillActivityIDs( ) {
        $contactSQL = array( );

        $contactSQL[] = "
SELECT     ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_contact c ON ca.source_contact_id = c.id
LEFT JOIN  civicrm_email e ON e.contact_id = c.id
LEFT JOIN  civicrm_option_group og ON og.name = 'activity_type'
LEFT JOIN  civicrm_option_value ov ON ( ov.option_group_id = og.id ) 
WHERE      c.display_name LIKE {$this->_text} OR
           ( e.email LIKE {$this->_text}    AND 
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
";

        $contactSQL[] = "
SELECT     ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_target cat ON cat.activity_id = ca.id
INNER JOIN civicrm_contact c ON cat.target_contact_id = c.id
LEFT  JOIN civicrm_email e ON cat.target_contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id ) 
WHERE      c.display_name LIKE {$this->_text} OR
           ( e.email LIKE {$this->_text}    AND 
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
";

        $contactSQL[] = "
SELECT     ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_assignment caa ON caa.activity_id = ca.id
INNER JOIN civicrm_contact c ON caa.assignee_contact_id = c.id
LEFT  JOIN civicrm_email e ON caa.assignee_contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id )
WHERE      caa.activity_id = ca.id
AND        caa.assignee_contact_id = c.id
AND        c.display_name LIKE {$this->_text}  OR
           ( e.email LIKE {$this->_text} AND
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
";
        
        $tables = array( 'civicrm_activity' => array( 'id' => 'id',
                                                      'fields' => array( 'subject' => null,
                                                                         'details' => null ) ),
                         'sql'              => $contactSQL );
        
        $this->fillCustomInfo( $tables, "( 'Activity' )" );
        $this->runQueries( $tables );
    }

    function fillActivity( ) {
        
        $this->fillActivityIDs( ) ;

        //move data from entity table to detail table        
        $this->moveEntityToDetail( 'Activity' ); 
     }

    function fillCase( ) {
        $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, display_name, case_id, case_start_date, case_end_date )
SELECT    'Case', c.id, c.display_name, cc.id, DATE(cc.start_date), DATE(cc.end_date)
FROM      civicrm_case cc 
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     c.display_name LIKE {$this->_text}
{$this->_limitClause}
";

        CRM_Core_DAO::executeQuery( $sql );
        if ( $this->_textID ) { 
            $sql = "
INSERT INTO {$this->_tableName}
  ( table_name, contact_id, display_name, case_id, case_start_date, case_end_date )
SELECT    'Case', c.id, c.display_name, cc.id, DATE(cc.start_date), DATE(cc.end_date)
FROM      civicrm_case cc 
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     cc.id = {$this->_textID}
{$this->_limitClause}
    ";

            CRM_Core_DAO::executeQuery( $sql );
        }
    }
    
    function fillContribution( ) {
        
        //get contribution ids in entity table.
        $this->fillContributionIDs( ); 
        
        //move data from entity table to detail table        
        $this->moveEntityToDetail( 'Contribution' ); 
    }
    
    /**
     * get contribution ids in entity tables.
     */
    function fillContributionIDs() {
        $contactSQL = array( );
        $contactSQL[] = "
SELECT     cc.id 
FROM       civicrm_contribution cc
INNER JOIN civicrm_contact c ON cc.contact_id = c.id
WHERE      c.display_name LIKE {$this->_text}
";
        $tables = 
            array( 'civicrm_contribution' => array( 'id'     => 'id',
                                                    'fields' => array( 'source'       => null,
                                                                       'amount_level' => null,
                                                                       'trxn_Id'      => null,
                                                                       'invoice_id'   => null,
                                                                       'check_number' => ($this->_textID) ? 'Int' : null,
                                                                       'total_amount' => ($this->_textID) ? 'Int' : null,
                                                                       ) 
                                                    ),

                   'sql'                  => $contactSQL,
                                     
                   'civicrm_note'         => array( 'id'           => 'entity_id',
                                                    'entity_table' => 'civicrm_contribution',
                                                    'fields'       => array( 'subject' => null,
                                                                             'note'    => null ) )
                   );
        
        // get the custom data info
        $this->fillCustomInfo( $tables, "( 'Contribution' )" );
        $this->runQueries( $tables );
    }
    
    function fillParticipant( ) {
        //get participant ids in entity table.
        $this->fillParticipantIDs( ); 
        
        //move data from entity table to detail table        
        $this->moveEntityToDetail( 'Participant' ); 
    }
    
    /**
     * get participant ids in entity tables.
     */
    function fillParticipantIDs() {
        $contactSQL = array( );
        $contactSQL[] = "
SELECT     cp.id 
FROM       civicrm_participant cp
INNER JOIN civicrm_contact c ON cp.contact_id = c.id
WHERE      c.display_name LIKE {$this->_text}
";
        $tables = 
            array( 'civicrm_participant' => array( 'id'     => 'id',
                                                   'fields' => array( 'source'     => null,
                                                                      'fee_level'  => null,
                                                                      'fee_amount' => ($this->_textID) ? 'Int' : null,
                                                                      ) 
                                                   ),

                   'sql'                  => $contactSQL,

                   'civicrm_note'         => array( 'id'           => 'entity_id',
                                                    'entity_table' => 'civicrm_participant',
                                                    'fields'       => array( 'subject' => null,
                                                                             'note'    => null ) )
                   );
        
        // get the custom data info
        $this->fillCustomInfo( $tables, "( 'Participant' )" );
        $this->runQueries( $tables );
    }
    
    function fillMembership( ) {
        
        //get membership ids in entity table.
        $this->fillMembershipIDs( ); 
        
        //move data from entity table to detail table        
        $this->moveEntityToDetail( 'Membership' ); 
    }
    
    /**
     * get membership ids in entity tables.
     */
    function fillMembershipIDs() {
        $contactSQL = array( );
        $contactSQL[] = "
SELECT     cm.id 
FROM       civicrm_membership cm
INNER JOIN civicrm_contact c ON cm.contact_id = c.id
WHERE      c.display_name LIKE {$this->_text}
";
        $tables = 
            array( 'civicrm_membership' => array( 'id'     => 'id',
                                                  'fields' => array( 'source' => null )
                                                  ),
                   
                   'sql'                => $contactSQL
                   );
        
        // get the custom data info
        $this->fillCustomInfo( $tables, "( 'Membership' )" );
        $this->runQueries( $tables );
    }
    
    function buildForm( &$form ) {
        require_once 'CRM/Core/Permission.php';
        $config =& CRM_Core_Config::singleton( );

        $form->applyFilter('__ALL__', 'trim');
        $form->add( 'text',
                    'text',
                    ts( 'Find' ),
                    true );
        
        // also add a select box to allow the search to be constrained
        $tables = array( ''             => ts( 'All tables' ) );
        if (CRM_Core_Permission::check('view all contacts')) {
          $tables['Contact'] = ts( 'Contacts' );
        }
        if (CRM_Core_Permission::check('view all activities')) {
          $tables['Activity'] = ts( 'Activities' );
        }
        if (in_array( 'CiviCase', $config->enableComponents ) ) {
          $tables['Case'] = ts( 'Cases' );
        }
        if (in_array( 'CiviContribute', $config->enableComponents ) ) {
          $tables['Contribution'] = ts( 'Contributions' );
        }
        if (in_array('CiviEvent', $config->enableComponents) && CRM_Core_Permission::check('view event participants')) {
          $tables['Participant'] = ts( 'Participants' );
        }
        if (in_array( 'CiviMember', $config->enableComponents ) ) {
          $tables['Membership'] = ts( 'Memberships' );
        }

        $form->add( 'select',
                    'table',
                    ts( 'Tables' ),
                    $tables );
        
        /**
         * You can define a custom title for the search form
         */
         $this->setTitle( ts('Full-text Search') );
         
    }

    function &columns( ) {
        $this->_columns = array( ts('Contact Id')      => 'contact_id'    ,
                                 ts('Name')            => 'display_name'  );
        
        return $this->_columns;
    }
    
    function summary( ) {
        $summary = array( 'Contact'      => array( ),
                          'Activity'     => array( ),
                          'Case'         => array( ),
                          'Contribution' => array( ),
                          'Participant'  => array( ),
                          'Membership'   => array( )
                        );
        
        
        // now iterate through the table and add entries to the relevant section
        $sql = "SELECT * FROM {$this->_tableName}";
        $dao = CRM_Core_DAO::executeQuery( $sql );
        
        $activityTypes = CRM_Core_PseudoConstant::activityType( true, true );
        while ( $dao->fetch( ) ) {
            $row = array( );
            foreach ( $this->_tableFields as $name => $dontCare ) {
                if ( $name != 'activity_type_id' ) {
                    $row[$name] = $dao->$name;
                } else {
                    $row['activity_type'] = CRM_Utils_Array::value( $dao->$name,
                                                                   $activityTypes );
                }
            }
            $summary[$dao->table_name][] = $row;
        }
        
        if ( ! $this->_table ) {
            $summary['addShowAllLink'] = true;
        }

        return $summary;
    }

    function count( ) {
        return CRM_Core_DAO::singleValueQuery( "SELECT count(id) FROM {$this->_tableName}" );
    }

    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) {
        return CRM_Core_DAO::singleValueQuery( "SELECT contact_id FROM {$this->_tableName}" );
    }

    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false ) {
        return "
SELECT 
  contact_a.contact_id   as contact_id  ,
  contact_a.display_name as display_name
FROM
  {$this->_tableName} contact_a
";
    }
    
    function from( ) {
        return null;
    }

    function where( $includeContactIDs = false ) {
        return null;
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom/FullText.tpl';
    }

    function setDefaultValues( ) {
        return array( );
    }

    function alterRow( &$row ) {
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        }
    }
    
    /**
     * get entity id retrieve related data from db and move all data to detail table.
     *
     */
    function moveEntityToDetail( $tableName ) {
        $sql = null;
        switch ( $tableName ) {
        case 'Contact':
            $sql = "
INSERT INTO {$this->_tableName}
( contact_id, display_name, table_name )
SELECT     c.id, c.display_name, 'Contact'
  FROM     civicrm_contact c
INNER JOIN {$this->_entityIDTableName} ct ON c.id = ct.entity_id
{$this->_limitClause}
";
            break;
            
        case 'Activity':
            $sql = "
INSERT INTO {$this->_tableName}
( table_name, activity_id, subject, details, contact_id, display_name, assignee_contact_id, assignee_display_name, target_contact_id, 
target_display_name, activity_type_id, case_id )
SELECT    'Activity', ca.id, substr(ca.subject, 1, 50), substr(ca.details, 1, 250),
           c1.id, c1.display_name,
           c2.id, c2.display_name,
           c3.id, c3.display_name,
           ca.activity_type_id,
           cca.case_id
FROM       {$this->_entityIDTableName} eid
INNER JOIN civicrm_activity ca ON ca.id = eid.entity_id
LEFT JOIN  civicrm_contact c1 ON ca.source_contact_id = c1.id
LEFT JOIN  civicrm_activity_assignment caa ON caa.activity_id = ca.id
LEFT JOIN  civicrm_contact c2 ON caa.assignee_contact_id = c2.id
LEFT JOIN  civicrm_activity_target cat ON cat.activity_id = ca.id
LEFT JOIN  civicrm_contact c3 ON cat.target_contact_id = c3.id
LEFT JOIN  civicrm_case_activity cca ON cca.activity_id = ca.id
{$this->_limitClause}
";   
            break;

        case 'Contribution':
            $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, display_name, contribution_id, contribution_type, contribution_page, contribution_receive_date, 
  contribution_total_amount, contribution_trxn_Id, contribution_source, contribution_status, contribution_check_number )
   SELECT  'Contribution', c.id, c.display_name, cc.id, cct.name, ccp.title, cc.receive_date, 
           cc.total_amount, cc.trxn_id, cc.source, contribution_status.label, cc.check_number 
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_contribution cc ON cc.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cc.contact_id = c.id
LEFT JOIN  civicrm_contribution_type cct ON cct.id = cc.contribution_type_id
LEFT JOIN  civicrm_contribution_page ccp ON ccp.id = cc.contribution_page_id 
LEFT JOIN  civicrm_option_group option_group_contributionStatus ON option_group_contributionStatus.name = 'contribution_status'
LEFT JOIN  civicrm_option_value contribution_status ON 
( contribution_status.option_group_id = option_group_contributionStatus.id AND contribution_status.value = cc.contribution_status_id )
{$this->_limitClause}
";
            break;
                        
        case 'Participant':
            $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, display_name, participant_id, event_title, participant_fee_level, participant_fee_amount, 
participant_register_date, participant_source, participant_status, participant_role )
   SELECT  'Participant', c.id, c.display_name, cp.id, ce.title, cp.fee_level, cp.fee_amount, cp.register_date, cp.source, 
           participantStatus.label, participant_role.label 
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_participant cp ON cp.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cp.contact_id = c.id
LEFT JOIN  civicrm_event ce ON ce.id = cp.event_id
LEFT JOIN  civicrm_participant_status_type participantStatus ON participantStatus.id = cp.status_id
LEFT JOIN  civicrm_option_group option_group_participantRole ON option_group_participantRole.name = 'participant_role'
LEFT JOIN  civicrm_option_value participant_role 
           ON ( participant_role.option_group_id = option_group_participantRole.id AND participant_role.value = cp.role_id )
{$this->_limitClause}
";
            break;

        case 'Membership':
            $sql = " 
INSERT INTO {$this->_tableName}
( table_name, contact_id, display_name, membership_id, membership_type, membership_fee, membership_start_date, 
membership_end_date, membership_source, membership_status )
   SELECT  'Membership', c.id, c.display_name, cm.id, cmt.name, cc.total_amount, cm.start_date, cm.end_date, cm.source, cms.name 
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_membership cm ON cm.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cm.contact_id = c.id
LEFT JOIN  civicrm_membership_type cmt ON cmt.id = cm.membership_type_id
LEFT JOIN  civicrm_membership_payment cmp ON cmp.membership_id = cm.id
LEFT JOIN  civicrm_contribution cc ON cc.id = cmp.contribution_id
LEFT JOIN  civicrm_membership_status cms ON cms.id = cm.status_id
{$this->_limitClause}
"; 
            break;
        }

        if ( $sql ) {
            CRM_Core_DAO::executeQuery( $sql );
        }
    }


}


