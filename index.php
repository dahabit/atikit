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
 * @class index
 */
require_once("classes/core.inc.php");

class index extends core
{
	public function main()
	{
		$data = base::subHeader("Open Incidents", "Create, Update or View your Open Tickets");
		$data .= base::begin();
		if ($this->isProvidingCompany())
			$data .= $this->providerMain();
		else
			$data .= $this->customerMain();
		$this->export($data);
	}

	private function getMyQueues()
	{
		$myLevel = $this->user->level_id;
		$queues = $this->query("SELECT * from queues ORDER by queue_name ASC");
		$data = [];
		foreach ($queues AS $queue)
		{
			$levels = explode(",", $queue['queue_levels']);
			if (in_array($myLevel, $levels))
				$data[] = $queue;
		}
		return $data;
	}
	
	private function getQuotedBilled(&$ticket)
	{
		$quoted = 0;
		$billed = 0;
		$trans = $this->query("SELECT * from transactions WHERE ticket_id='$ticket[id]'");
		foreach ($trans AS $tran)
			$billed += $tran['transaction_amount'];
		$sow = $this->query("SELECT * from sows WHERE ticket_id='$ticket[id]'")[0];
		if ($sow)
		{
			$items = $this->decode($sow['sow_meta']);
			foreach ($items AS $item)
				$quoted += $item['price'] * $item['qty'];
		}
		return "$" . number_format($quoted) . " / " . "<font color='green'>$" . number_format($billed)."</font>";
	}
	
	private function getProviderQueueTickets(&$queue)
	{
		$headers = ['#', 'Client', 'Subject', 'Status', 'Updated', 'Assigned'];
		if ($this->canSeeBilling())
			$headers[] = 'Quoted/Billed';
		$tickets = $this->query("SELECT * from tickets WHERE queue_id='$queue[id]' and ticket_isclosed = false");
		$rows = [];
		foreach ($tickets AS $ticket)
		{
			$well = ($ticket['ticket_standing']) ? "<div class='well'>".nl2br($ticket['ticket_standing'])."</div>" : null;
			$color = ($ticket['ticket_status'] == 'Waiting for Admin') ? "blue" : null;	
			$popover = base::popover($ticket['ticket_title'], $this->chop($ticket['ticket_body'], 150). $well, 'right');
			$row = [
						$ticket['id'],
						$this->getCompanyByID($ticket['company_id']),
						"<a $popover href='/ticket/$ticket[id]/'>$ticket[ticket_title]</a>",
						$ticket['ticket_status'],
						$this->fbTime($ticket['ticket_lastupdated']),
						($ticket['ticket_assigned']) ? "<a class='get' href='/selfassign/$ticket[id]/'> ". $this->getUserByID($ticket['ticket_assigned']) . "</a>" : "<a class='get' href='/selfassign/$ticket[id]/'>Unassigned</a>",
						$this->getQuotedBilled($ticket),$color
			];
			if (!$this->canSeeBilling())
				array_pop($row);
			if (!$color) 
				array_pop($row);
			$rows[] = $row;
		}
		$table = table::init()->headers($headers)->rows($rows)->render();
		return $table;
	}

