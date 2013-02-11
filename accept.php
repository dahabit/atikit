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
 * @class login
 */
require_once("classes/core.inc.php");

class accept extends core
{
	public function __construct()
	{
		parent::__construct(true, false);
	}
	
	public function processAccept($content)
	{
		$sow = $this->query("SELECT * from sows WHERE sow_hash='$content[hash]'")[0];
		if (!$sow)
			$this->reloadTarget();
		$ticket = $this->query("SELECT id,company_id FROM tickets WHERE id='$sow[ticket_id]'")[0];
		$uid = $this->returnFieldFromTable("id", "users", "company_id='$ticket[company_id]'");
		$now = time();
		$this->query("UPDATE sows SET sow_accepted = true, sow_acceptuid='$uid', sow_acceptts='$now'");
		$this->export(base::subHeader("Statement Accepted", "Your statement has been accepted."));
		$this->export(base::pageHeader("Statement Accepted", "The statement has been accepted and is ready for work to begin."));
		$this->notifyProvider("Statement Accepted", $this->getUserByID($uid). " accepted the Statement of Work for Ticket #{$ticket['id']} and is ready for work to be completed.", "/ticket/$ticket[id]/");
		$this->mailProvider("Statement of Work Accepted", $this->getUserByID($uid). " accepted the Statement of Work for Ticket #{$ticket['id']} and is ready for work to be completed.", $ticket['queue_id']);
	}
} //accept

$mod = new accept();
if (isset($_GET['hash']))
	$mod->processAccept($_GET);