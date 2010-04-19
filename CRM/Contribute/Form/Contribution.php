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
require_once 'CRM/Contribute/PseudoConstant.php';
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'CRM/Contribute/Form/AdditionalInfo.php';
require_once 'CRM/Custom/Form/CustomData.php';

/**
 * This class generates form components for processing a ontribution 
 * 
 */
class CRM_Contribute_Form_Contribution extends CRM_Core_Form
{
    public $_mode;

    public $_action;
    
    public $_bltID;
    
    public $_fields;
    
    public $_paymentProcessor;
    
    public $_processors;
    
    /**
     * the id of the contribution that we are proceessing
     *
     * @var int
     * @public
     */
    public $_id;

    /**
     * the id of the premium that we are proceessing
     *
     * @var int
     * @public
     */
    public $_premiumID  = null;
    public $_productDAO = null;

    /**
     * the id of the note 
     *
     * @var int
     * @public
     */
    public $_noteID;

    /**
     * the id of the contact associated with this contribution
     *
     * @var int
     * @public
     */
    public $_contactID;
  
    /**
     * the id of the pledge payment that we are processing
     *
     * @var int
     * @public
     */
    public $_ppID;
    
    /**
     * the id of the pledge that we are processing
     *
     * @var int
     * @public
     */
    public $_pledgeID;
    
    /**
     * is this contribution associated with an online
     * financial transaction
     *
     * @var boolean
     * @public 
     */ 
    public $_online = false;
    
    /**
     * Stores all product option
     *
     * @var array
     * @public 
     */ 
    public $_options ;

    
    /**
     * stores the honor id
     *
     * @var int
     * @public 
     */ 
    public $_honorID = null ;
    
    /**
     * Store the contribution Type ID
     *
     * @var array
     */
    public $_contributionType;
    
    /**
     * The contribution values if an existing contribution
     */
    public $_values;
    
    /**
     * The pledge values if this contribution is associated with pledge 
     */
    public $_pledgeValues;
    
    public $_contributeMode = 'direct';
    
    public $_context;
    
    /*
     * Store the line items if price set used.
     */
    public $_lineItems;

    protected $_formType;
    protected $_cdType;

    /** 
     * Function to set variables up before form is built 
     *                                                           
     * @return void 
     * @access public 
     */ 
    public function preProcess()  
    {  
        //check permission for action.
        if ( !CRM_Core_Permission::checkActionPermission( 'CiviContribute', $this->_action ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );  
        }
        
        $this->_cdType = CRM_Utils_Array::value( 'type', $_GET );
        
        $this->assign('cdType', false);
        if ( $this->_cdType ) {
            $this->assign('cdType', true);
            return CRM_Custom_Form_CustomData::preProcess( $this );
        }
        
        $this->_formType = CRM_Utils_Array::value( 'formType', $_GET );

        // get price set id.
        $this->_priceSetId  = CRM_Utils_Array::value( 'priceSetId', $_GET );
        $this->set( 'priceSetId',  $this->_priceSetId );
        $this->assign( 'priceSetId', $this->_priceSetId );
        
        //get the pledge payment id
        $this->_ppID = CRM_Utils_Request::retrieve( 'ppid', 'Positive', $this );

        //get the contact id
        $this->_contactID = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this );

        //get the action.
        $this->_action = CRM_Utils_Request::retrieve( 'action', 'String', $this, false, 'add' );
        $this->assign( 'action', $this->_action );

