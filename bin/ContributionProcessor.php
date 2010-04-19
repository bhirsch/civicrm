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

class CiviContributeProcessor {
    static $_paypalParamsMapper = 
        array(
              //category    => array(paypal_param    => civicrm_field);
              'contact'     => array(
                                     'salutation'    => 'prefix_id',
                                     'firstname'     => 'first_name',
                                     'lastname'      => 'last_name',
                                     'middlename'    => 'middle_name',
                                     'suffix'        => 'suffix_id',
                                     'email'         => 'email',
                                     ),
              'location'    => array(
                                     'shiptoname'    => 'address_name',
                                     'shiptostreet'  => 'street_address',
                                     'shiptostreet2' => 'supplemental_address_1',
                                     'shiptocity'    => 'city',
                                     'shiptostate'   => 'state',
                                     'shiptozip'     => 'postal_code',
                                     'countrycode'   => 'country',
                                     ),
              'transaction' => array(
                                     'amt'              => 'total_amount',
                                     'feeamt'           => 'fee_amount',
                                     'transactionid'    => 'trxn_id',
                                     'currencycode'     => 'currencyID',
                                     'l_name0'          => 'source',
                                     'ordertime'        => 'receive_date',
                                     'note'             => 'note',
                                     'is_test'          => 'is_test',
                                     'transactiontype'  => 'trxn_type',
                                     'recurrences'      => 'installments',
                                     'l_amt2'           => 'amount',
                                     'l_period2'        => 'lol',
                                     'invnum'           => 'invoice_id',
                                     'subscriptiondate' => 'start_date',
                                     'timestamp'        => 'modified_date',
                                     ),
              );

    static $_googleParamsMapper = 
        array(
              //category    => array(google_param    => civicrm_field);
              'contact'     => array(
                                     'contact-name'  => 'display_name',
                                     'contact-name'  => 'sort_name',
                                     'email'         => 'email',
                                     ),
              'location'    => array(
                                     'address1'     => 'street_address',
                                     'address2'     => 'supplemental_address_1',
                                     'city'         => 'city',
                                     'postal-code'  => 'postal_code',
                                     'country-code' => 'country',
                                     ),
              'transaction' => array(
                                     'total-charge-amount' => 'total_amount',
                                     'google-order-number' => 'trxn_id',
                                     'currency'            => 'currency',
                                     'item-name'           => 'note',
                                     'timestamp'           => 'receive_date',
                                     'latest-charge-fee'   => 'fee_amount',
                                     'net-amount'          => 'net_amount',
                                     ),
              );

    static $_csvParamsMapper = 
        array(
              // Note: if csv header is not present in the mapper, header itself 
              // is considered as a civicrm field.

              //category    => array(csv_header      => civicrm_field);
              'contact'     => array(
                                     'first_name'    => 'first_name',
                                     'last_name'     => 'last_name',
                                     'middle_name'   => 'middle_name',
                                     'email'         => 'email',
                                     ),
              'location'    => array(
                                     'street_address'         => 'street_address',
                                     'supplemental_address_1' => 'supplemental_address_1',
                                     'city'                   => 'city',
                                     'postal_code'            => 'postal_code',
                                     'country'                => 'country',
                                     ),
              'transaction' => array(
                                     'total_amount'  => 'total_amount',
                                     'trxn_id'       => 'trxn_id',
                                     'currency'      => 'currency',
                                     'source'        => 'source',
                                     'receive_date'  => 'receive_date',
                                     'note'          => 'note',
                                     'is_test'       => 'is_test',
                                     ),
              );

