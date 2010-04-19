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

// The sole purpose of this class is to fix a six-year-old bug in
// PEAR which makes Mail_mime wrongly encode email-sporting headers

require_once 'packages/Mail/mime.php';

class CRM_Utils_Mail_FixedMailMIME extends Mail_mime
{
    // a wrapper for the original function; this fixes PEAR bug #30 and CRM-4631
    function _encodeHeaders($input, $params = array())
    {
        // strip any emails from headers
        $emails = array();
        foreach ($input as $field => $value) {
            $matches = array();
            if (preg_match('/^(.*)<([^<]*)>$/', $value, $matches)) {
                $input[$field]  = trim($matches[1]);
                $emails[$field] = $matches[2];
            }
        }

        // encode the email-less headers
        $input = parent::_encodeHeaders($input, $params);

        // add emails back to headers, quoting these headers along the way
        foreach ($emails as $field => $email) {
            $input[$field] = str_replace('\\', '\\\\', $input[$field]);
            $input[$field] = str_replace('"',  '\"',   $input[$field]);
            // if the name was actually doubly-quoted, strip these (the next line will add them back); CRM-5640
            if (substr($input[$field], 0, 2) == '\"' and substr($input[$field], -2) == '\"') {
                $input[$field] = substr($input[$field], 2, -2);
            }
            $input[$field] = "\"$input[$field]\" <$email>";
        }

        return $input;
    }
}