        //get the contribution id if update
        $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        
        $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this );
        $this->assign('context', $this->_context );
        
        //set the contribution mode.
        $this->_mode = CRM_Utils_Request::retrieve( 'mode', 'String', $this );
        
        $this->assign( 'contributionMode', $this->_mode );
        
        $this->_paymentProcessor = array( 'billing_mode' => 1 );
        
        $this->assign( 'showCheckNumber', false );
        
        //ensure that processor has a valid config
        //only valid processors get display to user  
        if ( $this->_mode ) {
            $validProcessors = array( );
            $processors = CRM_Core_PseudoConstant::paymentProcessor( false, false, "billing_mode IN ( 1, 3 )" );
            foreach ( $processors as $ppID => $label ) {
                require_once 'CRM/Core/BAO/PaymentProcessor.php';
                require_once 'CRM/Core/Payment.php';
                $paymentProcessor =& CRM_Core_BAO_PaymentProcessor::getPayment( $ppID, $this->_mode );
                if ( $paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name'] ) {
                    continue;
                } else if ( $paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live' ) {
                    continue;
                } else {
                    $paymentObject =& CRM_Core_Payment::singleton( $this->_mode, 'Contribute', $paymentProcessor, $this );
                    $error = $paymentObject->checkConfig( );
                    if ( empty( $error ) ) {
                        $validProcessors[$ppID] = $label;
                    }
                    $paymentObject = null;
                }
            }
            if ( empty( $validProcessors )  ) {
                CRM_Core_Error::fatal( ts( 'You will need to configure the %1 settings for your Payment Processor before you can submit credit card transactions.', array( 1 => $this->_mode ) ) );
            } else {
                $this->_processors = $validProcessors;  
            }
        }
        
        // this required to show billing block    
        $this->assign_by_ref( 'paymentProcessor', $paymentProcessor );
        $this->assign( 'hidePayPalExpress', true );           
        
        if ( $this->_contactID ) {
            require_once 'CRM/Contact/BAO/Contact/Location.php';
            list( $this->userDisplayName, 
                $this->userEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $this->_contactID );
            $this->assign( 'displayName', $this->userDisplayName );
        }
        
        // also check for billing information
        // get the billing location type
        $locationTypes =& CRM_Core_PseudoConstant::locationType( );
        $this->_bltID = array_search( 'Billing',  $locationTypes );
        if ( ! $this->_bltID ) {
            CRM_Core_Error::fatal( ts( 'Please set a location type of %1', array( 1 => 'Billing' ) ) );
        }
        $this->set   ( 'bltID', $this->_bltID );
        $this->assign( 'bltID', $this->_bltID );
        
        $this->_fields = array( );
        
        require_once 'CRM/Core/Payment.php';
        require_once 'CRM/Core/Payment/Form.php';
        // payment fields are depending on payment type
        if ( $this->_processors['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT ) {
            CRM_Core_Payment_Form::setDirectDebitFields( $this );
        } else {
            CRM_Core_Payment_Form::setCreditCardFields( $this );
        }
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return;
        }
        
        $config =& CRM_Core_Config::singleton( );
        if ( in_array("CiviPledge", $config->enableComponents) &&
             ! $this->_formType ) {
    
            //get the payment values associated with given pledge payment id OR check for payments due. 
            $this->_pledgeValues = array( );
            if ( $this->_ppID ) {
                $payParams = array( 'id' => $this->_ppID );
                require_once "CRM/Pledge/BAO/Payment.php";
                CRM_Pledge_BAO_Payment::retrieve( $payParams, $this->_pledgeValues['pledgePayment'] );
                $this->_pledgeID = CRM_Utils_Array::value( 'pledge_id', $this->_pledgeValues['pledgePayment'] );
                $paymentStatusID = CRM_Utils_Array::value( 'status_id', $this->_pledgeValues['pledgePayment'] );
                $this->_id = CRM_Utils_Array::value( 'contribution_id', $this->_pledgeValues['pledgePayment'] );
        
                //get all status
                $allStatus = CRM_Contribute_PseudoConstant::contributionStatus( );
                if ( !( $paymentStatusID == array_search( 'Pending', $allStatus ) ||
                        $paymentStatusID == array_search( 'Overdue', $allStatus ) ) ) {
                    CRM_Core_Error::fatal( ts( "Pledge payment status should be 'Pending' or  'Overdue'.") );
                }
        
                //get the pledge values associated with given pledge payment.
                require_once 'CRM/Pledge/BAO/Pledge.php';
                $ids = array( );
                $pledgeParams = array( 'id' => $this->_pledgeID );
                CRM_Pledge_BAO_Pledge::getValues( $pledgeParams, $this->_pledgeValues, $ids );
                $this->assign('ppID', $this->_ppID );
            } else {
                // Not making a pledge payment, so check if pledge payment(s) are due for this contact so we can alert the user. CRM-5206
                if (isset( $this->_contactID )) {
                    require_once "CRM/Pledge/BAO/Pledge.php";
                    $contactPledges = array();
                    $contactPledges = CRM_Pledge_BAO_Pledge::getContactPledges($this->_contactID);
    
                    if ( ! empty( $contactPledges ) ) {
                        $payments = null;
                        $paymentsDue = null;
                        $multipleDue = false;
                        require_once "CRM/Pledge/BAO/Payment.php";
                        foreach ( $contactPledges as $key => $pledgeId ) {
                            $payments = CRM_Pledge_BAO_Payment::getOldestPledgePayment( $pledgeId );
                            if ( $payments ) {
                                if ( $paymentsDue ) {
                                    $multipleDue = true;
                                    break;
                                } else {
                                    $paymentsDue = $payments;
                                }                         
                            }
                        }
                        if ( $multipleDue ) {
                            // Show link to pledge tab since more than one pledge has a payment due
                            $pledgeTab = CRM_Utils_System::url( 'civicrm/contact/view',
                                                          "reset=1&force=1&cid={$this->_contactID}&selectedChild=pledge" );
                            CRM_Core_Session::setStatus( ts('This contact has pending or overdue pledge payments. <a href="%1">Click here to view their Pledges tab</a> and verify whether this contribution should be applied as a pledge payment.', array( 1 => $pledgeTab ) ) );
                        } else if ( $paymentsDue ) {
                            // Show user link to oldest Pending or Overdue pledge payment
                            require_once 'CRM/Utils/Date.php';
                            require_once 'CRM/Utils/Money.php';
                            $ppAmountDue = CRM_Utils_Money::format($payments['amount']);
                            $ppSchedDate = CRM_Utils_Date::customFormat( CRM_Core_DAO::getFieldValue( 'CRM_Pledge_DAO_Payment', $payments['id'], 'scheduled_date' ) );
                            if ( $this->_mode ) {
                                $ppUrl = CRM_Utils_System::url( 'civicrm/contact/view/contribution',
                                                         "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge&mode=live" );
                            } else {
                                $ppUrl = CRM_Utils_System::url( 'civicrm/contact/view/contribution',
                                                         "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge" );
                            }
                            CRM_Core_Session::setStatus( ts('This contact has a pending or overdue pledge payment of %2 which is scheduled for %3. <a href="%1">Click here to apply this contribution as a pledge payment<a/>.', array( 1 => $ppUrl, 2 => $ppAmountDue, 3 => $ppSchedDate ) ) );
                        }                    
                    }
            
                }
            }
        }
        
        $this->_values = array( );
        
        // current contribution id
        if ( $this->_id ) {
            $this->_online = CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_FinancialTrxn',
                                                          $this->_id, 'id', 'contribution_id' );
            if ( $this->_online ) {
                $this->assign('isOnline', true );
            }
            
            //to get Premium id
            $sql = "
