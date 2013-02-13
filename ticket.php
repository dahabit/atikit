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
 * @class ticket
 */
require_once("classes/core.inc.php");
class ticket extends core
{
	public function showTicket($content)
	{
		$id = $content['view'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$id'")[0];
		$this->pageTitle = "#{$id} - $ticket[ticket_title]";
		if ($this->isProvidingCompany())
		{
			if (!$ticket || !$this->isMyQueue($ticket['queue_id']) || !$this->isMyTicket($ticket))
				 $this->reloadTarget();
		}
		else
		{
			if (!$ticket || !$this->isMyTicket($ticket))
				$this->reloadTarget();
		}
		// Access Granted at this point. 
		$data = base::subHeader($this->getCompanyById($ticket['company_id']), $ticket['ticket_title']);
		$data .= base::begin();

		$this->export($data);		
		$this->showTicketDetails($ticket);
	}	
	
	private function getTicketReplies(&$ticket)
	{
		$private = ($this->isProvidingCompany()) ? null : "AND reply_isinternal = false";
		$pre = "<img src='/assets/img/talking.png' width='90px' align='left' style='padding-right:10px'><div class='well'><p>$ticket[ticket_body]</p></div>";
			
		
		$replies = $this->query("SELECT * from replies WHERE ticket_id='$ticket[id]' {$private} ORDER by reply_ts ASC");
		if (!$replies)
			return $pre . "<div class='bs-docs-example'><ul class='updatelist'><h4>No replies found.</h4></ul></div>";
		foreach ($replies AS $reply)
			$items[] = [
							'url' => '#',
							'author' => $this->getUserByID($reply['user_id']) . " <span class='pull-right'>" . $this->getUserTitleById($reply['user_id']). ", ". $this->getCompanyById($reply['company_id'])."</span>",
							'ago' => $this->fbTime($reply['reply_ts']),
							'internal' => $reply['reply_isinternal'],
							'post' => nl2br($reply['reply_body']),
							'thumb' => $this->getProfilePic($reply['user_id'])
			];	
		
		return $pre. base::feed($items);
	}
	
 	private function getCompanyNotes(&$ticket)
 	{
 		if (!$this->isProvidingCompany()) return null;
 		$notes = nl2br($this->returnFieldFromTable("company_notes", "companies", "id='$ticket[company_id]'"));
 		if (!$notes) return null;
 		return "<div class='well'><h4>Client Notes</h4><p>$notes</p></div>";
 	}
	
	private function getFiles(&$ticket)
	{
		$files = $this->query("SELECT * from files WHERE ticket_id='$ticket[id]' ORDER by file_ts ASC");
		$headers = ['Description', 'Type', 'From', 'When'];
		$rows = [];
		foreach ($files AS $file)
			$rows[] = ["<a href='/$file[file_loc]'>$file[file_title]</a>", $file['file_type'], $this->getUserByID($file['user_id']), $this->fbTime($file['file_ts'])];
		
		$table = table::init()->headers($headers)->rows($rows)->render();
		return widget::init()->icon('download')->header('Ticket Attachments')->content($table)->isTable()->render();
	}

	private function getIncidentStanding(&$ticket)
	{
		if (!$this->isProvidingCompany()) return null;
		// Just a simple form with a submit button really. 
		$fields = [['type' => 'textarea', 'span' => 12, 'rows' => 5, 'var' => 'ticket_standing', 'val' => $ticket['ticket_standing']]];
		$save = button::init()->text('Update Standing')->addStyle('post')->formid('updateStandingForm')->addStyle('btn-success')->icon('pencil')->postvar('updateStanding')->id($ticket['id'])->render();
		$form = form::init()->post('/ticket/')->elements($fields)->id('updateStandingForm')->render();
		if ($ticket['ticket_standinguid'])
			$form .= "<em>Standing Updated By: </em> " . $this->getUserByID($ticket['ticket_standinguid']). ",  " . $this->fbTime($ticket['ticket_standingts']);
		$form = "<img src='/assets/img/waiting.png' align='left'>$form";
		$widget = widget::init()->header('Ticket Standing')->icon('road')->content($form)->footer($save)->render();
		return $widget;
	}
	
	
	private function createReplyForm(&$ticket)
	{
		// If you are the providing company allow an internal note
	  	$reply = $this->query("SELECT * from replies WHERE ticket_id='$ticket[id]' AND reply_isinternal = false ORDER by reply_ts DESC LIMIT 1")[0];
	  	$pre = "<div class='well'><h4>".$this->getUserById($reply['user_id'])."</h4><p> $reply[reply_body]</p></div>";
		if (!$reply)
			$pre = null;
		$fields = [];
	  	$fields[] = ['type' => 'textarea', 'span' => 6, 'rows' => 5, 'var' => 'reply_body', 'text' => 'Reply:'];
	  	if ($this->isProvidingCompany())
	  	{
	  		$opts = [['val' => 'Y', 'text' => 'Make this an internal note.']];
	  		$fields[] = ['type' => 'checkbox', 'var' => 'reply_isinternal', 'opts' => $opts];
	  	}
	  	return  form::init()->id('newReplyForm')->elements($fields)->post('/ticket/')->render(). $pre;
	}

	public function saveReply($content)
	{
		$id = $content['createReply'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$id'")[0];
		$this->log("updating ticket $ticket[id] - content var is $id" . serialize($_POST));
		if (!$this->isMyTicket($ticket)) $this->failjson('Unable to save', "Access Denied");
		if (!$content['reply_body']) $this->failJson('Unable to Save', 'You must enter a response.');
		$internal = ($content['reply_isinternal']) ? true : false;
		if ($this->isProvidingCompany()) 
			$status = "Waiting for Customer";
		else 
			$status = "Waiting for Admin";
		
		$this->updateTicket(['ticket_id' => $id,
							 'internal' => $internal,
							 'ticket_body' => $content['reply_body'],
							 'ticket_status' => $status 
				]);
		$pic = $this->getProfilePic($this->user->id);
		$json['element'] = ".updatelist";
		$json['gtitle'] = 'Reply Added';
		$json['gbody'] = "Your reply was added.";
		$json['content'] = base::singleFeed([
				'author' => $this->getUserByID($this->user->id) . " (" . $this->getCompanyById($this->company->id) . ")",
				'post' => nl2br($content['reply_body']),
				'thumb' => $pic,
				'url' => '#',
				'internal' => $internal,
				'ago' => 'Just now']);
		$json['action'] = 'append';
		$json['ev'] = js::scrollBottom();
		$this->jsonE('success', $json);
	}
	
	private function getBillingTab(&$ticket)
	{
		// Left Side Credit Card / Right Side Dwolla
		// Each side will have a small history, update details. For dwolla, we may just update a PIN and have refund abilities. 
		// Stripe will have apply coupon, update card, update plan. 
		$company = $this->query("SELECT * from companies WHERE id='$ticket[company_id]'")[0];
		$data = "<div class='row-fluid'>";
		$data .= "<div class='span6'>";
		$headers = ['Date', 'Description', 'Amount'];
		if ($this->isProvidingCompany())
				$headers[] = "Refund"; 
		$stripes = $this->query("SELECT * from transactions WHERE company_id='$ticket[company_id]' AND transaction_source='stripe'");
		$rows = [];
		foreach ($stripes AS $stripe)
		{
			$row = [date("m/d/y h:ia", $stripe['transaction_ts']),
			$stripe['transaction_desc'],
			"$" . number_format($stripe['transaction_amount'],2),
			($this->isProvidingCompany()) ? "<a href='/refund/$stripe[id]/' class='mjax' data-target='#refundModal'>Refund</a>" : null
			];
			if (!$this->isProvidingCompany())
				$row = array_pop($row);
			$rows[] = $row;			
				
		}
		$stripeButtons = null;
		$table = table::init()->headers($headers)->rows($rows)->render();
		if ($this->isProvidingCompany())
		{
			$stripeButtons .= button::init()->text('Charge Card')->icon('arrow-up')->isModalLauncher()->url('#billCustomer')->addStyle('btn-success')->render();
			$stripeButtons .= button::init()->text('Add Item to Monthly Invoice')->icon('move')->isModalLauncher()->url('#invoiceCustomer')->addStyle('btn-inverse')->render();
			$billButton = button::init()->text('Charge Credit Card')->addStyle('mpost')->formid('billCCForm')->addStyle('btn-success')->icon('ok')->postVar('createStripeCharge')->id($ticket['id'])->message('Processing Transaction..')->render();
			$invButton = button::init()->text('Add Item')->addStyle('mpost')->formid('addInvItem')->addStyle('btn-success')->icon('ok')->postVar('createStripeInvoiceItem')->id($ticket['id'])->message('Adding Item..')->render();
			$this->exportModal(modal::init()->id('billCustomer')->header('Charge Credit Card')->content($this->billCCForm())->footer($billButton)->render());
			$this->exportModal(modal::init()->id('invoiceCustomer')->header('Add Item to Invoice')->content($this->StripeInvoiceForm())->footer($invButton)->render());
		}
		$updateCC .= button::init()->text('Update Credit Card')->isModalLauncher()->addStyle('btn-primary')->icon('money')->url('#updateCC')->render();
		
		$saveCC = button::init()->text('Save Credit Card')->addStyle('savecc')->addStyle('btn-success')->render();
		$this->exportModal(modal::init()->id('updateCC')->header('Update Credit Card')->content($this->ccUpdateForm($ticket))->footer($saveCC)->render());
		if (!$company['company_stripetoken'])
			$table .= base::alert('info', 'No Credit Card Found', 'This company currently has no credit card on file.');
		
		$data .= widget::init()->header('Credit Transactions')->content($table)->isTable()->icon('credit-card')->rightHeader($updateCC)->footer($stripeButtons)->maxHeight(400)->render();
		
		// Show Plan Details and Upcoming Invoice. 
		// Top part should be what plan they are on and a button that says "Update Plan" and "Retrieve Next Invoice" 
		// Bottom should have next invoice in an ajax form that displays when user presses retrieve .. so we're not querying stripe everytime someone opens a ticket.
		// Use whats in the tables for the modal, add plan and coupon management in the admin section. 
		
		$plan = $company['company_plan'];
		if (!$plan)
			$pdata = "<h5>No monthly subscription found</h5>";
		else
		{
			$plan = $this->query("SELECT * from plans WHERE id='$company[company_plan]'")[0];
			$items = [];
			$items[] = ['label' => 'Subscription Name:', 'content' => $plan['plan_name']];
			$items[] = ['label' => 'Interval:', 'content' => "Every ". $plan['plan_interval'] . " Month(s)"];
			$items[] = ['label' => 'Price:', 'content' => "$".number_format($plan['plan_amount'], 2)];
			$pdata = base::itemPairs($items);
		}
		$updateButton = null;
		$updateButton .= ($this->isProvidingCompany()) ? button::init()->isModalLauncher()->addStyle('btn-info')->url('#updatePlan')->text('Update Plan')->icon('upload-alt')->render() : null;
		if ($company['company_plan'])
		{
			$updateButton .= button::init()->isModalLauncher()->addStyle('btn-danger')->url('#cancelPlanForm')->text('Cancel Plan')->icon('remove')->render();
			$cancelButton = button::init()->addStyle('btn-danger')->icon('remove')->formid('cancelForm')->text('Confirm Cancellation')->postVar('cancelPlan')->addStyle('mpost')->id($company['id'])->message('Cancelling Subscription..')->render();
			$this->exportModal(modal::init()->id('cancelPlanForm')->header('Cancel Subscription')->content($this->cancelPlanForm())->footer($cancelButton)->render());
		}
		if ($this->isProvidingCompany())
		{
			$updatePlanButton = button::init()->formid('updatePlanForm')->postVar('updatePlan')->id($company['id'])->addStyle('mpost')->text('Apply new Plan')->icon('arrow-right')->addStyle('btn-primary')->message('Updating Plan..')->render();
			$this->exportModal(modal::init()->id('updatePlan')->header('Update Customer Plan')->content($this->updatePlanForm())->footer($updatePlanButton)->render());
		}
		$data .= widget::init()->header('Subscription Details')->content($pdata)->rightHeader($updateButton)->icon('calendar')->render();
		
		
		$data .= "</div>"; // end of credit card stuff
		
		
		$headers = ['Date', 'Description', 'Amount'];
		$dwollas = $this->query("SELECT * from transactions WHERE company_id='$ticket[company_id]' AND transaction_source='dwolla'");
		$rows = [];
		foreach ($dwollas AS $dwolla)
			$rows[] = [date("m/d/y h:ia", $dwolla['transaction_ts']),
			$dwolla['transaction_desc'],
			"$" . number_format($dwolla['transaction_amount'],2)
			];
		$table = table::init()->headers($headers)->rows($rows)->render();
		if (!$company['company_dwollatoken'])
		{
			$table .= base::alert('info', 'No Dwolla Authorization', 'This company has not linked their Dwolla Account.');
			$dwollaButtons = button::init()->url("/dwauth/$company[id]/")->text('Authorize Dwolla')->addStyle('btn-inverse')->icon('ok')->render();
		}
		
		$dwFooter = button::init()->text("Make Checking Payment")->isModalLauncher()->url('#dwollaSend')->addStyle('btn-info')->icon('money')->render();
		$sendDwolla = button::init()->formid('dwollaSendForm')->addstyle('btn-success')->text('Initiate Payment')->icon('arrow-right')->addStyle('mpost')->postVar('dwollaSend')->id($ticket['id'])->render();
		$this->exportModal(modal::init()->id('dwollaSend')->header('Make Payment With Dwolla')->content($this->dwollaSendForm($ticket))->footer($sendDwolla)->render());
		$data .= widget::init()->span(6)->header('Checking Transactions')->content($table)->isTable()->rightHeader($dwollaButtons)->footer($dwFooter)->render();
		$data .= "</div>";
		
		return $data;
		
	}

	private function dwollaSendForm(&$ticket)
	{
		$pre = "<img src='/assets/img/checking.jpg' align='left' width='200px' style='padding-right:10px'><h3>Pay with Dwolla</h3>
		<p>Dwolla is a bank to bank transfer service that allows you to pay using your checking account. Check out <a href='http://www.dwolla.com'>dwolla.com</a> for more information on this service.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'var' => 'dwolla_desc', 'text' => 'Transaction Note:', 'comment' => 'Enter a brief description for your records.'];
		$fields[] = ['type' => 'input', 'span' => 2, 'var' => 'dwolla_amount', 'text' => 'Amount to Pay:', 'prepend' => '$'];
		$fields[] = ['type' => 'input', 'span' => 1, 'var' => 'dwolla_pin', 'text' => 'Your Dwolla PIN', 'bottom' => 'This is required to authorize your transaction.'];
		$sources = $this->dwolla_getFundingSources($ticket['company_id']);
		$opts = [];
		foreach ($sources AS $source)
			$opts[] = ['val' => $source['Id'], 'text' => $source['Name']];
		$fields[] = ['type' => 'select', 'var' => 'dwolla_source', 'text' => 'Funding Source:', 'span' => 5, 'opts' => $opts];
		$form = form::init()->id('dwollaSendForm')->elements($fields)->post('/ticket/')->render();
		return $pre.$form;		
	}
	
	
	private function cancelPlanForm()
	{
		
		$pre = "<img src='/assets/img/cancel.jpg' align='left' width='200px' style='padding-right:10px'><h4>Cancel Subscription</h4>
				<p>This will cancel your monthly subscription immediately. Press Confirm below to complete.</p>";
		$fields = [];
		$form = form::init()->post('/ticket/')->id('cancelForm')->elements($fields)->render();
		return $pre.$form;
		
	}
	private function updatePlanForm()
	{
		$pre = "<img src='/assets/img/subscription.jpg' align='left' width='200px'><p>If you are updating a subscription, if the new plan is an additional cost then your client will be billed a pro-rated amount for the interval specified in the configuration. If the amount is
				less than their previous plan then the customer will be credited the pro-rated amount.</p>";
		
		$fields = [];
		$opts = [];
		$plans = $this->query("SELECT * from plans ORDER by plan_name ASC");
		foreach ($plans AS $plan)
		{
			$int = ($plan['plan_interval'] == 1) ? "Monthly" : "Every $plan[plan_interval] Months"; 
			$opts[] = ['val' => $plan['id'], 'text' => $plan['plan_name'] . " ($" . number_format($plan['plan_amount'],2). "/ $int)"];
		}
		$fields[] = ['type' => 'select', 'text' => 'Select New Plan:', 'opts' => $opts, 'var' => 'plan_id'];
		$form = form::init()->post('/ticket/')->elements($fields)->id('updatePlanForm')->render();
		return $pre.$form;
	}
	
	private function stripeInvoiceForm()
	{
		$pre = "<p>Stripe allows you to create monthly plans for each of your customers. Should an item need to be appeneded to the next invoice you can enter this item here. <b>Note:</b> Invoiced items are affected
				by coupon codes. If you need to circumvent a coupon, you should bill the customer directly vs. adding an invoice item.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'span' => 3, 'text' => 'Description:', 'comment' => 'This description will be shown on the invoice.', 'var' => 'charge_desc'];
		$fields[] = ['type' => 'input', 'span' => 1, 'prepend' => '$', 'text' => 'Amount:', 'bottom' => 'Enter the amount in dollars and cents. (i.e. 12.43)', 'var' => 'charge_amount'];
		
		$pre = "<img src='/assets/img/invoice.jpg' width='250px' align='left'>$pre";
		return $pre . form::init()->id('addInvItem')->post('/ticket/')->elements($fields)->render();
	}
	
