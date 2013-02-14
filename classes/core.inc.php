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
 */

$REAL_DIR = getcwd();
require_once($REAL_DIR . "/helpers/base.php");									
require_once($REAL_DIR . "/classes/config.inc.php");

require_once($REAL_DIR ."/classes/c3tools.php");
require_once($REAL_DIR ."/classes/db.trt.php");
require_once($REAL_DIR ."/classes/stripe/lib/Stripe.php");
require_once($REAL_DIR ."/classes/stripe.trt.php");
require_once($REAL_DIR ."/classes/vitelity.class.php");
// Extensions
require_once($REAL_DIR ."/classes/swift/swift_required.php");			
require_once($REAL_DIR ."/classes/dwolla/dwolla.php");
require_once($REAL_DIR ."/classes/fpdf/fpdf.php");
require_once($REAL_DIR ."/classes/PlancakeEmailParser.php");


class core
{
	use c3tools, c3db, c3stripe;								// Include all Core 3 traits
	
	public $ajax = false;										// By default we are not running as an AJAX Response
	public $jsData = null;										// Javascript container for all onReady functions.
	public $exportData = null;									// Document container for pre-processing exports.
	public $htmlData = null;
	public $pageTitle = "aTikit v1.0";
	public $modalData = null;
	
	public function __construct($passthru = false, $ajax = false)
	{
		date_default_timezone_set('EST');
		if ($ajax) $this->ajax = true;
		$this->passthru = $passthru;
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		openlog(config::APP_NAMESPACE, 0, LOG_LOCAL0);
		$this->logFile = fopen(config::LOG_FILE, "a+");

		$this->initDB(); 												// Bring up the database connection
		$this->initMemcache();
		$this->validateSession(); 										// Session startup and login validator
		$this->escapeVars(); 											// Sanitation
		$this->htmlData = base::init();
		if (!$passthru)
			$this->buildUserObject();										// Build User Object
		
		if ($this->getSetting('stripe_private'))
			Stripe::setApiKey($this->getSetting('stripe_private'));
	}
	
	/**
	 * Check for session existence If the user is not logged in.
 	 * @return void
	 */
	private function validateSession ()
	{
		session_cache_expire( 360 );
		session_name(config::APP_NAMESPACE);
		session_start();
	} // validateSession
	
	public function log($item, $module = "system")
	{
		$now = time();
		$date = date("m/d/y h:ia", $now);
		$msg = "[$date] [$module] :: $item";
		syslog(LOG_INFO, $item);
		fwrite($this->logFile, $msg . "\n");
	}
	
		
	public function exportJS($data)
	{
		$this->jsData .= $data." \n \n";
			
	}
	
	public function export ($data)
	{
		if ($this->ajax)
			$this->exportData = $data;
		else
			$this->exportData .= $data;
	
	}
	public function exportModal($data)
	{
		$this->modalData .= $data;
		
	}
	
	public function setSetting($var, $val)
	{
		$exists = $this->returnCountFromTable("settings", "setting_var='$var'");
		if ($exists > 0)
			$this->query("UPDATE settings SET setting_val='$val' WHERE setting_var='$var'");
		else
			$this->query("INSERT into settings SET setting_var='$var', setting_val='$val'");
	}
	
	public function getSetting($var)
	{
		return $this->returnFieldFromTable("setting_val", "settings", "setting_var='$var'");
	}
	
	public function getLevelById($id)
	{
		return $this->returnFieldFromTable("level_name", "levels", "id='$id'");
		
	}
	public function canSeeBilling()
	{
		$level = $this->query("SELECT * from levels WHERE id='{$this->user->level_id}'", true)[0];
		if ($this->isAdmin()) return true;
		if ($level['level_isbilling']) return true;
		else return false;
	}
	
	public function getUserByID($id)
	{
		return $this->returnFieldFromTable("user_name", "users", "id='$id'");
	}
	