	private function createTicketForm()
	{
		$fields = [];
		$queues = $this->query("SELECT * from queues");
		$opts = [];
		foreach ($queues AS $queue)
			$opts[] = ['val' => $queue['id'], 'text' => $queue['queue_name']];
		
		$custdata = [];
		$companies = $this->query("SELECT * from companies ORDER by company_name ASC");
		foreach ($companies AS $company)
			$custdata[] = ['val' => $company['id'], 'text' => $company['company_name']];
		$span = [];
		$fields[] = ['type' => 'select', 'var' => 'queue_id', 'opts' => $opts, 'text' => 'Queue:'];
		if ($this->isProvidingCompany())
			$fields[] = ['type' => 'select', 'var' => 'company_id', 'opts' => $custdata, 'text' => 'Client:'];		
		$fields[] = ['type' => 'input', 'var' => 'ticket_title', 'text' => 'Ticket Subject:'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$fields = [];
		$fields[] = ['type' => 'textarea', 'rows' => 7, 'var' => 'ticket_body', 'span' => 12, 'text' => 'Body:'];
		$span[] = ['span' => 6, 'elements' => $fields];
		
		$pre = "<p>You are about to open a new ticket. Once a ticket is opened, e-mails will be sent to all appropriate parties as well as notifications inside aTikit.</p>";
		$form = form::init()->spanElements($span)->id('newTicketForm')->post('/')->render();
		return $pre.$form;
	}
	
	public function createNewTicket($content)
	{
		if (!$content['ticket_title'] || !$content['ticket_body'])
			$this->failJson('Unable to Create', 'You must have a subject and a ticket body.');
		if (!$this->isProvidingCompany())
			$content['company_id'] = $this->company->id;
		$this->createTicket($content);
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = '/';
		$this->jsone('success', $json);
	}
	
	private function providerMain()
	{
		// Show tabs for all queues the agent has access to based on access level.
		$activeQueue = $_SESSION['activeQueue'];
		$myQueues = $this->getMyQueues();
		$tabs = [];
		$createTicket = button::init()->isModalLauncher()->url('#newTicket')->text('Create New Ticket')->addStyle('btn-inverse')->icon('tasks')->render();
		foreach ($myQueues AS $queue)
		{
			$tabs[] = ['id' => "q".$queue['id'], 'class' => ($activeQueue == $queue['id']) ? 'active' : null,
						'title' => "<i class='icon-{$queue['queue_icon']}'></i>" . $queue['queue_name'], 
						'content' => $this->getProviderQueueTickets($queue)]; 
		}
		if (!$activeQueue) $tabs[0]['class'] = 'active';
		$widget = widget::init()->span(12)->header('Ticket List')->isTabs($tabs)->rightHeader($createTicket)->render();
		$data = base::row($widget);
		$save = button::init()->formid('newTicketForm')->addStyle('mpost')->postVar('createTicket')->text('Create Ticket')->addStyle('btn-success')->icon('ok')->render();
		$this->exportModal(modal::init()->id('newTicket')->header('Create New Ticket')->content($this->createTicketForm())->footer($save)->render());
		return $data;
	}	
	
	private function customerMain()
	{
		// Show tabs for all queues the agent has access to based on access level.
		$headers = ['#', 'Subject', 'Status', 'Last Updated', 'Assigned', 'Queue'];
		$tickets = $this->query("SELECT * from tickets WHERE company_id='{$this->company->id}' and ticket_isclosed = false");
		$rows = [];
		foreach ($tickets AS $ticket)
		{
			$rows[] = [
			$ticket['id'],
			"<a href='/ticket/$ticket[id]/'>$ticket[ticket_title]</a>",
			$ticket['ticket_status'],
			$this->fbTime($ticket['ticket_lastupdated']),
			($ticket['ticket_assigned']) ? $this->getUserByID($ticket['ticket_assigned']) : "Awaiting Assignment",
			$this->returnFieldFromTable("queue_name", "queues", "id='$ticket[queue_id]'")];
		}
		$table = table::init()->headers($headers)->rows($rows)->render();
		$createTicket = button::init()->isModalLauncher()->url('#newTicket')->text('Create New Ticket')->addStyle('btn-inverse')->icon('tasks')->render();
		$widget = widget::init()->span(12)->header('Ticket List')->content($table)->isTable(true)->rightHeader($createTicket)->render();
		$data = base::row($widget);
		$save = button::init()->formid('newTicketForm')->addStyle('mpost')->postVar('createTicket')->text('Create Ticket')->addStyle('btn-success')->icon('ok')->render();
		$this->exportModal(modal::init()->id('newTicket')->header('Create New Ticket')->content($this->createTicketForm())->footer($save)->render());
		return $data;
	}
	
	public function selfAssign($content)
	{
		$id = $content['selfAssign'];
		$ticket = $this->query("SELECT * from tickets WHERE id='$id'")[0];
		if (!$this->isMyQueue($ticket['queue_id']))
			$this->failJson('Unable to Assign', 'You do not have access to this queue');
		$this->query("UPDATE tickets SET ticket_assigned = '{$this->user->id}' WHERE id='$id'");
		$json = [];
		$json['gtitle'] = 'Ticket Assigned';
		$json['gbody'] = 'You have assigned this ticket to yourself.';
		$json['action'] = 'reassignsource';
		$json['elementval'] = $this->user->user_name;
		$this->jsone('success', $json);
	}
	
	public function saveProfile($content)
	{
		$password = ($content['user_password']) ? md5($content['user_password']) : null;
		$cansms = ($content['user_cansms'] == 'Y') ? 'true' : 'false';
		$this->query("UPDATE users SET user_cansms = $cansms, user_sms='$content[user_sms]' WHERE id='{$this->user->id}'");
		if ($password)
			$this->query("UPDATE users SET user_password = '$password' WHERE id='{$this->user->id}'");
		$json = [];
		$json['gtitle'] = "Settings Updated";
		$json['gbody'] = "Your settings have been saved.";
		$json['action'] = 'fade';
		$this->jsone('success', $json);
	}
	
}

$mod = new index();
if (isset($_POST['createTicket']))
	$mod->createNewTicket($_POST);
else if (isset($_GET['selfAssign']))
	$mod->selfAssign($_GET);
else if (isset($_POST['saveProfile']))
	$mod->saveProfile($_POST);
else
	$mod->main();

