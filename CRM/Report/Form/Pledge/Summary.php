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

require_once 'CRM/Report/Form.php';

class CRM_Report_Form_Pledge_Summary extends CRM_Report_Form {

    protected $_summary = null;
    protected $_totalPaid = false;
    
    function __construct( ) {
        $this->_columns = 
            array(
                  'civicrm_contact'  =>
                  array( 'dao'       => 'CRM_Contact_DAO_Contact',
                         'fields'    =>
                         array( 'display_name' => 
                                array( 'title'     => ts( 'Contact Name' ),
                                       'required'  => true,
                                       'no_repeat' => true ),
                                ),
                         'filters'   =>             
                         array('sort_name'    => 
                               array( 'title' => ts( 'Contact Name' )  ),
                               
                               'id'           => 
                               array( 'no_display' => true ), ),
                         'grouping'  => 'contact-fields',
                         ),
			    
                  'civicrm_address' =>
                  array( 'dao'      => 'CRM_Core_DAO_Address',
                         'fields'   =>
                         array( 'street_address'    => null,
                                'city'              => null,
                                'postal_code'       => null,
                                'state_province_id' => 
                                array( 'title'      => ts( 'State/Province' ), ),
                                'country_id'        => 
                                array( 'title'      => ts( 'Country' ),  
                                       'default'    => true ), 
                                ),
                         'grouping'=> 'contact-fields',
                         ),
                  
                  'civicrm_email' => 
                  array( 'dao'    => 'CRM_Core_DAO_Email',
                         'fields' =>
                         array( 'email' =>
                                array( 'no_repeat' => true), ),
                         'grouping'=> 'contact-fields',
                         ),
                  
                  'civicrm_pledge'  =>
                  array('dao'       => 'CRM_Pledge_DAO_Pledge',
                        'fields'    =>
                        array('id'         =>
                              array( 'no_display'=> true,
                                     'required'  => true, ),
                              
                              'contact_id' =>
                              array( 'no_display'=> true,
                                     'required'  => true, ),
                              
                              'amount'     =>
                              array( 'title'     => ts('Pledge Amount'),
                                     'required'  => true,
                                     'type'      => CRM_Utils_Type::T_MONEY ),
                              
                              'frequency_unit'=>
                              array( 'title'=> ts('Frequency Unit'),),
                              
                              'installments'=>
                              array( 'title'=> ts('Installments'),),
                              
                              'start_date'  =>
                              array( 'title'=> ts('Peldge Start Date'),
                                     'type' => CRM_Utils_Type::T_DATE ),
                              
                              'pledge_create_date' =>
                              array( 'title'=> ts('Pledge Create Date'), ),                                        

                              'end_date'    =>   
                              array( 'title'=> ts('Pledge End Date'),
                                     'type' => CRM_Utils_Type::T_DATE ),
                              
                              'status_id'   =>
                              array( 'title'   => ts('Pledge Status'),
                                     'required'=>true               ),
                              
                              'total_paid'  =>
                              array( 'title'   => ts('Total Amount Paid'), ),
                              
                              ),
                        'filters'   => 
                        array(
                              'pledge_create_date' => 
                              array( 'title'        => 'Pledge Create Date',
                                     'operatorType' => CRM_Report_Form::OP_DATE ),
                              'pledge_amount'  => 
                              array( 'title'        => ts( 'Pledged Amount' ),
                                     'operatorType' => CRM_Report_Form::OP_INT ), 
                              'sid'    =>
                              array( 'name'    => 'status_id',
                                     'title'   => ts( 'Pledge Status' ),
                                     'operatorType' => CRM_Report_Form::OP_MULTISELECT ,
                                     'options' => CRM_Core_OptionGroup::values('contribution_status') ), ), 
                        ),

                  'civicrm_group' => 
                  array( 'dao'    => 'CRM_Contact_DAO_Group',
                         'alias'  => 'cgroup',
                         'filters' =>             
                         array( 'gid' => 
                                array( 'name'    => 'group_id',
                                       'title'   => ts( ' Group' ),
                                       'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                       'group'   => true,
                                       'options' => CRM_Core_PseudoConstant::group( ) ) ), ),
                  
                  
                  );
        parent::__construct( );
    }
    
