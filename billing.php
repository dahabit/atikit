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
 * @class billing
 */
require_once("classes/core.inc.php");

class billing extends core
{
	public function __construct()
	{
		parent::__construct();
		$data = base::subHeader("Billing Administration", "Update your Records and Download Invoices");
		$data .= base::begin();
		$this->export($data);
	}
	private function billingNav($active)
	{
		$opt[$active] = "class='active'";
	
		// redo this when nav lists are modular
		$data = "<div class='bs-docs-example'>
		<div class='well'>
		<ul class='nav nav-list'>
		<li class='nav-header'>Billing Administration</li>
		<li $opt[credit]><a href='/billing/'><i class='icon-credit-card icon-white'></i> Credit Card</a></li>
		<li $opt[checking]><a href='/billing/checking/'><i class='icon-book'></i> Checking Account</a></li>
		<li $opt[invoices]><a href='/billing/invoices/'><i class='icon-book'></i> My Invoices</a></li>
		</ul>
		</div>
		<!-- /well -->
		</div>";
		return $data;
	}
	
	private function stripeDetails()
	{
		$cid = $this->company->id;
		$this->exportJS(js::stripeToken($this->getSetting('stripe_publish'), 'savecc'));
		$span = [];
		$fields = [];
		$fields[] = array('type' => 'input', 'text' => 'Name on Card', 'val' => null, 'var' => null, 'class' => 'card-name');
		$fields[] = array('type' => 'input', 'text' => 'Card Number', 'val' => null, 'var' => null,  'class' => 'card-number');
		$fields[] = array('type' => 'input', 'text' => 'CVC', 'labelnote' => '3 digits' , 'val' => null, 'var' => null, 'comment' => 'Enter 3 or 4 digit code on back of your card', 'class' => 'card-cvc');
		$span[] = ['span' => 5, 'elements' => $fields];
		$fields = [];
		$fields[] = array('type' => 'input', 'text' => 'Expiration Month', 'val' => null, 'var' => null, 'comment' => 'Expiration Month in Two Digits', 'class' => 'card-expiry-month');
		$fields[] = array('type' => 'input', 'text' => 'Expiration Year',  'val' => null, 'var' => null, 'comment' => 'Expiration Year in Four Digits', 'class' => 'card-expiry-year');
		$fields[] = array('type' => 'input', 'text' => 'Billing Zip Code', 'val' => null, 'var' => null, 'class' => 'card-zip');
		$span[] = ['span' => 5, 'elements' => $fields];
		// Mask Inputs
		$this->exportJs(js::maskInput('card-expiry-month', "99"));
		$this->exportJs(js::maskInput('card-zip', "99999"));
		$this->exportJs(js::maskInput('card-expiry-year', "9999"));
		$form = form::init()->id('payment-form')->post('/billing/')->spanElements($span)->render();
		$data = "<p>You are about to add or update your credit card information. <b>This form will NOT charge your credit card</b>. This is merely updating our merchant with your new card details for monthly subscriptions or billable items.</p>";
		$saveCC = button::init()->text('Update Credit Card')->icon('arrow-right')->addStyle('savecc')->addStyle('btn-info')->render();
		if (isset($_GET['newcard']))
			$pre = base::alert('success', "Card Updated!", "Your new card has been updated and is ready for use!") . $pre;
		
		$data .= $form;
		if ($this->company->company_stripeid)
		{
			$plan = $this->stripe_getCustomerPlan($this->company->id);
			if (!$plan)
				$pdata = "<h4>No Subscription Found</h4>";
			else
			{
				$items = [];
				$items[] = ['label' => 'Subscription Name:', 'content' => $plan['name']];
				$items[] = ['label' => 'Interval:', 'content' => "Every ". $plan['interval_count'] . " Month(s)"];
				$items[] = ['label' => 'Price:', 'content' => "$".number_format($plan['amount']/100, 2)];
				$pdata = base::itemPairs($items);
				$cancel = button::init()->text("Cancel Subscription")->addStyle('get')->url('/billing/cancel/')->addStyle('btn-danger')->icon('remove')->render();
			}
			$data .= $pdata . $cancel;
		}
		
		$data =  widget::init()->span(8)->header("Update Credit Card")->content($data)->icon('credit-card')->footer($saveCC)->render();
		return $data;
	}

public function updateToken($content)
	{
		// Get Current Token - If none exists, then we need to create the customer w/ stripe and get their id
			$stripeid = $this->returnFieldFromTable("company_stripeid", "companies", "id='{$this->company->id}'");
			if (!$stripeid)
			{
				$newid = $this->stripe_createCustomer($content['stripeToken'], $content['cid']);
				$this->query("UPDATE companies SET company_stripeid='$newid', company_stripetoken='$content[stripeToken]' WHERE id='{$this->company->id}'");
			}
			else
			{
				$this->stripe_updateCustomer($content['stripeToken'], $content['cid']);
				$this->query("UPDATE companies SET company_stripetoken='$content[stripeToken]' WHERE id='{$this->company->id}'");
			}
			
		$this->reloadTarget("billing/?newcard=yes");
	}
	
	
	
