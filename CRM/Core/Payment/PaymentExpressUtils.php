<?php 

/*
 * PxPay Functionality Copyright (C) 2008 Lucas Baker, Logistic Information Systems Limited (Logis)
 * PxAccess Functionality Copyright (C) 2008 Eileen McNaughton
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * Grateful acknowledgements go to Donald Lobo for invaluable assistance
 * in creating this payment processor module
 */
 
function _valueXml($element, $value=null)
{
    $nl = "\n";
    
    if (is_array($element)) {
        $xml = '';
        foreach ($element as $elem => $value) {
            $xml .= _valueXml($elem, $value); 
        }
        return $xml;
    }
    return "<".$element.">".$value."</".$element.">".$nl;
}
	
function _xmlElement($xml, $name)
{
    $value = preg_replace('/.*<'.$name.'[^>]*>(.*)<\/'.$name.'>.*/', '\1', $xml);
    return $value;
}
	
function _xmlAttribute($xml, $name)
{
    $value = preg_replace('/<.*'.$name.'="([^"]*)".*>/', '\1', $xml);
    return $value != $xml ? $value : null;
}	
	
function &_initCURL($query,$url)
{
    $curl = curl_init();
	
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_setopt($curl, CURLOPT_POSTFIELDSIZE, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_SSLVERSION, 3);
	
    if (strtoupper(substr(@php_uname('s'), 0, 3)) === 'WIN') {
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    return $curl;
}
