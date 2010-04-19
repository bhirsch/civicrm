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

/**
 * Class handles functions for JSON format
 */
class CRM_Utils_JSON
{
    /**
     * Function to create JSON object
     * 
     * @param  array  $params     associated array, that needs to be
     *                            converted to JSON array
     * @param  string $identifier identifier for the JSON array 
     * 
     * @return string $jsonObject JSON array     
     * @static
     */
    static function encode ( $params, $identifier = 'id' ) 
    {
        $buildObject = array( );
        foreach ( $params as $value ) {
            $name = addslashes( $value['name'] );
            $buildObject[] = "{ name: \"$name\", {$identifier}:\"{$value[$identifier]}\"}";
        }

        $jsonObject = '{ identifier: "'. $identifier .'", items: [' . implode( ',', $buildObject) . ' ]}';

        return $jsonObject;
    }

    /**
     * Function to encode json format for flexigrid, NOTE: "id" should be present in $params for each row 
     * @param array  $params associated array of values rows
     * @param int    $page  page no for selector 
     * @param array  $selectorElements selector rows
     *
     * @return json encode string   
     */
    static function encodeSelector( &$params, $page, $total, $selectorElements )
    {
        $json = "";
        $json .= "{\n";
        $json .= "page: $page,\n";
        $json .= "total: $total,\n";
        $json .= "rows: [";
        $rc = false;

        foreach( $params as $key => $value) {
            if ( $rc ) $json .= ",";
            $json .= "\n{";
            $json .= "id:'".$value['id']."',";
            $json .= "cell:[";
            $addcomma = false;
            foreach ( $selectorElements as $element ) {
                if ( $addcomma ) $json .= ",";
                $json .= "'".addslashes($value[$element])."'";
                $addcomma = true;
            }
            $json .= "]}";
            $rc = true;
        }
        
        $json .= "]\n";
        $json .= "}";
     
        return $json;
    } 

}
