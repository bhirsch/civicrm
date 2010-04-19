<?PHP

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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Task.php';
require_once 'CRM/Event/PseudoConstant.php';
require_once 'CRM/Event/Form/EventFees.php';
require_once 'CRM/Custom/Form/CustomData.php';
require_once 'CRM/Core/BAO/CustomGroup.php';
require_once 'CRM/Contact/BAO/Contact/Location.php';

/**
 * This class generates form components for processing a participation 
 * in an event
 */
class CRM_Event_Form_Participant extends CRM_Contact_Form_Task
{
    /**
     * the values for the contribution db object
     *
     * @var array
     * @protected
     */
    public $_values;

    /**
     * Price Set ID, if the new price set method is used
     *
     * @var int
     * @protected
     */
    public $_priceSetId;

    /**
     * Array of fields for the price set
     *
     * @var array
     * @protected
     */
    public $_priceSet;

    /**
     * the id of the participation that we are proceessing
     *
     * @var int
     * @protected
     */
    public $_id;
    
    /**
     * the id of the note 
     *
     * @var int
     * @protected
     */
    protected $_noteId = null;

    /**
     * the id of the contact associated with this participation
     *
     * @var int
     * @protected
     */
    public $_contactId;
    
    /**
     * array of event values
     * 
     * @var array
     * @protected
     */
    protected $_event;

    /**
     * Are we operating in "single mode", i.e. adding / editing only
     * one participant record, or is this a batch add operation
     *
     * @var boolean
     */
    public $_single = false;

    /**
     * If event is paid or unpaid
     */
    public $_isPaidEvent;

    /**
     * Page action
     */
    public $_action;
    /**
     * Role Id
     */
    protected $_roleId = null;

    /**
     * Event Type Id
     */
    protected $_eventTypeId = null;

    /**
     * participant status Id
     */
    protected $_statusId = null;

    /**
     * participant mode
     */
    public  $_mode = null;
    /*
     *Line Item for Price Set
     */
    public  $_lineItem = null;
    /*
     *Contribution mode for event registration for offline mode
     */
    public $_contributeMode = 'direct';

    public  $_online;

	/**
	 * store id of role custom data type ( option value )
	 */
	protected $_roleCustomDataTypeID;
	
	/**
	 * store id of event Name custom data type ( option value)
	 */
	protected $_eventNameCustomDataTypeID;
    
    /*
     * selected discount id
     */
    public $_originalDiscountId = null;
	
	/**
     * event id
     */
    public $_eventId = null;
    
    /** 
     * Function to set variables up before form is built 
     *                                                           
     * @return void 
     * @access public 
     */ 
    public function preProcess()  
    {
        $this->_showFeeBlock = CRM_Utils_Array::value( 'eventId', $_GET );
        $this->assign( 'showFeeBlock', false );

        $this->_contactId 	   = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this );
        $this->_mode           = CRM_Utils_Request::retrieve( 'mode', 'String', $this );
        $this->_context        = CRM_Utils_Request::retrieve('context', 'String', $this );
        $this->assign('context', $this->_context );
        
        if ( $this->_contactId ) {
            require_once 'CRM/Contact/BAO/Contact.php';
            $displayName = CRM_Contact_BAO_Contact::displayName( $this->_contactId );
            $this->assign( 'displayName', $displayName );
        }
        
        // check the current path, if search based, then dont get participantID
        // CRM-5792
        $path = CRM_Utils_System::currentPath( );
        if ( strpos( $path, 'civicrm/contact/search' ) === 0 ) {
            $this->_id = null;
        } else { 
            $this->_id = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        }

		// get the option value for custom data type 	
		$this->_roleCustomDataTypeID      = CRM_Core_OptionGroup::getValue( 'custom_data_type', 'ParticipantRole', 'name' );
		$this->_eventNameCustomDataTypeID = CRM_Core_OptionGroup::getValue( 'custom_data_type', 'ParticipantEventName', 'name' );		
		$this->_eventTypeCustomDataTypeID = CRM_Core_OptionGroup::getValue( 'custom_data_type', 'ParticipantEventType', 'name' );

		$this->assign( 'roleCustomDataTypeID', $this->_roleCustomDataTypeID );
		$this->assign( 'eventNameCustomDataTypeID', $this->_eventNameCustomDataTypeID );
		$this->assign( 'eventTypeCustomDataTypeID', $this->_eventTypeCustomDataTypeID );
		
        if ( $this->_mode ) {
            $this->assign( 'participantMode', $this->_mode );

            $this->_paymentProcessor = array( 'billing_mode' => 1 );

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
                CRM_Core_Error::fatal( ts( 'Could not find valid payment processor for this page' ) );
            } else {
                $this->_processors = $validProcessors;  
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
            
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::setCreditCardFields( $this );
            
            // this required to show billing block    
            $this->assign_by_ref( 'paymentProcessor', $paymentProcessor );
            $this->assign( 'hidePayPalExpress', true );
        }
        
        if ( $this->_showFeeBlock ) {
            $this->assign( 'showFeeBlock', true );
            $this->assign( 'paid', true );
            return CRM_Event_Form_EventFees::preProcess( $this );
        }
        
        //custom data related code
        $this->_cdType     = CRM_Utils_Array::value( 'type', $_GET );
        $this->assign('cdType', false);
        if ( $this->_cdType ) {
            $this->assign('cdType', true);
            return CRM_Custom_Form_CustomData::preProcess( $this );
        }
       
        //check the mode when this form is called either single or as
        //search task action
        if ( $this->_id || $this->_contactId || $this->_context == 'standalone') {
            $this->_single = true;
            $this->assign( 'urlPath'   , 'civicrm/contact/view/participant' );
            if ( !$this->_id && !$this->_contactId ) {
                $breadCrumbs = array( array( 'title' => ts('CiviEvent Dashboard'),
                                             'url'   => CRM_Utils_System::url('civicrm/event','reset=1') ) );
                
                CRM_Utils_System::appendBreadCrumb( $breadCrumbs );
            }
        } else {
            //set the appropriate action
            $advanced = null;
            $builder  = null;

            $session =& CRM_Core_Session::singleton();
            $advanced = $session->get('isAdvanced');
            $builder  = $session->get('isSearchBuilder');
            
            $searchType = "basic";
            if ( $advanced == 1 ) {
                $this->_action = CRM_Core_Action::ADVANCED;
                $searchType = "advanced";
            } else if ( $advanced == 2 && $builder = 1) {
                $this->_action = CRM_Core_Action::PROFILE;
                $searchType = "builder";
            } else if ( $advanced == 3 ) {
                $searchType = "custom";
            }
            
            parent::preProcess( );

            $this->_single    = false;
            $this->_contactId = null;

            //set ajax path, this used for custom data building
            $this->assign( 'urlPath'   , "civicrm/contact/search/$searchType" );
            $this->assign( 'urlPathVar', "_qf_Participant_display=true&qfKey={$this->controller->_key}" ); 
        }
        