    static function paypal( $paymentProcessor, $paymentMode, $start, $end ) {
        $url       = "{$paymentProcessor['url_api']}nvp";
        
        $keyArgs = array( 'user'      => $paymentProcessor['user_name'],
                          'pwd'       => $paymentProcessor['password'] ,
                          'signature' => $paymentProcessor['signature'],
                          'version'   => 3.0,
                          );

        $args =  $keyArgs;
        $args += array( 'method'    => 'TransactionSearch',
                        'startdate' => $start,
                        'enddate'   => $end );

        require_once 'CRM/Core/Payment/PayPalImpl.php';
        $result = CRM_Core_Payment_PayPalImpl::invokeAPI( $args, $url );

        require_once "CRM/Contribute/BAO/Contribution/Utils.php";

        $keyArgs['method'] = 'GetTransactionDetails';
        foreach ( $result as $name => $value ) {
            if ( substr( $name, 0, 15 ) == 'l_transactionid' ) {

                // We don't/can't process subscription notifications, which appear
                // to be identified by transaction ids beginning with S-
                if ( substr( $value, 0, 2 ) == 'S-' )  {
                    continue;
                }
            
                $keyArgs['transactionid'] = $value;
                $trxnDetails = CRM_Core_Payment_PayPalImpl::invokeAPI( $keyArgs, $url );
                if ( is_a( $trxnDetails, 'CRM_Core_Error' ) ) {
                    echo "PAYPAL ERROR: Skipping transaction id: $value<p>";
                    continue;
                }

                // only process completed payments
                if ( strtolower( $trxnDetails['paymentstatus'] ) != 'completed' ) {
                    continue;
                }

                // only process receipts, not payments
                if ( strtolower( $trxnDetails['transactiontype'] ) == 'sendmoney' ) {
                    continue;
                }

                $params = CRM_Contribute_BAO_Contribution_Utils::formatAPIParams( $trxnDetails, 
                                                                                  self::$_paypalParamsMapper,
                                                                                  'paypal' );
                if ( $paymentMode == 'test' ) {
                    $params['is_test'] = 1;
                } else {
                    $params['is_test'] = 0;
                }

                if ( CRM_Contribute_BAO_Contribution_Utils::processAPIContribution( $params ) ) {
                    CRM_Core_Error::debug_log_message( "Processed - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", true );
                } else {
                    CRM_Core_Error::debug_log_message( "Skipped - {$trxnDetails['email']}, {$trxnDetails['amt']}, {$value} ..<p>", true );
                }
            }
        }
    }

    static function google( $paymentProcessor, $paymentMode, $start, $end ) {
        require_once "CRM/Contribute/BAO/Contribution/Utils.php";
        require_once 'CRM/Core/Payment/Google.php';
        $nextPageToken = true;
        $searchParams  = array( 'start'              => $start, 
                                'end'                => $end,
                                'notification-types' => array('charge-amount') );
        
        $response = CRM_Core_Payment_Google::invokeAPI( $paymentProcessor, $searchParams );
        
        while ( $nextPageToken ) {
            if ( $response[0] == 'error' ) {
                CRM_Core_Error::debug_log_message( "GOOGLE ERROR: " . 
                                                   $response[1]['error']['error-message']['VALUE'], true);
            }
            $nextPageToken = isset($response[1][$response[0]]['next-page-token']['VALUE']) ? 
                $response[1][$response[0]]['next-page-token']['VALUE'] : false;
            
            if ( is_array($response[1][$response[0]]['notifications']['charge-amount-notification']) ) {

                if ( array_key_exists('google-order-number', 
                                      $response[1][$response[0]]['notifications']['charge-amount-notification']) ) {
                    // sometimes 'charge-amount-notification' itself is an absolute 
                    // array and not array of arrays. This is the case when there is only one 
                    // charge-amount-notification. Hack for this special case -
                    $chrgAmt = $response[1][$response[0]]['notifications']['charge-amount-notification'];
                    unset($response[1][$response[0]]['notifications']['charge-amount-notification']);
                    $response[1][$response[0]]['notifications']['charge-amount-notification'][] = $chrgAmt;
                }

                foreach ( $response[1][$response[0]]['notifications']['charge-amount-notification']
                          as $amtData ) {
                    $searchParams =
                        array( 'order-numbers'      => array($amtData['google-order-number']['VALUE']),
                               'notification-types' => array('risk-information', 'new-order','charge-amount') );
                    $response     = CRM_Core_Payment_Google::invokeAPI( $paymentProcessor, 
                                                                        $searchParams );
                    $response[]   = $amtData; // append amount information as well

                    $params = CRM_Contribute_BAO_Contribution_Utils::formatAPIParams( $response,
                                                                                      self::$_googleParamsMapper,
                                                                                      'google' );
                    if ( $paymentMode == 'test' ) {
                        $params['transaction']['is_test'] = 1;
                    } else {
                        $params['transaction']['is_test'] = 0;
                    }
                    if ( CRM_Contribute_BAO_Contribution_Utils::processAPIContribution( $params ) ) {
                        CRM_Core_Error::debug_log_message( "Processed - {$params['email']}, {$amtData['total-charge-amount']['VALUE']}, {$amtData['google-order-number']['VALUE']} ..<p>", true ) ;
                    } else {
                        CRM_Core_Error::debug_log_message( "Skipped - {$params['email']}, {$amtData['total-charge-amount']['VALUE']}, {$amtData['google-order-number']['VALUE']} ..<p>", true ) ;
                    }
                }
                
                if ( $nextPageToken ) {
                    $searchParams = array( 'next-page-token' => $nextPageToken );
                    $response     = CRM_Core_Payment_Google::invokeAPI( $paymentProcessor, $searchParams );
                }
            }
        }
    }