SELECT *
FROM   civicrm_contribution_product
WHERE  contribution_id = {$this->_id}
";
            $dao = CRM_Core_DAO::executeQuery( $sql,
                                               CRM_Core_DAO::$_nullArray );
            if ( $dao->fetch( ) ) {
                $this->_premiumID  = $dao->id;
                $this->_productDAO = $dao;
            }
            $dao->free( );

            $ids    = array( );
            $params = array( 'id' => $this->_id );
            require_once "CRM/Contribute/BAO/Contribution.php";
            CRM_Contribute_BAO_Contribution::getValues( $params, $this->_values, $ids );
            
            //unset the honor type id:when delete the honor_contact_id
            //and edit the contribution, honoree infomation pane open
            //since honor_type_id is present
            if ( ! CRM_Utils_Array::value( 'honor_contact_id', $this->_values ) ) {
                unset( $this->_values['honor_type_id'] );
            }
            //to get note id 
            require_once 'CRM/Core/BAO/Note.php';
            $daoNote = & new CRM_Core_BAO_Note();
            $daoNote->entity_table = 'civicrm_contribution';
            $daoNote->entity_id = $this->_id;
            if ( $daoNote->find(true) ) {
                $this->_noteID = $daoNote->id;
                $this->_values['note'] = $daoNote->note;
            }
            
            $this->_contributionType = $this->_values['contribution_type_id'];
            
            $csParams = array( 'contribution_id' => $this->_id );
            $softCredit = CRM_Contribute_BAO_Contribution::getSoftContribution( $csParams, true );
           
            if ( CRM_Utils_Array::value( 'soft_credit_to', $softCredit ) ) {
                $softCredit['sort_name'] = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', 
                                                                        $softCredit['soft_credit_to'], 'sort_name' );
                
            }
            $this->_values['soft_credit_to' ] = CRM_Utils_Array::value( 'sort_name',      $softCredit);
            $this->_values['softID'         ] = CRM_Utils_Array::value( 'soft_credit_id', $softCredit);
            $this->_values['soft_contact_id'] = CRM_Utils_Array::value( 'soft_credit_to', $softCredit);
            
            $this->_values['pcp_made_through_id']  = CRM_Utils_Array::value( 'pcp_id',              $softCredit);
            $this->_values['pcp_display_in_roll' ] = CRM_Utils_Array::value( 'pcp_display_in_roll', $softCredit);
            $this->_values['pcp_roll_nickname' ]   = CRM_Utils_Array::value( 'pcp_roll_nickname',   $softCredit);
            $this->_values['pcp_personal_note' ]   = CRM_Utils_Array::value( 'pcp_personal_note',   $softCredit);
            
            //display check number field only if its having value or its offline mode.
            if ( CRM_Utils_Array::value( 'payment_instrument_id', $this->_values ) == CRM_Core_OptionGroup::getValue( 'payment_instrument', 'Check', 'name' ) 
                 || CRM_Utils_Array::value( 'check_number', $this->_values ) ) {
                $this->assign( 'showCheckNumber', true );  
            }
        }
        
        // when custom data is included in this page
        if ( CRM_Utils_Array::value( "hidden_custom", $_POST ) ) {
            $this->set('type',     'Contribution');
            $this->set('subType',  CRM_Utils_Array::value( 'contribution_type_id', $_POST ) );
            $this->set('entityId', $this->_id );

            CRM_Custom_Form_Customdata::preProcess( $this );
            CRM_Custom_Form_Customdata::buildQuickForm( $this );
            CRM_Custom_Form_Customdata::setDefaultValues( $this );
        }
        
        require_once 'CRM/Price/BAO/Set.php';
        $this->_lineItems = array( );
        if ( $this->_id  && 
             $priceSetId = CRM_Price_BAO_Set::getFor( 'civicrm_contribution', $this->_id ) ) {
            $this->_priceSetId = $priceSetId;
            require_once 'CRM/Price/BAO/LineItem.php';
            $this->_lineItems[] = CRM_Price_BAO_LineItem::getLineItems( $this->_id, 'contribution' );
        }
        $this->assign( 'lineItem', empty( $this->_lineItems ) ? false : $this->_lineItems );
    }

    function setDefaultValues( ) 
    {
        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::setDefaultValues( $this );
        }
       
        $defaults = $this->_values;

        //set defaults for pledge payment.
        if ( $this->_ppID ) {
            $defaults['total_amount'] = CRM_Utils_Array::value( 'scheduled_amount', $this->_pledgeValues['pledgePayment'] );
            $defaults['honor_type_id'] = CRM_Utils_Array::value( 'honor_type_id', $this->_pledgeValues );
            $defaults['honor_contact_id'] = CRM_Utils_Array::value( 'honor_contact_id', $this->_pledgeValues );
            $defaults['contribution_type_id'] = CRM_Utils_Array::value( 'contribution_type_id', $this->_pledgeValues );
        }
        
        $fields   = array( );
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return $defaults;
        }
        
        if ( $this->_mode ) {
            $billingFields = array( );
            foreach ( $this->_fields as $name => $dontCare ) {
                if ( strpos( $name, 'billing_' ) === 0 ) {
                    $name = $idName = substr( $name, 8 ); 
                    if ( in_array( $name, array( "state_province_id-$this->_bltID", "country_id-$this->_bltID" ) ) ) {
                        $name = str_replace( '_id', '', $name );
                    }
                    $billingFields[$name] = "billing_" . $idName;
                }
                $fields[$name] = 1;
            }
            
            require_once "CRM/Core/BAO/UFGroup.php";
            if ( $this->_contactID ) {
                CRM_Core_BAO_UFGroup::setProfileDefaults( $this->_contactID, $fields, $defaults  );
            }
            foreach ( $billingFields as $name => $billingName ) {
                $defaults[$billingName] = $defaults[$name];
            }
        }
        
        if ( $this->_id ) {
            $this->_contactID = $defaults['contact_id'];
        } else {
            list( $defaults['receive_date'] ) = CRM_Utils_Date::setDateDefaults( );
        }

        require_once 'CRM/Utils/Money.php';
        // fix the display of the monetary value, CRM-4038
        if (isset($defaults['total_amount'])) {
            $defaults['total_amount'] = CRM_Utils_Money::format($defaults['total_amount'], null, '%a');
        }
        
        if (isset($defaults['non_deductible_amount'])) {
            $defaults['non_deductible_amount'] = CRM_Utils_Money::format($defaults['non_deductible_amount'], null, '%a');
        }
        
        if (isset($defaults['fee_amount'])) {
            $defaults['fee_amount'] = CRM_Utils_Money::format($defaults['fee_amount'], null, '%a');
        }
        
        if (isset($defaults['net_amount'])) {
            $defaults['net_amount'] = CRM_Utils_Money::format($defaults['net_amount'], null, '%a');
        }

        if ($this->_contributionType) {
            $defaults['contribution_type_id'] = $this->_contributionType;
        }

        if (  CRM_Utils_Array::value('is_test',$defaults) ){
            $this->assign( "is_test" , true);
        } 

        if (isset ( $defaults["honor_contact_id"] ) ) {
            $honorDefault   = array();
            $ids            = array();
            $this->_honorID = $defaults["honor_contact_id"];
            $honorType      = CRM_Core_PseudoConstant::honor( );   
            $idParams       = array( 'id' => $defaults["honor_contact_id"], 'contact_id' => $defaults["honor_contact_id"] );
            CRM_Contact_BAO_Contact::retrieve( $idParams, $honorDefault, $ids );
            $defaults["honor_prefix_id"]  = $honorDefault["prefix_id"];
            $defaults["honor_first_name"] = CRM_Utils_Array::value("first_name", $honorDefault);
            $defaults["honor_last_name"]  = CRM_Utils_Array::value("last_name",  $honorDefault);
            $defaults["honor_email"]      = CRM_Utils_Array::value("email",      $honorDefault['email'][1]);
            $defaults["honor_type"]       = $honorType[$defaults["honor_type_id"]];
        }
        
        $this->assign('showOption',true);
        // for Premium section
        if( $this->_premiumID ) {
            $this->assign('showOption',false);
            $options = isset($this->_options[$this->_productDAO->product_id]) ? $this->_options[$this->_productDAO->product_id] : "";
            if ( ! $options ) {
                $this->assign('showOption',true);
            }
            $options_key = CRM_Utils_Array::key($this->_productDAO->product_option,$options);
            if( $options_key) {
                $defaults['product_name']   = array ( $this->_productDAO->product_id , trim($options_key) );
            } else {
                $defaults['product_name']   = array ( $this->_productDAO->product_id);
            }
            list( $defaults['fulfilled_date'] ) = CRM_Utils_Date::setDateDefaults( $this->_productDAO->fulfilled_date );
        }
        
        if ( isset($this->userEmail) ) {
            $this->assign( 'email', $this->userEmail );
        }
        
        if ( CRM_Utils_Array::value( 'is_pay_later',$defaults ) ) {
            $this->assign( 'is_pay_later', true ); 
        }
        $this->assign( 'contribution_status_id', CRM_Utils_Array::value( 'contribution_status_id',$defaults ) );
        
        $dates = array( 'receive_date', 'receipt_date', 'cancel_date', 'thankyou_date' );
        foreach( $dates as $key ) {
            if ( CRM_Utils_Array::value( $key, $defaults ) ) {
                list( $defaults[$key] ) = CRM_Utils_Date::setDateDefaults( CRM_Utils_Array::value( $key, $defaults ) );
            }
        }

        $this->assign( 'receive_date', CRM_Utils_Array::value( 'receive_date', $defaults ) );
        $this->assign( 'currency', CRM_Utils_Array::value( 'currency', $defaults ) );
        $this->assign( 'totalAmount', CRM_Utils_Array::value( 'total_amount', $defaults ) );

        return $defaults;
    }
    
    /** 
     * Function to build the form 
     * 
     * @return None 
     * @access public 
     */ 
    public function buildQuickForm( )  
    {           
        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::buildQuickForm( $this );
        }
        
        // build price set form.
        $buildPriceSet = false;
        if ( empty( $this->_lineItems ) && 
             ($this->_priceSetId || CRM_Utils_Array::value( 'price_set_id', $_POST ) ) ) {
            $buildPriceSet = true;
            $getOnlyPriceSetElements = true;
            if ( !$this->_priceSetId ) { 
                $this->_priceSetId = $_POST['price_set_id'];
                $getOnlyPriceSetElements = false;
            }
            
            $this->set( 'priceSetId', $this->_priceSetId );
            require_once 'CRM/Price/BAO/Set.php';
            CRM_Price_BAO_Set::buildPriceSet( $this );
            
            // get only price set form elements.
            if ( $getOnlyPriceSetElements ) return;
        }
        // use to build form during form rule.
        $this->assign( 'buildPriceSet', $buildPriceSet );
        
        $showAdditionalInfo = false;
        
        require_once 'CRM/Contribute/Form/AdditionalInfo.php';
        
        $defaults = $this->_values;
        $additionalDetailFields = array( 'note', 'thankyou_date', 'invoice_id', 'non_deductible_amount', 'fee_amount', 'net_amount');
        foreach ( $additionalDetailFields as $key ) {
            if ( ! empty( $defaults[$key] ) ) {
                $defaults['hidden_AdditionalDetail'] = 1;
                break;
            }
        }
        
        $honorFields = array('honor_type_id', 'honor_prefix_id', 'honor_first_name', 
                             'honor_lastname','honor_email');
        foreach ( $honorFields as $key ) {
            if ( ! empty( $defaults[$key] ) ) {
                $defaults['hidden_Honoree'] = 1;
                break;
            }
        }
        
        //check for honoree pane.
        if ( $this->_ppID  && CRM_Utils_Array::value( 'honor_contact_id', $this->_pledgeValues ) ) {
            $defaults['hidden_Honoree'] = 1;
        }
        
        if ( $this->_productDAO ) {
            if ( $this->_productDAO->product_id ) {
                $defaults['hidden_Premium'] = 1;
            }
        }
        
        if ( $this->_noteID &&
             isset( $this->_values['note'] ) ) {
            $defaults['hidden_AdditionalDetail'] = 1;
        }
        
        $paneNames =  array ( 'Additional Details'  => 'AdditionalDetail',
                              'Honoree Information' => 'Honoree' 
                              );
        
        //Add Premium pane only if Premium is exists.
        require_once 'CRM/Contribute/DAO/Product.php';
        $dao = & new CRM_Contribute_DAO_Product();
        $dao->is_active = 1;
        
        if ( $dao->find( true ) ) {
            $paneNames['Premium Information'] = 'Premium';
        }

        $ccPane = null;
        if ( $this->_mode ) { 
            if (  $this->_processors['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT){
                $ccPane = array( ts('Direct Debit Information') => 'DirectDebit' );
            } else {         
                $ccPane = array( 'Credit Card Information' => 'CreditCard' );
            }
        }
        if ( is_array( $ccPane ) ) {
            $paneNames = array_merge( $ccPane, $paneNames );
        }
        
        foreach ( $paneNames as $name => $type ) {
            $urlParams = "snippet=4&formType={$type}";
            if ( $this->_mode ) {
                $urlParams .= "&mode={$this->_mode}";
            }
            
            $open = 'false';
            if ( $type == 'CreditCard' ||
                 $type == 'DirectDebit' ) {
                $open = 'true';
            }

            $allPanes[$name] = array( 'url'  => CRM_Utils_System::url( 'civicrm/contact/view/contribution', $urlParams ),
                                      'open' => $open,
                                      'id'   => $type,
                                      );

            // see if we need to include this paneName in the current form
            if ( $this->_formType == $type ||
                 CRM_Utils_Array::value( "hidden_{$type}", $_POST ) ||
                 CRM_Utils_Array::value( "hidden_{$type}", $defaults ) ) {
                $showAdditionalInfo = true;
                $allPanes[$name]['open'] = 'true';
            }
            
            if ( $type == 'CreditCard' ) {
                $this->add('hidden', 'hidden_CreditCard', 1 );
                CRM_Core_Payment_Form::buildCreditCard( $this, true );
            } else if ( $type == 'DirectDebit' ) {
                $this->add('hidden', 'hidden_DirectDebit', 1 );
                CRM_Core_Payment_Form::buildDirectDebit( $this, true );
            } else {
                eval( 'CRM_Contribute_Form_AdditionalInfo::build' . $type . '( $this );' );
            }
        }
        
        $this->assign( 'allPanes', $allPanes );
        $this->assign( 'showAdditionalInfo', $showAdditionalInfo );
        
        if ( $this->_formType ) {
            $this->assign('formType', $this->_formType );
            return;
        }

        $this->applyFilter('__ALL__', 'trim');
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            $this->addButtons(array( 
                                    array ( 'type'      => 'next', 
                                            'name'      => ts('Delete'), 
                                            'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 
                                            'isDefault' => true   ), 
                                    array ( 'type'      => 'cancel', 
                                            'name'      => ts('Cancel') ), 
                                    ) 
                              );
            return;
        }
        
        //need to assign custom data type and subtype to the template
        $this->assign('customDataType', 'Contribution');
        $this->assign('customDataSubType',  $this->_contributionType );
        $this->assign('entityID',  $this->_id );
        
        if ( $this->_context == 'standalone' ) {
            require_once 'CRM/Contact/Form/NewContact.php';
            CRM_Contact_Form_NewContact::buildQuickForm( $this );
        }        
        
        $attributes = CRM_Core_DAO::getAttribute( 'CRM_Contribute_DAO_Contribution' );
        
        $element =& $this->add('select', 'contribution_type_id', 
                               ts( 'Contribution Type' ), 
                               array(''=>ts( '- select -' )) + CRM_Contribute_PseudoConstant::contributionType( ),
                               true, array('onChange' => "buildCustomData( 'Contribution', this.value );"));
        if ( $this->_online ) {
            $element->freeze( );
        }
        if ( !$this->_mode ) { 
            $element =& $this->add('select', 'payment_instrument_id', 
                                   ts( 'Paid By' ), 
                                   array(''=>ts( '- select -' )) + CRM_Contribute_PseudoConstant::paymentInstrument( ),
                                   false, array( 'onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"));
            
            if ( $this->_online ) {
                $element->freeze( );
            }
        }
        
        $element =& $this->add( 'text', 'trxn_id', ts('Transaction ID'), 
                                $attributes['trxn_id'] );
        if ( $this->_online ) {
            $element->freeze( );
        } else {
            $this->addRule( 'trxn_id',
                            ts( 'This Transaction ID already exists in the database. Include the account number for checks.' ),
                            'objectExists', 
                            array( 'CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id' ) );
        }
        //add receipt for offline contribution
        $this->addElement('checkbox','is_email_receipt', ts('Send Receipt?'),null, array('onclick' =>"return showHideByValue('is_email_receipt','','receiptDate','table-row','radio',true);") );

        $status = CRM_Contribute_PseudoConstant::contributionStatus( );
        // supressing contribution statuses that are NOT relevant to pledges (CRM-5169)
        if ( $this->_ppID ) {
            foreach ( array('Cancelled', 'Failed', 'In Progress') as $supress ) {
                unset($status[CRM_Utils_Array::key($supress,$status)]);
            }
        }
        
        $this->add('select', 'contribution_status_id',
                   ts('Contribution Status'), 
                   $status,
                   false, array(
                                'onClick' => "if (this.value != 3) status(); else return false",
                                'onChange' => "return showHideByValue('contribution_status_id','3','cancelInfo','table-row','select',false);"));

        // add various dates
        $this->addDate( 'receive_date', ts('Received'), false, array( 'formatType' => 'activityDate') );
                
        if ( $this->_online ) {
            $this->assign("hideCalender" , true );
        }
        $element =& $this->add( 'text', 'check_number', ts('Check Number'), $attributes['check_number'] );
        if ( $this->_online ) {
            $element->freeze( );
        }
        
        $this->addDate( 'receipt_date', ts('Receipt Date'), false, array( 'formatType' => 'activityDate') );
        $this->addDate( 'cancel_date', ts('Cancelled Date'), false, array( 'formatType' => 'activityDate') );
        
        $this->add('textarea', 'cancel_reason', ts('Cancellation Reason'), $attributes['cancel_reason'] );
        
        $element =& $this->add( 'select', 'payment_processor_id',
                                ts( 'Payment Processor' ),
                                $this->_processors );
        if ( $this->_online ) {
            $element->freeze( );
        }
        
        if ( empty( $this->_lineItems ) ) {
            $buildPriceSet = false;
            require_once 'CRM/Price/BAO/Set.php';
            $priceSets = CRM_Price_BAO_Set::getAssoc( false, 'CiviContribute');
            if ( !empty( $priceSets ) && !$this->_ppID ) {
                $buildPriceSet = true;
            }
            
            // don't allow price set for contribution if it is related to participant,
            // and if we already have line items for that participant. CRM-5095
            if ( $buildPriceSet && $this->_id ) {
                $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails( $this->_id );
                if ( $participantID = CRM_Utils_Array::value( 'participant', $componentDetails ) ) {
                    require_once 'CRM/Price/BAO/LineItem.php';
                    $participantLI = CRM_Price_BAO_LineItem::getLineItems( $participantID );
                    if ( !CRM_Utils_System::isNull( $participantLI ) ) {
                        $buildPriceSet = false;  
                    }
                }
            }
            
            $hasPriceSets = false;
            if ( $buildPriceSet ) {
                $hasPriceSets = true;
                $element =& $this->add( 'select', 'price_set_id', ts( 'Choose price set' ),
                                        array( '' => ts( 'Choose price set' )) + $priceSets,
                                        null, array('onchange' => "buildAmount( this.value );" ) );
                if ( $this->_online ) $element->freeze( );
            }
            $this->assign( 'hasPriceSets', $hasPriceSets );
            $element =& $this->add( 'text', 'total_amount', ts('Total Amount'),
                                    $attributes['total_amount'], ($hasPriceSets)?false:true );
            $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');
            if ( $this->_online || $this->_ppID ) {
                $element->freeze( );
            }
        }
        
        $element =& $this->add( 'text', 'source', ts('Source'), CRM_Utils_Array::value('source',$attributes) );
        if ( $this->_online ) {
            $element->freeze( );
        }
        
        $dataUrl = CRM_Utils_System::url( "civicrm/ajax/contactlist",
                                          "reset=1&context=softcredit&id={$this->_id}",
                                          false, null, false );
        $this->assign('dataUrl',$dataUrl );                                          
        $this->addElement( 'text', 'soft_credit_to', ts('Soft Credit To') );
        $this->addElement( 'hidden', 'soft_contact_id', '', array( 'id' => 'soft_contact_id' ) );
        if ( CRM_Utils_Array::value( 'pcp_made_through_id', $defaults ) &&
             $this->_action & CRM_Core_Action::UPDATE ) {
            $ele = $this->addElement('select', 'pcp_made_through_id', 
                                     ts( 'Personal Campaign Page' ),
                                     array( '' => ts( '- select -' ) ) +
                                     CRM_Contribute_PseudoConstant::pcPage( ) );
            $ele->freeze();
            $this->addElement('checkbox','pcp_display_in_roll', ts('Honor Roll?'), null, 
                              array('onclick' =>"return showHideByValue('pcp_display_in_roll','','nameID|nickID|personalNoteID','table-row','radio',false);") );
            $this->addElement('text', 'pcp_roll_nickname', ts('Nickname') );
            $this->addElement('textarea', 'pcp_personal_note', ts('Personal Note'));
        }

        $js = null;
        if ( !$this->_mode ) {
            $js = array( 'onclick' => "return verify( );" );    
        }
        
        require_once "CRM/Core/BAO/Preferences.php";
        $mailingInfo =& CRM_Core_BAO_Preferences::mailingPreferences();
        $this->assign( 'outBound_option', $mailingInfo['outBound_option'] );
        
        $this->addButtons(array( 
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save'), 
                                        'js'        => $js,
                                        'isDefault' => true   ),
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save and New'), 
                                        'js'        => $js,
                                        'subName'   => 'new' ), 
                                array ( 'type'      => 'cancel', 
                                        'name'      => ts('Cancel') ), 
                                ) 
                          );
        
        $this->addFormRule( array( 'CRM_Contribute_Form_Contribution', 'formRule' ), $this );
        
        if ( $this->_action & CRM_Core_Action::VIEW ) {
            $this->freeze( );
        }
    }
    
    /**  
     * global form rule  
     *  
     * @param array $fields  the input form values  
     * @param array $files   the uploaded files if any  
     * @param array $options additional user data  
     *  
     * @return true if no errors, else array of errors  
     * @access public  
     * @static  
     */  
    static function formRule( &$fields, &$files, $self ) 
    {  
        $errors = array( );
        
        //check if contact is selected in standalone mode
        if ( isset( $fields['contact_select_id'] ) && !$fields['contact_select_id'] ) {
            $errors['contact'] = ts('Please select a contact or create new contact');
        }
         
        if ( isset( $fields["honor_type_id"] ) ) {
            if ( !((  CRM_Utils_Array::value( 'honor_first_name', $fields ) && 
                      CRM_Utils_Array::value( 'honor_last_name' , $fields )) ||
                   CRM_Utils_Array::value( 'honor_email' , $fields ) )) {
                $errors['honor_first_name'] = ts('Honor First Name and Last Name OR an email should be set.');
            }
        }
        
        //check for Credit Card Contribution.
        if ( $self->_mode ) {
            if ( empty( $fields['payment_processor_id'] ) ) {
                $errors['payment_processor_id'] = ts( 'Payment Processor is a required field.' );
            }
        }
        
        // do the amount validations.
        if ( !CRM_Utils_Array::value( 'total_amount', $fields ) && empty( $self->_lineItems ) ) {
            if ( $priceSetId = CRM_Utils_Array::value( 'price_set_id', $fields ) ) {
                require_once 'CRM/Price/BAO/Field.php';
                CRM_Price_BAO_Field::priceSetValidation( $priceSetId, $fields, $errors );
            } else { 
                $errors['total_amount'] = ts('Please enter a valid amount.');
            }
        }
        
        return $errors;
    }
    
    /** 
     * Function to process the form 
     * 
     * @access public 
     * @return None 
     */ 
    public function postProcess( )  
    {   
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            require_once 'CRM/Contribute/BAO/Contribution.php';
            CRM_Contribute_BAO_Contribution::deleteContribution( $this->_id );
            return;
        }    
        
        // get the submitted form values.  
        $submittedValues = $this->controller->exportValues( $this->_name );

        // process price set and get total amount and line items.
        $lineItem = array( );
        $priceSetId = null;
        if ( $priceSetId = CRM_Utils_Array::value( 'price_set_id', $submittedValues ) ) {
            require_once 'CRM/Price/BAO/Set.php';
            CRM_Price_BAO_Set::processAmount( $this->_priceSet['fields'], 
                                              $submittedValues, $lineItem[$priceSetId] );
            $submittedValues['total_amount'] = $submittedValues['amount'];
        } 
        if ( !CRM_Utils_Array::value( 'total_amount', $submittedValues ) ) {
            $submittedValues['total_amount'] = $this->_values['total_amount']; 
        }
        $this->assign( 'lineItem', !empty($lineItem) ? $lineItem : false );
        
        if ( CRM_Utils_Array::value('soft_credit_to', $submittedValues) ) {
            $submittedValues['soft_credit_to'] =  $submittedValues['soft_contact_id'];
        }      

        // set the contact, when contact is selected
        if ( CRM_Utils_Array::value('contact_select_id', $submittedValues ) ) {
            $this->_contactID = CRM_Utils_Array::value('contact_select_id', $submittedValues);
        }
        
        $config  =& CRM_Core_Config::singleton( );
        $session =& CRM_Core_Session::singleton( );
        
        //Credit Card Contribution.
        if ( $this->_mode ) {
            $unsetParams = array('trxn_id','payment_instrument_id', 'contribution_status_id',
                                 'receive_date', 'cancel_date','cancel_reason');
            foreach ( $unsetParams as $key ) {
                if ( isset( $submittedValues[$key] ) ) {
                    unset( $submittedValues[$key] );
                }
            }
            
            //Get the rquire fields value only.
            $params = $this->_params = $submittedValues;
                    
            require_once 'CRM/Core/BAO/PaymentProcessor.php';
            $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $this->_params['payment_processor_id'],
                                                                                  $this->_mode );
            require_once "CRM/Contact/BAO/Contact.php";
            
            $now = date( 'YmdHis' );
            $fields = array( );
            
            // we need to retrieve email address
            if ( $this->_context == 'standalone' && CRM_Utils_Array::value( 'is_email_receipt', $submittedValues ) ) {
                require_once 'CRM/Contact/BAO/Contact/Location.php';
                list( $this->userDisplayName, 
                    $this->userEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $this->_contactID );
                $this->assign( 'displayName', $this->userDisplayName );
            }
            
            //set email for primary location.
            $fields["email-Primary"] = 1;
            $params["email-Primary"] = $this->userEmail;
            
            // now set the values for the billing location.
            foreach ( $this->_fields as $name => $dontCare ) {
                $fields[$name] = 1;
            }
            
            // also add location name to the array
            $params["address_name-{$this->_bltID}"] =
                CRM_Utils_Array::value( 'billing_first_name' , $params ) . ' ' .
                CRM_Utils_Array::value( 'billing_middle_name', $params ) . ' ' .
                CRM_Utils_Array::value( 'billing_last_name'  , $params );
            $params["address_name-{$this->_bltID}"] = trim( $params["address_name-{$this->_bltID}"] );
            $fields["address_name-{$this->_bltID}"] = 1;
                        
            $ctype = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact',
                                                  $this->_contactID,
                                                  'contact_type' );
            
            $nameFields = array( 'first_name', 'middle_name', 'last_name' );
            foreach ( $nameFields as $name ) {
                $fields[$name] = 1;
                if ( array_key_exists( "billing_$name", $params ) ) {
                    $params[$name] = $params["billing_{$name}"];
                    $params['preserveDBName'] = true;
                }
            }
            
            if ( CRM_Utils_Array::value( 'source', $params ) ) {
                unset( $params['source'] );
            }
            $contactID = CRM_Contact_BAO_Contact::createProfileContact( $params, $fields,
                                                                        $this->_contactID, 
                                                                        null, null, 
                                                                        $ctype );
            
            // add all the additioanl payment params we need
            $this->_params["state_province-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::stateProvinceAbbreviation( $this->_params["billing_state_province_id-{$this->_bltID}"] );
            $this->_params["country-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::countryIsoCode( $this->_params["billing_country_id-{$this->_bltID}"] );
            
            if ( $this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD ) {
                $this->_params['year'      ]     = $this->_params['credit_card_exp_date']['Y'];
                $this->_params['month'     ]     = $this->_params['credit_card_exp_date']['M'];
            }
            $this->_params['ip_address']     = CRM_Utils_System::ipAddress( );
            $this->_params['amount'        ] = $this->_params['total_amount'];
            $this->_params['amount_level'  ] = 0;
            $this->_params['currencyID'    ] = $config->defaultCurrency;
            $this->_params['payment_action'] = 'Sale';
                       
            if ( CRM_Utils_Array::value('soft_credit_to', $params) ) {
                $this->_params['soft_credit_to'] = $params['soft_credit_to'];
                $this->_params['pcp_made_through_id'] = $params['pcp_made_through_id'];
            } 
            
            $this->_params['pcp_display_in_roll'] = $params['pcp_display_in_roll'];
            $this->_params['pcp_roll_nickname'] = $params['pcp_roll_nickname'];
            $this->_params['pcp_personal_note'] = $params['pcp_personal_note'];
            
            //Add common data to formatted params
            CRM_Contribute_Form_AdditionalInfo::postProcessCommon( $params, $this->_params );
            
            if ( empty( $this->_params['invoice_id'] ) ) {
                $this->_params['invoiceID'] = md5( uniqid( rand( ), true ) );
            } else {
                $this->_params['invoiceID'] = $this->_params['invoice_id'];
            }
            
            // at this point we've created a contact and stored its address etc
            // all the payment processors expect the name and address to be in the 
            // so we copy stuff over to first_name etc. 
            $paymentParams = $this->_params;
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::mapParams( $this->_bltID, $this->_params, $paymentParams, true );
            
            $contributionType =& new CRM_Contribute_DAO_ContributionType( );
            $contributionType->id = $params['contribution_type_id'];
            if ( ! $contributionType->find( true ) ) {
                CRM_Core_Error::fatal( "Could not find a system table" );
            }
            
            // add some contribution type details to the params list
            // if folks need to use it
            $paymentParams['contributionType_name']                = 
                $this->_params['contributionType_name']            = $contributionType->name;
            $paymentParams['contributionType_accounting_code']     = 
                $this->_params['contributionType_accounting_code'] = $contributionType->accounting_code;
            $paymentParams['contributionPageID']                   = null;
            if ( CRM_Utils_Array::value( 'is_email_receipt', $this->_params ) ) {
                $paymentParams['email'] = $this->userEmail;
            }
            
            $payment =& CRM_Core_Payment::singleton( $this->_mode, 'Contribute', $this->_paymentProcessor, $this );
            
            $result =& $payment->doDirectPayment( $paymentParams );
            
            if ( is_a( $result, 'CRM_Core_Error' ) ) {
                //set the contribution mode.
                $urlParams = "action=add&cid={$this->_contactID}";
                if ( $this->_mode ) {
                    $urlParams .= "&mode={$this->_mode}";
                } 
                CRM_Core_Error::displaySessionError( $result );
                CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/contact/view/contribution', $urlParams ) );
            }
            
            if ( $result ) {
                $this->_params = array_merge( $this->_params, $result );
            }
            
            $this->_params['receive_date'] = $now;
            
            if ( CRM_Utils_Array::value( 'is_email_receipt', $this->_params ) ) {
                $this->_params['receipt_date'] = $now;
            } else {
                $this->_params['receipt_date'] = CRM_Utils_Date::processDate( $this->_params['receipt_date'], null, true );
            }
            
            $this->set( 'params', $this->_params );
            $this->assign( 'trxn_id', $result['trxn_id'] );
            $this->assign( 'receive_date', CRM_Utils_Date::processDate( $this->_params['receive_date']) );
            
            // result has all the stuff we need
            // lets archive it to a financial transaction
            if ( $contributionType->is_deductible ) {
                $this->assign('is_deductible',  true );
                $this->set   ('is_deductible',  true );
            }
            
            // set source if not set 
            if ( empty( $this->_params['source'] ) ) {
                $userID = $session->get( 'userID' );
                $userSortName = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $userID,
                                                                            'sort_name' );
                $this->_params['source'] = ts( 'Submit Credit Card Payment by: %1', array( 1 => $userSortName ) );
            }

			// build custom data getFields array
			$customFieldsContributionType = CRM_Core_BAO_CustomField::getFields( 'Contribution', false, false, 
															        CRM_Utils_Array::value( 'contribution_type_id', $params ));
			$customFields      = CRM_Utils_Array::crmArrayMerge( $customFieldsContributionType, 
																CRM_Core_BAO_CustomField::getFields( 'Contribution', false, false, null, null, true ) );
	        $params['custom'] = CRM_Core_BAO_CustomField::postProcess( $params,
	                                                                   $customFields,
	                                                                   $this->_id,
	                                                                   'Contribution' );
                        
            require_once 'CRM/Contribute/Form/Contribution/Confirm.php';
            $contribution 
                =& CRM_Contribute_Form_Contribution_Confirm::processContribution( $this, 
                                                                                  $this->_params, 
                                                                                  $result, 
                                                                                  $this->_contactID, 
                                                                                  $contributionType,  
                                                                                  false, false, false );

            // process line items, until no previous line items.
            if ( empty( $this->_lineItems ) && $contribution->id && !empty( $lineItem ) ) {
                CRM_Contribute_Form_AdditionalInfo::processPriceSet( $contribution->id, $lineItem );
            }
            
            //send receipt mail.
            if ( $contribution->id &&
                 CRM_Utils_Array::value( 'is_email_receipt', $this->_params ) ) {
                $this->_params['trxn_id']         = CRM_Utils_Array::value( 'trxn_id', $result );
                $this->_params['contact_id']      = $this->_contactID;
                $this->_params['contribution_id'] = $contribution->id;
                $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt( $this, $this->_params, true );
            }
            
            //process the note
            if ( $contribution->id && isset($params['note']) ) {
                CRM_Contribute_Form_AdditionalInfo::processNote( $params, $contactID, $contribution->id, null );
            }
            //process premium
            if ( $contribution->id && isset($params['product_name'][0]) ) {
                CRM_Contribute_Form_AdditionalInfo::processPremium( $params, $contribution->id, null, $this->_options );
            }
            
            //update pledge payment status.
            if ( $this->_ppID && $contribution->id ) { 
                //store contribution id in payment record.
                CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_Payment', $this->_ppID, 'contribution_id', $contribution->id );

                require_once 'CRM/Pledge/BAO/Payment.php';
                CRM_Pledge_BAO_Payment::updatePledgePaymentStatus( $this->_pledgeID, array( $this->_ppID ), 
                                                                   $contribution->contribution_status_id );
            }
            
            if ( $contribution->id ) {
                $statusMsg = ts('The contribution record has been processed.');
                if ( CRM_Utils_Array::value( 'is_email_receipt', $this->_params ) && $sendReceipt ) {
                    $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
                }
                CRM_Core_Session::setStatus( $statusMsg );
            }
            //submit credit card contribution ends.
        } else {
            //Offline Contribution.
            $unsetParams = array( "payment_processor_id", "email-{$this->_bltID}",
                                  "hidden_buildCreditCard", "hidden_buildDirectDebit",
                                  "billing_first_name", "billing_middle_name",
                                  "billing_last_name", "street_address-5",
                                  "city-{$this->_bltID}", "state_province_id-{$this->_bltID}",
                                  "postal_code-{$this->_bltID}",
                                  "country_id-{$this->_bltID}",
                                  "credit_card_number", "cvv2",
                                  "credit_card_exp_date", "credit_card_type",);
            foreach ( $unsetParams as $key ) {
                if ( isset( $submittedValues[$key] ) ) {
                    unset( $submittedValues[$key] );
                }
            }
                        
            // get the required field value only.
            $formValues    = $submittedValues;
            $params = $ids = array( );
            
            $params['contact_id'] = $this->_contactID;

            $params['currency'  ] = $config->defaultCurrency;
            
            if ( $currency = CRM_Utils_Array::value( 'currency', $this->_values ) ) {
                $params['currency'  ] = $currency;
            }
            
            $fields = array( 'contribution_type_id',
                             'contribution_status_id',
                             'payment_instrument_id',
                             'cancel_reason',
                             'source',
                             'check_number',
                             'soft_credit_to',
                             'pcp_made_through_id',
                             'pcp_display_in_roll',
                             'pcp_roll_nickname',
                             'pcp_personal_note',
                             );
            
            foreach ( $fields as $f ) {
                $params[$f] = CRM_Utils_Array::value( $f, $formValues );
            }

            if ( $softID = CRM_Utils_Array::value( 'softID', $this->_values ) ){
                $params['softID'] = $softID;
            }
            //if priceset is used, no need to cleanup money
            //CRM-5740
            if( $priceSetId ) {
                $params['skipCleanMoney'] = 1;
            }

            $dates = array( 'receive_date',
                            'receipt_date',
                            'cancel_date' );
            
            foreach ( $dates as $d ) {
                $params[$d] = CRM_Utils_Date::processDate( $formValues[$d], null, true );
            }

            if ( CRM_Utils_Array::value( 'is_email_receipt', $formValues ) ) {
                $params['receipt_date'] = date("Y-m-d");
            }

            if ( $params["contribution_status_id"] == 3 ) {
                if ( CRM_Utils_System::isNull( CRM_Utils_Array::value( 'cancel_date', $params ) ) ) {
                    $params['cancel_date'] = date("Y-m-d");
                }
            } else { 
                $params['cancel_date']   = 'null';
                $params['cancel_reason'] = 'null';
            }
            
            $ids['contribution'] = $params['id'] = $this->_id;
            
            //Add Additinal common information  to formatted params
            CRM_Contribute_Form_AdditionalInfo::postProcessCommon( $formValues, $params );
            
            //create contribution.
            require_once 'CRM/Contribute/BAO/Contribution.php';
            $contribution =& CRM_Contribute_BAO_Contribution::create( $params, $ids );
            
            // process line items, until no previous line items.
            if ( empty( $this->_lineItems )  && $contribution->id && !empty( $lineItem ) ) {
                CRM_Contribute_Form_AdditionalInfo::processPriceSet( $contribution->id, $lineItem );
            }
            
            // process associated membership / participant, CRM-4395
            $relatedComponentStatusMsg = null;
            if ( $contribution->id && $this->_action & CRM_Core_Action::UPDATE ) {
                $relatedComponentStatusMsg = $this->updateRelatedComponent( $contribution->id,
                                                                            $contribution->contribution_status_id,
                                                                            CRM_Utils_Array::value( 'contribution_status_id',
                                                                                                    $this->_values  ) );
            }
            
            //process  note
            if ( $contribution->id && isset( $formValues['note'] ) ) {
                CRM_Contribute_Form_AdditionalInfo::processNote( $formValues, $this->_contactID, $contribution->id, $this->_noteID );
            }
            
            //process premium
            if ( $contribution->id && isset( $formValues['product_name'][0] ) ) {
                CRM_Contribute_Form_AdditionalInfo::processPremium( $formValues, $contribution->id, 
                                                                    $this->_premiumID, $this->_options ); 
            }
            
            //send receipt mail.
            if ( $contribution->id && CRM_Utils_Array::value( 'is_email_receipt', $formValues ) ) {
                $formValues['contact_id']      = $this->_contactID;
                $formValues['contribution_id'] = $contribution->id;
                $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt( $this, $formValues );
            }
            
            //update pledge payment status.
            if ( ($this->_ppID && $contribution->id) && $this->_action & CRM_Core_Action::ADD ) { 
             
                //store contribution id in payment record.
                CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_Payment', $this->_ppID, 'contribution_id', $contribution->id );

                require_once 'CRM/Pledge/BAO/Payment.php';
                CRM_Pledge_BAO_Payment::updatePledgePaymentStatus( $this->_pledgeID, array( $this->_ppID ), 
                                                                   $contribution->contribution_status_id );
            } 
            
            $statusMsg = ts('The contribution record has been saved.');
            if ( CRM_Utils_Array::value( 'is_email_receipt', $formValues ) && $sendReceipt ) {
                $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
            }
            
            if ( $relatedComponentStatusMsg ) {
                $statusMsg .= ' ' . $relatedComponentStatusMsg;
            }
            
            CRM_Core_Session::setStatus( $statusMsg );
            //Offline Contribution ends.
        }
        
        $buttonName = $this->controller->getButtonName( );
        if ( $this->_context == 'standalone' ) {
            if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
                $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/add', 'reset=1&action=add&context=standalone') );
            } else {
                $session->replaceUserContext(CRM_Utils_System::url( 'civicrm/contact/view',
                                              "reset=1&cid={$this->_contactID}&selectedChild=contribute" ) );
            }
        } else if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution', "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}") );
        }
    }
    
    /**
     * This function process contribution related objects.
     */
    function updateRelatedComponent( $contributionId, $statusId, $previousStatusId = null ) {
        $statusMsg = null;
        if ( !$contributionId || !$statusId ) {
            return $statusMsg;
        }
        
        $params = array( 'contribution_id'                 => $contributionId,
                         'contribution_status_id'          => $statusId,
                         'previous_contribution_status_id' => $previousStatusId );
        
        require_once 'CRM/Contribute/BAO/Contribution.php';
        $updateResult = CRM_Contribute_BAO_Contribution::transitionComponents( $params );
        
        if ( ! is_array( $updateResult ) ||
             ! ($updatedComponents = CRM_Utils_Array::value('updatedComponents',$updateResult)) ||
             ! is_array( $updatedComponents ) || 
             empty( $updatedComponents ) ) {
            return $statusMsg;
        }
        
        // get the user display name.
        $sql = "
   SELECT  display_name as displayName 
     FROM  civicrm_contact
LEFT JOIN  civicrm_contribution on (civicrm_contribution.contact_id = civicrm_contact.id )
    WHERE  civicrm_contribution.id = {$contributionId}";
        $userDisplayName = CRM_Core_DAO::singleValueQuery( $sql ); 
        
        // get the status message for user.
        foreach ( $updatedComponents as $componentName => $updatedStatusId ) {
            
            if ( $componentName == 'CiviMember' ) {
                require_once 'CRM/Member/PseudoConstant.php';
                $updatedStatusName = CRM_Utils_Array::value( $updatedStatusId,
                                                             CRM_Member_PseudoConstant::membershipStatus( ) );
                if (  $updatedStatusName == 'Cancelled' ) {
                    $statusMsg .= ts( "<br />Membership for %1 has been Cancelled.", array( 1 => $userDisplayName ) ); 
                } else if (  $updatedStatusName == 'Expired') {
                    $statusMsg .= ts( "<br />Membership for %1 has been Expired.", array( 1 => $userDisplayName ) );
                } else if ( $endDate = CRM_Utils_Array::value( 'membership_end_date', $updateResult ) ) {
                    $statusMsg .= ts( "<br />Membership for %1 has been updated. The membership End Date is %2.",
                                      array( 1 => $userDisplayName,
                                             2 => $endDate ) );
                }
            }
            
            if ( $componentName == 'CiviEvent' ) {
                require_once 'CRM/Event/PseudoConstant.php';
                $updatedStatusName = CRM_Utils_Array::value( $updatedStatusId,
                                                             CRM_Event_PseudoConstant::participantStatus() );
                if ( $updatedStatusName == 'Cancelled' ) {
                    $statusMsg .= ts( "<br />Event Registration for %1 has been Cancelled." , array( 1 => $userDisplayName ) );   
                } else if ( $updatedStatusName == 'Registered' ) {
                    $statusMsg .= ts( "<br />Event Registration for %1 has been updated.", array( 1 => $userDisplayName) ); 
                }
            }
            
            if ( $componentName == 'CiviPledge' ) {
                require_once 'CRM/Contribute/PseudoConstant.php';
                $updatedStatusName =  CRM_Utils_Array::value( $updatedStatusId, 
                                                              CRM_Contribute_PseudoConstant::contributionStatus( null,'name') );
                if ( $updatedStatusName == 'Cancelled' ) {
                    $statusMsg .= ts( "<br />Pledge Payment for %1 has been Cancelled.", array( 1 => $userDisplayName ) ); 
                } else if ( $updatedStatusName == 'Failed') {
                    $statusMsg .= ts( "<br />Pledge Payment for %1 has been Failed.", array( 1 => $userDisplayName ) );
                } else if ( $updatedStatusName == 'Completed') {
                    $statusMsg .= ts( "<br />Pledge Payment for %1 has been updated.", array( 1 => $userDisplayName) );
                }
            }
        }
        
        return $statusMsg; 
    }
    
}