	public function main()
	{
		// Left Side Nav
		$nav = $this->billingNav('credit'); // Default to Credit.
		$data = base::span(4, $nav);
		// Start with Stripe Configurations. Lets just throw up a span 6 edit update credit card form. 
		// and a span 6 - current plan form. let's pull the customer record from stripe.
		$data .= $this->stripeDetails();
		$data = base::row($data);
		$this->export($data);
	}
	
	public function cancelSubscription()
	{
		$cid = $this->company->id;
		$result = $this->stripe_cancelSubscription($cid);
		if ($result === true)
		{
			$json = [];
			$json['gtitle'] = 'Subscription Cancelled';
			$json['gbody'] = "Subscription has been immediately cancelled.";
			$json['action'] = 'reassignsource';
			$json['elementval'] = 'Subscription Cancelled!';
			$this->query("UPDATE companies SET company_plan=0 where id='$cid'");
			$this->mailProvider("Subscription Cancelled", $this->getCompanyById($cid) . " has cancelled their subscription.");
			$this->notifyProvider("Subscription Cancelled", $this->getCompanyById($cid) . " has cancelled their subscription.", null);
			$this->jsone('success', $json);
		}
		else
			$this->failjson("Unable to Cancel", "Reason: ". $result);
	}
	
	public function checkingMain()
	{
		$nav = $this->billingNav('checking'); // Default to Credit.
		$data = base::span(4, $nav);
		$data .= $this->dwollaMain();
		$data = base::row($data);
		$this->export($data);
	}
	
	private function dwollaAuthorizeOrCreate()
	{
		$buttons = button::init()->text("Authorize My Dwolla Account")->url("/dwauth/{$this->company->id}/")->addStyle('btn-success')->icon('ok')->render();
		$buttons .= button::init()->text("Create A New Dwolla Account")->url('#createDwolla')->isModalLauncher()->addStyle('btn-info')->icon('share-alt')->render();
		$title = "Pay with Dwolla!";
		$content = "<p><a href='http://www.dwolla.com'>dwolla.com</a> is a free bank-to-bank transfer service that allows you to pay your invoices with your checking account rather than a credit card. With dwolla, you no longer have to use paper checks!</p>
				<p>" . $buttons;
		$createButton = button::init()->text('Create Account')->icon('arrow-right')->addStyle('btn-success')->addStyle('mpost')->postVar('createDwollaAccount')->id(true)->formid('dwollaCreateForm')->render();
		$this->exportModal(modal::init()->id('createDwolla')->header("Create Dwolla Account")->content($this->dwollaCreateForm())->footer($createButton)->render());
		return base::span(7, base::hero($title, $content));
	}

	private function dwollaCreateForm()
	{
		$first = reset(explode(" ", $this->user->user_name));
		$last = end(explode(" ", $this->user->user_name));
		$span = [];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'E-mail Address', 'var' => 'd_email', 'val' => $this->user->user_email];
		$fields[] = ['type' => 'password', 'text' => 'New Password:', 'var' => 'd_password', 'comment' => 'Dwolla <b>REQUIRES</b> a Capital Letter and a Number In the Password'];
		$fields[] = ['type' => 'input', 'text' => '4 Digit PIN:', 'comment' => 'This PIN will authorize transactions', 'class' => 'pin', 'var' => 'd_pin'];
		$fields[] = ['type' => 'input', 'text' => 'First Name:', 'var' => 'd_first', 'val' => $first];
		$fields[] = ['type' => 'input', 'text' => 'Last Name:', 'var' => 'd_last', 'val' => $last];
		$span[] = ['span' => 4, 'elements' => $fields];
		$fields = [];
		