	private function billCCForm()
	{
		$pre = "<p>This will bill the customer's credit card immediately. The charge will not show up in the charges list below until the transactions clears. Stripe will notify aTikit as soon as funds have been
				successfully obtained. This generally takes anywhere from 5 to 30 seconds. You will receive an immediate decline or pre-authorization once you hit submit.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'span' => 3, 'text' => 'Description:', 'comment' => 'This description will be shown on the invoice.', 'var' => 'charge_desc'];
		$fields[] = ['type' => 'input', 'span' => 1, 'prepend' => '$', 'text' => 'Amount:', 'bottom' => 'Enter the amount in dollars and cents. (i.e. 12.43)', 'var' => 'charge_amount'];
		
		$pre = "<img src='/assets/img/creditcard.jpg' width='250px' align='left'> $pre";
		return $pre . form::init()->id('billCCForm')->post('/ticket/')->elements($fields)->render();
		
	}
	
	private function ccUpdateForm(&$ticket)
	{
		$cid = $ticket['company_id'];
		$this->exportJS(js::stripeToken($this->getSetting('stripe_publish'), 'savecc'));
		$span = [];
		$fields = [];
		$fields[] = array('type' => 'input', 'text' => 'Name on Card', 'val' => null, 'var' => null, 'class' => 'card-name');
		$fields[] = array('type' => 'input', 'text' => 'Card Number', 'val' => null, 'var' => null,  'class' => 'card-number');
		$fields[] = array('type' => 'input', 'text' => 'CVC', 'labelnote' => '3 digits' , 'val' => null, 'var' => null, 'comment' => 'Enter 3 or 4 digit code on back of your card', 'class' => 'card-cvc');
		
		$span[] = ['span' => 6, 'elements' => $fields];
		$fields = [];
		$fields[] = array('type' => 'input', 'text' => 'Expiration Month', 'val' => null, 'var' => null, 'comment' => 'Expiration Month in Two Digits', 'class' => 'card-expiry-month');
		$fields[] = array('type' => 'input', 'text' => 'Expiration Year',  'val' => null, 'var' => null, 'comment' => 'Expiration Year in Four Digits', 'class' => 'card-expiry-year');
		$fields[] = array('type' => 'input', 'text' => 'Billing Zip Code', 'val' => null, 'var' => null, 'class' => 'card-zip');
		
		$fields[] = ['type' => 'hidden', 'var' => 'cid', 'val' => $ticket['company_id']];
		$fields[] = ['type' => 'hidden', 'var' => 'tid', 'val' => $ticket['id']];
		$span[] = ['span' => 6, 'elements' => $fields];
		// Mask Inputs
		$this->exportJs(js::maskInput('card-expiry-month', "99"));
		$this->exportJs(js::maskInput('card-zip', "99999"));
		$this->exportJs(js::maskInput('card-expiry-year', "9999"));
		$form = form::init()->id('payment-form')->post('/ticket/')->spanElements($span)->render();
		$pre = "<p>You are about to add or update your billing information. <b>This will not charge your credit card</b>. This is merely updating our merchant with your new card details.</p>";
		
		return $pre.$form;
	}
	
	private function getTicketBilled(&$ticket)
	{
		if (!$this->canSeeBilling() && $this->isProvidingCompany()) 
			return null;
		$headers = ['Date', 'Description', 'Amount'];
		$stripes = $this->query("SELECT * from transactions WHERE ticket_id='$ticket[id]'");
		$rows = [];
		foreach ($stripes AS $stripe)
		{
			$row = [date("m/d/y h:ia", $stripe['transaction_ts']),
			$stripe['transaction_desc'],
			"$" . number_format($stripe['transaction_amount'],2)
			];
			$rows[] = $row;			
				
		}
		$table = table::init()->headers($headers)->rows($rows)->render();
		$buttons = null;
		
		if (!$this->isProvidingCompany())
		{
			if ($this->company->company_stripeid)
			{
				$buttons .= button::init()->text("Make Credit Card Payment")->isModalLauncher()->url('#customerCCPayment')->addStyle('btn-inverse')->addStyle('btn-small')->icon('credit-card')->render();
				$createCCPayment = button::init()->text('Authorize Payment')->addStyle('btn-info')->icon('ok')->formid('billCCForm')->addStyle('mpost')->postVar('createStripeCharge')->id($ticket['id'])->render();
				$this->exportModal(modal::init()->id('customerCCPayment')->header("Make a Credit Card Payment")->content($this->customerCCPayment())->footer($createCCPayment)->render());
			}
			if ($this->company->company_dwollatoken)
			{
				$buttons .= button::init()->text("Make Checking Account Payment")->isModalLauncher()->url('#dwollaSend')->addStyle('btn-info')->addStyle('btn-small')->icon('money')->render();
				$sendDwolla = button::init()->formid('dwollaSendForm')->addstyle('btn-success')->text('Initiate Payment')->icon('arrow-right')->addStyle('mpost')->postVar('dwollaSend')->id($ticket['id'])->render();
				$this->exportModal(modal::init()->id('dwollaSend')->header('Make Payment With Dwolla')->content($this->dwollaSendForm($ticket))->footer($sendDwolla)->render());
			}
			
		}
		return widget::init()->header("Ticket Charges")->icon('money')->content($table)->isTable()->footer($buttons)->render();
	}
	
	private function customerCCPayment($content)
	{
		$company = $this->getSetting('mycompany');
		$pre = "<p>You are about to make a payment to $company. This will authorize your credit card for the amount you enter below. This payment will be associated with this ticket and an invoice will be generated and e-mailed to you.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'span' => 3, 'text' => 'Description:', 'comment' => 'This description will be shown on your invoice.', 'var' => 'charge_desc'];
		$fields[] = ['type' => 'input', 'span' => 1, 'prepend' => '$', 'text' => 'Amount:', 'bottom' => 'Enter the amount in dollars and cents. (i.e. 12.43)', 'var' => 'charge_amount'];
		$pre = "<img src='/assets/img/creditcard.jpg' width='250px' align='left'> $pre";
		return $pre . form::init()->id('billCCForm')->post('/ticket/')->elements($fields)->render();
	}
	
	private function getHistory(&$ticket)
	{
		$company = $this->query("SELECT * from companies WHERE id='$ticket[company_id]'")[0];
		$data = "<div class='row-fluid'>";
		$data .= "<div class='span6'>";
		$rows = [];
		$lists = $this->query("SELECT * from tickets WHERE company_id='$ticket[company_id]' ORDER by ticket_lastupdated DESC");
		$headers = ['#', 'Subject', 'Status'];
		foreach ($lists AS $list)
				$rows[] = [$list['id'],
					"<a href='/ticket/$ticket[id]/'>$list[ticket_title]</a>",
					($list['ticket_isclosed']) ? "Closed" : "Open",
					($list['ticket_isclosed']) ? "red" : "green"
				];
		$table = table::init()->headers($headers)->rows($rows)->render();
		$data .= widget::init()->icon('envelope')->header('Previous Tickets')->maxHeight(450)->content($table)->istable(true)->render();
		
		
		$data .= "</div>";
		if ($this->isProvidingCompany())
		{
			
			$data .= "<div class='span6'>";
			
			$fields = [['type' => 'textarea', 'span' => 12, 'rows' => 10, 'var' => 'company_notes', 'val' => $company['company_notes']]];
			$save = button::init()->text('Update Customer Notes')->addStyle('post')->formid('updateCustomerNotesForm')->addStyle('btn-success')->icon('pencil')->postvar('updateNotes')->id($company['id'])->render();
			$form = form::init()->post('/ticket/')->elements($fields)->id('updateCustomerNotesForm')->render();
			$form = "<img src='/assets/img/notes.jpg' align='left'>$form";
			$data .= widget::init()->header('Customer Notes')->icon('road')->content($form)->footer($save)->render();			
			$data .= "</div>";
		}
		$data .= "</div>";
		
		return $data;
		
	}
	
	
	private function showTicketDetails(&$ticket)
	{
		$tabs = [];
		$crumbs = base::crumbs([['url' => '#', 'text' => "($ticket[id]) $ticket[ticket_title]"]]);
		// Tab #1 is going to be split. So lets create the span6 that we will need. 
	    // Update Active Queue
	    $_SESSION['activeQueue'] = $ticket['queue_id'];
		$replyContent = "
				<div class='row-fluid'>
							<div class='span6'>".$this->getTicketReplies($ticket)."</div>
							<div class='span6'>".$this->getFiles($ticket).$this->getCompanyNotes($ticket).$this->getTicketBilled($ticket).$this->getIncidentStanding($ticket)."</div>
						</div>";
				
		// Counter Init
		$maincount = $this->returnCountFromTable("replies", "ticket_id='$ticket[id]' AND reply_isinternal = false");
		$subcount = $this->returnCountFromTable("subtickets", "ticket_id='$ticket[id]' AND subticket_isclosed = false");
		$ttlcount = $this->returnCountFromTable("tickets", "company_id='$ticket[company_id]'");
		// Tab Creation
		$tabs[] = ['class' => 'active', 'id' => 'ticketTrail', 'title' => "<i class='icon-comments'></i> <span class='badge badge-success'>$maincount</span> Main Communication", 'content' => $replyContent];
		if ($this->isProvidingCompany())
			$tabs[] = ['id' => 'subTickets', 'title' => "<i class='icon-tasks'></i> <span class='badge badge-warning'>$subcount</span> Ticket Tasks", 'content' => $this->getSubTickets($ticket)];
		$tabs[] = ['id' => 'history', 'title' => "<i class='icon-calendar'></i> <span class='badge badge-inverse'>$ttlcount</span> History", 'content' => $this->getHistory($ticket)];
		if ($this->canSeeBilling() || !$this->isProvidingCompany())
		{
			$tabs[] = ['id' => 'sow', 'title' => "<i class='icon-bookmark'></i> Statement of Work", 'content' => $this->getStatementOfWork($ticket)];
			
		}
		if ($this->canSeeBilling() && $this->isProvidingCompany())
			$tabs[] = ['id' => 'billing', 'title' => "<i class='icon-money'></i> Billing", 'content' => $this->getBillingTab($ticket)];
		
		$buttons = button::init()->text('Add Reply')->isModalLauncher()->url('#createReplyModal')->addStyle('btn-inverse')->icon('comment')->render();
		$buttons .= button::init()->text('Upload File')->isModalLauncher()->url('#uploadFile')->addStyle('btn-info')->icon('arrow-up')->render();
		$buttons .= button::init()->text('Close Ticket')->url("/close/$ticket[id]/")->addStyle('btn-danger')->addStyle('get')->icon('remove')->render();
		$fbuttons = $buttons;
		$fbuttons .= button::init()->text('Back to Top')->url("#")->addStyle('btn-warning')->addStyle('top')->icon('arrow-up')->render();
		$fbuttons .= button::init()->text('Ticket List')->url("/")->addStyle('btn-primary')->icon('arrow-left')->render();
		$widget = widget::init()->span(12)->icon('edit')->header($ticket['ticket_title'])->isTabs($tabs)->rightHeader($buttons)->footer($fbuttons)->render();
		$data = base::row($widget);
		$save = button::init()->formid('newReplyForm')->addStyle('mpost')->postVar('createReply')->text('Add Reply')->addStyle('btn-success')->icon('ok')->message('Submitting..')->id($ticket['id'])->render();
		$this->exportModal(modal::init()->id('createReplyModal')->header('Add Reply')->content($this->createReplyForm($ticket))->footer($save)->render());
		$saveFile = button::init()->formid('newUploadForm')->addStyle('mpost')->postVar('createAttachment')->text('Save Attachment')->addStyle('btn-success')->icon('ok')->message('Submitting..')->id($ticket['id'])->render();
		$this->exportModal(modal::init()->id('uploadFile')->header('Upload Attachment')->content($this->createUploadForm($ticket))->footer($saveFile)->render());
		$this->export($data);
		$this->exportjs(js::scrollBottom());
		$this->exportjs(js::scrollTop('top'));
	}
	
	private function getStatementOfWork(&$ticket)
	{
		$data = "<div class='row-fluid'>";
		$sow = $this->query("SELECT * from sows WHERE ticket_id='$ticket[id]'")[0];
		if (!$sow)
			$sdata .= "<h5>No Statement of Work Found</h5>
					<p>A statement of work is a quote for work to be completed. The statement of work can be used as an invoice for clients who require an invoice before payment is made. aTikit generated invoices generally
					after a payment has been made, therefore this system allows clients to have the proper paperwork in order to process payment on their end.</p>";  
		else 
		{ // Get SOW Details
			if (!$sow['sow_accepted'])
				$sdata .= base::alert('warning', 'Statement Pending', "This statement of work has not been approved.");
			
			$items = [];
			$items[] = ['label' => 'Created/Updated:', 'content' => $this->fbTime($sow['sow_updated'])];
			$items[] = ['label' => 'Created By', 'content' => $this->getUserByID($sow['sow_updatedby'])];
			if ($sow['sow_accepted'])
			{
				$items[] = ['label' => 'Accepted On:', 'content' => $this->fbTime($sow['sow_acceptts'])];
				$items[] = ['label' => 'Accepted By:', 'content' => $this->getUserById($sow['sow_acceptuid'])];
				$sdata .= base::alert("success", "Statement Accepted", "The client has accepted this statement of work and you can begin work.");
			}
			$sdata .= base::itemPairs($items);
		}
		$items = $this->decode($sow['sow_meta']);
		if (!is_array($items))
			$items = [];
		
		$headers = ['Description', 'Price', 'QTY', 'Ext. Price'];
		$rows = [];
		$ttl = 0;
		foreach ($items AS $ix => $item)
		{
			$del = ($this->isProvidingCompany()) ? " &nbsp;&nbsp; <a class='get' href='/sow/remove/$sow[id]/$ix/'><i class='icon-remove'></i></a>" : null;
			$rows[] = [$item['desc'] . $del, 
			"$". number_format($item['price'], 2),
			$item['qty'],
			"$". number_format($item['extprice'], 2),
			];
			$ttl += $item['extprice'];
		}		
		$rows[] = [null, null, "<span class='pull-right'><b>Total:</span>", "<b>$" . number_format($ttl, 2)."</b>", 'blue'];
		$table = table::init()->headers($headers)->rows($rows)->id('sowTable')->render();
		$modifyButton = null;
		if ($this->isProvidingCompany())
		{
			$modifyButton .= button::init()->text('Add to Statement')->icon('edit')->isModalLauncher()->url('#addSOW')->addStyle('btn-warning')->render();
			$saveStatement = button::init()->text('Add Item')->icon('ok')->formid('addSOWForm')->addStyle('btn-primary')->addStyle('mpost')->postVar('addSOWItem')->id($ticket['id'])->message('Updating Statement of Work')->render();
			$this->exportModal(modal::init()->id('addSOW')->header('Update Statement of Work')->content($this->addStatementForm($sow))->footer($saveStatement)->render());
		}
			$loc = "/download/sow/$sow[id]/";
			$modifyButton .= button::init()->text("Download Statement")->icon('arrow-down')->url($loc)->addStyle('btn-success')->render();
		if ($this->isProvidingCompany())
				$sendNow = button::init()->addStyle('btn-danger')->addStyle('get')->text('Send Statement to Client')->url("/send/$ticket[id]/")->render();
		$resendButton = button::init()->addStyle('btn-success')->addStyle('get')->text('Re-Send Statement to Client')->url("/send/$ticket[id]/")->render();
		$sowSent = ($sow['sow_sent']) ? base::alert("success", "Statement Sent", "This statement of work has been sent to the customer.<br/>$resendButton", true) : base::alert("error", "Statement has not been E-mailed", "This statement has not been sent to the client.<br/>$sendNow", true);
		$header = ($sow['sow_title']) ? $sow['sow_title'] : 'Modify Statement of Work';
		if (!$sow || !$this->isProvidingCompany()) $sowSent = null;
		$data .= widget::init()->span(8)->icon('cogs')->header($header)->rightHeader($modifyButton)->content($table)->footer($sowSent)->render();
		$data .= widget::init()->span(4)->icon('share')->header('Statement Status')->content($sdata)->render();
		$data .= "</div>";
		return $data;
		
		
		
	}
	
	private function addStatementForm(&$sow)
	{
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Update Statement Title:', 'var' => 'sow_title', 'val' => $sow['sow_title']];
		$fields[] = ['type' => 'input', 'text' => 'Item Description:', 'var' => 'desc', 'span' => 4];
		$fields[] = ['type' => 'input', 'text' => 'Item Price:', 'var' => 'price', 'span' => 1];
		$fields[] = ['type' => 'input', 'text' => 'Item QTY:', 'var' => 'qty', 'span' => 1];
		$pre = "<img src='/assets/img/invoiceitem.jpg' align='left' width='200px' style='padding-right:10px'><p>Extended price (Price * Qty) will be determined based on input and applied without your calculations. Tax should be applied to the item price if your items require tax. If your items require a shipping cost
				then you must add that as an item.</p>";
		$form = form::init()->id('addSOWForm')->elements($fields)->post('/ticket/')->render();
		return $pre.$form;
	}
	
	private function createUploadForm(&$ticket)
	{
		$pre = "<p>You can upload any type of file into the ticket. The file must not exceed 20MB in size. If you wish to upload a file larger than 20MB, please make prior arrangements to send to another e-mail address.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Description:', 'var' => 'file_title'];
		$fields[] = ['type' => 'ajax', 'text' => 'Upload:', 'id' => 'uploadBlock'];
		
		$this->exportJS(js::ajaxFile('uploadBlock', "t_$ticket[id]"));
		return $pre . form::init()->post('/ticket/')->elements($fields)->id('newUploadForm')->render();
	}
	
	public function saveAttachment($content)
	{
		$tid = $content['createAttachment'];
		if (!$_SESSION['fid'])
			$this->failJson('No file found.', 'No file was found to be uploaded.');
		if (!$content['file_title'])
			$this->failJson("No Description", "You must enter a description.");
		$fid = $_SESSION['fid'];
		unset($_SESSION['fid']);
		$this->query("UPDATE files SET file_title='$content[file_title]' WHERE id='$fid'");
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/ticket/$tid/";
		$this->jsone('success', $json);
	}
	
	public function closeTicket($content)
	{
		$ticket = $this->query("SELECT * from tickets WHERE id='$content[close]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failjson('Access Denied', 'Not your ticket');
		if ($this->ticketHasOpenTasks($ticket))
			$this->failJson("Unable to Close", "This ticket has active tasks. You will need to close them first.");
		$this->query("UPDATE tickets SET ticket_isclosed = true where id='$ticket[id]'");
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/";
		$this->jsone('success',$json);
	}
	
	public function updateStanding($content)
	{
		$ticket = $this->query("SELECT * from tickets WHERE id='$content[updateStanding]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failjson('Access Denied', 'Not your ticket');
		$now = time();
		$this->query("UPDATE tickets SET ticket_standingts='$now', ticket_standing='$content[ticket_standing]', ticket_standinguid='{$this->user->id}' WHERE id='$ticket[id]'");
		$json = [];
		$json['gtitle'] = 'Standing Updated';
		$json['gbody'] = "The standing for this incident has been updated.";
		$json['action'] = 'reassignsource';
		$json['elementval'] = "Standing Saved."; 
		$this->jsone('success', $json);
	}
	
	private function getSubTickets(&$ticket)
	{
		// Lets try to ajax this as much as possible just for the UX
		$subs = $this->query("SELECT * from subtickets WHERE ticket_id='$ticket[id]'");
		// Lets make this a nav like the admin.. just for aesthetics. 
		
		$addButton .= button::init()->isModalLauncher()->text('Add Task')->url('#addTask')->addStyle('btn-inverse')->addStyle('btn-block')->withGroup(false)->icon('plus')->render();
		$data = "<div class='row-fluid'>
				<div class='span6'>";
		$headers = ['Task', 'Assigned', 'Author', 'Last Updated'];
		foreach ($subs AS $sub)
		{
			$color = ($sub['subticket_isclosed']) ? "red" : "green";
			$ss = ($sub['subticket_isclosed']) ? "<s>" : null;
			$se = ($sub['subticket_isclosed']) ? "</s>": null;
			$rows[] = ["<a class='get' href='/ticket/$ticket[id]/$sub[id]/'>{$ss}$sub[subticket_title]{$se}</a>",
				($sub['subticket_assigned']) ? "<a class='get' href='/assign/sub/$sub[id]/'>".$this->getUserById($sub['subticket_assigned'])."</a>" : "<a class='get' href='/assign/sub/$sub[id]/'>Unassigned</a>",	 
				$this->getUserById($sub['subticket_creator']),
				$this->fbTime($sub['subticket_lastupdated']),
			$color];
		}
		if (!$subs)
			$data .= "<h4>No tasks found</h4>";
		else
			$data .= table::init()->headers($headers)->rows($rows)->render();
		$data .= "$addButton</div>";
		
		
		// Start Viewer on the next 6 span.
		$data .= "<div class='span6'>";
		$data .= widget::init()->header("<span class='viewportTitle'>Task Viewport</span>")->content("<div id='viewport'></div>")->icon('tasks')->render();
		$data .= "</div>"; // end span6
		$data .= "</div>"; // end rowfluid
		$save = button::init()->addStyle('mpost')->addStyle('btn-primary')->icon('ok')->text('Create Task')->formid('addTaskForm')->postVar('addTask')->id($ticket['id'])->render();
		$this->exportModal(modal::init()->header('Add Task')->content($this->addTaskForm())->id('addTask')->footer($save)->render());		
		return $data;
	}
	
	private function addTaskForm()
	{
		$pre = "<img src='/assets/img/tasks.jpg' align='left' width='200px' style='padding-right:10px'><h3>Create Task</h3>";
		$fields[] = ['type' => 'input', 'text' => 'Task Subject:', 'var' => 'subticket_title'];
		$fields[] = ['type' => 'textarea', 'span' => 4, 'rows' => 6, 'text' => 'Task Details:', 'var' => 'subticket_body'];
		return $pre .form::init()->id('addTaskForm')->elements($fields)->post('/ticket/')->render();
	}	
	
	public function addTask($content)
	{
		$tid = $content['addTask'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		if (!$ticket)
			$this->failJson("Ticket not found", "This ticket no longer exists.");
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "This is not your ticket.");
		if (!$content['subticket_title'] || !$content['subticket_body'])
			$this->failJson('Unable to create task', 'You must have a subject and a body for the task.');
		$now = time();
		$this->query("INSERT into subtickets SET subticket_lastupdated='$now', subticket_creator='{$this->user->id}', ticket_id='$tid', subticket_title='$content[subticket_title]', subticket_body='$content[subticket_body]'");
		$subid = $this->insert_id;
		// Lets render the new task into the list as we fade the modal out. 
		$this->notifyProvider("Task added in Ticket #$tid", $this->user->user_name. " added a new task ($content[subticket_title])", "/ticket/$tid/");
		$json = [];
		$json['gtitle'] = "Task Added";
		$json['gbody'] = "You have successfully added a task.";
		$json['action'] = 'append';
		$json['element'] = '.tasks';
		$json['content'] = "<li><a class='get' href='/ticket/$tid/$subid/'><i class='icon-tasks'></i> $content[subticket_title]</a></li>";
		$this->jsone('success', $json);
	}
	
	public function viewTask($content)
	{
		$tid = $content['tid'];
		$sid = $content['viewTask'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "You do not have access to this ticket.");
		$subticket = $this->query("SELECT * from subtickets WHERE id='$sid' AND ticket_id='$tid'")[0];
		$data = "<div class='well'>" . nl2br($subticket['subticket_body']) . "</div>";
		// display feed like you would a normal ticket from the subticket meta
		// our output is going to be a json response filling in the viewPort element with the task data. 
		
		//Form will be inline. No reason to modal the crap out of everything.
		$meta = $this->decode($subticket['subticket_meta']);
		if (!is_array($meta))
			$meta = [];
		$items = [];		
		
		foreach ($meta['replies'] AS $reply)
				$items[] = [
				'url' => '#',
				'author' => $this->getUserByID($reply['user_id']) . " (" . $this->getUserTitleById($reply['user_id']). ")",
				'ago' => $this->fbTime($reply['reply_ts']),
				'internal' => false,
				'post' => nl2br($reply['reply_body']),
				'thumb' => $this->getProfilePic($reply['user_id'])
				];
		if ($items)
		$data .= base::feed($items, 'taskPort');
		else $data .= "<ul class='taskPort'></ul>";
		// Now the form
		$data .= $this->taskUpdateForm($subticket);
		$json = [];
		$json['gtitle'] = "Task Loaded";
		$json['gbody'] = $subticket['subticket_title'];
		$json['action'] = 'inline';
		$json['element'] = '#viewport';
		$json['restore'] = true; // Restore link values.
		$json['content'] = $data;
		$json['ev'] = "$('.viewportTitle').html('$subticket[subticket_title]'); "; 
		$this->jsonE('success', $json);
	}
	
	private function taskUpdateForm(&$subticket)
	{
		$fields = [];
		$fields[] = ['type' => 'textarea', 'span' => 12, 'rows' => 6, 'var' => 'reply_body', 'text' => 'Reply:'];
		
		$data = form::init()->post('/ticket/')->elements($fields)->id('taskReplyForm')->render();
		$data .= button::init()->addStyle('btn-primary')->text('Update Task')->postVar('updateTask')->id($subticket['id'])->message('Updating Task..')->addStyle('post')->icon('plus-sign')->formid('taskReplyForm')->render();
		$data .= button::init()->addStyle('btn-danger')->text('Close Task')->url("/closetask/$subticket[id]/")->message('Closing Task..')->icon('remove')->addStyle('get')->formid('taskReplyForm')->render();
		
		return $data;
	}
	
	public function closeTask($content)
	{
		$sid = $content['closeTask'];
		$sub = $this->query("SELECT * from subtickets WHERE id='$sid'")[0];
		$ticket = $this->query("SELECT * from tickets WHERE id='$sub[ticket_id]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "Not your ticket.");
		$now = time();
		$this->query("UPDATE subtickets SET subticket_lastupdated='$now', subticket_isclosed = true WHERE id='$sid'");
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/ticket/$ticket[id]/";
		$this->jsonE('success', $json);
		
	}
	
	
	public function updateTask($content)
	{
		$sid = $content['updateTask'];
		$sub = $this->query("SELECT * from subtickets WHERE id='$sid'")[0];
		$ticket = $this->query("SELECT * from tickets WHERE id='$sub[ticket_id]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "Not your ticket.");
		if (!$content['reply_body'])
			$this->failjson('Unable to update', 'You must enter a reply into the box.');
				
		// Subticket replies are done in meta, not in a table. 
		$meta = $this->decode($sub['subticket_meta']);
		if (!is_array($meta))
			 $meta = [];
		// Store in the ['replies'] key.
		$now = time(); 
		$meta['replies'][] = ['reply_ts' => $now,
							'user_id' => $this->user->id,
							'company_id' => $this->company->id,
							'reply_body' => $content['reply_body']];
							
		$meta = $this->encode($meta);
		$now = time();
		$this->query("UPDATE subtickets SET subticket_lastupdated='$now', subticket_meta='$meta', subticket_isclosed = false WHERE id='$sid'");
		$this->notifyProvider("Task updated in Ticket #$ticket[id]", $this->user->user_name. " updated a the task ($sub[subticket_title])", "/ticket/$ticket[id]/");
		$json = [];
		$json['gtitle'] = "Task Updated";
		$json['gbody'] = "Your task has been updated";
		$json['action'] = 'reload';
		$json['url'] = "/ticket/$ticket[id]/";
		$this->jsone('success', $json);
	}
	
	public function updateToken($content)
	{
		// Get Current Token - If none exists, then we need to create the customer w/ stripe and get their id
		
		if ($this->isProvidingCompany())
		{
			$stripeid = $this->returnFieldFromTable("company_stripeid", "companies", "id='$content[cid]'");
			if (!$stripeid)
			{
				$newid = $this->stripe_createCustomer($content['stripeToken'], $content['cid']);
				$this->query("UPDATE companies SET company_stripeid='$newid', company_stripetoken='$content[stripeToken]' WHERE id='$content[cid]'");
			}
			else
			{
				$this->stripe_updateCustomer($content['stripeToken'], $content['cid']);
				$this->query("UPDATE companies SET company_stripetoken='$content[stripeToken]' WHERE id='$content[cid]'");
			}
			
		}
		else
		{
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
			
		}
		
		
		$this->reloadTarget("/ticket/$content[tid]/");
	}
	
	public function createStripeCharge($content)
	{
		if (!$this->canSeeBilling() && $this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to bill a credit card.");
		$tid = $content['createStripeCharge'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "Not your ticket.");
		$cid = $ticket['company_id'];
		// Convert to Stripe Amount
		if (!$content['charge_desc'])
			$this->failJson("Unable to Process". "You must enter a description for the invoice.");
		if (!$content['charge_amount'])
			$this->failJson("Unable to Process", "Unable to process a zero amount.");
		$orig = $content['charge_amount'];
		$amount = $content['charge_amount'] * 100; // Take 12.53 and make it 1253 (stripe only works in cents)
		$check = @$amount / 1; // 1253 / 1 should be 1253.. Otherwise something wierd was entered.
		if ($check != $amount)
			$this->failJson("Unable to Process", "Unable to process amount given.");
		
		// Send to stripe.. wait for a response. 
		
		$content['charge_desc'] = "[$tid] " . $content['charge_desc'];
		$result = $this->stripe_chargeCustomer($amount, $content['charge_desc'], $cid);
		if ($result === true)
		{
			$json = [];
			$json['action'] = 'fade';
			$json['gtitle'] = "Transaction Approved";
			$json['gbody'] = "Credit Card was authorized $".number_format($orig,2).". Final settlement is pending.";
			$this->jsone('success', $json);
		}
		else
			$this->failJson('Transaction Declined', "The credit card was declined. Reason: $result");
	}
	
	public function createStripeInvoiceItem($content)
	{
		if (!$this->canSeeBilling() || !$this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to bill a credit card.");
		$tid = $content['createStripeInvoiceItem'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		$cid = $ticket['company_id'];
		// Convert to Stripe Amount
		if (!$content['charge_desc'])
			$this->failJson("Unable to Process". "You must enter a description for the invoice.");
		if (!$content['charge_amount'])
			$this->failJson("Unable to Process", "Unable to process a zero amount.");
		$orig = $content['charge_amount'];
		$amount = $content['charge_amount'] * 100; // Take 12.53 and make it 1253 (stripe only works in cents)
		$check = @$amount / 1; // 1253 / 1 should be 1253.. Otherwise something wierd was entered.
		if ($check != $amount)
			$this->failJson("Unable to Process", "Unable to process amount given.");
		
		// Send to stripe.. wait for a response.
		
		$content['charge_desc'] = "[$tid] " . $content['charge_desc'];
		$result = $this->stripe_invoiceitem($amount, $content['charge_desc'], $cid);
		if ($result === true)
		{
			$json = [];
			$json['action'] = 'fade';
			$json['gtitle'] = "Item Added";
			$json['gbody'] = "Invoice item for $".number_format($orig,2)." added to next invoice.";
			$this->jsone('success', $json);
		}
		else
			$this->failJson('Unable to Add to Invoice', "Stripe rejected the invoice item. Reason: $result");
	}
	
	public function updateStripePlan($content)
	{
		$cid = $content['updatePlan'];
		if (!$this->canSeeBilling() || !$this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to do this function.");
		$planID = $this->returnFieldFromTable("plan_id", "plans", "id='$content[plan_id]'");
		$result = $this->stripe_setPlan($planID, $cid);
		if ($result === true)
		{
			$json = [];
			$json['action'] = 'fade';
			$json['gtitle'] = "Plan Updated";
			$json['gbody'] = "Plan has been altered successfully.";
			$this->query("UPDATE companies SET company_plan='$content[plan_id]' WHERE id='$cid'");
			$this->jsone('success', $json);
		}
		else
			$this->failJson('Unable to Change Plan', "Stripe rejected the plan change. Reason: $result");
	}
	
	public function addItemtoSow($content)
	{
		if (!$this->canSeeBilling() || !$this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to do this function.");
		 
		$price = $content['price'] * 100; // Take 12.53 and make it 1253 (stripe only works in cents)
		$check = @$price/ 1; // 1253 / 1 should be 1253.. Otherwise something wierd was entered.
		if ($check != $price)
			$this->failJson("Unable to Add", "Unable to process amount given.");
		if (!$content['desc'] || !$content['price'])
			$this->failJson("Unable to Add", "You must have a description and Price in numerics.");
		if (!$content['qty'])
			$content['qty'] = '1';
		if (!$content['sow_title'])
			$this->failJson('Unable to Add', "Your statement of work must have a title.");
		$tid = $content['addSOWItem'];
		$sow = $this->query("SELECT * from sows WHERE ticket_id='$tid'")[0];
		$items = $this->decode($sow['sow_meta']);
		if (!is_array($items))
			$items = [];
		$extprice = $content['price'] * $content['qty'];
		$items[] = ['desc' => $content['desc'],
					'price'=> $content['price'],
					'qty' => $content['qty'],
					'extprice' => $extprice
		];
		$items = $this->encode($items);
		$now = time();
		if ($sow['id'])
			$this->query("UPDATE sows SET sow_title='$content[sow_title]', sow_sent = false, sow_meta='$items', sow_updated='$now', sow_accepted = false, sow_updatedby = '{$this->user->id}' WHERE id='$sow[id]'");
		else
			$this->query("INSERT INTO sows SET sow_title='$content[sow_title]', sow_sent = false, ticket_id='$tid', sow_meta='$items', sow_updated='$now', sow_accepted = false, sow_updatedby = '{$this->user->id}'");
		
		$json = [];
		$json['action'] = 'fade';
		$json['gtitle'] = "Item Added";
		$json['gbody'] = "$content[desc] has been added to the Statement";
		$json['raw'] = "<tr><td>$content[desc]</td><td>$content[price]</td><td>$content[qty]</td><td>$extprice</td></tr>";
		$json['ev'] = "$(data.raw).prependTo('#sowTable').slideDown('slow')";
		$this->jsone('success', $json);
		
	}
	
	public function sendSOW($content)
	{
		if (!$this->canSeeBilling() || !$this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to do this function.");
		$tid = $content['sendSOW'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		$sow = $this->query("SELECT * from sows WHERE ticket_id='$tid'")[0];
		$items = $this->decode($sow['sow_meta']);
		if (!is_array($items))
			$this->failJson("Unable to Send", "This statement of work is empty");
		$hash = md5(uniqid());
		
		$url = $this->getSetting('atikit_url');
		
		$loc = $this->createPDFSOW($sow);
		$this->mailCompany($ticket['company_id'], "Statement of Work Created/Updated", "A new statement of work has been created or updated for ticket #{$tid} ($ticket[ticket_title]). The statement has been attached in 
this email. If you wish to approve this statement and begin work, click the link below.
		
$url" . "/accept/$hash/", $ticket['queue_id'], $loc );
		$json = [];
		$json['gtitle'] = "Statement sent.";
		$json['gbody'] = "Your statement of work has been sent for approval.";
		$json['action'] = 'reassignsource';
		$json['elementval'] = "Statement Sent.";
		$this->query("UPDATE sows SET sow_hash='$hash', sow_sent = true WHERE id='$sow[id]'");
		$this->jsone('success', $json); 
	}
	
	public function removeSOWItem($content)
	{
		$sid = $content['removeSOWItem'];
		$index = $content['si'];
		if (!$this->canSeeBilling() || !$this->isProvidingCompany())
			$this->failJson("Access Denied", "You do not have permissions to do this function.");
		$sow = $this->query("SELECT * from sows WHERE id='$sid'")[0];
		$now = time();	
		$items = $this->decode($sow['sow_meta']);
		unset($items[$index]);
		$items = $this->encode($items);
		$this->query("UPDATE sows SET sow_meta='$items', sow_accepted = false, sow_sent = false, sow_updated='{$now}', sow_updatedby='{$this->user->id}'");
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = "/ticket/$sow[ticket_id]/";
		$this->jsone('success', $json);
	}
	
	public function cancelPlan($content)
	{
		$cid = $content['cancelPlan'];
		if (!$this->isProvidingCompany() && $cid != $this->company->id)
			$this->failjson('Unable to Cancel', 'Access Denied');
		$result = $this->stripe_cancelSubscription($cid);
		if ($result === true)
		{
			$json = [];
			$json['gtitle'] = 'Subscription Cancelled';
			$json['gbody'] = "Subscription has been immediately cancelled.";
			$json['action'] = 'fade';
			$this->query("UPDATE companies SET company_plan=0 where id='$cid'");
			$this->mailProvider("Subscription Cancelled", $this->getCompanyById($cid) . " has cancelled their subscription.");
			$this->notifyProvider("Subscription Cancelled", $this->getCompanyById($cid) . " has cancelled their subscription.", null);
			$this->jsone('success', $json);
		}
		else 
			$this->failjson("Unable to Cancel", "Reason: ". $result);
	}
	
	public function downloadSOW($content)
	{
		$sow = $this->query("SELECT * from sows WHERE id='$content[downloadSOW]'")[0];
		$cid = $this->returnFieldFromTable("company_id", "tickets", "id='$sow[ticket_id]'");
		if (!$this->isProvidingCompany() && $cid != $this->company->id)
			$this->failjson('Unable to Cancel', 'Access Denied');
		$this->ajax = true;
		$this->createPDFSOW($sow, true);
	}
	
	public function updateCustomerNotes($content)
	{
		$cid = $content['updateNotes'];
		if (!$this->isProvidingCompany()) 
			$this->failJson('Access Denied', 'You cannot access this feature.');
		
		$this->query("UPDATE companies SET company_notes='$content[company_notes]' WHERE id='$cid'");
		$json = [];
		$json['gtitle'] = 'Notes Updated';
		$json['gbody'] = 'Customer notes have been updated';
		$json['action'] = 'reassignsource';
		$json['elementval'] = 'Notes Updated!';
		$this->jsonE('success', $json);
	}
	
	public function createDwollaPayment($content)
	{
		$tid= $content['dwollaSend'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Access Denied", "This is not your ticket.");
		$result = $this->dwolla_sendMoney($tid, $content['dwolla_amount'], $content['dwolla_pin'], $content['dwolla_desc'], $content['dwolla_source']);
		if ($result === true)
		{
			$json = [];
			$json['gtitle'] = 'Payment sent!';
			$json['gbody'] = "Payment has been authorized.";
			$json['action'] = 'fade';
			$this->jsone('success', $json);
		}
		else
			$this->failJson("Unable to Authorize", $result);		
		
	}
	public function assignSubTask($content)
	{
		$sid = $content['assignSub'];
		$subticket = $this->query("SELECT * from subtickets WHERE id='$sid'")[0];
		$ticket = $this->query("SELECT * from tickets WHERE id='$subticket[ticket_id]'")[0];
		if (!$this->isMyTicket($ticket))
			$this->failJson("Unable to assign", "Access Denied");
		$now = time();
		$this->query("UPDATE subtickets SET subticket_lastupdated='$now', subticket_assigned='{$this->user->id}' WHERE id='$sid'");
		$json = [];
		$json['gtitle'] = "Task Assigned";
		$json['gbody'] = "You have assigned this task to yourself.";
		$json['action'] = 'reassignsource';
		$json['elementval'] = $this->user->user_name;
		$this->jsone('success', $json);
		
	}
} // class


$mod = new ticket();

if (isset($_GET['view']))
	$mod->showTicket($_GET);
else if (isset($_POST['createReply']))
	$mod->saveReply($_POST);
else if (isset($_POST['createAttachment']))
	$mod->saveAttachment($_POST);
else if (isset($_GET['close']))
	$mod->closeTicket($_GET);
else if (isset($_POST['updateStanding']))
	$mod->updateStanding($_POST);
else if (isset($_POST['addTask']))
	$mod->addTask($_POST);
else if (isset($_GET['viewTask']))
	$mod->viewTask($_GET);
else if (isset($_GET['closeTask']))
	$mod->closeTask($_GET);
else if (isset($_POST['updateTask']))
	$mod->updateTask($_POST);
else if (isset($_POST['stripeToken']))
	$mod->updateToken($_POST);
else if (isset($_POST['createStripeCharge']))
	$mod->createStripeCharge($_POST);
else if (isset($_POST['createStripeInvoiceItem']))
	$mod->createStripeInvoiceItem($_POST);
else if (isset($_POST['updatePlan']))
	$mod->updateStripePlan($_POST);
else if (isset($_POST['addSOWItem']))
	$mod->addItemtoSow($_POST);
else if (isset($_GET['sendSOW']))
	$mod->sendSOW($_GET);
else if (isset($_GET['removeSOWItem']))
	$mod->removeSOWItem($_GET);
else if (isset($_POST['cancelPlan']))
	$mod->cancelPlan($_POST);
else if (isset($_GET['downloadSOW']))
	$mod->downloadSOW($_GET);
else if (isset($_POST['updateNotes']))
	$mod->updateCustomerNotes($_POST);
else if (isset($_POST['dwollaSend']))
	$mod->createDwollaPayment($_POST);
else if (isset($_GET['assignSub']))
	$mod->assignSubTask($_GET);
