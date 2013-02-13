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
 * @class dwauth
 */
require_once("classes/core.inc.php");

class dwauth extends core
{
	public function main($content)
	{
		$permissions = ["Send", "Transactions", "Balance",  "AccountInfoFull", "Funding"];
		if (isset($content['myid']))
			$myid = $content['myid'];
		else
			$myid = $this->company->id;
		
		if (!$this->isProvidingCompany())
			$myid = $this->company->id;
		$Dwolla = new DwollaRestClient($this->getSetting('dwolla_app_key'), $this->getSetting('dwolla_app_secret'), $this->getSetting('atikit_url') . "/dwauth/$myid/", $permissions);
		if (!isset($content['code']))
		{
			$durl = $Dwolla->getAuthUrl();
			header("Location: $durl");
		}
		if (isset($content['error'])) 
		{
			$this->notifyCompany($myid, "Dwolla Error", $content['error_description'], null);
			$this->reloadTarget();
		}
		else if (isset($content['code'])) 
		{
			$code = $content['code'];
			$token = $Dwolla->requestToken($code);
			if (!$token) 
			{
				print($Dwolla->getError());  // Check for errors
				die("Unable to Obtain token");
			}
			else 
			{
				$this->query("UPDATE companies SET company_dwollatoken='$token' WHERE id='$myid'");
				$this->notifyCompany($myid, "Dwolla Authorized!", "You can now begin using Dwolla with aTikit.", null);
				if (!$this->isProvidingCompany())
					$this->reloadTarget('billing/checking/');
				else $this->reloadTarget();
			} // Print the access token
		}
	}	
}

$mod = new dwauth();
	$mod->main($_GET);