		$fields[] = ['type' => 'input', 'text' => 'Address Line 1:', 'var' => 'd_address1', 'val' => $this->company->company_address];
		$fields[] = ['type' => 'input', 'text' => 'Address Line 2:', 'var' => 'd_address2', 'val' => $this->company->company_address2];
		$fields[] = ['type' => 'input', 'text' => 'City:', 'var' => 'd_city', 'val' => $this->company->company_city];
		$fields[] = ['type' => 'input', 'text' => 'State:', 'var' => 'd_state', 'val' => $this->company->company_state];
		$fields[] = ['type' => 'input', 'text' => 'Zip:', 'var' => 'd_zip', 'val' => $this->company->company_zip];
		$fields[] = ['type' => 'input', 'text' => 'Phone:', 'var' => 'd_phone', 'val' => $this->company->company_phone];
		$span[] = ['span' => 4, 'elements' => $fields];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Date of Birth:', 'var' => 'd_dob', 'class' => 'dob'];
		$opts = [
				['val' => 'Personal', 'text' => 'Personal Account'],
				['val' => 'Commercial', 'text' => 'Commercial Account'],
				['val' => 'NonProfit', 'text' => 'Non-Profit Account'],
		];
		$fields[] = ['type' => 'select', 'var' => 'd_type', 'opts' => $opts, 'text' => 'Type of Account:'];
		$fields[] = ['type' => 'input', 'var' => 'd_org', 'text' => 'Company/Organization Name:', 'val' => $this->company->company_name];
		$fields[] = ['type' => 'input', 'var' => 'd_ein', 'text' => 'EIN (If Applicable):'];
		$opts = [['val' => 'N', 'text' => 'No'], ['val' => 'Y', 'text' => 'Yes']];
		$fields[] = ['type' => 'select', 'var' => 'd_terms', 'text' => 'Agree to Terms and Conditions?:', 'opts' => $opts];
		$span[] = ['span' => 4, 'elements' => $fields];
		$fields = [];
		$this->exportJS(js::maskInput('pin', '9999'));
		$this->exportJS(js::maskInput('dob', '99-99-9999'));
		$data = form::init()->id('dwollaCreateForm')->spanElements($span)->post('/billing/')->render();
		$data .= "<div class='well'><h4>Dwolla Terms of Service</h4>
				<p>You must read and agree to the rules set forth at <a target='_blank' href='https://www.dwolla.com/tos'>https://www.dwolla.com/tos</a> before continuing. This will open in a new window.</p></div>
				";
		return $data;
	}
	
	public function createDwollaAccount($content)
	{
		if (!$content['d_email'] || !$content['d_password'] || !$content['d_pin'] || !$content['d_first'] || !$content['d_last'] || !$content['d_address1'] || !$content['d_city']
				|| !$content['d_state'] || !$content['d_zip'] || !$content['d_phone'] || !$content['d_dob'] || !$content['d_type'] || !$content['d_terms'])
			$this->failJSON("Unable to Create", "There are some fields missing.");
		$myid = $this->company->id;
		if (!preg_match("/[A-Z]/", $content['d_password']))
			$this->failJson("Bad Password", "Dwolla Requires a Capital Letter in their Passwords.");
		if (!preg_match("/[0-9]/", $content['d_password']))
			$this->failJson("Bad Password", "Dwolla Requires a Number in their Passwords.");
		$Dwolla = new DwollaRestClient($this->getSetting('dwolla_app_key'), $this->getSetting('dwolla_app_secret'), $this->getSetting('atikit_url') . "dwauth/$myid/", null, 'live', true);
		$terms = ($content['d_terms'] == 'Y') ? true : false;
		$user = $Dwolla->register(
				$content['d_email'], 
				$content['d_password'],
				$content['d_pin'], 
				$content['d_first'],
				$content['d_last'],
				$content['d_address1'],
				$content['d_city'],
				$content['d_state'], 
				$content['d_zip'],
				$content['d_phone'], 
				$content['d_dob'],
				$terms,
				$content['d_address2'], 
				$content['d_type'],
				$content['d_org'],
				$content['d_ein']);
		$errorMsg = $Dwolla->getError();
		
		if(!$user) 
			$this->failJson("Unable to Create", $errorMsg);
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/dwauth/{$this->company->id}/";
		$this->jsone('success', $json);
	}
	
	private function dwollaMain()
	{
		// Do we have an authorized Dwolla Account? If no, then display a hero-like unit with two options. Create Account or Authorize Your Account
		if (!$this->company->company_dwollatoken)
		{
			return $this->dwollaAuthorizeOrCreate();
			
		}
		$myid = $this->company->id;
		$Dwolla = new DwollaRestClient($this->getSetting('dwolla_app_key'), $this->getSetting('dwolla_app_secret'), $this->getSetting('atikit_url') . "dwauth/$myid/");
		$Dwolla->setToken($this->company->company_dwollatoken);
		// Create a list of Funding Sources and an Add Button
		$fundingSources = $Dwolla->fundingSources();
		$rows = [];
		foreach ($fundingSources AS $source)
		{
			$color = ($source['Verified']) ? "green" : "red";
			$verified = ($source['Verified']) ? "Yes" : "No";
			$row = [
						$source['Name'],
						$source['Type'],
						$verified,
						$color
			];
			$rows[] = $row;
		}
		$headers = ['Source Name', 'Type', 'Verified'];
		$table = table::init()->headers($headers)->rows($rows)->render();
		$add = button::init()->text("Add Bank Account")->isModalLauncher()->addStyle('btn-info')->icon('plus')->url('#addAccount')->render();
		$saveAccount = button::init()->text("Save Account")->addStyle('mpost')->icon('ok')->addStyle('btn-success')->postVar('addAccount')->formid('addAccountForm')->render();
		$this->exportModal(modal::init()->id('addAccount')->header("Add Bank Account to Dwolla")->content($this->addBankAccountForm())->footer($saveAccount)->render());
		$table .= base::alert('info', "Removing Accounts", "If you wish to remove your bank account from Dwolla, you must login to dwolla.com and remove.");
		return widget::init()->header('Funding Sources')->content($table)->span(8)->rightHeader($add)->isTable()->render();
	}
	
	private function addBankAccountForm()
	{
		$pre = "<p>You are adding a bank account to your dwolla.com account. You will be able to draft payments from this account to pay any invoices you may have. Please note that we do 
				not store this information on our servers. Credit and Checking account information is stored with the respective merchant; in this case Dwolla.com</p>";
		$span = [];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Routing Number', 'comment' => 'First 9 Numbers in the bottom left of the check', 'var' => 'routing'];
		$fields[] = ['type' => 'input', 'text' => 'Account Number', 'comment' => 'Account number without leading zeros', 'var' => 'account'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$fields = [];
		$opts = [['val' => 'Checking', 'text' => 'Checking Account'], ['val' => 'Savings', 'text' => 'Savings Account']];
		$fields[] = ['type' => 'select', 'var' => 'type', 'text' => 'Account Type:', 'opts' => $opts];
		$fields[] = ['type' => 'input', 'text' => 'Nickname:', 'comment' => '(i.e. Chase Checking Account)', 'var' => 'nickname'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$form = form::init()->post('/billing/')->id('addAccountForm')->spanelements($span)->render();
		return $pre.$form;  
	}
	
	public function addBankAccount($content)
	{
		if (!$content['routing'] || !$content['account'] || !$content['type'] || !$content['nickname'])
			$this->failJson("Unable to Add", "Some fields are blank, Please try again.");
		$myid = $this->company->id;
		$Dwolla = new DwollaRestClient($this->getSetting('dwolla_app_key'), $this->getSetting('dwolla_app_secret'), $this->getSetting('atikit_url') . "dwauth/$myid/");
		$Dwolla->setToken($this->company->company_dwollatoken);
		$newFundingSource = $Dwolla->addFundingSource($content['account'], $content['routing'], $content['type'], $content['nickname']);
		if (!$newFundingSource)
			$this->failJson("Unable to Add", $Dwolla->getError());
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/billing/checking/";
		$this->jsone('success', $json);
	}

	public function showInvoices()
	{
		$nav = $this->billingNav('invoices'); // Default to Credit.
		$data = base::span(4, $nav);
		//Just a simple table with a download button for downloading the invoices. 
		$headers = ['Ticket', 'Date', 'Description', 'Type', 'Download'];
		$rows = [];
		$transactions = $this->query("SELECT * from transactions WHERE company_id='{$this->company->id}'");
		foreach ($transactions AS $transaction)
		{
			switch ($transaction['transaction_source'])
			{
				case 'stripe' : $source = "Credit Card"; break;
				case 'dwolla' : $source = "Checking Draft" ; break;
				case 'check'  : $source = "Posted Check" ; break;
			}
			$row = ["<a href='/ticket/$transaction[ticket_id]/'>$transaction[ticket_id]</a>",
					date("m/d/y", $transaction['transaction_ts']),
					$transaction['transaction_desc'],
					$source,
					button::init()->url("/billing/invoice/$transaction[id]/")->text("Download Invoice")->addStyle('btn-small')->addStyle('btn-info')->icon('arrow-down')->render()
			];
			$rows[] = $row;
		}
		$table = table::init()->rows($rows)->headers($headers)->render();		
		$data .= widget::init()->span(8)->isTable()->header("Processed Transactions")->content($table)->render();
		$data = base::row($data);
		$this->export($data);
	}
	
	public function downloadInvoice($content)
	{
		$id = $content['downloadInvoice'];
		$transaction = $this->query("SELECT * from transactions WHERE id='$id'")[0];
		$ticket = $this->query("SELECT * from tickets WHERE id='$transaction[ticket_id]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->reloadTarget();
		$this->ajax = true;
		$this->createPDFInvoice($transaction, true);
	}
	
}


$mod = new billing();
if (isset($_POST['stripeToken']))
	$mod->updateToken($_POST);
else if (isset($_GET['cancelsub']))
	$mod->cancelSubscription();
else if (isset($_GET['checking']))
	$mod->checkingMain();
else if (isset($_POST['createDwollaAccount']))
	$mod->createDwollaAccount($_POST);
else if (isset($_POST['addAccount']))
	$mod->addBankAccount($_POST);
else if (isset($_GET['invoices']))
	$mod->showInvoices();
else if (isset($_GET['downloadInvoice']))
	$mod->downloadInvoice($_GET);
else
	$mod->main();