    function preProcess( ) {
        parent::preProcess( );            
    }
    
    function select( ) {
        $select = array( );
        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {

                        if ( CRM_Utils_Array::value( 'total_paid', $this->_params['fields'] ) ) {
                            $this->_totalPaid = true;
                            unset( $this->_params['fields']['total_paid'] );
                        }
                        
                        // to include optional columns address and email, only if checked
                        if ( $tableName == 'civicrm_address' ) {
                            $this->_addressField = true;
                            $this->_emailField = true; 
                        } else if ( $tableName == 'civicrm_email' ) { 
                            $this->_emailField = true;  
                        }
                        
                        $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                        $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value( 'type', $field );
                        $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value( 'title', $field );
                    }
                }
            }
        }
        
        $this->_select = "SELECT DISTINCT " . implode( ', ', $select );
    }
    
    function from( ) {
        $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                      ON ({$this->_aliases['civicrm_contact']}.id = 
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

        // include address field if address column is to be included
        if ( $this->_addressField ) {  
            $this->_from .= "
                 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                           ON ({$this->_aliases['civicrm_contact']}.id = 
                               {$this->_aliases['civicrm_address']}.contact_id) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1\n";
        }
        
        // include email field if email column is to be included
        if ( $this->_emailField ) { 
            $this->_from .= "
                 LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                           ON ({$this->_aliases['civicrm_contact']}.id = 
                               {$this->_aliases['civicrm_email']}.contact_id) AND 
                               {$this->_aliases['civicrm_email']}.is_primary = 1\n";     
        }
    }

    function where( ) {
        $clauses = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('filters', $table) ) {
                foreach ( $table['filters'] as $fieldName => $field ) {
                    $clause = null;
                    if ( CRM_Utils_Array::value( 'type', $field ) & CRM_Utils_Type::T_DATE ) {
                        $relative = CRM_Utils_Array::value( "{$fieldName}_relative", $this->_params );
                        $from     = CRM_Utils_Array::value( "{$fieldName}_from"    , $this->_params );
                        $to       = CRM_Utils_Array::value( "{$fieldName}_to"      , $this->_params );
                        
                        if ( $relative || $from || $to ) {
                            $clause = $this->dateClause( $field['name'], $relative, $from, $to, $field['type'] );
                        }
                    } else {
                        $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
                        if ( $op ) {
                            $clause = 
                                $this->whereClause( $field,
                                                    $op,
                                                    CRM_Utils_Array::value( "{$fieldName}_value", 
                                                                            $this->_params ),
                                                    CRM_Utils_Array::value( "{$fieldName}_min",
                                                                            $this->_params ),
                                                    CRM_Utils_Array::value( "{$fieldName}_max",
                                                                            $this->_params ) );
                        }
                    }
                    
                    if ( ! empty( $clause ) ) {
                        $clauses[] = $clause;
                    }
                }
            }
        }    
        if ( empty( $clauses ) ) {
            $this->_where = "WHERE ({$this->_aliases['civicrm_pledge']}.is_test=0 ) ";
        } else {
            $this->_where = "WHERE  ({$this->_aliases['civicrm_pledge']}.is_test=0 )  AND 
                                      " . implode( ' AND ', $clauses );
        }

        if ( $this->_aclWhere ) {
            $this->_where .= " AND {$this->_aclWhere} ";
        } 
    }
     
    function postProcess( ) {
        
        $this->beginPostProcess( );

        // get the acl clauses built before we assemble the query
        $this->buildACLClause( $this->_aliases['civicrm_contact'] );
        $this->select ( );
        $this->from   ( null, true );
        $this->where  ( ); 
        $this->limit( );  
        
        $sql   = "{$this->_select} {$this->_from} {$this->_where} {$this->_limit}";
        
        $rows  = $payment = array();
        $count = $due = $paid = 0;
        
        $dao   = CRM_Core_DAO::executeQuery( $sql );

        // Set pager for the Main Query only which displays basic information
        $this->setPager( );
        $this->assign ( 'columnHeaders', $this->_columnHeaders );
       
        while( $dao->fetch() ){ 
            $pledgeID  = $dao->civicrm_pledge_id;
            foreach ( $this->_columnHeaders as $columnHeadersKey => $columnHeadersValue ) {
                $row = array();
                if ( property_exists( $dao, $columnHeadersKey ) ) {
                    $display[$pledgeID][$columnHeadersKey] = $dao->$columnHeadersKey;
                }
            }
            $pledgeIDArray[] = $pledgeID;
        }
        
        // Pledge- Payment Detail Headers 
        $tableHeader = array( 'scheduled_date'  => array ( 'type'  => CRM_Utils_Type::T_DATE , 
                                                           'title' => 'Payment Scheduled Date'),
                              'scheduled_amount'=> array ( 'type'  => CRM_Utils_Type::T_MONEY , 
                                                           'title' => 'Payment Amount'),  
                              'total_paid'      => array ( 'type'  => CRM_Utils_Type::T_MONEY , 
                                                           'title' => 'Total Amount Paid'),
                              'balance_due'     => array ( 'type'  => CRM_Utils_Type::T_MONEY , 
                                                           'title' => 'Balance Due') , 
                              'status_id'       => null,
                              );
        foreach ( $tableHeader as $k => $val ) {
            $this->_columnHeaders[$k] = $val;
        }

        if ( !$this->_totalPaid ){ 
            unset( $this->_columnHeaders['total_paid'] );
        }

        // To Display Payment Details of pledged amount
        // for pledge payments In Progress
        if ( !empty( $display ) ){
            $sqlPayment = "
                 SELECT min(payment.scheduled_date) as scheduled_date,
                        payment.pledge_id, 
                        payment.scheduled_amount, 
                        pledge.contact_id
              
                  FROM civicrm_pledge_payment payment 
                       LEFT JOIN civicrm_pledge pledge 
                                 ON pledge.id = payment.pledge_id
                     
                  WHERE payment.status_id = 2  

                  GROUP BY payment.pledge_id";
            
            $daoPayment = CRM_Core_DAO::executeQuery( $sqlPayment );
            
            while ( $daoPayment->fetch() ) {
                foreach ( $pledgeIDArray as $key => $val ) {
                    if ( $val == $daoPayment->pledge_id ) {

                        $display[$daoPayment->pledge_id]['scheduled_date']   = 
                            $daoPayment->scheduled_date;

                        $display[$daoPayment->pledge_id]['scheduled_amount'] = 
                            $daoPayment->scheduled_amount;
                    }
                }
            }
         
            // Do calculations for Total amount paid AND
            // Balance Due, based on Pledge Status either 
            // In Progress, Pending or Completed
            foreach ( $display as $pledgeID => $data ) {
                $count = $due = $paid = 0;

                // Get Sum of all the payments made
                $payDetailsSQL = "
                    SELECT SUM( payment.scheduled_amount ) as total_amount 
                       FROM civicrm_pledge_payment payment 
                       WHERE payment.pledge_id = {$pledgeID} AND
                             payment.status_id = 1";
                
                $totalPaidAmt = CRM_Core_DAO::singleValueQuery( $payDetailsSQL );
                
                if ( CRM_Utils_Array::value( 'civicrm_pledge_status_id', $data ) == 5 ) {
                    $due  = $data['civicrm_pledge_amount'] - $totalPaidAmt;
                    $paid = $totalPaidAmt;
                    $count++; 
                } else if ( CRM_Utils_Array::value( 'civicrm_pledge_status_id', $data ) == 2 ) {
                    $due  = $data['civicrm_pledge_amount']; 
                    $paid = 0;
                } else if ( CRM_Utils_Array::value( 'civicrm_pledge_status_id', $data ) == 1 ) {
                    $due  = 0;
                    $paid = $paid + $data['civicrm_pledge_amount'];
                }
                
                $display[$pledgeID]['total_paid' ] = $paid;
                $display[$pledgeID]['balance_due'] = $due;
            }
        }
                
        // Displaying entire data on the form
        if( ! empty( $display ) ) {
            foreach( $display as $key => $value ) {                
                $row = array( );  
                foreach ( $this->_columnHeaders as $columnKey => $columnValue ) {
                    if ( CRM_Utils_Array::value( $columnKey, $value ) ) {
                        $row[ $columnKey ] = $value [ $columnKey ];
                    }
                } 
                $rows[ ] = $row;
            }     
        }
        
        unset($this->_columnHeaders['status_id']);
        unset($this->_columnHeaders['civicrm_pledge_id']);
        unset($this->_columnHeaders['civicrm_pledge_contact_id']);
               
        $this->formatDisplay( $rows, false);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );
    }
    
    function alterDisplay( &$rows ) {
        // custom code to alter rows
        $entryFound = false;
        $checkList  =  array();
        $display_flag = $prev_cid = $cid =  0;

        foreach ( $rows as $rowNum => $row ) {
            if ( !empty($this->_noRepeats) && $this->_outputMode != 'csv' ) {
                // don't repeat contact details if its same as the previous row
                if ( array_key_exists('civicrm_pledge_contact_id', $row ) ) {
                    if ( $cid =  $row['civicrm_pledge_contact_id'] ) {
                        if ( $rowNum == 0 ) {
                            $prev_cid = $cid;
                        } else {
                            if( $prev_cid == $cid ) {
                                $display_flag = 1;
                                $prev_cid = $cid;
                            } else {
                                $display_flag = 0;
                                $prev_cid = $cid;
                            }
                        }
                        
                        if ( $display_flag ) {
                            foreach ( $row as $colName => $colVal ) {
                                if ( in_array($colName, $this->_noRepeats) ) {
                                    unset($rows[$rowNum][$colName]);          
                                }
                            }
                        }
                        $entryFound = true;
                    }
                }
            }
            
            // convert display name to links
            if ( array_key_exists('civicrm_contact_display_name', $row) && 
                 array_key_exists('civicrm_pledge_contact_id', $row) ) {
                $url = CRM_Utils_System::url( "civicrm/contact/view"  , 
                                              'reset=1&cid=' . $row['civicrm_pledge_contact_id'], 
                                              $this->_absoluteUrl );
                $rows[$rowNum]['civicrm_contact_display_name_link' ] = $url;
                $rows[$rowNum]['civicrm_contact_display_name_hover'] =  
                    ts("View Contact Summary for this Contact.");
                $entryFound = true;
            }
            
            //handle status id
            if ( array_key_exists( 'civicrm_pledge_status_id', $row ) ) {
                if ( $value = $row['civicrm_pledge_status_id'] ) {
                    $rows[$rowNum]['civicrm_pledge_status_id'] = 
                        CRM_Core_OptionGroup::getLabel( 'contribution_status', $value );
                }
                $entryFound = true;
            } 
            
            // handle state province
            if ( array_key_exists('civicrm_address_state_province_id', $row) ) {
                if ( $value = $row['civicrm_address_state_province_id'] ) {
                    $rows[$rowNum]['civicrm_address_state_province_id'] = 
                        CRM_Core_PseudoConstant::stateProvinceAbbreviation( $value, false );
                }
                $entryFound = true;
            }
            
            // handle country
            if ( array_key_exists('civicrm_address_country_id', $row) ) {
                if ( $value = $row['civicrm_address_country_id'] ) {
                    $rows[$rowNum]['civicrm_address_country_id'] = 
                        CRM_Core_PseudoConstant::country( $value, false );
                }
                $entryFound = true;
            }

            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