	public function isMyQueue($qid)
	{
		// This function returns true if you have access to this queue. You don't want people assigning billing tickets
		// to themselves by using the ID when they don't have access.
		if ($this->user->isadmin) return true; // default true for admins
		$levels = explode(",", $this->query("SELECT queue_levels FROM queues WHERE id='$qid'")[0]['queue_levels']);
		if (in_array($this->user->level_id, $levels))
			return true;
		else return false;
	}
	
	public function isMyTicket(&$ticket)
	{
		if ($this->isAdmin()) return true;
		if ($this->isProvidingCompany()) return true;
		if ($this->company->id == $ticket['company_id']) return true;
		return false;
	}
	
	
	
	public function getCompanyById($id)
	{
		return $this->returnFieldFromTable("company_name", "companies", "id='$id'");
		
	}
	
	public function mailCompany($cid, $subject, $body, $q = null, $attach = null)
	{
		$users = $this->query("SELECT * from users WHERE company_id='$cid'");
		if ($q)
			$queue = $this->query("SELECT * from queues WHERE id='$q'")[0];
		$defEmail = $this->getSetting("defaultEmail");
		$defName = $this->getSetting("defaultName");
		if (!$queue)
			$from = [$defEmail => $defName];
		else
			$from = [$queue['queue_email'] => $queue['queue_name']];
		foreach ($users AS $user)
			$this->sendMail($user['user_email'], $subject, $body, $attach, $from);
		
	}
	
	public function mailProvider($subject, $body, $q = null)
	{
		$cid = $this->returnFieldFromTable("id", "companies", "company_isprovider = true");
		$users = $this->query("SELECT * from users WHERE company_id='$cid'");
		if ($q)
			$queue = $this->query("SELECT * from queues WHERE id='$q'")[0];
		$defEmail = $this->getSetting("defaultEmail");
		$defName = $this->getSetting("defaultName");
		if (!$queue)
			$from = [$defEmail => $defName];
		else
			$from = [$queue['queue_email'] => $queue['queue_name']];
		
		
		foreach ($users AS $user)
			$this->sendMail($user['user_email'], $subject, $body, null, $from);
	}

	public function notifyCompany($cid, $title, $body, $url)
	{
		$users = $this->query("SELECT * from users WHERE company_id='$cid'");
		foreach ($users AS $user)
			$this->createNotification($user['id'], $title, $body, $url);
		
	}
	
	public function getUserTitleById($id)
	{
		return $this->returnFieldFromTable("user_title", "users", "id='$id'");
		
	}
	
	public function notifyProvider($title, $body, $url, $isBilling = false, $isAdmin = false, $fromCID = null)
	{
		$cid = $this->returnFieldFromTable("id", "companies", "company_isprovider = true");
		$users = $this->query("SELECT * from users WHERE company_id='$cid'");
		foreach ($users AS $user)
			$this->createNotification($user['id'], $title, $body, $url, $fromCID, $isBilling, $isAdmin);
	}
	
