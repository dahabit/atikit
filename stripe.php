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
 * 
 */
require_once ("classes/core.inc.php");
$mod = new core ( true, true );
Stripe::setApiKey($mod->getSetting("stripe_private"));
$body = @file_get_contents ( 'php://input' );
$event_json = json_decode ( $body );
if (!$event_json) $mod->log("No Event ID, Terminating..", "stripeHooks");
$event_id = $event_json->id;
try
{
	$event = Stripe_Event::retrieve ( $event_id );
}
catch (Exception $e)
{
	die("No Event ID, Terminating..");
}
$data = $event->data ['object'];
$mod->log("Handling $event->type", "stripeHooks");
switch ($event->type) {
	
	
	case "customer.subscription.created" :
		syslog ( LOG_INFO, "Creating Subscription Record for Customer $data[customer]" );
		$customer = $data ['customer'];
		$uid = $mod->returnFieldFromTable ( "id", "users", "user_stripe_id='$customer'" );
		$mod->query ( "UPDATE users SET user_subbegin='$data[current_period_start]', user_subend='$data[current_period_end]', user_status='ACTIVE' WHERE id='$uid'" );
		break;
	
	case "charge.refunded" :
		syslog ( LOG_INFO, "Refunding Invoice for $data[customer]  on invoice $data[invoice]" );
		break;
	
	case "charge.succeeded" :
		$fee = $data ['fee'];
		$amount =  $data ['amount'];
		$customer = $data ['customer'];
		$invoice = $data['invoice'];
		$transid = $data['id'];
		$company = $mod->query("SELECT * from companies WHERE company_stripeid='$customer'")[0];
		$cid = $company['id'];
		$amt = "$" . @$amount / 100;
		if (!$data['description']) $data['description'] = "Payment for Invoice: $invoice";
		
		if (preg_match("/\[/", $data['description']))
		{
			// Parse out the ticket. 
			$x = end(explode("[", $data['description']));
			$tid = reset(explode("]", $x));
		}
		$myCompany = $mod->getSetting("mycompany");
		$mod->log("$company[company_name] Charged $amount for on invoice $invoice. Fee: $fee, Ticket ID: $tid", "stripeHooks" );
		$now = time ();
		$desc = addslashes ( $data ['description'] );
		$realamount = $amount / 100;
		$realfee = $fee / 100;
		$net = $realamount - $realfee;
		$desc = str_replace("[" . $tid . "]", null, $desc);
		$desc = trim($desc);
		$mod->query ( "INSERT into transactions SET transaction_merchant_id='$transid', transaction_ts='$now', transaction_amount='$realamount', transaction_fee='$realfee', transaction_net='$net', transaction_source='stripe', transaction_desc='$desc', ticket_id='$tid', company_id='$company[id]'" );
		$revid = $mod->insert_id;
		$mod->notifyCompany($company['id'], "Authorization Successful", "$" . number_format($realamount,2). " has been authorized for Ticket #$tid", "/ticket/$tid/");
		if (preg_match('/in_/', $data['description']))
			$mod->notifyProvider( "$data[description] Paid", "Settled Charge $" . $realamount . " (-$" . $realfee . ") from $company[company_name]", '/', true, false );
		else 
			$mod->notifyProvider( "New Charge Successful", "Settled Charge $" . $realamount . " (-$" . $realfee . ") from $company[company_name]", '/', true, false);
		$mod->mailCompany($cid, "Payment $".$realamount." Charged to Your Account", "A charge has been applied to your account. The details are below: 
				
Type: Credit Credit
Amount: $ $realamount
Description: $data[description]

This payment will be listed on your credit card statement as $myCompany.
				
");
		break;
	
	case "charge.failed" :
		$customer = $data ['customer'];
		$company = $mod->query("SELECT * from comanies WHERE company_stripeid='$customer'")[0];
		$cid = $company['id'];
		$amt = "$" . number_format ( @$data ['amount'] / 100, 2 );
		$mod->log("Card Declined for  $company[company_name] for $amt", "stripeHooks");
		
		$mod->notifyProvider( "Charge Failed for $company[company_name]", "Investigate a " . $amt . " failed charge.", '/', true, false);
		$mod->mailCompany($cid, "Your credit card was declined", "Oops. We tried to bill your card $amt and it was declined. Please login to the support portal and update your credit card details under the billing menu item.
				
Thank you for your attention in this matter!");
		break;
	
	case "customer.subscription.deleted" :
		syslog ( LOG_INFO, "Cancelling Account for Customer $data[customer]" );
		$customer = $data ['customer'];
		$uid = $mod->returnFieldFromTable ( "id", "users", "user_stripe_id='$customer'" );
		$company = $mod->returnFieldFromTable ( "company_id", "users", "id='$uid'" );
		$uid = $mod->returnFieldFromTable ( "id", "users", "user_stripe_id='$customer'" );
		$name = $mod->getCompanyById($company);
		$mod->mailAdmins("$name Cancellation", $name. " has cancelled their service.");
		$mod->notifyAdmins($name . " has Cancelled", "$name has cancelled their service with whoismy.com", '#');
		break;
	
	
	case "transfer.created" :
		$sum = $data->summary;
		$mod->query("INSERT into transfers SET transfer_amt='$data->amount', transfer_ts='$data->date', transfer_source='stripe'");
		$mod->notifyProvider( "Pending ACH Transfer on ".date("m/d/y", $data->date), "Gross/Fees: $" . (@$sum->charge_gross / 100) . "-$" . (@$sum->charge_fees/100) . " | Net: $" . ($data->amount / 100), '/admin/', true, true);
		break;
}