        $this->assign( 'single', $this->_single );
        
        $this->_action = CRM_Utils_Request::retrieve( 'action', 'String', $this, false, 'add' );
        $this->assign( 'action'  , $this->_action   ); 

        // check for edit permission
        if ( ! CRM_Core_Permission::checkActionPermission( 'CiviEvent', $this->_action ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
        }     
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return;
        }

        if ( $this->_id ) {
            // assign participant id to the template
            $this->assign('participantId',  $this->_id );
            $this->_roleId = CRM_Core_DAO::getFieldValue( "CRM_Event_DAO_Participant", $this->_id, 'role_id' );
        } 

        // when fee amount is included in form
        if ( CRM_Utils_Array::value( 'hidden_feeblock', $_POST ) 
             || CRM_Utils_Array::value( 'send_receipt', $_POST ) ) {
            CRM_Event_Form_EventFees::preProcess( $this );
            CRM_Event_Form_EventFees::buildQuickForm( $this );
            CRM_Event_Form_EventFees::setDefaultValues( $this );
        }

        // when custom data is included in this page
    	if ( CRM_Utils_Array::value( "hidden_custom", $_POST ) ) {
    		//custom data of type participant role
    		CRM_Custom_Form_Customdata::preProcess( $this, $this->_roleCustomDataTypeID, $_POST['role_id'], 1, 'Participant', $this->_id );
    		CRM_Custom_Form_Customdata::buildQuickForm( $this );
    		CRM_Custom_Form_Customdata::setDefaultValues( $this );
		
    		//custom data of type participant event
    		CRM_Custom_Form_Customdata::preProcess( $this, $this->_eventNameCustomDataTypeID, $_POST['event_id'], 1, 'Participant', $this->_id );
    		CRM_Custom_Form_Customdata::buildQuickForm( $this );
    		CRM_Custom_Form_Customdata::setDefaultValues( $this );

    		// custom data of type participant event type
            $eventTypeId = CRM_Core_DAO::getFieldValue( "CRM_Event_DAO_Event", $_POST['event_id'], 
                                                        'event_type_id', 'id' );
    		CRM_Custom_Form_Customdata::preProcess( $this, $this->_eventTypeCustomDataTypeID, $eventTypeId, 
                                                    1, 'Participant', $this->_id );
    		CRM_Custom_Form_Customdata::buildQuickForm( $this );
    		CRM_Custom_Form_Customdata::setDefaultValues( $this );

    		//custom data of type participant, ( we 'null' to reset subType and subName)
    		CRM_Custom_Form_Customdata::preProcess( $this, 'null', 'null', 1, 'Participant', $this->_id );
    		CRM_Custom_Form_Customdata::buildQuickForm( $this );
    		CRM_Custom_Form_Customdata::setDefaultValues( $this );

    	}
        
