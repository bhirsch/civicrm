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

require_once 'CRM/Core/Selector/Base.php';
require_once 'CRM/Core/Selector/API.php';

require_once 'CRM/Utils/Pager.php';
require_once 'CRM/Utils/Sort.php';

require_once 'CRM/Contact/BAO/Query.php';

/**
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Event_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API 
{
    /**
     * This defines two actions- View and Edit.
     *
     * @var array
     * @static
     */
    static $_links = null;

    /**
     * we use desc to remind us what that column is, name is used in the tpl
     *
     * @var array
     * @static
     */
    static $_columnHeaders;

    /**
     * Properties of contact we're interested in displaying
     * @var array
     * @static
     */
    static $_properties = array( 'contact_id', 
                                 'contact_type',
                                 'sort_name',
                                 'event_id',
                                 'participant_status_id',
                                 'event_title',
                                 'participant_fee_level',
                                 'participant_id',
                                 'event_start_date',
                                 'event_end_date',
                                 'event_type_id',
                                 'modified_date',
                                 'participant_is_test',
                                 'participant_role_id',
                                 'participant_register_date',
                                 'participant_fee_amount',
                                 'participant_fee_currency'
                                 );

    /** 
     * are we restricting ourselves to a single contact 
     * 
     * @access protected   
     * @var boolean   
     */   
    protected $_single = false;

    /**  
     * are we restricting ourselves to a single contact  
     *  
     * @access protected    
     * @var boolean    
     */    
    protected $_limit = null;

    /**
     * what context are we being invoked from
     *   
     * @access protected     
     * @var string
     */     
    protected $_context = null;

    /**
     * queryParams is the array returned by exportValues called on
     * the HTML_QuickForm_Controller for that page.
     *
     * @var array
     * @access protected
     */
    public $_queryParams;

    /**
     * represent the type of selector
     *
     * @var int
     * @access protected
     */
    protected $_action;

    /** 
     * The additional clause that we restrict the search with 
     * 
     * @var string 
     */ 
    protected $_eventClause = null;

    /** 
     * The query object
     * 
     * @var string 
     */ 
    protected $_query;

    /**
     * Class constructor
     *
     * @param array   $queryParams array of parameters for query
     * @param int     $action - action of search basic or advanced.
     * @param string  $eventClause if the caller wants to further restrict the search (used in participations)
     * @param boolean $single are we dealing only with one contact?
     * @param int     $limit  how many participations do we want returned
     *
     * @return CRM_Contact_Selector
     * @access public
     */
    function __construct(&$queryParams,
                         $action = CRM_Core_Action::NONE,
                         $eventClause = null,
                         $single = false,
                         $limit = null,
                         $context = 'search' ) 
    {
        // submitted form values
        $this->_queryParams =& $queryParams;

        $this->_single  = $single;
        $this->_limit   = $limit;
        $this->_context = $context;

        $this->_eventClause = $eventClause;

        // type of selector
        $this->_action = $action;

        $this->_query =& new CRM_Contact_BAO_Query( $this->_queryParams, null, null, false, false,
                                                    CRM_Contact_BAO_Query::MODE_EVENT );
    }//end of constructor


    /**
     * This method returns the links that are given for each search row.
     * currently the links added for each row are 
     * 
     * - View
     * - Edit
     *
     * @return array
     * @access public
     *
     */
    static function &links()
    {
        if (!(self::$_links)) {
            self::$_links = array(
                                  CRM_Core_Action::VIEW   => array(
                                                                   'name'     => ts('View'),
                                                                   'url'      => 'civicrm/contact/view/participant',
                                                                   'qs'       => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=event',
                                                                   'title'    => ts('View Participation'),
                                                                   ),
                                  CRM_Core_Action::UPDATE => array(
                                                                   'name'     => ts('Edit'),
                                                                   'url'      => 'civicrm/contact/view/participant',
                                                                   'qs'       => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%',
                                                                   'title'    => ts('Edit Participation'),
                                                                  ),
                                  CRM_Core_Action::DELETE => array(
                                                                   'name'     => ts('Delete'),
                                                                   'url'      => 'civicrm/contact/view/participant',
                                                                   'qs'       => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%',
                                                                   'title'    => ts('Delete Participation'),
                                                                  ),
                                  );
        }
        return self::$_links;
    } //end of function


    /**
     * getter for array of the parameters required for creating pager.
     *
     * @param 
     * @access public
     */
    function getPagerParams( $action, &$params ) 
    {
        $params['status']       = ts('Event') . ' %%StatusMessage%%';
        $params['csvString']    = null;
        if ( $this->_limit ) {
            $params['rowCount']     = $this->_limit;
        } else {
            $params['rowCount']     = CRM_Utils_Pager::ROWCOUNT;
        }

        $params['buttonTop']    = 'PagerTopButton';
        $params['buttonBottom'] = 'PagerBottomButton';
    } //end of function

    /**
     * Returns total number of rows for the query.
     *
     * @param 
     * @return int Total number of rows 
     * @access public
     */
    function getTotalCount($action)
    {
        return $this->_query->searchQuery( 0, 0, null,
                                           true, false, 
                                           false, false, 
                                           false, 
                                           $this->_eventClause );
    }
    
    /**
     * returns all the rows in the given offset and rowCount
     *
     * @param enum   $action   the action being performed
     * @param int    $offset   the row number to start from
     * @param int    $rowCount the number of rows to return
     * @param string $sort     the sql string that describes the sort order
     * @param enum   $output   what should the result set include (web/email/csv)
     *
     * @return array  rows in the given offset and rowCount
     */
     function &getRows( $action, $offset, $rowCount, $sort, $output = null ) 
     {
         $result = $this->_query->searchQuery( $offset, $rowCount, $sort,
                                               false, false, 
                                               false, false, 
                                               false, 
                                               $this->_eventClause );
         // process the result of the query
         $rows = array( );
         
         //lets handle view, edit and delete separately. CRM-4418 
         $permissions = array( CRM_Core_Permission::VIEW );
         if ( CRM_Core_Permission::check( 'edit event participants' ) ) {
             $permissions[] = CRM_Core_Permission::EDIT;
         }
         if ( CRM_Core_Permission::check( 'delete in CiviEvent' ) ) {
             $permissions[] = CRM_Core_Permission::DELETE; 
         }
         $mask = CRM_Core_Action::mask( $permissions );
         
         require_once 'CRM/Event/BAO/Event.php';
         require_once 'CRM/Event/PseudoConstant.php';
         $statusTypes   = CRM_Event_PseudoConstant::participantStatus();
         $statusClasses = CRM_Event_PseudoConstant::participantStatusClass();

         
         while ( $result->fetch( ) ) {
             $row = array();
             // the columns we are interested in
             foreach (self::$_properties as $property) {
                 if ( isset( $result->$property ) ) {
                     $row[$property] = $result->$property;
                 }
             }
             
             // gross hack to show extra information for pending status
             $statusClass = null;
             if( $statusId   = array_search( $row['participant_status_id'], $statusTypes ) ) {
                $statusClass = $statusClasses[$statusId];
             }

             $extraInfo = array();
             $row['showConfirmUrl'] = false;
             if ($statusClass == 'Pending') {
                 $row['showConfirmUrl'] = true;
             }             
             if (CRM_Utils_Array::value('participant_is_test', $row)) $extraInfo[] = ts('test');

             if ($extraInfo) $row['participant_status_id'] .= ' (' . implode(', ', $extraInfo) . ')';

             $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->participant_id;
             
             $row['action']   = CRM_Core_Action::formLink( self::links(), $mask,
                                                           array( 'id'  => $result->participant_id,
                                                                  'cid' => $result->contact_id,
                                                                  'cxt' => $this->_context ) );

             require_once( 'CRM/Contact/BAO/Contact/Utils.php' );

             $row['contact_type' ] = 
                 CRM_Contact_BAO_Contact_Utils::getImage( $result->contact_sub_type ? 
                                                          $result->contact_sub_type : $result->contact_type );
                         
             $row['paid'] = CRM_Event_BAO_Event::isMonetary ( $row['event_id'] );
             
             if ( CRM_Utils_Array::value( 'participant_fee_level', $row ) ) {
                 CRM_Event_BAO_Participant::fixEventLevel( $row['participant_fee_level'] );
             }
             
             if ( CRM_Event_BAO_Event::usesPriceSet( $row['event_id'] ) ) {
                 // add line item details if applicable
                 require_once 'CRM/Price/BAO/LineItem.php';
                 $lineItems[$row['participant_id']] = CRM_Price_BAO_LineItem::getLineItems( $row['participant_id'] );
             }
             $rows[] = $row;
         }
         CRM_Core_Selector_Controller::$_template->assign_by_ref( 'lineItems', $lineItems );

         return $rows;
     }
     
     /**
      * @return array              $qill         which contains an array of strings
      * @access public
      */
     
     // the current internationalisation is bad, but should more or less work
     // for most of "European" languages
     public function getQILL( )
     {
         return $this->_query->qill( );
     }
     
     /** 
      * returns the column headers as an array of tuples: 
     * (name, sortName (key to the sort array)) 
     * 
     * @param string $action the action being performed 
     * @param enum   $output what should the result set include (web/email/csv) 
     * 
     * @return array the column headers that need to be displayed 
     * @access public 
     */ 
    public function &getColumnHeaders( $action = null, $output = null ) 
    {
        if ( ! isset( self::$_columnHeaders ) ) {
            self::$_columnHeaders = array(
                                          array('name'      => ts('Event'),
                                                'sort'      => 'title',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          array(
                                                'name'      => ts('Fee Level'),
                                                'sort'      => 'fee_level',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          array(
                                                'name'      => ts('Amount'),
                                                'sort'      => 'fee_amount',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          array(
                                                'name'      => ts('Event Date(s)'),
                                                'sort'      => 'event_start_date',
                                                'direction' => CRM_Utils_Sort::DESCENDING, 
                                                ),
                                          array(
                                                'name'      => ts('Registered'),
                                                'sort'      => 'participant_register_date',
                                                'direction' => CRM_Utils_Sort::DESCENDING, 
                                                ),
                                          array(
                                                'name'      => ts('Status'),
                                                'sort'      => 'participant_status_id',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          array(
                                                'name'      => ts('Role'),
                                                'sort'      => 'participant_role_id',
                                                'direction' => CRM_Utils_Sort::DONTCARE,
                                                ),
                                          array('desc' => ts('Actions') ),
                                          );

            if ( ! $this->_single ) {
                $pre = array( 
                             array('desc' => ts('Contact Type') ), 
                             array( 
                                   'name'      => ts('Participant'), 
                                   'sort'      => 'sort_name', 
                                   'direction' => CRM_Utils_Sort::DONTCARE,
                                   )
                             );
                self::$_columnHeaders = array_merge( $pre, self::$_columnHeaders );
            }
        }
        return self::$_columnHeaders;
    }
    
    function &getQuery( )
    {
        return $this->_query;
    }

    /** 
     * name of export file. 
     * 
     * @param string $output type of output 
     * @return string name of the file 
     */ 
    function getExportFileName( $output = 'csv')
    { 
        return ts('CiviCRM Event Search'); 
    } 

}//end of class


