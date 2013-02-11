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
 * @class poll
 */
require_once("classes/core.inc.php");

class poll extends core
{
	public function __construct()
	{
		parent::__construct(true, true);

	}
	private function parseFrom($from)
	{
		if (preg_match("/</", $from ))
		{
			$x = end(explode("<", $from));
			// Gives us email.com>
			$x = reset(explode(">", $x));
			// gives us email.com
			return $x;
		}
		else
			return $from;
	}
	private function getTicketNumber($subject)
	{
		// Ticket (#11) (or RE: Ticket # etc..)
		if (!preg_match("/\[\#/", $subject))
			return false;
		// Lets just do a normal explode for (# and ) for not wanting to deal with preg
		$x = end(explode("[#", $subject));
		// Gives us 12) blah blah
		$x = reset(explode("]", $x));
		// Should be 12.
		print("Ticket Found was $x");
		return $x;
	}

	private function update($id, $uid, $body)
	{
		$ticket = $this->query("SELECT * from tickets WHERE id='$id'")[0];
		
		$now = time();
		$cid = $this->returnFieldFromTable("company_id", "users", "id='$uid'");
		if ($ticket['company_id'] != $cid)
			return null; // no access

		$this->updateTicket(['ticket_id' => $id,
							 'internal' => false,
							 'ticket_body' => $body,
							 'ticket_status' => "Waiting for Admin",
							 'cid' => $cid,
							 'uid' => $uid]);
	}


	public function processEmail($from, $subject, $body, $queue)
	{
		// Since we are using gmail we'll hope their spam filters help us.
		// See if we can map a cid with the from From: "Chris Horne <chorne@core3networks.com>"
		// Parse Subject - See if there is a "Ticket (#11)" as the start of the message to append a note to.
		// If not, start a new ticket. If CID not found, then pop up a modal to assign.
		// Step 1. Parse out the email address if there is a < in it.
		$from = $this->parseFrom(trim($from));
		if (!$from) return null;
		$uid = $this->getUIDByEmail(trim($from));
		$cid = $this->returnFieldFromTable("company_id", "users", "id='$uid'");
		if (!$cid) return null; // Don't open a ticket for people who aren't users..
		$id = $this->getTicketNumber($subject);
		if (!$id)
			$this->createTicket(['queue_id' => $queue,
					'ticket_title' => $subject,
					'ticket_body' => $body,
					'company_id' => $cid,
					'ticket_body' => $body
					]);
		else
			$this->update($id, $uid, $body);
	}

	private function decode_imap_text($str)
	{
		$result = null;
		$decode_header = imap_mime_header_decode($str);
		foreach ($decode_header AS $obj)
			$result .= htmlspecialchars(rtrim($obj->text, "\t"));
	
		return $result;
	}
	
	public function emailParseBody($content)
	{
		$emailParser = new PlancakeEmailParser($content);
		// You can use some predefined methods to retrieve headers...
		$emailTo = $emailParser->getTo();
		$emailSubject = $emailParser->getSubject();
		$emailCc = $emailParser->getCc();
		// ... or you can use the 'general purpose' method getHeader()
		$emailDeliveredToHeader = $emailParser->getHeader('Delivered-To');
		$emailBody = $emailParser->getPlainBody();
		return $emailBody;
	}


	public function main()
	{
		$this->log("Beginning Polling Cycle..", "poll");
		$mailboxes = [];
		$queues = $this->query("SELECT * from queues");
		foreach ($queues AS $queue)
			$mailboxes[] = [		
					'label'     => $queue['queue_name'],
					'mailbox'   => "{".$queue['queue_host'].":993/imap/ssl}INBOX",
					'username'  => $queue['queue_email'],
					'password'  => $queue['queue_password'],
					'queue' => $queue['id']
					];
	foreach ($mailboxes as $current_mailbox)
	{
	// Open an IMAP stream to our mailbox
		$this->log("Contacting: $current_mailbox[label]", "poll");
		$stream = @imap_open($current_mailbox['mailbox'], $current_mailbox['username'], $current_mailbox['password']);
		if (!$stream)
		{
			$this->query("UPDATE queues SET queue_lastmessage='".imap_last_error()."' WHERE id='$current_mailbox[queue]'");
			$this->log("Failed to Connect to: $current_mailbox[label] ($current_mailbox[username])", 'poll');
		}
		$emails = imap_search($stream, 'ALL');
		// Instead of searching for this week's messages, you could search
		// for all the messages in your inbox using: $emails = imap_search($stream, 'ALL');
		$now = time();
		$when = $this->fbTime($now);
		$this->query("UPDATE queues SET queue_lastmessage='0 new messages as of $when' WHERE id='$current_mailbox[queue]'");
		
		// If we've got some email IDs, sort them from new to old and show them
		if ($emails)
			rsort($emails);
		if (count($emails) > 0)
			$this->query("UPDATE queues SET queue_lastmessage='".count($emails). " messages processed.' WHERE id='$current_mailbox[queue]'");
		foreach($emails as $email_id)
		{
			// Fetch the email's overview and show subject, from and date.
			$overview = imap_fetch_overview($stream,$email_id,0);
			$subject = $overview[0]->subject;
			$from = $overview[0]->from;
			$date = $overview[0]->date;
			$body = imap_fetchbody($stream, $email_id, '0');
			$body .= imap_fetchbody($stream, $email_id, '1');
			$body = $this->emailParseBody($body);
			$body = strip_tags($body, "<p><br>");
			$body = utf8_decode($body);
			$newbody = null;
			foreach (explode("\n", $body) AS $line)
			{
				if (!preg_match("/\>/i", $line))
					$newbody .= $line . "\n";
			}
			$body = $newbody;
			//print_r(imap_fetchstructure($stream, $email_id));
			$this->processEmail($from, $subject, $body, $current_mailbox['queue']);
			imap_delete($stream, $email_id);
		}
		// Close our imap stream.
		imap_expunge($stream);
		imap_close($stream);
		}
	}
} //poll
$mod = new poll();
$mod->main();