        // CRM-4395, get the online pending contribution id.
        $this->_onlinePendingContributionId = null;
        if ( !$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE) ) {
            require_once 'CRM/Contribute/BAO/Contribution.php';
            $this->_onlinePendingContributionId = 
                CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution( $this->_id, 
                                                                                 'Event' );
        }
        $this->set( 'onlinePendingContributionId', $this->_onlinePendingContributionId );
    }
    
    /**
     * This function sets the default values for the form in edit/view mode
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    public function setDefaultValues( ) 
    { 
        if ( $this->_showFeeBlock ) {
            return CRM_Event_Form_EventFees::setDefaultValues( $this );
        }

        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::setDefaultValues( $this );
        }

        $defaults = array( );
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            return $defaults;
        }
                            
        if ( $this->_id ) {
            $ids    = array( );
            $params = array( 'id' => $this->_id );
            
            require_once "CRM/Event/BAO/Participant.php";
            CRM_Event_BAO_Participant::getValues( $params, $defaults, $ids );
            $this->_contactId = $defaults[$this->_id]['contact_id'];
            $this->_statusId = $defaults[$this->_id]['participant_status_id'];
            
            //set defaults for note
            $noteDetails = CRM_Core_BAO_Note::getNote( $this->_id, 'civicrm_participant' );
            $defaults[$this->_id]['note'] = array_pop( $noteDetails );
        }
        
        if ($this->_action & ( CRM_Core_Action::VIEW | CRM_Core_Action::BROWSE ) ) {
            $inactiveNeeded = true;
            $viewMode = true;
        } else {
            $viewMode = false;
            $inactiveNeeded = false;
        }

        //setting default register date
        if ( $this->_action == CRM_Core_Action::ADD ) {            
            if ( CRM_Utils_Array::value( 'event_id' , $defaults[$this->_id] ) ) {
                $contributionTypeId =  CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event',
                                                                    $defaults[$this->_id]['event_id'], 
                                                                    'contribution_type_id' );
                if ( $contributionTypeId ){
                    $defaults[$this->_id]['contribution_type_id'] = $contributionTypeId;
                }
            }
            
            if ( $this->_mode ) {
                $fields["email-{$this->_bltID}"         ] = 1;
                $fields["email-Primary"                 ] = 1;

                if ( $this->_contactId ) {
                    require_once "CRM/Core/BAO/UFGroup.php";
                    CRM_Core_BAO_UFGroup::setProfileDefaults( $this->_contactId, $fields, $defaults  );
                }
               
                if ( empty( $defaults["email-{$this->_bltID}"] ) &&
                     ! empty( $defaults["email-Primary"] ) ) {
                    $defaults[$this->_id]["email-{$this->_bltID}"] = $defaults["email-Primary"];
                }
            }

			$submittedRole = $this->getElementValue( 'role_id' );
			if ( $submittedRole[0] ) {
				$roleID  = $submittedRole[0];
			}
			$submittedEvent = $this->getElementValue( 'event_id' );
			if ( $submittedEvent[0] ) {
				$eventID  = $submittedEvent[0];
			}
        } else {
            $defaults[$this->_id]['record_contribution'] = 0;

            if ( $defaults[$this->_id]['participant_is_pay_later'] ) {
                $this->assign( 'participant_is_pay_later', true );
            }

            $this->assign( 'participant_status_id', $defaults[$this->_id]['participant_status_id'] );
			$roleID  = $defaults[$this->_id]['participant_role_id'];
			$eventID = $defaults[$this->_id]['event_id'];
			
			$this->_eventTypeId = CRM_Core_DAO::getFieldValue( "CRM_Event_DAO_Event", $eventID, 'event_type_id', 'id' );

            $this->_discountId = CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Participant', $this->_id , 'discount_id' );
            if ( $this->_discountId ) {
                $this->set( 'discountId', $this->_discountId );
            }
        }
        
        list( $defaults[$this->_id]['register_date'], 
              $defaults[$this->_id]['register_date_time'] ) = CRM_Utils_Date::setDateDefaults( 
                                                                         CRM_Utils_Array::value( 'register_date', $defaults[$this->_id] ), 'activityDateTime' );
        
		//assign event and role id, this is needed for Custom data building
		if ( isset( $_POST['role_id'] ) ) {
		    $roleID = $_POST['role_id'];
		}
		
		if ( isset( $roleID ) ) {
		    $this->assign( 'roleID',  $roleID );
		}

		if ( isset( $_POST['event_id'] ) ) {
		    $eventID = $_POST['event_id'];
			$this->_eventTypeId = 
                CRM_Core_DAO::getFieldValue( "CRM_Event_DAO_Event", $eventID, 'event_type_id', 'id' );
		}

		if (  isset( $eventID ) ) {
		    $this->assign( 'eventID', $eventID );
		    $this->set( 'eventId', $eventID );
		}
        
		if (  isset( $this->_eventTypeId ) ) {
		    $this->assign( 'eventTypeID', $this->_eventTypeId );
		}

        $this->assign( 'event_is_test', CRM_Utils_Array::value('event_is_test',$defaults[$this->_id]) );
        return $defaults[$this->_id];
    }
    
    /** 
     * Function to build the form 
     * 
     * @return None 
     * @access public 
     */ 

    public function buildQuickForm( )  
    { 
        if ( $this->_showFeeBlock ) {
            return CRM_Event_Form_EventFees::buildQuickForm( $this );
        }

        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::buildQuickForm( $this );
        }

        //need to assign custom data type to the template
        $this->assign('customDataType', 'Participant');

        $this->applyFilter('__ALL__', 'trim');
       
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            if (  $this->_single ) {
                require_once 'CRM/Event/BAO/Event.php';
                $additionalParticipant = count (CRM_Event_BAO_Event::buildCustomProfile( $this->_id, null, $this->_contactId, false, true )) - 1;
                if ( $additionalParticipant ) {
                    $this->assign( "additionalParticipant", $additionalParticipant );
                }   
            }
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
        

        if ( $this->_single ) {
            $urlPath   = 'civicrm/contact/view/participant';
            $urlParams = "reset=1&cid={$this->_contactId}&context=participant";
            if ( $this->_context == 'standalone' ) {
                require_once 'CRM/Contact/Form/NewContact.php';
                CRM_Contact_Form_NewContact::buildQuickForm( $this );
                $urlParams = "reset=1&context=standalone";
                $urlPath   = 'civicrm/participant/add';
            }        

            if ( $this->_id ) {
                $urlParams .= "&action=update&id={$this->_id}";
            } else {
                $urlParams .= "&action=add";
            }
            
            if (CRM_Utils_Request::retrieve( 'past', 'Boolean', $this ) ) {
                $urlParams .= "&past=true";
            }
            if ( $this->_mode ) {
                $urlParams .= "&mode={$this->_mode}";
            }
            
            $url = CRM_Utils_System::url( $urlPath, $urlParams, 
                                          false, null, false ); 
        } else {
            $currentPath = CRM_Utils_System::currentPath( );

            $url = CRM_Utils_System::url( $currentPath, '_qf_Participant_display=true',
                                          false, null, false  );
        }

        $this->assign("refreshURL",$url);
        $url .= "&past=true";
        $this->assign("pastURL", $url);
        
        $events = array( );
        $this->assign("past", false);
        
        require_once "CRM/Event/BAO/Event.php";
        if ( CRM_Utils_Request::retrieve( 'past', 'Boolean', $this ) || ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            $events = CRM_Event_BAO_Event::getEvents( true );
            $this->assign("past", true);
        } else {
            $events = CRM_Event_BAO_Event::getEvents( );
        }
        
        if ( $this->_mode ) {
            //unset the event which are not monetary when credit card
            //event registration is used
            foreach( $events as $key => $val ) {
                $isPaid = CRM_Core_DAO::getFieldValue( "CRM_Event_DAO_Event", $key, 'is_monetary' );
                if ( ! $isPaid ) {
                    unset( $events[$key] );
                }
            }
            $this->add( 'select', 'payment_processor_id', ts( 'Payment Processor' ), $this->_processors, true );
        }
        
        // build array(event -> eventType) mapper
        $query = "
