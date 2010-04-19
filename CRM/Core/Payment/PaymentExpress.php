<?php

  /*
   * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
   * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
   * Licensed to CiviCRM under the Academic Free License version 3.0.
   *
   * Grateful acknowledgements go to Donald Lobo for invaluable assistance
   * in creating this payment processor module
   */
 
require_once 'CRM/Core/Payment.php';


class CRM_Core_Payment_PaymentExpress extends CRM_Core_Payment {
    const
        CHARSET = 'iso-8859-1';
    static protected $_mode = null;

    static protected $_params = array();
    /**
     * Constructor
     *
     * @param string $mode the mode of operation: live or test
     *
     * @return void
     */
    function __construct( $mode, &$paymentProcessor ) {

        $this->_mode             = $mode;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = ts('DPS Payment Express');
    }

    function checkConfig( ) {
        $config =& CRM_Core_Config::singleton( );

        $error = array( );

        if ( empty( $this->_paymentProcessor['user_name'] ) ) {
            $error[] = ts( 'UserID is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( empty( $this->_paymentProcessor['password'] ) ) {
            $error[] = ts( 'pxAccess / pxPay Key is not set in the Administer CiviCRM &raquo; Payment Processor.' );
        }
        
        if ( ! empty( $error ) ) {
            return implode( '<p>', $error );
        } else {
            return null;
        }
    }

    function setExpressCheckOut( &$params ) {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function getExpressCheckoutDetails( $token ) {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }
    function doExpressCheckout( &$params ) {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) ); 
    }

    function doDirectPayment( &$params ) {
        CRM_Core_Error::fatal( ts( 'This function is not implemented' ) );
    }

    /**  
     * Main transaction function
     *  
     * @param array $params  name value pair of contribution data
     *  
     * @return void  
     * @access public 
     *  
     */   
    function doTransferCheckout( &$params, $component ) 
    {
        $component = strtolower( $component );
	$config    =& CRM_Core_Config::singleton( );
        if ( $component != 'contribute' && $component != 'event' ) {
            CRM_Core_Error::fatal( ts( 'Component is invalid' ) );
        }
        
        $url = $config->userFrameworkResourceURL."extern/pxIPN.php";
		
        if ( $component == 'event') {
            $cancelURL = CRM_Utils_System::url( 'civicrm/event/register',
                                                "_qf_Confirm_display=true&qfKey={$params['qfKey']}", 
                                                false, null, false );
	} else if ( $component == 'contribute' ) {
            $cancelURL = CRM_Utils_System::url( 'civicrm/contribute/transact',
                                                "_qf_Confirm_display=true&qfKey={$params['qfKey']}", 
                                                false, null, false );
	}		
        
        
	/*  
         * Build the private data string to pass to DPS, which they will give back to us with the
         *
         * transaction result.  We are building this as a comma-separated list so as to avoid long URLs.
         *
         * Parameters passed: a=contactID, b=contributionID,c=contributionTypeID,d=invoiceID,e=membershipID,f=participantID,g=eventID
         */
	$privateData = "a={$params['contactID']},b={$params['contributionID']},c={$params['contributionTypeID']},d={$params['invoiceID']}";
        
	if ( $component == 'event') {
	    $privateData .= ",f={$params['participantID']},g={$params['eventID']}";
	    $merchantRef = "event registration";            
	} elseif ( $component == 'contribute' ) {
            $merchantRef = "Charitable Contribution";
            $membershipID = CRM_Utils_Array::value( 'membershipID', $params );
            if ( $membershipID ) {
                $privateData .= ",e=$membershipID";
            }
	}		

    // Allow further manipulation of params via custom hooks
    CRM_Utils_Hook::alterPaymentProcessorParams( $this, $params, $privateData );

	/*  
	 *  determine whether method is pxaccess or pxpay by whether signature (mac key) is defined
         */
        
        
        if ( empty($this->_paymentProcessor['signature']) ) {
            /*
             * Processor is pxpay 
             *
             * This contains the XML/Curl functions we'll need to generate the XML request
             */
            require_once 'CRM/Core/Payment/PaymentExpressUtils.php';
            
            // Build a valid XML string to pass to DPS
            $generateRequest = _valueXml(array(
                                               'PxPayUserId'       => $this->_paymentProcessor['user_name'],
                                               'PxPayKey'          => $this->_paymentProcessor['password'],
                                               'AmountInput'       => str_replace(",","", number_format($params['amount'],2)),
                                               'CurrencyInput'     => $params['currencyID'],
                                               'MerchantReference' => $merchantRef,
                                               'TxnData1'          => $params['qfKey'],
                                               'TxnData2'          => $privateData,
                                               'TxnData3'          => $component,
                                               'TxnType'           => 'Purchase',
                                               'TxnId'             => '', // Leave this empty for now, causes an error with DPS if we populate it
                                               'UrlFail'           => $url,
                                               'UrlSuccess'        => $url
                                               ));

	    $generateRequest = _valueXml('GenerateRequest', $generateRequest);
	    // Get the special validated URL back from DPS by sending them the XML we've generated
            $curl    = _initCURL($generateRequest,$this->_paymentProcessor['url_site']);
            $success = false;
        
	    if ( $response = curl_exec($curl) ) {
		curl_close($curl);
		$valid = _xmlAttribute($response, 'valid');
		if (1 == $valid){
		    // the request was validated, so we'll get the URL and redirect to it
		    $uri = _xmlElement($response, 'URI');
		    CRM_Utils_System::redirect( $uri );
		} else {
		    // redisplay confirmation page
		    CRM_Utils_System::redirect($cancelURL);
		}
	    } else {
		// calling DPS failed
		CRM_Core_Error::fatal( ts( 'Unable to establish connection to the payment gateway.' ) );
	    }		     
        } else {
	    $processortype   = "pxaccess";
	    require_once('PaymentExpress/pxaccess.php');
	    $PxAccess_Url    = $this->_paymentProcessor['url_site'];    // URL
            $PxAccess_Userid = $this->_paymentProcessor['user_name'];   // User ID
            $PxAccess_Key    = $this->_paymentProcessor['password'];    // Your DES Key from DPS
            $Mac_Key	     = $this->_paymentProcessor['signature'];   // Your MAC key from DPS
            
            $pxaccess = new PxAccess($PxAccess_Url, $PxAccess_Userid, $PxAccess_Key,$Mac_Key);
	    $request  = new PxPayRequest();
	    $request->setAmountInput(number_format($params['amount'],2));
	    $request->setTxnData1($params['qfKey']); 
	    $request->setTxnData2($privateData); 
	    $request->setTxnData3($component);	
	    $request->setTxnType("Purchase"); 
	    $request->setInputCurrency($params['currencyID']);
	    $request->setMerchantReference($merchantRef);
	    $request->setUrlFail ($url);
	    $request->setUrlSuccess ($url);

	    $request_string = $pxaccess->makeRequest($request);
		
	    CRM_Utils_System::redirect( $request_string ) ;
	    exit;
        }		     
    }		
}
