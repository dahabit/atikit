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
 * @trait ticket
 */
trait c3tools
{
    
	public function number_ending ($number)
	{
		$suff = array("","st","nd","rd","th");
		$index = intval($number);
		if($index > 4){
			$index = 4;
		}elseif($index < 1){
			$index = 0;
		}
		return ($number . $suff[$index]);
	}
	
	static public function chop ($data, $places)
    {
        $newdata = NULL;
        $len = strlen($data);
        if ($len > $places)
        {
            for ($i = 0; $i <= $places; $i ++)
                $newdata .= $data[$i];
                $newdata .= "..";
        } else
                    return ($data);
                    return ($newdata);
    } // chop
    
    
    static public function chopAndFill ($data, $places)
    {
    	$newdata = NULL;
    	$len = strlen($data);
    	if ($len > $places)
    	{
    		for ($i = 0; $i <= $places; $i ++)
    			$newdata .= $data[$i];
    			$newdata .= "..";
    		return $newdata;
    	} 
    	else
    	{
    		$needed = $places - $len;
    		$newdata = $data;
    		for ($i=0; $i<=$needed; $i++)
    			$newdata .= "&nbsp; ";
    		return $newdata;
    	}
    		
    		
    } // chop
    
    static public function upperWords($text)
    {
        $text = explode(" ", $text);
        $new = null;
        foreach ($text AS $word)
            $new .= ucfirst(strtolower($word)). " ";
        return $new;
        
    }
    
    
    static public function encode($string)
    {
    	$encoded = strtr(base64_encode(addslashes(gzcompress(serialize($string),9))), '+/=', '-_,');
    	return $encoded;
    }
    
    
    static public function decode($encoded)
    {
    	$decoded = unserialize(@gzuncompress(stripslashes(base64_decode(strtr($encoded, '-_,', '+/=')))));
    	return $decoded;
    }
    
    public function bytes($bytes)
    {
    	$size = $bytes / 1024;
    	if($size < 1024)
    	{
    		$size = number_format($size, 2);
    		$size .= ' KB';
    	}
    	else
    	{
    		if($size / 1024 < 1024)
    		{
    			$size = number_format($size / 1024, 2);
    			$size .= ' MB';
    		}
    		else if ($size / 1024 / 1024 < 1024)
    		{
    			$size = number_format($size / 1024 / 1024, 2);
    			$size .= ' GB';
    		}
    	}
    	return $size;
    }
    

   public function get_dir_size($dir_name)
   {
    	$dir_size =0;
    	if (is_dir($dir_name)) {
    		if ($dh = opendir($dir_name)) {
    			while (($file = readdir($dh)) !== false) {
    				if($file !="." && $file != ".."){
    				if(is_file($dir_name."/".$file)){
    				$dir_size += filesize($dir_name."/".$file);
    			}
    			/* check for any new directory inside this directory */
    			if(is_dir($dir_name."/".$file)){
    			$dir_size +=  $this->get_dir_size($dir_name."/".$file);
    		}
    	}
    }
    }
    }
    if (isset($dh))
    	closedir($dh);
    return $dir_size;
    }
    
    public function remove_directory($dir)
    {
    	// recursively remove a directory
    
    		foreach(glob($dir . '/*') as $file) {
    			if(is_dir($file))
    				$this->remove_directory($file);
    			else
    				unlink($file);
    		}
    		$this->remove_directory($dir);
    }
    
    
    
    /**
     * Sanitize variables given through POST and GET and REQUEST. This is run
     * automatically. This adds slashes to all data given from the browser and
     * reapplies them to the _POST and _GET variables.
     *
     * @return void
     */
    public function escapeVars ()
     {
        
         
        if (isset($_POST))
            foreach ($_POST as $pkey => $pdata)
            $_POST[$pkey] = strip_tags(addslashes(trim($pdata)), "<p><a><b><i><strong><img><u>");
    
        if (isset($_GET))
            foreach ($_GET as $gkey => $gdata)
            $_GET[$gkey] = strip_tags(addslashes(trim($gdata)), "<p><a><b><i><strong><img><u>");
    
        if (isset($_REQUEST))
            foreach ($_REQUEST as $rkey => $rdata)
            $_REQUEST[$rkey] = strip_tags(addslashes(trim($rdata)), "<p><a><b><i><strong><img><u>");
    
    } // escapeVars
    
    /**
     * Returns a 'facebook' like time string Takes a date from $date to $to
     * (NULL if now) and returns how long ago or how long in the future in human
     * readable text: 4 hours from now.. or 2 minutes ago. <code> $when =
     * $this->fbTime(($now-1), $now); </code>
     *
     * @param $date int
     *       	 in unix time() format
     * @param $to int
     *       	 in unit time() format
     * @return string style time length measurement
     */
    public function fbTime ($date, $to = NULL)
    {
        if (empty($date))
            return "No date provided";
    
        if (! $to)
            $to = time();
        $periods = array(
                "second", "minute", "hour", "day", "week", "month", "year", "decade");
        $lengths = array(
                "60", "60", "24", "7", "4.35", "12", "10");
        $now = $to;
        $unix_date = $date;
        // check validity of date
        if (empty($unix_date))
            return "Bad date";
        	
        // is it future date or past date
        if ($now > $unix_date)
        {
            $difference = $now - $unix_date;
            $tense = "ago";
    
        } else
        {
            $difference = $unix_date - $now;
            $tense = "from now";
        }
    
        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j ++)
            $difference /= $lengths[$j];
    
