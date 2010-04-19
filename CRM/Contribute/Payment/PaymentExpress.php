<?php 

  /*
   * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
   * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
   * Licensed to CiviCRM under the Academic Free License version 3.0.
   *
   * Grateful acknowledgements go to Donald Lobo for invaluable assistance
   * in creating this payment processor module
   */


require_once('CRM/Core/Payment/PaymentExpress.php');

class CRM_Contribute_Payment_PaymentExpress extends CRM_Core_Payment_PaymentExpress { 
    /** 
     * We only need one instance of this object. So we use the singleton 
     * pattern and cache the instance in this variable 
     * 
     * @var object 
     * @static 
     */ 
    static private $_singleton = null; 
    
    /** 
     * Constructor 
     *
     * @param string $mode the mode of operation: live or test
     * 
     * @return void 
     */ 
    function __construct( $mode, &$paymentProcessor ) {
        parent::__construct( $mode, $paymentProcessor );
    }

    /** 
     * singleton function used to manage this object 
     * 
     * @param string $mode the mode of operation: live or test
     *
     * @return object 
     * @static 
     * 
     */ 
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] =& new CRM_Contribute_Payment_PaymentExpress( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    } 

    /**  
     * Sets appropriate parameters for checking out to google
     *  
     * @param array $params  name value pair of contribution datat
     *  
     * @return void  
     * @access public 
     *  
     */  
    function doTransferCheckout( &$params ) {
        parent::doTransferCheckout( $params, 'contribute' );
    }

}
