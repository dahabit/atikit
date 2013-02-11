<?php
/**
 * aTikit v1.0 by Core 3 Networks (www.core3networks.com)
 *
 * Copyright (c) 2013 Core 3 Networks, Inc and Chris Horne <chorne@core3networks.com>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package atikit10
 * @class vitelity
 */
/*
 * Vitelity API Class
 * Req: PHP 5.4.x
 * Date: 01/20/13
 * Author: Chris Horne (chorne@core3networks.com)
 * 
 * Requirements: cURL
 */


class vitelity
{
	public static $VITELITY_USERNAME;							// Your Vitelity API Login
	public static $VITELITY_PASSWORD;							// Your Vitelity API Password
	const LOG_DIR = "/tmp"; 									// FAX Transmission RAW Logs
	const VITELITY_FAXAPI = "http://api.vitelity.net/fax.php";	// FAX API Location
	const VITELITY_SMSAPI = "http://smsout-api.vitelity.net/api.php"; // SMS API Location
	
	public static $sourceSMS = "1-000-000-0000";						// Default SMS Source
	public $sourceFAX = "1-000-000-0000"; 						// Default Fax Source
	public $sourceShort = "55555";								// Your SMS Shortcode (if any)
	/*
	 * Contact Vitelity Server
	 */
	public function transmit($url, $post = false, $fields = [])
	{
		$fields_string = null;
		foreach ($fields AS $id=>$field)
			$fields_string .=  "$id=".urlencode($field)."&";
		$fields_string = substr($fields_string, 0, -1);	
	
		if (!$post)
		{
			if ($fields)
				$url = $url . "?" . $fields_string;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
		}
		else
		{
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			$c = fopen(self::LOG_DIR."/transmission.log", "w+");
			fwrite($c, "POST Transmission: $url - Fields: " . count($fields). " and String Data: $fields_string");
			fwrite($c, "Outcome: $output");
			fclose($c);
			curl_close($ch);
		}
		return new SimpleXMLElement($output);
	}
	
	
	static public function vitelity_sendSMS($destination, $msg, $source = null)
	{
		$api = new vitelity();
		if (!$source) 
			$source = self::$sourceSMS;
		$fields = [
					'login' => self::$VITELITY_USERNAME,
					'pass' => self::$VITELITY_PASSWORD,
					'cmd' => 'sendsms',
					'src' => $source,
					'dst' => $destination,
					'msg' => $msg,
					'xml' => 'yes'
					];
		$result = $api->transmit(self::VITELITY_SMSAPI, true, $fields);
	}

	static public function vitelity_sendFax($faxNum, $contactName, $file, $sourceFax = null)
	{
		$api = new vitelity();
		if (!$sourceFax)
			$sourceFax = $api->sourceFAX;
		if (!file_exists($file))
			return null;
		$data = base64_encode(fread(fopen($file, "r"), filesize($file)));
		$fields = [
					'login' => self::$VITELITY_USERNAME,
					'pass' => self::$VITELITY_PASSWORD,
					'cmd' => 'sendfax',
					'faxnum' => $faxNum,
					'faxsrc' => $sourceFax,
					'recname' => $contactName,
					'file1' => $file,
					'data1' => $data,
					'xml' => 'yes'
					];
		$result = $api->transmit(self::VITELITY_FAXAPI, true, $fields);
	}
	
	static public function vitelity_shortCode($destination, $msg, $source = null)
	{
		$api = new vitelity();
		if (!$source)
			$source = $api->sourceShort;
		$fields = [
					'login' => self::$VITELITY_USERNAME,
					'pass' => self::$VITELITY_PASSWORD,
					'cmd' => 'sendshort',
					'src' => $source,
					'dst' => $destination,
					'msg' => $msg,
					'xml' => 'yes'
				];
		$result = $api->transmit(self::VITELITY_SMSAPI, true, $fields);
	}
	
} //class