        $difference = round($difference);
    
        if ($difference != 1)
            $periods[$j] .= "s";
            $retdata = "$difference $periods[$j] {$tense}";
            if (trim($retdata) == "0 seconds from now")
            return ("Just now!");
            else
    			return ($retdata);
    	} // fbTime
    	
    	public function reloadTarget ($loc = null)
    	{
    	    die(header("Location: http://" . $_SERVER['SERVER_NAME'] . "/$loc"));
    	} // refreshTarget
    	
    	public function reloadExternal($loc)
    	{
    		die(print("<meta http-equiv='refresh' content='0;url=$loc'>"));
    		
    	}
    	static public function seoify ($title)
    	{
    	    $seoname = preg_replace('/\%/', ' percentage', $title);
    	    $seoname = preg_replace('/\@/', ' at ', $seoname);
    	    $seoname = preg_replace('/\&/', ' and ', $seoname);
    	    $seoname = preg_replace('/\s[\s]+/', '-', $seoname); // Strip off multiple
    	    // spaces
    	    $seoname = preg_replace('/[\s\W]+/', '-', $seoname); // Strip off spaces
    	    // and
    	    // non-alpha-numeric
    	    $seoname = preg_replace('/^[\-]+/', '', $seoname); // Strip off the
    	    // starting hyphens
    	    $seoname = preg_replace('/[\-]+$/', '', $seoname); // // Strip off the
    	    // ending hyphens
    	    $seoname = strtolower($seoname);
    	    return ($seoname);
    	}
    	 
    	public function generatePassword ($length = 8)
    	{
    	    // start with a blank password
    	    $password = "";
    	
    	    // define possible characters - any character in this string can be
    	    // picked for use in the password, so if you want to put vowels back in
    	    // or add special characters such as exclamation marks, this is where
    	    // you should do it
    	    $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
    	
    	    // we refer to the length of $possible a few times, so let's grab it now
    	    $maxlength = strlen($possible);
    	
    	    // check for length overflow and truncate if necessary
    	    if ($length > $maxlength) {
    	        $length = $maxlength;
    	    }
    	
    	    // set up a counter for how many characters are in the password so far
    	    $i = 0;
    	
    	    // add random characters to $password until $length is reached
    	    while ($i < $length) {
    	
    	        // pick a random character from the possible ones
    	        $char = substr($possible, mt_rand(0, $maxlength-1), 1);
    	
    	        // have we already used this character in $password?
    	        if (!strstr($password, $char)) {
    	            // no, so it's OK to add it onto the end of whatever we've already got...
    	            $password .= $char;
    	            // ... and increase the counter by one
    	            $i++;
    	        }
    	
    	    }
    	
    	    // done!
    	    return $password;
    	
    	}
    
    	/**
    	 * Send AJAX Response with JSON
    	 *
    	 * Sends the headers for JSON to the browser
    	 * encodes the json array and prints directly
    	 * out and immediately terminates (calls
    	 * __destruct()) the application.
    	 */
    	public function jsonE($status, array $json)
    	{
    		$this->ajax = true;
    		$json['status'] =  $status;
    		$json = json_encode($json);
    		header('Cache-Control: no-cache, must-revalidate');
    		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    		header('Content-type: application/json');
    		print($json);
    		die(); 												// The only die that will be used in the entire app.
    	} //jsonE
    	
    	
    	public function deadError($msg)
    	{
    		$this->log($msg);
    		$this->exportJS(js::alert(['title' => 'Error Reported', 'body' => $msg]));
    	}
    	
    	public function failJSON($header, $msg, $url = null)
    	{
    		$json = [];
    		$json['gtitle'] = $header;
    		$json['gbody'] = $msg;
    		$this->jsonE('error', $json);
    	}
    
    	public function sendMail($email, $subject, $body, $attach = null, $from = null)
    	{
    		// Create the message
    		$transport = Swift_SmtpTransport::newInstance('localhost', 25);
    		$mailer = Swift_Mailer::newInstance($transport);
    		$message = Swift_Message::newInstance();
    		$message->setSubject($subject);
    		if (!$from)
    			$message->setFrom(['info@whoismy.com' => 'WhoIsMy Mail']);
    		else
    			$message->setFrom($from);
    		$message->setTo([$email]);
    		$message->setBody($body);
    		if ($attach)
    			$message->attach(Swift_Attachment::fromPath($attach));
    		return $mailer->send($message);
    	}
    
}