    static function csv( ) {
        $csvFile     = '/home/deepak/Desktop/crm-4247.csv';
        $delimiter   = ";";
        $row         = 1;

        $handle = fopen($csvFile, "r");
        if ( ! $handle ) {
            CRM_Core_Error::fatal("Can't locate csv file.");
        }

        require_once "CRM/Contribute/BAO/Contribution/Utils.php";
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if ( $row !== 1 ) {
                $data['header'] = $header;
                $params = CRM_Contribute_BAO_Contribution_Utils::formatAPIParams( $data,
                                                                                  self::$_csvParamsMapper,
                                                                                  'csv' );
                if ( CRM_Contribute_BAO_Contribution_Utils::processAPIContribution( $params ) ) {
                    CRM_Core_Error::debug_log_message( "Processed - line $row of csv file .. {$params['email']}, {$params['transaction']['total_amount']}, {$params['transaction']['trxn_id']} ..<p>", true ) ;
                } else {
                    CRM_Core_Error::debug_log_message( "Skipped - line $row of csv file .. {$params['email']}, {$params['transaction']['total_amount']}, {$params['transaction']['trxn_id']} ..<p>", true ) ;
                }
                
                // clean up memory from dao's
                CRM_Core_DAO::freeResult( );
            } else {
                // we assuming - first row is always the header line
                $header = $data;
                CRM_Core_Error::debug_log_message( "Considering first row ( line $row ) as HEADER ..<p>", true );
                
                if ( empty($header) ) {
                    CRM_Core_Error::fatal("Header is empty.");
                }
            }
            $row++;
        }
        fclose($handle);
    }

    static function process( ) {
        require_once 'CRM/Utils/Request.php';

        $type = CRM_Utils_Request::retrieve( 'type', 'String', CRM_Core_DAO::$_nullObject, false, 'csv', 'REQUEST' );
        $type = strtolower( $type );

        switch ( $type ) {
        case 'paypal':
        case 'google':
            $start = CRM_Utils_Request::retrieve( 'start', 'String', 
                                                  CRM_Core_DAO::$_nullObject, false, 31, 'REQUEST' );
            $end   = CRM_Utils_Request::retrieve( 'end', 'String', 
                                                  CRM_Core_DAO::$_nullObject, false, 0 , 'REQUEST'  );
            if ( $start < $end ) {
                CRM_Core_Error::fatal("Start offset can't be less than End offset.");
            }

            $start = date( 'Y-m-d', time( ) - $start * 24 * 60 * 60 ) . 'T00:00:00.00Z';
            $end   = date( 'Y-m-d', time( ) - $end   * 24 * 60 * 60 ) . 'T23:59:00.00Z';

            $ppID  = CRM_Utils_Request::retrieve( 'ppID'  , 'Integer', 
                                                  CRM_Core_DAO::$_nullObject, true, null, 'REQUEST' );
            $mode  = CRM_Utils_Request::retrieve( 'ppMode', 'String', 
                                                  CRM_Core_DAO::$_nullObject, false, 'live', 'REQUEST' );

            require_once 'CRM/Core/BAO/PaymentProcessor.php';
            $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $ppID, $mode );
            
            CRM_Core_Error::debug_log_message("Start Date=$start,  End Date=$end, ppID=$ppID, mode=$mode <p>", true);

            return self::$type( $paymentProcessor, $mode, $start, $end );

        case 'csv':
            return self::csv( );
        }
    }

}

// bootstrap the environment and run the processor
session_start();
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();

CRM_Utils_System::authenticateScript(true);

require_once 'CRM/Core/Lock.php';
$lock = new CRM_Core_Lock('CiviContributeProcessor');

if ($lock->isAcquired()) {
    // try to unset any time limits
    if (!ini_get('safe_mode')) set_time_limit(0);

    CiviContributeProcessor::process( );
} else {
    throw new Exception('Could not acquire lock, another CiviMailProcessor process is running');
}

$lock->release();

echo "Done processing<p>";