	public function createNotification($uid, $title, $body, $url, $from = null, $isBilling = false, $isAdmin = false)
	{
		if (!$from)
			$from = $this->user->id;
		if (!$from)
		{
			$from = $this->returnFieldFromTable("id", "companies", "company_isprovider = true");
			$from = $this->returnFieldFromTable("id", "users", "company_id='$from' AND user_isadmin = true");
		}
		if (!$uid) return null;
		$now = time();
		$isBilling = ($isBilling) ? 'true' : 'false';
		$isAdmin = ($isAdmin) ? 'true' : 'false';
		$this->query("INSERT into notifications SET notification_url='$url', notification_isbilling = $isBilling, notification_isadmin = $isAdmin, 
					user_id='$uid', notification_title='$title', notification_body='$body', notification_from='$from', notification_ts='$now', notification_active = TRUE");
	}
	
	public function createTicket($params)
	{
		// Queue, Company ID, Title, Body
		$url = $this->getSetting('atikit_url');
		$now = time();
		$params['ticket_body'] = nl2br($params['ticket_body']);
		$this->query("INSERT into tickets SET ticket_title='$params[ticket_title]', company_id='$params[company_id]', queue_id='$params[queue_id]',
				ticket_body='$params[ticket_body]', ticket_isclosed = false, ticket_status='New', ticket_opents='$now', ticket_lastupdated = '$now'");
		$id = $this->insert_id;
		$queueName = $this->returnFieldFromTable("queue_name", 'queues', "id='$params[queue_id]'");
		// Do Notifications here
		if ($this->isProvidingCompany())
		{
			$userOpened = $this->getUserByID($this->user->id);
			// Let client know we opened up a ticket on their behalf.
			$this->mailCompany($params['company_id'], "[#$id] ($queueName) $params[ticket_subject] created on your behalf", "
A new ticket in the $queueName queue has been opened on your behalf by $userOpened. The details are as follows:
	
$params[ticket_body]
--------------------

To respond to this ticket, you can reply to this e-mail or login to the support portal at $url. Please note that no attachements are allowed in replies.
				
". $this->getSetting('signature'), $params['queue_id']);
					$this->notifyCompany($params['company_id'], "Ticket #{$id} $params[ticket_subject] Created", "A new $queueName ticket has been created.", "/ticket/$id/");
					//$this->SMSAdmins("Ticket #{$id} Updated in WIM Tickets");
		}
		
		if (!$this->isProvidingCompany())
		{
			// Customer opened this ticket, we need to notify our admins. And notify the customers via email that the ticket was opened
			$this->notifyProvider("$queueName Ticket #{$id} Opened", $params['ticket_title'], "/ticket/$id/");
			$this->mailCompany($params['company_id'], "[#$id] ($params[ticket_title]) has been created", "A new ticket has been created for your account. You may login to the support portal at $url or
reply to this email to post any updates to the ticket.");
			$cname = $this->getCompanyById($params['company_id']);
			$this->mailProvider("($queueName) Ticket #{$id} Opened By $cname", "
New Ticket Details:
Subject: $params[ticket_title]
Body: ". str_replace("<br />", "\n", $params['ticket_body'])."					
");
		
		if ($this->companyIsVIP($params['company_id']))
		{
		 	$this->VIPSMS($this->getCompanyById($params['company_id']). " has created a ticket: $params[ticket_title]");	
		}
	}
			
		
		return $this->insert_id;
		
	}

	public function updateTicket($params)
	{
		// Ticket id, status, body, internal?
		$url = $this->getSetting('atikit_url');
		$now = time();
		if (!$params['cid'])
			$params['cid'] = $this->company->id;
		if (!$params['uid'])
			$params['uid'] = $this->user->id;
		$internal = ($params['internal']) ? 'true' : 'false';
		$this->query("INSERT into replies SET ticket_id='$params[ticket_id]', reply_ts='$now', company_id='$params[cid]', user_id='$params[uid]', reply_body='$params[ticket_body]', reply_isinternal = $internal");
		$this->query("UPDATE tickets SET ticket_isclosed = false, ticket_status='$params[ticket_status]', ticket_lastupdated='$now' WHERE id='$params[ticket_id]'");
		// Do Notifications here		
		$ticket = $this->query("SELECT * from tickets WHERE id='$params[ticket_id]'")[0];
		$queueName = $this->returnFieldFromTable("queue_name", 'queues', "id='$ticket[queue_id]'");
		
		if ($this->isProvidingCompany() && !$params['internal'])
		{
			$userOpened = $this->getUserByID($this->user->id);
			// Let client know we opened up a ticket on their behalf.
			$id = $params['ticket_id'];
			$this->log("Trying to mail $ticket[company_id]");
			$this->mailCompany($ticket['company_id'], "[#$id] ($queueName) $ticket[ticket_subject] has been Updated!", "
Your ticket in the $queueName queue has been updated by $userOpened. The details are as follows:

$params[ticket_body]
--------------------

To respond to this ticket, you can reply to this e-mail or login to the support portal. Please note that no attachements are allowed in replies.

". $this->getSetting("signature"), $ticket['queue_id']);
			$this->notifyCompany($ticket['company_id'], "#{$id} $ticket[ticket_subject] has been Updated", $ticket['ticket_body'], "/ticket/$id/");
			//$this->SMSAdmins("Ticket #{$id} Updated in WIM Tickets");
			
		}
		else
		{
			// Customer opened this ticket, we need to notify our admins. And notify the customers via email that the ticket was opened
			$id = $ticket['id'];
			$this->notifyProvider("$queueName Ticket #{$id} Updated", $ticket['ticket_title'], "/ticket/$id/");
			if (!$params['internal'])
				$this->mailCompany($ticket['company_id'], "[#$id] ($queueName) Updated ", "Your ticket has been updated in our system. You can login and respond at the following link: $url or
reply to this email to post any updates to the ticket.");
			$cname = $this->getCompanyById($ticket['company_id']);
			$this->mailProvider("($queueName) Ticket #{$id} Updated By $cname", "
			Ticket Updates:
			Subject: $ticket[ticket_title]
			Body: ". str_replace("<br />", "\n", $params['ticket_body'])."
					");
			if ($this->companyIsVIP($ticket['company_id']))
			{
				$this->VIPSMS($this->getCompanyById($ticket['company_id']). " has updated ticket #$id: $ticket[ticket_title]");
			}
		}
		
	}
	
	public function VIPSMS($message)
	{
		$numbers = explode(",", $this->getSetting("notify_sms"));
		if (!$this->getSetting("vitelity_user") || !$this->getSetting("vitelity_password"))
			return null;
		vitelity::$VITELITY_USERNAME = $this->getSetting("vitelity_user");
		vitelity::$VITELITY_PASSWORD = $this->getSetting("vitelity_password");
		vitelity::$sourceSMS = $this->getSetting("vitelity_sms");
		foreach ($numbers AS $number)
		{
			$this->log("Sending $message to $number", "sms");
			vitelity::vitelity_sendSMS($number, $message);
		}
		
	}
	
	public function companyIsVIP($cid)
	{
		return $this->returnFieldFromTable("company_vip", "companies", "id='$cid'");
	}
	
	public function getCIDByEmail($email)
	{
		$cid = $this->returnFieldFromTable("company_id", "users", "user_email='$email'");
		if ($cid) return $cid;
		$cid = $this->query("SELECT company_id FROM users WHERE user_altemails like '%$email%'")[0]['company_id'];
		return $cid;
	}
	
	public function getUIDByEmail($email)
	{
		$uid = $this->returnFieldFromTable("id", "users", "user_email='$email'");
		if ($uid) return $uid;
		$uid = $this->query("SELECT id FROM users WHERE user_altemails like '%$email%'")[0]['id'];
		return $uid;
	}
	/**
	 * Check to see if user is logged in and obtain objects. Obtain class
	 * objects to allow modules to interact with the user logged in. Also sets
	 * the module id you are on.
	 *
	 * @return void
	 */
	private function buildUserObject ()
	{
		if (!isset($_SESSION[config::APP_NAMESPACE]))
				$this->reloadTarget(config::LOGIN_URL);
			$id = $_SESSION[config::APP_NAMESPACE];
			$this->user = (object) $this->query("SELECT * from users WHERE id='{$id}'")[0];
			if ($this->user->company_id > 0)
				$this->company = (object) $this->query("SELECT * from companies WHERE id='{$this->user->company_id}'")[0];
			if (!$this->user) die(); // At this point we should have an id because the modal would've died.. 
	}
	
	private function getNotifications()
	{
		if (!$this->user) return null;
		$notifications = $this->query("SELECT * from notifications WHERE user_id='{$this->user->id}' ORDER by notification_ts DESC LIMIT 10");
		$items = [];
		foreach ($notifications AS $notification)
			$items[] = ['link' => $notification['notification_url'], 'body' => "<p><b>$notification[notification_title]</b><br/>".strip_tags($this->chop($notification['notification_body'], 125))."</p>", 'time' => $this->fbTime($notification['notification_ts'])
		,'thumb' => "/".$this->getProfilePic($notification['user_id'])];
		
		return base::notifications($items);
		
	}
	private function getNotificationCount()
	{
		return $this->returnCountFromTable("notifications", "notification_active = true AND user_id='{$this->user->id}'");
		
	}

	public function isProvidingCompany($cid = null)
	{
		
		if ($this->company->company_isprovider)
			return true;
		else return false;
		
	}
	
	public function isAdmin()
	{
		// This is for the app admin
		if ($this->user->user_isadmin)
			return true;
		else return false;
		
		
		
	}
	
	public function generateNav()
	{
		$data = null;
		if (!$this->user) return null;						
		if ($this->isProvidingCompany())
		{
			$data .= "	<li class='dropdown'><a data-toggle='dropdown'	class='dropdown-toggle' href='/clients/'>
									Clients <b class='caret'></b></a>
									<ul class='dropdown-menu'>
											<li class=''><a href='/clients/create/'>Create Client</a></li>
											<li class=''><a href='/clients/'>List Clients</a></li>
									</ul>
						</li>
					
					";
			
		}
		else
		{
			$data .= "<li><a href='/billing/'>Billing</a></li>";
		}
		
		if ($this->isAdmin())
		{
			$data .= "<li><a href='/admin/'>aTikit Admin</a></li>";
			
		}
		$data .= "<li class='dropdown'><a data-toggle='dropdown' class='dropdown-toggle' href='/clients/'>
					Options <b class='caret'></b></a>
					<ul class='dropdown-menu'>
						<li><a data-toggle='modal' href='#me'><i class='icon-user'></i>  My Profile</a></li>
						<li><a href='/logout/'><i class='icon-signout'></i>  Logout</a></li>
					</ul>
			</li>";
		$save = button::init()->addStyle('mpost')->addStyle('btn-info')->text('Save Profile')->formid('myProfileForm')->postVar('saveProfile')->id('true')->icon('ok')->render();
		$this->exportModal(modal::init()->id('me')->header("My Profile")->content($this->getMyProfile())->footer($save)->render());
		return $data;
		
	}

	private function getMyProfile()
	{
		$pre = "<p>Your profile is used to determine how you get updates to your tickets, billing notifications as well as your picture that is shown for your ticket updates.</p>";
		$span = [];
		$fields = [];
		$fields[] = ['type' => 'ajax', 'id' => 'profilePicture', 'text' => 'Profile Picture:'];
		$fields[] = ['type' => 'password', 'var' => 'user_password', 'text' => 'Change Password:'];
		$fields[] = ['type' => 'input', 'text' => 'Cell Phone Number:', 'var' => 'user_sms', 'val' => $this->user->user_sms, 'class' => 'sms'];
		$opts = [];
		
		$opts[] = ['val' => $this->user->user_cansms, 'text' => ($this->user->user_cansms) ? "Yes" : 'No'];
		$opts[] = ['val' => $this->user->user_cansms, 'text' => '--------------'];
		$opts[] = ['val' => 'Y', 'text'  => 'Yes'];
		$opts[] = ['val' => 'N', 'text' => 'No'];
		$fields[] = ['type' => 'select', 'var' => 'user_cansms', 'opts' => $opts, 'text' => 'Send Text Messages?', 'comment' => 'If set to Yes, you will be sent a text when tickets are updated or you are billed.'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Other E-mail Addresses:', 'var' => 'user_altemails', 'val' => $this->user->user_altemails, 'comment' => "Enter any secondary email addresses separated by commas"];
		$span[] = ['span' => 6, 'elements' => $fields];
		$this->exportJS(js::maskInput('sms', '1-999-999-9999'));
		$this->exportJS(js::ajaxFile('profilePicture', "profilePic"));
		return $pre . form::init()->id('myProfileForm')->post('/')->spanelements($span)->render();
	}
	
	public function processFile ($loc, $code)
	{
		// Save fileid in session of where this is stored.
		$this->log("Code is $code");
		if (preg_match('/t_/i', $code))
		{
			$ticketid = str_replace("t_", null, $code);
			mkdir("files/$ticketid", 0777, true);
			rename (config::AJAX_UPLOAD_FOLDER. "/". $loc, "files/$ticketid/$loc");
			$now = time();
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$type = finfo_file($finfo, "files/$ticketid/$loc");
			$this->query("INSERT into files SET ticket_id='$ticketid', file_ts='$now', file_loc='files/$ticketid/$loc', file_type='$type', user_id='{$this->user->id}'");
			$_SESSION['fid'] = $this->insert_id;
		} 
		
		if ($code == 'profilePic')
		{
			
			$ext = end(explode(".", $loc));
			rename (config::AJAX_UPLOAD_FOLDER. "/". $loc, "files/{$this->user->id}.$ext");
			$now = time();
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$type = finfo_file($finfo, "files/{$this->user->id}.$ext");
			if (!preg_match("/image/i", $type))
				return null;
			$file = "files/{$this->user->id}.$ext";
			$img = new Imagick ($file);
			$img->scaleImage( 50, 50, false);
			$img->writeImage($file);
			$this->query("UPDATE users SET user_pic='$file' WHERE id='{$this->user->id}'");
		}
	}
	
	public function getProfilePic($uid)
	{
		$pic = $this->returnFieldFromTable("user_pic", "users", "id='$uid'");
		if ($pic) 
			return $pic;
		else
			return "avatar.png";
	}
	
	public function ticketHasOpenTasks($ticket)
	{
		$exists = $this->returnCountFromTable("subtickets", "ticket_id='$ticket[id]' AND subticket_isclosed = false");
		if ($exists > 0)
			return true;
		else return false;
	}

	public function createPDFInvoice(&$transaction, $inline = null, $transactions = null)
	{
		$ticket = $this->query("SELECT * from tickets WHERE id='$transaction[ticket_id]'")[0];
		$company = $this->query("SELECT * from companies WHERE company_isprovider = true")[0];
		$client_company = $this->query("SELECT * from companies WHERE id='$ticket[company_id]'")[0];
		$reference = ($ticket) ? $ticket['ticket_title'] : "Transaction $transaction[id]";
			
		require_once("invoice.inc.php");
		$pdf = new c3invoice('P', 'mm', 'A4');
		$pdf->AddPage();
		
		$pdf->addCompany($company['company_name'] . "\n",
				"$company[company_address]\n" . "$company[company_address2]\n".
				"$company[company_city], $company[company_state]. $company[company_zip]\n".
				"$company[company_phone]\n",
				$this->getSetting("company_logo"));
		$pdf->fact_dev($transaction['id'] );
		$pdf->temporaire( "Posted Invoice" );
		$pdf->addDate( date("m/d/y", $transaction['transaction_ts']));
		$pdf->addClient("#$client_company[id]");
		$pdf->addPageNumber("1");
		$pdf->addClientAdresse("$client_company[company_name]\n$client_company[company_address]\n$client_company[company_address2]\n$client_company[company_city], $client_company[company_state]. $client_company[company_zip]");
		$pdf->addReglement("Auto-Draft Credit");
		$pdf->addEcheance(date("m/d/y", $transaction['transaction_ts']));
		$pdf->addNumTVA($company['company_name']);
		$pdf->addReference($reference);
		$cols=array( "TICKET"    => 23,
				"DESCRIPTION"  => 89,
				"METHOD"     => 26,
				"PRICE"      => 26,
				"POSTED DATE" => 26,
				 );
		$pdf->addCols( $cols);
		$cols=array( "TICKET"    => "C",
				"DESCRIPTION"  => "L",
				"METHOD"     => "C",
				"PRICE"      => "R",
				"POSTED DATE" => "R"
				);
		$pdf->addLineFormat($cols);
		$pdf->addLineFormat($cols);
		$y    = 109;
		// Items
		$ttl = 0;
		if (!$transactions) 
			$transactions[] = $transaction; 
		if ($transactions)
			foreach ($transactions AS $transaction)
			{
				$ttl += $transaction['transaction_amount'];
				switch ($transaction['transaction_source'])
				{
					case 'stripe' : $source = "Credit Card"; break;
					case 'dwolla' : $source = "Checking Draft"; break;
					case 'check'  : $source = "Posted Check"; break;
					case 'cash'   : $source = "Cash" ; break;
				}
				
					$line = array(
						"TICKET"    => $ticket['id'],
						"DESCRIPTION"  => $transaction['transaction_desc'],
						"METHOD"     => $source,
						"PRICE"      => number_format($transaction['transaction_amount'],2),
						"POSTED DATE" =>  date("m/d/y", $transaction['transaction_ts']),
						);
					
					$size = $pdf->addLine( $y, $line );
					$y   += $size + 2;
					$y   += $size + 2;
			} // each
		
			$line = array(
					"TICKET"    => null,
					"DESCRIPTION"  => null,
					"METHOD"     => "TOTAL",
					"PRICE"      => "$". number_format($ttl,2),
					"POSTED DATE" => null
			);
			$size = $pdf->addLine( $y, $line );
			$y   += $size + 2;
			$line = array(
					"TICKET"    => null,
					"DESCRIPTION"  => null,
					"METHOD"     => null,
					"PRICE"      => null,
					"POSTED DATE" => null
			);
			
			
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
	
		$file = md5(uniqid(time())) . ".pdf";
		if ($inline)
			$pdf->output($file, 'I');
		else
		{
			$pdf->output("/tmp/".$file, 'F');
			return "/tmp/".$file;
		}
	
	
	
	}
	
	
	public function createPDFSOW($sow, $inline = null)
	{
	
		$ticket = $this->query("SELECT * from tickets WHERE id='$sow[ticket_id]'")[0];
		$company = $this->query("SELECT * from companies WHERE company_isprovider = true")[0];
		$client_company = $this->query("SELECT * from companies WHERE id='$ticket[company_id]'")[0];
		require_once("invoice.inc.php");
		$pdf = new c3invoice('P', 'mm', 'A4');
		$pdf->AddPage();
		
		$pdf->addCompany($company['company_name'] . "\n",
				"$company[company_address]\n" . "$company[company_address2]\n".
				"$company[company_city], $company[company_state]. $company[company_zip]\n".
				"$company[company_phone]\n",
				$this->getSetting("company_logo"));
		$pdf->fact_dev($sow['id'] );
		$pdf->temporaire( "Statement of Work" );
		$pdf->addDate( date("m/d/y", $sow['sow_updated']));
		$pdf->addClient("#$client_company[id]");
		$pdf->addPageNumber("1");
		$pdf->addClientAdresse("$client_company[company_name]\n$client_company[company_address]\n$client_company[company_address2]\n$client_company[company_city], $client_company[company_state]. $client_company[company_zip]");
		$pdf->addReglement("Auto-Draft Credit");
		$pdf->addEcheance(date("m/d/y", $sow['sow_updated']));
		$pdf->addNumTVA($company['company_name']);
		$pdf->addReference($sow['sow_title']);
		$cols=array( "TICKET"    => 23,
				"DESCRIPTION"  => 89,
				"QTY"     => 22,
				"PRICE"      => 26,
				"EXT. PRICE" => 30,
				 );
		$pdf->addCols( $cols);
		$cols=array( "TICKET"    => "C",
				"DESCRIPTION"  => "L",
				"QTY"     => "C",
				"PRICE"      => "R",
				"EXT. PRICE" => "R"
				);
		$pdf->addLineFormat($cols);
		$pdf->addLineFormat($cols);
		$y    = 109;
		// Items
		$ttl = 0;
		$items = $this->decode($sow['sow_meta']);
		foreach ($items AS $item)
		{
			$line = array(
				"TICKET"    => $sow['ticket_id'],
				"DESCRIPTION"  => $item['desc'],
				"QTY"     => $item['qty'],
				"PRICE"      => number_format($item['price'],2),
				"EXT. PRICE" => number_format($item['extprice'],2)
				);
			$ttl += $item['extprice'];
			$size = $pdf->addLine( $y, $line );
			$y   += $size + 2;
		}

		
		$line = array(
				"TICKET"    => null,
				"DESCRIPTION"  => null,
				"QTY"     => null,
				"PRICE"      => null,
				"EXT. PRICE" => null
				);
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
	
		$line = array(
				"ITEM"    => null,
				"DESCRIPTION"  => null,
				"QTY"     => null,
				"PRICE"      => "TOTAL:",
				"EXT. PRICE" => "$".number_format($ttl,2),
				);
		$size = $pdf->addLine( $y, $line );
		$y   += $size + 2;
	
		$file = md5(uniqid(time())) . ".pdf";
		if ($inline)
			$pdf->output($file, 'I');
		else
		{
			$pdf->output("/tmp/".$file, 'F');
			return "/tmp/".$file;
		}
	
	
	
	}
	
	public function dwolla_getBalance($cid)
	{
		$company = $this->query("SELECT id,company_dwollatoken FROM companies WHERE id='$cid'")[0];
		if (!$company['company_dwollatoken'])
			return null;
		$Dwolla = new DwollaRestClient();
		// Seed a previously generated access token
		$Dwolla->setToken($company['company_dwollatoken']);
		$balance = $Dwolla->balance();
		return "Dwolla Balance: $" . number_format($balance,2);
		
	}
	
	public function dwolla_sendMoney($tid, $amount, $pin, $desc, $source)
	{
		$dest = $this->getSetting('dwolla_id');
		$ticket = $this->query("SELECT * from tickets WHERE id='$tid'")[0];
		$company = $this->query("SELECT * from companies WHERE id='$ticket[company_id]'")[0];
		$cid = $company['id'];
		$token = $company['company_dwollatoken'];
		if (!$token) return "No Dwolla Authorization.";
		$Dwolla = new DwollaRestClient();
		$Dwolla->setToken($token);
		$transactionId = $Dwolla->send($pin, $dest, $amount, 'Dwolla', $desc, 0, false, $source);
		if (!$transactionId) 
		 return $Dwolla->getError();
		else 
			{ 
				$now = time();
				$net = $amount - 0.25;
				$this->query("INSERT into transactions SET transaction_merchant_id='$transactionId', transaction_ts='$now', transaction_amount='$amount', transaction_fee='0.25', transaction_net='$net', transaction_source='dwolla', transaction_desc='$desc', ticket_id='$tid', company_id='$cid'");
				$this->notifyCompany($cid, "Checking Payment Authorized", "A payment in the amount of ${$amount} has been authorized.", "/ticket/$tid/");
				$this->notifyProvider("Dwolla Payment Authorized", "A payment in the amount of ${$amount} has been authorized from Ticket #$tid.", "/ticket/$tid/");
				return true;
			}
	}
	
	public function dwolla_getFundingSources($cid)
	{
		$token = $this->returnFieldFromTable("company_dwollatoken", "companies", "id='$cid'");
		if (!$token) return null;
		$Dwolla = new DwollaRestClient();
		$Dwolla->setToken($token);
		return $Dwolla->fundingSources();
	}
	
	public function __destruct()
	{
		fclose($this->logFile);
		if (!$this->meta) $this->meta = null;
		$this->htmlData = str_replace("%%META%%", $this->meta, $this->htmlData);
		$this->htmlData = str_replace("%%TITLE%%", $this->pageTitle, $this->htmlData);
		$this->htmlData = str_replace("%%CRUMBS%%", $this->crumbs, $this->htmlData);
		$this->htmlData = str_replace("%%NOTIFICATIONS%%", $this->getNotifications(), $this->htmlData);
		$this->htmlData = str_replace("%%NOTIFY_COUNT%%", $this->getNotificationCount(), $this->htmlData);
		$this->htmlData = str_replace("%%NAV%%", $this->generateNav(), $this->htmlData);
		if (!$this->ajax)
		{
			print($this->htmlData);
			print($this->exportData);
			if ($this->modalData) print $this->modalData;
			print(base::footer($this->jsData));
		}
			
				
		
	}
	

}