SELECT     civicrm_event.id as id, civicrm_event.event_type_id as event_type_id
FROM       civicrm_event
WHERE      civicrm_event.is_template IS NULL OR civicrm_event.is_template = 0";
        $dao =& CRM_Core_DAO::executeQuery( $query );
        $eventAndTypeMapping = array();
        while ( $dao->fetch( ) ) {
            $eventAndTypeMapping[$dao->id] = $dao->event_type_id;
        }
        $eventAndTypeMapping = json_encode($eventAndTypeMapping);
        // building of mapping ends --

        $element = $this->add('select', 'event_id',  ts( 'Event' ),  
                              array( '' => ts( '- select -' ) ) + $events,
                              true,
                              array('onchange' => "buildFeeBlock( this.value ); buildCustomData( 'Participant', this.value, {$this->_eventNameCustomDataTypeID} ); buildEventTypeCustomData( this.value, {$this->_eventTypeCustomDataTypeID}, '{$eventAndTypeMapping}' );", 'class' => 'huge' ) 
                              );
        
        //frozen the field fix for CRM-4171
        if ( $this->_action & CRM_Core_Action::UPDATE && $this->_id ) {
            if ( CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_ParticipantPayment', 
                                              $this->_id, 'contribution_id', 'participant_id' ) ) {
                $element->freeze();
            }
        }
       
        $this->addDateTime( 'register_date', ts('Registration Date'), true, array( 'formatType' => 'activityDateTime') );
        
		if ( $this->_id ) {
			$this->assign( 'entityID', $this->_id );
		}
		
        $this->add( 'select', 'role_id' , ts( 'Participant Role' ),
                    array( '' => ts( '- select -' ) ) + CRM_Event_PseudoConstant::participantRole( ),
                    true,
                    array('onchange' => "buildCustomData( 'Participant', this.value, {$this->_roleCustomDataTypeID} );") );
        
        // CRM-4395
        $checkCancelledJs = array('onchange' => "return sendNotification( );");
        $confirmJS = null;
        if ( $this->_onlinePendingContributionId ) {
            $cancelledparticipantStatusId  = array_search( 'Cancelled',CRM_Event_PseudoConstant::participantStatus() );
            $cancelledContributionStatusId = array_search( 'Cancelled', 
                                                           CRM_Contribute_PseudoConstant::contributionStatus(null, 'name') ); 
            $checkCancelledJs = array('onchange' => 
                                      "checkCancelled( this.value, {$cancelledparticipantStatusId},{$cancelledContributionStatusId});");
            
            $participantStatusId  = array_search( 'Pending from pay later', 
                                                  CRM_Event_PseudoConstant::participantStatus() );
            $contributionStatusId = array_search( 'Completed', 
                                                  CRM_Contribute_PseudoConstant::contributionStatus(null, 'name') ); 
            $confirmJS = array( 'onclick' => "return confirmStatus( {$participantStatusId}, {$contributionStatusId} );" );
        }
        
        $this->add( 'select', 'status_id' , ts( 'Participant Status' ),
                    array( '' => ts( '- select -' ) ) + CRM_Event_PseudoConstant::participantStatus( null, null, 'label' ),
                    true, 
                    $checkCancelledJs );
        
        $this->addElement('checkbox', 'is_notify', ts( 'Send Notification' ) , null);
        
        $this->add( 'text', 'source', ts('Event Source') );
        
        $noteAttributes = CRM_Core_DAO::getAttribute( 'CRM_Core_DAO_Note' );
        $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);
        
        $this->addButtons(array( 
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save'), 
                                        'isDefault' => true,
                                        'js'        => $confirmJS ),
                                array ( 'type'      => 'upload',
                                        'name'      => ts('Save and New'), 
                                        'subName'   => 'new',
                                        'js'        => $confirmJS ),         
                                array ( 'type'      => 'cancel', 
                                        'name'      => ts('Cancel') ), 
                                ) 
                          );
        if ($this->_action == CRM_Core_Action::VIEW) { 
            $this->freeze();
        } 
    }
    
    /**
     * Add local and global form rules
     *
     * @access protected
     * @return void
     */
    function addRules( ) 
    {
        $this->addFormRule( array( 'CRM_Event_Form_Participant', 'formRule'), $this );
    }
    
    /**
     * global validation rules for the form
     *
     * @param array $fields posted values of the form
     *
     * @return array list of errors to be posted back to the form
     * @static
     * @access public
     */
    static function formRule( &$values, $files, $self ) 
    {
        // If $values['_qf_Participant_next'] is Delete or 
        // $values['event_id'] is empty, then return 
        // instead of proceeding further.
        
        if ( ( CRM_Utils_Array::value( '_qf_Participant_next', $values) == 'Delete' ) ||  
             ( ! $values['event_id'] ) 
             ) {
            return true;
        }
        
        //check if contact is selected in standalone mode
        if ( isset( $values['contact_select_id'] ) && !$values['contact_select_id'] ) {
            $errorMsg['contact'] = ts('Please select a valid contact or create new contact');
        }
        
        if ( CRM_Utils_Array::value( 'payment_processor_id', $values ) ) {
            // make sure that credit card number and cvv are valid
            require_once 'CRM/Utils/Rule.php';
            if ( CRM_Utils_Array::value( 'credit_card_type', $values ) ) {
                if ( CRM_Utils_Array::value( 'credit_card_number', $values ) &&
                     ! CRM_Utils_Rule::creditCardNumber( $values['credit_card_number'], $values['credit_card_type'] ) ) {
                    $errorMsg['credit_card_number'] = ts( "Please enter a valid Credit Card Number" );
                }
                
                if ( CRM_Utils_Array::value( 'cvv2', $values ) &&
                     ! CRM_Utils_Rule::cvv( $values['cvv2'], $values['credit_card_type'] ) ) {
                    $errorMsg['cvv2'] =  ts( "Please enter a valid Credit Card Verification Number" );
                }
            }
        }
        
        if ( CRM_Utils_Array::value( 'record_contribution', $values ) && !  CRM_Utils_Array::value( 'contribution_type_id', $values ) ){
            $errorMsg['contribution_type_id'] = ts( "Please enter the associated Contribution Type" );
        }
        
        // validate contribution status for 'Failed'.
        if ( $self->_onlinePendingContributionId && 
             CRM_Utils_Array::value( 'record_contribution', $values ) && 
             (CRM_Utils_Array::value( 'contribution_status_id', $values ) == 
              array_search( 'Failed', CRM_Contribute_PseudoConstant::contributionStatus(null, 'name'))) ) {
            $errorMsg['contribution_status_id'] = ts( "Please select a valid payment status before updating." );
        }
        
        // do the amount validations.
        if ( !CRM_Utils_Array::value( 'total_amount', $values ) && empty( $self->_values['line_items'] ) ) {
            if ( $priceSetId = CRM_Utils_Array::value( 'priceSetId', $values ) ) {
                require_once 'CRM/Price/BAO/Field.php';
                CRM_Price_BAO_Field::priceSetValidation( $priceSetId, $values, $errorMsg );
            }
        }
        return CRM_Utils_Array::crmIsEmptyArray( $errorMsg ) ? true : $errorMsg;
    }    
    
    /** 
     * Function to process the form 
     * 
     * @access public 
     */ 
    public function postProcess( )
    {   
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            require_once "CRM/Event/BAO/Participant.php";
            CRM_Event_BAO_Participant::deleteParticipant( $this->_id );
            return;
        }
        
        // get the submitted form values.  
        $params = $this->controller->exportValues( $this->_name );
             
        // set the contact, when contact is selected
        if ( CRM_Utils_Array::value('contact_select_id', $params ) ) {
            $this->_contactId = CRM_Utils_Array::value('contact_select_id', $params);
        }
        
        if ( $this->_id ) {
            $params['id'] = $this->_id;
        }

        $config =& CRM_Core_Config::singleton();        
        if ( $this->_isPaidEvent ) {
            
            $contributionParams           = array( );
            $lineItem                     = array( );
            $additionalParticipantDetails = array( );
            if ( $this->_id && $this->_action & CRM_Core_Action::UPDATE ) {
                $participantBAO     =& new CRM_Event_BAO_Participant( );
                $participantBAO->id = $this->_id;
                $participantBAO->find(true);
                $contributionParams['total_amount'] = $participantBAO->fee_amount;
               
                $params['discount_id'] = null;
                //re-enter the values for UPDATE mode
                $params['fee_level'  ] = $params['amount_level'] = $participantBAO->fee_level;
                $params['fee_amount' ] = $participantBAO->fee_amount;
                if ( isset($params['priceSetId']) ) {
                    require_once 'CRM/Price/BAO/LineItem.php';
                    $lineItem[0] = CRM_Price_BAO_LineItem::getLineItems( $this->_id );   
                }
                //also add additional participant's fee level/priceset
                if ( CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id) ) {
                    if ( !isset($params['priceSetId']) ) {
                        $additionalParticipantDetails = CRM_Event_BAO_Participant::getAdditionalParticipantIds( $this->_id, true, null, array( 'fee_level' => 1) );
                        
                    } else {
                        $additionalParticipantDetails = CRM_Event_BAO_Participant::getAdditionalParticipantIds( $this->_id, true, null, array( 'priceset' => 1) ); 
                    }
                }
            } else {
                
                //check if discount is selected
                if ( CRM_Utils_Array::value( 'discount_id', $params ) ) {
                    $discountId = $params['discount_id'];
                } else {
                    $discountId = $params['discount_id'] = 'null';
                }
                
                //lets carry currency, CRM-4453
                $params['fee_currency'] = $config->defaultCurrency;
                // fix for CRM-3088
                if ( $discountId &&
                     ! empty( $this->_values['discount'][$discountId] ) ) {
                    $params['amount_level'] = $this->_values['discount'][$discountId][$params['amount']]['label'];
                    $params['amount']       = $this->_values['discount'][$discountId][$params['amount']]['value'];
                    
                } else if ( ! isset( $params['priceSetId'] ) && CRM_Utils_Array::value('amount', $params ) ) {
                    $params['amount_level'] = $this->_values['fee'][$params['amount']]['label'];
                    $params['amount']       = $this->_values['fee'][$params['amount']]['value'];
                    
                } else {
                    CRM_Price_BAO_Set::processAmount( $this->_values['fee']['fields'], 
                                                      $params, $lineItem[0] );
                }
                
                $params['fee_level']                = $params['amount_level'];
                $contributionParams['total_amount'] = $params['amount'];
                //fix for CRM-3086
                $params['fee_amount'] = $params['amount'];
            }
            
            if ( isset( $params['priceSetId'] ) ) {
                $this->set( 'lineItem', $lineItem );
                $this->_lineItem = $lineItem;
                $lineItem = array_merge($lineItem, $additionalParticipantDetails);
                $this->assign ( 'lineItem', empty($lineItem[0])?false:$lineItem );
            } else {
                $this->assign( 'amount_level', $params['amount_level'] ); 
            }
        } 
                
        $this->_params = $params;
        unset($params['amount']);
        $params['register_date'] = CRM_Utils_Date::processDate( $params['register_date'], $params['register_date_time'] );
        $params['receive_date' ] = CRM_Utils_Date::processDate( CRM_Utils_Array::value( 'receive_date', $params ) );
        $params['contact_id'   ] = $this->_contactId;
        
        // overwrite actual payment amount if entered
        if ( CRM_Utils_Array::value( 'total_amount', $params ) ) {
            $contributionParams['total_amount'] = CRM_Utils_Array::value( 'total_amount', $params );
        }
        
        require_once 'CRM/Contact/BAO/Contact.php';
        // Retrieve the name and email of the current user - this will be the FROM for the receipt email
        $session =& CRM_Core_Session::singleton( );
        $userID  = $session->get( 'userID' );
        list( $userName, 
              $userEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $userID );
        require_once "CRM/Event/BAO/Participant.php";
        
        if ( $this->_mode ) {
            if ( ! $this->_isPaidEvent ) {
                CRM_Core_Error::fatal( ts( 'Selected Event is not Paid Event ') );
            }
            //modify params according to parameter used in create
            //participant method (addParticipant)            
            $params['participant_status_id']     = $params['status_id'] ;
            $params['participant_role_id']       = $params['role_id'] ;
            $params['participant_register_date'] = $params['register_date'] ;
            $params['participant_source']        = $params['source'] ;
           
            require_once 'CRM/Core/BAO/PaymentProcessor.php';
            $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $this->_params['payment_processor_id'],
                                                                                  $this->_mode );
            require_once "CRM/Contact/BAO/Contact.php";
            
            $now = date( 'YmdHis' );
            $fields = array( );
            
            // set email for primary location.
            $fields["email-Primary"] = 1;
            list( $this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail ) = CRM_Contact_BAO_Contact::getContactDetails( $this->_contactId );
            $params["email-Primary"] = $params["email-{$this->_bltID}"] = $this->_contributorEmail;
            
            $params['register_date'] = $now;
            
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
            
            $fields["email-{$this->_bltID}"] = 1;
            
            $ctype = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $this->_contactId, 'contact_type' );
            
            $nameFields = array( 'first_name', 'middle_name', 'last_name' );
            
            foreach ( $nameFields as $name ) {
                $fields[$name] = 1;
                if ( array_key_exists( "billing_$name", $params ) ) {
                    $params[$name]            = $params["billing_{$name}"];
                    $params['preserveDBName'] = true;
                }
            }
            $contactID = CRM_Contact_BAO_Contact::createProfileContact( $params, $fields, $this->_contactId, null, null, $ctype );
            
        }

		// build custom data getFields array
		$customFieldsRole  = CRM_Core_BAO_CustomField::getFields( 'Participant', false, false, 
																CRM_Utils_Array::value( 'role_id', $params ), $this->_roleCustomDataTypeID );
		$customFieldsEvent = CRM_Core_BAO_CustomField::getFields( 'Participant', false, false, 
														        CRM_Utils_Array::value( 'event_id', $params ), $this->_eventNameCustomDataTypeID );
		$customFieldsEventType = 
            CRM_Core_BAO_CustomField::getFields( 'Participant', false, false, 
                                                 $this->_eventTypeId, $this->_eventTypeCustomDataTypeID );
		$customFields      = CRM_Utils_Array::crmArrayMerge( $customFieldsRole, 
															CRM_Core_BAO_CustomField::getFields( 'Participant', false, false, null, null, true ) );
		$customFields      = CRM_Utils_Array::crmArrayMerge( $customFieldsEvent, $customFields );
		$customFields      = CRM_Utils_Array::crmArrayMerge( $customFieldsEventType, $customFields );

        $params['custom'] = CRM_Core_BAO_CustomField::postProcess( $params,
                                                                   $customFields,
                                                                   $this->_id,
                                                                   'Participant' );

        if ( $this->_mode ) {
            // add all the additioanl payment params we need
            $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::stateProvinceAbbreviation( $this->_params["billing_state_province_id-{$this->_bltID}"] );
            $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::countryIsoCode( $this->_params["billing_country_id-{$this->_bltID}"] );
            
            $this->_params['year'      ]     = $this->_params['credit_card_exp_date']['Y'];
            $this->_params['month'     ]     = $this->_params['credit_card_exp_date']['M'];
            $this->_params['ip_address']     = CRM_Utils_System::ipAddress( );
            $this->_params['amount'        ] = $params['fee_amount'];
            $this->_params['amount_level'  ] = $params['amount_level'];
            $this->_params['currencyID'    ] = $config->defaultCurrency;
            $this->_params['payment_action'] = 'Sale';
            $this->_params['invoiceID']      = md5( uniqid( rand( ), true ) );
        
            // at this point we've created a contact and stored its address etc
            // all the payment processors expect the name and address to be in the 
            // so we copy stuff over to first_name etc. 
            $paymentParams = $this->_params;
            if ( CRM_Utils_Array::value( 'send_receipt', $this->_params ) ) {
                $paymentParams['email'] = $this->_contributorEmail;
            }
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::mapParams( $this->_bltID, $this->_params, $paymentParams, true );
            
            $payment =& CRM_Core_Payment::singleton( $this->_mode, 'Event', $this->_paymentProcessor, $this );
            
            $result =& $payment->doDirectPayment( $paymentParams );
            
            if ( is_a( $result, 'CRM_Core_Error' ) ) {
                CRM_Core_Error::displaySessionError( $result );
                CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/contact/view/participant',
                                                                   "reset=1&action=add&cid={$this->_contactId}&context=participant&mode={$this->_mode}" ) );
            }
            
            if ( $result ) {
                $this->_params = array_merge( $this->_params, $result );
            }
            
            $this->_params['receive_date'] = $now;
            
            if ( CRM_Utils_Array::value( 'send_receipt', $this->_params ) ) {
                $this->_params['receipt_date'] = $now;
            } else {
                $this->_params['receipt_date'] = null;
            }
            
            $this->set( 'params', $this->_params );
            $this->assign( 'trxn_id', $result['trxn_id'] );
            $this->assign( 'receive_date',
                           CRM_Utils_Date::processDate( $this->_params['receive_date']) );
            // set source if not set 
            
            $this->_params['description'] = ts( 'Submit Credit Card for Event Registration by: %1', array( 1 => $userName ) );
            require_once 'CRM/Event/Form/Registration/Confirm.php';
            require_once 'CRM/Event/Form/Registration.php';
            //add contribution record
            $this->_params['contribution_type_id'] = 
                CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event', $params['event_id'], 'contribution_type_id' );
            $this->_params['mode'] = $this->_mode;

            //add contribution reocord
            $contribution = CRM_Event_Form_Registration_Confirm::processContribution( $this, $this->_params, $result, $contactID, false );
          
            // add participant record
            $participants   = array();
            $participants[] = CRM_Event_Form_Registration::addParticipant( $this->_params, $contactID );

            //add custom data for participant
            require_once 'CRM/Core/BAO/CustomValueTable.php';
            CRM_Core_BAO_CustomValueTable::postProcess( $this->_params,
                                                        CRM_Core_DAO::$_nullArray,
                                                        'civicrm_participant',
                                                        $participants[0]->id,
                                                        'Participant' );
            //add participant payment
            require_once 'CRM/Event/BAO/ParticipantPayment.php';
            $paymentParticipant = array( 'participant_id' => $participants[0]->id ,
                                         'contribution_id' => $contribution->id, ); 
            $ids = array();       
            
            CRM_Event_BAO_ParticipantPayment::create( $paymentParticipant, $ids);
            $eventTitle = CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event',
                                                   $params['event_id'],
                                                   'title' );
            $this->_contactIds[] = $this->_contactId;

        } else {
            $participants = array();
            // fix note if deleted
            if ( !$params['note'] ) {
                $params['note'] = 'null';
            }

            if ( $this->_single ) {
                $participants[] = CRM_Event_BAO_Participant::create( $params );
            } else { 
                foreach ( $this->_contactIds as $contactID ) {
                    $commonParams = $params;
                    $commonParams['contact_id'] = $contactID;
                    $participants[] = CRM_Event_BAO_Participant::create( $commonParams );   
                }        
            }
            
            if ( isset( $params['event_id'] ) ) {
                $eventTitle = CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_Event',
                                                           $params['event_id'],
                                                           'title' );
            }
            
            if ( $this->_single ) {
                $this->_contactIds[] = $this->_contactId;
            }
            
            if ( CRM_Utils_Array::value( 'record_contribution', $params ) ) {
                if ( CRM_Utils_Array::value( 'id', $params ) ) {
                    if ( $this->_onlinePendingContributionId ) {
                        $ids['contribution'] = $this->_onlinePendingContributionId;
                    } else {
                        $ids['contribution'] = CRM_Core_DAO::getFieldValue( 'CRM_Event_DAO_ParticipantPayment', 
                                                                            $params['id'], 
                                                                            'contribution_id', 
                                                                            'participant_id' );
                    }
                }
                unset($params['note']);
                
                //build contribution params 
                if ( !$this->_onlinePendingContributionId ) {
                    $contributionParams['source'] = "{$eventTitle}: Offline registration (by {$userName})";
                }
                
                $contributionParams['currency'             ] = $config->defaultCurrency;
                $contributionParams['non_deductible_amount'] = 'null';
                $contributionParams['receipt_date'         ] = CRM_Utils_Array::value( 'send_receipt', $params ) ? CRM_Utils_Array::value( 'receive_date', $params ) : 'null';
                
                $recordContribution = array( 'contact_id', 'contribution_type_id', 'payment_instrument_id', 'trxn_id', 'contribution_status_id', 'receive_date', 'check_number' );
                
                foreach ( $recordContribution as $f ) {
                    $contributionParams[$f] = CRM_Utils_Array::value( $f, $params );
                    if ( $f == 'trxn_id' ) {
                        $this->assign ( 'trxn_id',  $contributionParams[$f] );                   
                    }
                }
                
                //insert contribution type name in receipt.
                $this->assign( 'contributionTypeName', CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_ContributionType',
                                                                                    $contributionParams['contribution_type_id'] ) );
                require_once 'CRM/Contribute/BAO/Contribution.php';
                $contributions = array( );
                if ( $this->_single ) {
                    $contributions[] =& CRM_Contribute_BAO_Contribution::create( $contributionParams, $ids );
                } else {
                    $ids = array( );
                    foreach ( $this->_contactIds as $contactID ) {
                        $contributionParams['contact_id'] = $contactID;
                        $contributions[] =& CRM_Contribute_BAO_Contribution::create( $contributionParams, $ids );
                    }           
                }
                
                //insert payment record for this participation
                if( !$ids['contribution'] ) {
                    require_once 'CRM/Event/DAO/ParticipantPayment.php';
                    foreach ( $this->_contactIds as $num => $contactID ) {
                        $ppDAO =& new CRM_Event_DAO_ParticipantPayment();   
                        $ppDAO->participant_id  = $participants[$num]->id;
                        $ppDAO->contribution_id = $contributions[$num]->id;
                        $ppDAO->save();
                    }
                }
            } 
        }

        // also store lineitem stuff here        
        if ( $this->_lineItem  & $this->_action & CRM_Core_Action::ADD ) {
            require_once 'CRM/Price/BAO/LineItem.php';
            foreach ( $this->_contactIds as $num => $contactID ) {
                foreach ( $this->_lineItem as $key => $value ) {
                    if ( is_array ( $value ) && $value != 'skip' ) {
                        foreach( $value as $line ) {
                            $line['entity_table'] = 'civicrm_participant';
                            $line['entity_id'] = $participants[$num]->id;
                            CRM_Price_BAO_LineItem::create( $line );
                        }
                    }
                }
            }
        }
        
        $updateStatusMsg = null;
        //send mail when participant status changed, CRM-4326
        if ( $this->_id && $this->_statusId && 
             $this->_statusId != CRM_Utils_Array::value( 'status_id', $params ) &&
             CRM_Utils_Array::value( 'is_notify', $params )
             ) {
            require_once "CRM/Event/BAO/Participant.php";
            $updateStatusMsg = CRM_Event_BAO_Participant::updateStatusMessage( $this->_id, 
                                                                               $params['status_id'], 
                                                                               $this->_statusId );
        }
        
        if ( CRM_Utils_Array::value( 'send_receipt', $params ) ) {
            $receiptFrom = "$userName <$userEmail>";
                        
            $this->assign( 'module', 'Event Registration' );          
            //use of the message template below requires variables in different format
            $event = $events = array();
            $returnProperties = array( 'fee_label', 'start_date', 'end_date', 'is_show_location', 'title' );
            
            //get all event details.
            CRM_Core_DAO::commonRetrieveAll( 'CRM_Event_DAO_Event', 'id', $params['event_id'], $events, $returnProperties );
            $event = $events[$params['event_id']];
            unset($event['start_date']);
            unset($event['end_date']);
           
            $role = CRM_Event_PseudoConstant::participantRole();
            $event['participant_role'] = $role[$params['role_id']];
            $event['is_monetary'] = $this->_isPaidEvent;
           
            if ( $params['receipt_text'] ) {
                $event['confirm_email_text'] =  $params['receipt_text'];
            }
          
            $this->assign( 'isAmountzero', 1 );
            $this->assign( 'event' , $event );
            
            $this->assign( 'isShowLocation', $event['is_show_location'] ); 
            if ( CRM_Utils_Array::value( 'is_show_location', $event ) == 1 ) {
                $locationParams = array( 'entity_id'    => $params['event_id'] ,
                                         'entity_table' => 'civicrm_event');
                require_once 'CRM/Core/BAO/Location.php';
                $location = CRM_Core_BAO_Location::getValues( $locationParams, true );
                $this->assign( 'location', $location );
            }             
            
            $status = CRM_Event_PseudoConstant::participantStatus();
            if ( $this->_isPaidEvent ) {
                $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
                if ( ! $this->_mode ) {
                    $this->assign( 'paidBy',
                                   CRM_Utils_Array::value( $params['payment_instrument_id'],
                                                           $paymentInstrument ) );
                }

                $this->assign( 'totalAmount', $contributionParams['total_amount'] );
                
                $this->assign( 'isPrimary', 1 );
                $this->assign('checkNumber', CRM_Utils_Array::value( 'check_number', $params )); 
            }
            if ( $this->_mode ) {
                if ( CRM_Utils_Array::value( 'billing_first_name', $params ) ) {
                    $name = $params['billing_first_name'];
                    
                }
                
                if ( CRM_Utils_Array::value( 'billing_middle_name', $params ) ) {
                    $name .= " {$params['billing_middle_name']}";
                }
                
                if ( CRM_Utils_Array::value( 'billing_last_name', $params ) ) {
                    $name .= " {$params['billing_last_name']}";
                }
                $this->assign( 'billingName', $name );
                                                             
                // assign the address formatted up for display
                $addressParts  = array( "street_address-{$this->_bltID}",
                                        "city-{$this->_bltID}",
                                        "postal_code-{$this->_bltID}",
                                        "state_province-{$this->_bltID}",
                                        "country-{$this->_bltID}");
                $addressFields = array( );
                foreach ( $addressParts as $part ) {
                    list( $n, $id ) = explode( '-', $part );
                    if ( isset ( $this->_params['billing_' . $part] ) ) {
                        $addressFields[$n] = $this->_params['billing_' . $part];
                    }                    
                }
                require_once 'CRM/Utils/Address.php';
                $this->assign('address', CRM_Utils_Address::format( $addressFields ) );

                $date = CRM_Utils_Date::format( $params['credit_card_exp_date'] );
                $date = CRM_Utils_Date::mysqlToIso( $date );
                $this->assign( 'credit_card_exp_date', $date );
                $this->assign( 'credit_card_number',
                               CRM_Utils_System::mungeCreditCard( $params['credit_card_number'] ) );
                $this->assign( 'credit_card_type', $params['credit_card_type'] );
                $this->assign( 'contributeMode', 'direct');
                $this->assign( 'isAmountzero' , 0);
                $this->assign( 'is_pay_later',0);
                $this->assign( 'isPrimary', 1 );
            }
            
            $this->assign( 'register_date', $params['register_date'] );
            if ( $params['receive_date'] ) {
                $this->assign( 'receive_date', $params['receive_date'] );
            }

            $participant = array( array( 'participant_id', '=', $participants[0]->id, 0, 0 ) );
            // check whether its a test drive ref CRM-3075
            if ( CRM_Utils_Array::value( 'is_test', $this->_defaultValues ) ) {
                $participant[] = array( 'participant_test', '=', 1, 0, 0 ); 
            } 
            
            $template =& CRM_Core_Smarty::singleton( );
            $customGroup = array( );
            //format submitted data
            foreach ( $params['custom'] as $fieldID => $values) {
                foreach ( $values as $fieldValue ) {
                    $customValue = array( 'data' => $fieldValue['value'] );
                    $customFields[$fieldID]['id'] = $fieldID;
                    $formattedValue = CRM_Core_BAO_CustomGroup::formatCustomValues( $customValue, $customFields[$fieldID] );
                    $customGroup[$customFields[$fieldID]['groupTitle']][$customFields[$fieldID]['label']] = str_replace( '&nbsp;', '', $formattedValue );
                }                
            }

            foreach ( $this->_contactIds as $num => $contactID ) {
                // Retrieve the name and email of the contact - this will be the TO for receipt email
                list( $this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail ) = CRM_Contact_BAO_Contact::getContactDetails( $contactID );
                $this->_contributorDisplayName = ($this->_contributorDisplayName == ' ') ? $this->_contributorEmail : $this->_contributorDisplayName;
                $this->assign( 'customGroup', $customGroup );
                $this->assign( 'contactID', $contactID);
                $this->assign( 'participantID', $participants[$num]->id );
                
                if ( $this->_isPaidEvent ) {
                    // fix amount for each of participants ( for bulk mode )
                    $eventAmount = array();
                    if ( !empty($additionalParticipantDetails) ) {
                        $params['amount_level'] = $params['amount_level'].' - '.$this->_contributorDisplayName;
                    }
                    
                    $eventAmount[$num] = array( 'label'  => $params['amount_level'], 
                                                'amount' => $params['fee_amount'] );
                    //as we are using same template for online & offline registration.
                    //So we have to build amount as array.
                    $eventAmount = array_merge( $eventAmount, $additionalParticipantDetails );
                    $this->assign( 'amount', $eventAmount );
                }

                $sendTemplateParams = array(
                    'groupName' => 'msg_tpl_workflow_event',
                    'valueName' => 'event_offline_receipt',
                    'contactId' => $contactID,
                    'isTest'    => (bool) CRM_Utils_Array::value('is_test', $this->_defaultValues),
                );

                // try to send emails only if email id is present
                // and the do-not-email option is not checked for that contact 
                if($this->_contributorEmail and !$this->_toDoNotEmail) {
                    $sendTemplateParams['from']    = $receiptFrom;
                    $sendTemplateParams['toName']  = $this->_contributorDisplayName;
                    $sendTemplateParams['toEmail'] = $this->_contributorEmail;
                }

                require_once 'CRM/Core/BAO/MessageTemplates.php';
                list ($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);

                if ($mailSent) {
                    $sent[] = $contactID;
                } else {
                    $notSent[] = $contactID;
                }
            }
        }
        
        if ( ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            $statusMsg = ts('Event registration information for %1 has been updated.', array(1 => $this->_contributorDisplayName));
            if ( CRM_Utils_Array::value( 'send_receipt', $params ) && count($sent) ) {
                $statusMsg .= ' ' .  ts('A confirmation email has been sent to %1', array(1 => $this->_contributorEmail));
            }
            
            if ( $updateStatusMsg ) {
                $statusMsg = "{$statusMsg} {$updateStatusMsg}";
            }
            
        } elseif ( $this->_action & CRM_Core_Action::ADD ) {
            if ( $this->_single ) {
                $statusMsg = ts('Event registration for %1 has been added.', array(1 => $this->_contributorDisplayName));
                if ( CRM_Utils_Array::value( 'send_receipt', $params ) && count($sent) ) {
                    $statusMsg .= ' ' .  ts('A confirmation email has been sent to %1.', array(1 => $this->_contributorEmail));
                }
            } else {
                $statusMsg = ts('Total Participant(s) added to event: %1.', array(1 => count($this->_contactIds)));
                if( count($notSent) > 0 ) {
                    $statusMsg .= ' ' . ts('Email has NOT been sent to %1 contact - communication preferences specify DO NOT EMAIL OR valid Email is NOT present. ', array(1 => count($notSent)));
                } elseif ( isset ( $params['send_receipt'] ) ) {
                    $statusMsg .= ' ' .  ts('A confirmation email has been sent to ALL participants');
                }
            }
        }
        require_once "CRM/Core/Session.php";
        CRM_Core_Session::setStatus( "{$statusMsg}" );
        
        $buttonName = $this->controller->getButtonName( );
        if ( $this->_context == 'standalone' ) {
            if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
                $session->replaceUserContext(CRM_Utils_System::url('civicrm/participant/add', 
                                                                   'reset=1&action=add&context=standalone') );
            } else {
                $session->replaceUserContext(CRM_Utils_System::url( 'civicrm/contact/view',
                                                                    "reset=1&cid={$this->_contactId}&selectedChild=participant" ) );
            }
        } else if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/participant', 
                                                               "reset=1&action=add&context={$this->_context}&cid={$this->_contactId}") );
        }
    }
      
}

