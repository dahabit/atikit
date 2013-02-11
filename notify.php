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
 * @class notify
 */
require_once("classes/core.inc.php");

class notify extends core
{
	public function main()
	{
		$target = 86400 * 6;
		// Checkin
		$lastTS = $this->company->company_lastts-10;
		if (!$this->user) return null;		
		// First thing do some maint. 
		$now = time();
		$target = $now - $target;  // So now - 5 days.. anything that's lower than that kill it.
		$this->query("DELETE from notifications WHERE notification_ts < $target AND notification_active = TRUE");
		$notifications = $this->query("SELECT * from notifications WHERE user_id='{$this->user->id}' ORDER by notification_ts DESC LIMIT 6");
		$count = $this->returnCountFromTable("notifications", "user_id='{$this->user->id}' AND notification_viewed=FALSE");
		$data = null;
		$gTitle = $gBody = null;
		if ($notifications)
		foreach ($notifications AS $notification)
		{
			$when = $this->fbTime($notification['notification_ts']);
			if (!$notification['notification_popped'])
			{
				
				$gTitle = $notification['notification_title'];
				$gBody = $notification['notification_body'];
				$this->query("UPDATE notifications SET notification_popped=TRUE WHERE id='$notification[id]'");
			}
			
			$avatar = $this->getProfilePic($notification['notification_from']);
			$body = strip_tags($this->chop($notification['notification_body'], 150));
			$data .= "<li class='clearfix'><a href='$notification[notification_url]'><span class='dropdown-thumb'><img align='left' src='/$avatar' width='60' height='60' alt='Avatar' style='padding-right:5px;'></span>
						<span><p><b>$notification[notification_title]</b><br/>$body</p></span>
						<span class='notification-meta'>
						".$this->fbTime($notification['notification_ts'])."</span></a></li>";
		}
		if (!$notifications)
			$data = "<li><span class='title'>No notifications found</span></li>";
		$this->ajax = true;
		if ($count > 0)
			$count = "<font color='lime'><b>$count</b></font>";
		$content = ['count' => $count, 'content' => $data, 'gtitle' => $gTitle, 'gbody' => $gBody ]; 
		$this->jsonE('success', $content);
	}
	
	public function clear()
	{
		$this->query("UPDATE notifications SET notification_viewed = TRUE where user_id='{$this->user->id}'");
		$suc = ['result' => 'Notifications Cleared'];
		$this->jsonE('success', $suc);
	}
} //notify


$mod = new notify();
if (!isset($mod->user))
	die();
if (isset($_GET['getCount']))
        $mod->getCount();
else if (isset($_GET['clear']))
        $mod->clear();
else
	$mod->main();