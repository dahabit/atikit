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
 * @class admin
 */
require_once("classes/core.inc.php");
class admin extends core
{
	public function __construct()
	{
		parent::__construct();
		if (!$this->isAdmin()) die();
		$data = base::subHeader("aTikit Admin", "Modify Users, Clients, Queues and Access Levels");
		$data .= base::begin(true);
		$data .= base::crumbs([['url' => '#', 'text' => 'Admin']]);
		$this->export($data);
	}
	
	private function adminNav($active)
	{
		$opt[$active] = "class='active'";
		// redo this when nav lists are done through a helper - and not this crap
		$data = "<div class='bs-docs-example'>
						<div class='well'>
							<ul class='nav nav-list'>
								<li class='nav-header'>Administrative Options</li>
								<li $opt[billing]><a href='/admin/billing/'><i class='icon-money icon-white'></i> Billing</a></li>
								<li $opt[stripe]><a href='/admin/stripe/'><i class='icon-credit-card icon-white'></i> Stripe Configuration</a></li>
								<li $opt[levels]><a href='/admin/levels/'><i class='icon-lock'></i> Access Levels</a></li>				
								<li $opt[users]><a href='/admin/users/'><i class='icon-user'></i> aTikit Users</a></li>
								<li $opt[queues]><a href='/admin/queues/'><i class='icon-upload-alt'></i> Queues</a></li>
								<li $opt[settings]><a href='/admin/settings/'><i class='icon-wrench'></i> General Settings</a></li>
								<li $opt[partnerships]><a href='/admin/partnerships/'><i class='icon-user-md'></i> Partnerships</a></li>
							</ul>
						</div>
						<!-- /well -->
					</div>";
		return $data;
	}
	
	public function main()
	{
		$this->showTransactionLog();
	}	
	
	// --------------------------------------------------------------------------------------------------------
	// 			Access Levels
	// --------------------------------------------------------------------------------------------------------
	
	private function getNumberAssignedToLevel($lid)
	{
		return $this->returnCountFromTable("users", "level_id='$lid'");
	}
	
	private function addLevelForm()
	{
		$pre = "<p>You are about to add an access level. Access levels are used to limit or extend access to various parts of the system. Be careful when choosing options and assigning 
				access levels. If your account is an administrator this will not effect you.</p>
				<p>Once you have created an access level you will be able to assign users and queues to those levels.</p>";
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Access Level Name:', 'var' => 'level_name'];
		$opts = [];
		$opts[] = ['val' => 'Y', 'text' => 'Enable Billing Management'];
		$fields[] = ['type' => 'checkbox', 'var' => 'level_isbilling', 'opts' => $opts];
		$form = form::init()->id('createLevel')->post('/admin/')->elements($fields)->legend('Access Paremeters')->render();
		return $pre.$form;
		
	}

	private function levelHasBilling($level)
	{
		if ($level['level_isbilling']) return "Yes";
		else return "No";
		
	}
	public function levels()
	{
		$nav = base::span(4, $this->adminNav('levels'));
		$levels = $this->query("SELECT * from levels");
		$headers = ['Level Name', 'Users Assigned', 'Billing Enabled', 'Delete'];
		$rows = [];
		foreach ($levels AS $level)
			$rows[] = ["<a class='mjax' data-target='#editLevel' href='/admin/level/$level[id]/'>$level[level_name]</a>", 
			$this->getNumberAssignedToLevel($level['id']),
			$this->levelHasBilling($level), 
					"<a class='get' href='/admin/level/delete/$level[id]/'><i class='icon-remove'></i></a>"];
			
		$table = table::init()->headers($headers)->rows($rows)->render();
		$addButton = button::init()->addStyle('btn-primary')->isModalLauncher()->url('#addLevel')->text('Add Access Level')->render();		
		$widget = widget::init()->span(8)->header("Access Levels")->content($table)->isTable()->icon('key')->rightHeader($addButton)->render();
		$this->export(base::row($nav.$widget, true));
		$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Create')->postVar('createLevel')->message('Creating Level..')->render();
		$addModal = modal::init()->id('addLevel')->header('Add Access Level')->content($this->addLevelForm())->footer($saveButton)->render();
		$editModal = modal::init()->onlyConstruct()->id('editLevel')->render();
		$this->exportModal($addModal.$editModal);
	}
	
	public function createLevel($content)
	{
		if (!$content['level_name'])
			$this->failJson("Unable to Add", "A Level name must be given.");
		$exists = $this->returnCountFromTable("levels", "level_name='$content[level_name]'");
		if ($exists > 0)
			$this->failJson("Unable to Add", "This access level already exists.");
		$billing = ($content['level_isbilling']) ? 'true' : 'false';
		$this->query("INSERT into levels SET level_name='$content[level_name]', level_isbilling = $billing");
		$json = [];
		$json['gtitle'] = 'Level Added';
		$json['gbody'] = "$content[level_name] has been created.";
		$json['action'] = 'reload';
		$json['url'] = '/admin/levels/';
		$this->jsonE('success', $json);
	}

	public function editLevelForm($content)
	{
		$id = $content['editLevel'];
		$level = $this->query("SELECT * from levels where id='$id'")[0];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Access Level Name:', 'var' => 'level_name', 'val' => $level['level_name']];
		$opts = [];
		$checked = ($level['level_isbilling']) ? true : false;
		
		$opts[] = ['val' => 'Y', 'text' => 'Enable Billing Management', 'checked' => $checked];
		$fields[] = ['type' => 'checkbox', 'var' => 'level_isbilling', 'opts' => $opts];
		$pre = "<p>You will only be able to change the name of the access level here. If you wish to delete the level you must remove all users from the access level first.</p>";
		$form = form::init()->id('editForm')->post('/admin/')->elements($fields)->legend("Edit $level[level_name]")->render();
		$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Edit Level')->postVar('editLevel')->id($id)->message('Editing Level..')->formid('editForm')->render();
		$this->ajax = true;
		print modal::init()->isInline()->header("Edit $level[level_name]")->content($pre.$form)->footer($saveButton)->render();
	}
	
	public function editLevel($content)
	{
		if (!$content['level_name'])
			$this->failJson("Unable to Change", "An access level must have a name.");
		$billing = ($content['level_isbilling']) ? 'true' : 'false';
		$this->query("UPDATE levels SET level_name='$content[level_name]', level_isbilling = $billing WHERE id='$content[editLevel]'");
		$json = [];
		$json['gtitle'] = 'Level edited';
		$json['gbody'] = "$content[level_name] has been edited.";
		$json['action'] = 'reload';
		$json['url'] = '/admin/levels/';
		$this->jsonE('success', $json);
	}
	
	public function deleteLevel($content)
	{
		$id = $content['deleteLevel'];
		$count = $this->getNumberAssignedToLevel($id);
		if ($count > 0)
			$this->failJson('Unable to Remove', "All users must be removed from the access level before you can delete.");
		$this->query("DELETE from levels WHERE id='$id'");
		$json = [];
		$json['gtitle'] = 'Level edited';
		$json['gbody'] = "Access Level has been deleted.";
		$json['action'] = 'reload';
		$json['url'] = '/admin/levels/';
		$this->jsonE('success', $json);
		
	}
	
	// --------------------------------------------------------------------------------------------------------
	// 			Users
	// --------------------------------------------------------------------------------------------------------
	private function getAccessLevelsForSelect($user = null)
	{
		$opts = [];
		if ($user['level_id'])
		{
			$opts[] = ['val' => $user['level_id'], 'text' => $this->getLevelById($user['level_id'])];
			$opts[] = ['val' => $user['level_id'], 'text' => '---------------'];
				
			
		}
		$levels = $this->query("SELECT * from levels order by level_name ASC");
		foreach ($levels as $level)
			$opts[] = ['val' => $level['id'], 'text' => $level['level_name']];
		return $opts;
	}

	private function addUserForm()
	{
		$pre = "<p>You are about to add a user to aTikit. This is not a client record, this is an employee of your company that does business with
		other clients.</p>";
		$span = [];
		$fields = [];
		$fields[] = ['type' => 'input', 'text' => 'Full Name:', 'var' => 'user_name'];
		$fields[] = ['type' => 'input', 'text' => 'E-mail Address:', 'var' => 'user_email'];
		$fields[] = ['type' => 'input', 'text' => 'Title:', 'var' => 'user_title'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$fields = [];
		$fields[] = ['type' => 'password', 'text' => 'Password:', 'var' => 'user_password'];
		$opts = $this->getAccessLevelsForSelect();
		$fields[] = ['type' => 'select', 'opts' => $opts, 'text' => 'Access Level:', 'var' => 'level_id'];
		$span[] = ['span' => 6, 'elements' => $fields];
		$form = form::init()->id('createUser')->post('/admin/')->spanelements($span)->legend('User Paremeters')->render();
		return $pre.$form;
	
	}
	
	
	public function users()
	{
		$nav = base::span(4, $this->adminNav('users'));
		$pcid = $this->returnFieldFromTable("id", "companies", "company_isprovider = true");
		$users = $this->query("SELECT * from users where company_id='$pcid'");
		$headers = ['Name', 'Title', 'E-mail', 'Access Level', 'Delete'];
		$rows = [];
		foreach ($users AS $user)
			$rows[] = ["<a class='mjax' data-target='#editUser' href='/admin/user/$user[id]/'>$user[user_name]</a>",
			$user['user_title'],
			$user['user_email'],
			$this->getLevelById($user['level_id']),
			"<a class='get' href='/admin/user/delete/$user[id]/'><i class='icon-remove'></i></a>"];
				
			$table = table::init()->headers($headers)->rows($rows)->render();
			$addButton = button::init()->addStyle('btn-primary')->isModalLauncher()->url('#addUser')->text('Add User')->render();
					$widget = widget::init()->span(8)->header("Provider Users")->content($table)->isTable()->icon('key')->rightHeader($addButton)->render();
							$this->export(base::row($nav.$widget, true));
							$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Create')->postVar('createUser')->message('Creating User..')->render();
							$addModal = modal::init()->id('addUser')->header('Add User')->content($this->addUserForm())->footer($saveButton)->render();
							$editModal = modal::init()->onlyConstruct()->id('editUser')->render();
			$this->exportModal($addModal.$editModal);
		}

		
		public function createUser($content)
		{
			if (!$content['user_name'] || !$content['user_password'] || !$content['user_title'])
				$this->failJson("Unable to Add", "All fields must be entered to complete.");
			$exists = $this->returnCountFromTable("users", "user_email='$content[user_email]'");
			if ($exists > 0)
				$this->failJson("Unable to Add", "This e-mail address already exists.");
			$password = md5($content['user_password']);
			$pcid = $this->returnFieldFromTable("id", "companies", "company_isprovider = true");
			$this->query("INSERT into users SET company_id='$pcid', user_name='$content[user_name]', user_password='$password', user_title='$content[user_title]',
					level_id='$content[level_id]', user_email='$content[user_email]'");
			$json = [];
			$json['gtitle'] = 'User Added';
			$json['gbody'] = "$content[user_name] has been created.";
			$json['action'] = 'reload';
			$json['url'] = '/admin/users/';
			$this->jsonE('success', $json);
		}
		
		public function editUserForm($content)
		{
			$user = $this->query("SELECT * from users WHERE id='$content[editUser]'")[0];
			$span = [];
			$fields = [];
			$fields[] = ['type' => 'input', 'text' => 'Full Name:', 'var' => 'user_name', 'val' => $user['user_name']];
			$fields[] = ['type' => 'input', 'text' => 'E-mail Address:', 'var' => 'user_email', 'val' => $user['user_email']];
			$fields[] = ['type' => 'input', 'text' => 'Title:', 'var' => 'user_title', 'val' => $user['user_title']];
			$span[] = ['span' => 6, 'elements' => $fields];
			$fields = [];
			$fields[] = ['type' => 'password', 'text' => 'Password:', 'var' => 'user_password'];
			$opts = $this->getAccessLevelsForSelect($user);
			$fields[] = ['type' => 'select', 'opts' => $opts, 'text' => 'Access Level:', 'var' => 'level_id'];
			$span[] = ['span' => 6, 'elements' => $fields];
			$form = form::init()->id('editUserForm')->post('/admin/')->spanelements($span)->legend('User Paremeters')->render();
			
			$this->ajax = true;
			$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Edit User')->postVar('editUser')->id($user['id'])->message('Editing User..')->formid('editUserForm')->render();
			print modal::init()->isInline()->header("Edit $user[user_name]")->content($form)->footer($saveButton)->render();
		}
		
		public function editUser($content)
		{
			if (!$content['user_name'] || !$content['user_title'] || !$content['user_email'])
				$this->failJson("Unable to Change", "A user must have a name, email and title.");
			
			$this->query("UPDATE users SET user_name='$content[user_name]', user_email='$content[user_email]', user_title='$content[user_title]', 
					level_id='$content[level_id]' WHERE id='$content[editUser]'");
			if ($content['user_password'])
			{
				$password = md5($content['user_password']);
				$this->query("UPDATE users SET user_password='$password' WHERE id='$content[editUser]");
			}
			$json = [];
			$json['gtitle'] = 'User Updated';
			$json['gbody'] = "$content[user_name] has been updated.";
			$json['action'] = 'reload';
			$json['url'] = '/admin/users/';
			$this->jsonE('success', $json);
		}
		
		public function deleteUser($content)
		{
			$id = $content['deleteUser'];
			$this->query("DELETE from users WHERE id='$id'");
			$json = [];
			$json['gtitle'] = 'User Deleted';
			$json['gbody'] = "User has been deleted.";
			$json['action'] = 'reload';
			$json['url'] = '/admin/users/';
			$this->jsonE('success', $json);
		
		}

		// --------------------------------------------------------------------------------------------------------
		// 			Queues
		// --------------------------------------------------------------------------------------------------------
		private function getLevelsForQueue(&$queue)
		{
			
			$levels = $this->query("SELECT * from levels WHERE id in ($queue[queue_levels])");
			$ldata = [];
			foreach ($levels AS $level)
				 array_push($ldata, $this->getLevelById($level['id']));
			return implode(", ", $ldata);
		}

		private function addQueueForm()
		{
			$pre = "<p>You are about to add a new queue. Queues must be IMAP boxes accessible with SSL/TLS. Select the access levels you wish to have access
					to this queue.</p>";
			$span = [];
			$fields = [];
			$fields[] = ['type' => 'input', 'text' => 'Queue Name:', 'var' => 'queue_name'];
			
			$fields[] = ['type' => 'input', 'text' => 'E-mail Address:', 'var' => 'queue_email', 'bottom' => 'ex. myqueue@mydomain.com'];
			$fields[] = ['type' => 'input', 'text' => 'Hostname:', 'var' => 'queue_host', 'bottom' => 'ex. mail.mydomain.com'];
			$fields[] = ['type' => 'input', 'text' => 'Password:', 'var' => 'queue_password'];
			$fields[] = ['type' => 'input', 'text' => 'Icon:', 'var' => 'queue_icon'];
			$opts = [];
			$opts[] = ['val' => 'Y', 'text' => 'Only allow customer emails to create tickets vs. allowing all email addresses', 'checked' => true];
			$fields[] = ['type' => 'checkbox', 'var' => 'queue_islocked', 'opts' => $opts];
			$span[] = ['span' => 6, 'elements' => $fields];
			$fields = [];
			
			// Show all levels as a checkbox. 
			$levels = $this->query("SELECT * from levels ORDER by level_name ASC");
			foreach ($levels AS $level)
			{
				$opts = [];
				$opts[] = ['val' => $level['id'], 'text' => $level['level_name']];
				$fields[] = ['type' => 'checkbox', 'var' => 'lev_' . $level['id'], 'opts' => $opts];
			}
			$span[] = ['span' => 6, 'elements' => $fields];			
			$form = form::init()->id('addQueueForm')->spanElements($span)->post('/admin/')->render();
			return $pre.$form;
		}
		
		public function queues()
		{
			$nav = base::span(4, $this->adminNav('queues'));
			$queues = $this->query("SELECT * from queues");
			$headers = ['Queue', 'E-mail', 'Access Lists', 'Last Message', 'Delete'];
			$rows = [];
			foreach ($queues AS $queue)
				$rows[] = ["<a class='mjax' data-target='#editQueue' href='/admin/queue/$queue[id]/'>$queue[queue_name]</a>",
				$queue['queue_email'],
				$this->getLevelsForQueue($queue),
				$queue['queue_lastmessage'],
				"<a class='get' href='/admin/queue/delete/$queue[id]/'><i class='icon-remove'></i></a>"];
		
			$table = table::init()->headers($headers)->rows($rows)->render();
			$addButton = button::init()->addStyle('btn-primary')->isModalLauncher()->url('#addQueue')->text('Add Queue')->render();
			$widget = widget::init()->span(8)->header("Queues in Use")->content($table)->isTable()->icon('key')->rightHeader($addButton)->render();
			$this->export(base::row($nav.$widget, true));
			$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Create')->postVar('createQueue')->message('Creating Queue..')->render();
			$addModal = modal::init()->id('addQueue')->header('Add Queue')->content($this->addQueueForm())->footer($saveButton)->render();
			$editModal = modal::init()->onlyConstruct()->id('editQueue')->render();
			$this->exportModal($addModal.$editModal);
		}
		
		public function createQueue($content)
		{
			if (!$content['queue_name'] || !$content['queue_email'] || !$content['queue_password'])
				$this->failJson('Unable to create', 'You must fill in all fields and select access levels.');
			
			$ilevels = [];
			foreach ($content AS $id => $val)
				if (preg_match('/lev_/i', $id))
					array_push($ilevels, $val);
			
			if (count($ilevels) == 0)
				$this->failJson('Unable to Create', 'You must select at least one access level.');
			
			$ilevels = implode(",", $ilevels);
			$this->query("INSERT into queues SET queue_name='$content[queue_name]', queue_host='$content[queue_host]', queue_email='$content[queue_email]',
					queue_password='$content[queue_password]', queue_levels='$ilevels', queue_icon='$content[queue_icon]'");
			$json = [];
			$json['action'] = 'reload';
			$json['url'] = '/admin/queues/';
			$this->jsone('success', $json);
		}
		
		public function editQueueForm($content)
		{
			$queue = $this->query("SELECT * from queues WHERE id='$content[editQueue]'")[0];
			$span = [];
			$fields = [];
			$fields[] = ['type' => 'input', 'text' => 'Queue Name:', 'var' => 'queue_name', 'val' => $queue['queue_name']];
			$fields[] = ['type' => 'input', 'text' => 'E-mail Address:', 'var' => 'queue_email', 'val' => $queue['queue_email'], 'bottom' => 'ex. myqueue@mydomain.com'];
			$fields[] = ['type' => 'input', 'text' => 'Hostname:', 'var' => 'queue_host', 'val' => $queue['queue_host'], 'bottom' => 'ex. mail.mydomain.com'];
			$fields[] = ['type' => 'input', 'text' => 'Password:', 'var' => 'queue_password', 'val' => $queue['queue_password']];
			$fields[] = ['type' => 'input', 'text' => 'Icon:', 'var' => 'queue_icon', 'val' => $queue['queue_icon']];
			$span[] = ['span' => 6, 'elements' => $fields];
			$fields = [];
			$alevels = explode(",", $queue['queue_levels']);
			// Show all levels as a checkbox. 
			$levels = $this->query("SELECT * from levels ORDER by level_name ASC");
			foreach ($levels AS $level)
			{
				$opts = [];
				$checked = (in_array($level['id'], $alevels)) ? true : false;
				$opts[] = ['val' => $level['id'], 'text' => $level['level_name'], 'checked' => $checked];
				$fields[] = ['type' => 'checkbox', 'var' => 'lev_' . $level['id'], 'opts' => $opts];
			}
			$span[] = ['span' => 6, 'elements' => $fields];			
			$form = form::init()->id('editQueueForm')->post('/admin/')->spanelements($span)->legend('Queue Options')->render();
				
			$this->ajax = true;
			$saveButton = button::init()->addStyle('btn-primary')->addStyle('mpost')->text('Edit Queue')->postVar('editQueue')->id($queue['id'])->message('Editing Queue..')->formid('editQueueForm')->render();
			print modal::init()->isInline()->header("Edit $queue[queue_name]")->content($form)->footer($saveButton)->render();
		}
		
		public function editQueue($content)
		{
			if (!$content['queue_name'] || !$content['queue_email'] || !$content['queue_password'])
				$this->failJson('Unable to save', 'You must fill in all fields and select access levels.');
				
			$ilevels = [];
			foreach ($content AS $id => $val)
				if (preg_match('/lev_/i', $id))
				array_push($ilevels, $val);
				
			if (count($ilevels) == 0)
				$this->failJson('Unable to save', 'You must select at least one access level.');
				
			$ilevels = implode(",", $ilevels);
			$this->query("UPDATE queues SET queue_name='$content[queue_name]', queue_host='$content[queue_host]', queue_email='$content[queue_email]',
					queue_password='$content[queue_password]', queue_levels='$ilevels', queue_icon='$content[queue_icon]' WHERE id='$content[editQueue]'");
			$json = [];
			$json['action'] = 'reload';
			$json['url'] = '/admin/queues/';
			$this->jsone('success', $json);
			
		}
		public function deleteQueue($content)
		{
			$id = $content['deleteQueue'];
			$this->query("DELETE from queues WHERE id='$id'");
			$json = [];
			$json['gtitle'] = 'User Deleted';
			$json['gbody'] = "User has been deleted.";
			$json['action'] = 'reload';
			$json['url'] = '/admin/queues/';
			$this->jsonE('success', $json);
		
		}
		
		// --------------------------------------------------------------------------------------------------------
		// 			Settings (Keys, Etc)
		// --------------------------------------------------------------------------------------------------------
		public function settings()
		{
			$nav = base::span(4, $this->adminNav('settings'));
			$tabs = [];
			$categories = $this->query("SELECT DISTINCT setting_cat FROM settings");
			$x = 0;
			foreach ($categories AS $category)
			{
				$x++;
				$settings = $this->query("SELECT * from settings WHERE setting_cat='$category[setting_cat]'");
				$fields = [];
				foreach ($settings AS $setting)
		    		$fields[] = ['type' => $setting['setting_type'], 'span' => $setting['setting_span'], 'text' => $setting['setting_desc'], 'comment' => $setting['setting_help'], 'val' => $setting['setting_val'], 'var' => 's_' . $setting['setting_var']];
			
				$form = form::init()->post('/admin/')->elements($fields)->id("form_$x")->render();
				$button = button::init()->withGroup(false)->addStyle('btn-block')->addStyle('btn-inverse')->addStyle('post')->postVar('saveSettings')->text('Save Settings')->icon('ok-sign')->formid("form_$x")->render();
				$class = ($x==1) ? 'active' : null;
				$tabs[] = ['id' => "cat_$x", 'title' => $category['setting_cat'], 'class' => $class, 'content' => $form.$button];	
			}
			$widget = widget::init()->span(8)->icon('edit')->header('aTikit Settings')->isTabs($tabs)->render();
			$this->export(base::row($nav.$widget, true));
		}
		
		public function saveSettings($content)
		{
			foreach ($content AS $key=>$val)
				if (preg_match('/s_/', $key))
				$this->query("UPDATE settings SET setting_val='$val' WHERE setting_var = '".str_replace("s_", null, $key)."'");
			$json = [];
			$json['gtitle'] = "Settings updated";
			$json['gbody'] = "Settings have been saved";
			$json['action'] = 'reassignsource';
			$json['elementval'] = "Settings Saved";
			$this->jsone('success', $json);			
		}
		
		private function getStripePlans()
		{
			// When this routine calls, be sure to go ahead and update our local db for any new plans or weirdness. 
			// Remember plan_id is from stripe.. need to match OUR id for the record count.
			$plans = $this->stripe_getPlans();
			$headers = ['ID', 'Plan Name', 'Price', 'Customers', 'Remove'];
			$rows = [];
			
			foreach ($plans AS $plan)
			{
				$myPlan = $this->query("SELECT id from plans WHERE plan_id='$plan[id]'")[0];
				if (!$myPlan)
					$this->query("INSERT into plans SET plan_id='$plan[id]', plan_name='$plan[name]', plan_amount='".($plan['amount'] / 100)."', plan_interval='$plan[interval_count]', plan_trial='$plan[trial_period_days]'");
				if ($myPlan)
					$count = $this->returnCountFromTable("companies", "company_plan='$myPlan[id]'");
				else $count = 0;
				$rows[] = [
							$plan['id'],
							$plan['name'],
							"$" . number_format($plan['amount'] / 100,2),
							$count,
							"<a class='get' href='/admin/billing/stripe/removeplan/$plan[id]/'><i class='icon-remove'></i></a>"							
				];
			}
			$addPlan = button::init()->text("Create Plan")->addStyle('btn-inverse')->icon('plus')->isModalLauncher()->url('#createPlan')->render();
			$savePlan = button::init()->text("Save Plan")->addStyle('btn-success')->formid('createPlanForm')->icon('ok')->addStyle('mpost')->postVar('createPlan')->id('true')->message('Creating Plan..')->render();
			$this->exportModal(modal::init()->id('createPlan')->header("Create Credit Card Plan")->content($this->createPlanForm())->footer($savePlan)->render());
			$table = table::init()->headers($headers)->rows($rows)->render();
			$widget = widget::init()->icon('calendar')->span(4)->header('Stripe Plans')->isTable()->content($table)->rightHeader($addPlan)->render();
			return $widget;
		}
		
		private function createPlanForm()
		{
			$pre = "<p>When you create a plan with Stripe you are creating an automatic draft of your client's credit card on a timed basis. Note that any coupons you apply to the customer
					will subtract from their invoice.</p>";
			
			$span = [];
			$fields = [];
			$fields[] = ['type' => 'input', 'text' => 'Plan Name:', 'var' => 'plan_name'];
			$fields[] = ['type' => 'input', 'text' => 'Price:', 'prepend' => '$', 'var' => 'plan_amount'];
			$opts = [];
			$opts[] = ['val' => 'month', 'text' => 'Monthly'];
			$opts[] = ['val' => 'year', 'text' => 'Yearly'];
			
			$span[] = ['span' => 6, 'elements' => $fields];
			$fields = [];
			$fields[] = ['type' => 'select', 'var' => 'plan_interval', 'opts' => $opts, 'text' => 'Interval:'];
			$fields[] = ['type' => 'input', 'text' => 'Interval Count:', 'comment' => 'Enter 1 for monthly/yearly, 3 for quarterly (or every 3 years)', 'var' => 'plan_interval_count'];
			$fields[] = ['type' => 'input', 'var' => 'plan_trial', 'text' => 'Trial in Days:', 'val' => '0'];
			$span[] = ['span' => 6, 'elements' => $fields];
			$form = form::init()->spanElements($span)->post('/admin/')->id('createPlanForm')->render();
			return $pre.$form;
		}
		
		private function getStripeCoupons()
		{
			$coupons = $this->stripe_getCoupons();
			
			$headers = ['ID', '%/Amt Off', 'Duration', 'Used/Max', 'Lifespan', 'Remove'];
			$rows = [];
			foreach ($coupons AS $coupon)
			{
				$row = [];
				$row[] = $coupon['id'];
				if ($coupon['percent_off'])
					$row[] = $coupon['percent_off'] . "%";
				else $row[] = "$" . number_format($coupon['amount_off']  / 100,2);
				$row[] = $coupon['duration'];
				$row[] = $coupon['times_redeemed'] . "/" . $coupon['max_redemptions'];
				$row[] = ($coupon['duration_in_months']) ? $coupon['duration_in_months'] . ' Months' : "Once";
				$row[] = "<a class='get' href='/admin/billing/stripe/removecoupon/$coupon[id]/'><i class='icon-remove'></i></a>";
				if ($coupon['times_redeemed'] < $coupon['max_redemptions'])
					$row[] = 'green';
				else $row[] = 'red';
				$rows[] = $row;
			}
			$addCoupon = button::init()->text("Create Coupon")->addStyle('btn-inverse')->icon('plus')->isModalLauncher()->url('#createCoupon')->render();
			$saveCoupon = button::init()->text("Save Coupon")->addStyle('btn-success')->formid('createCouponForm')->icon('ok')->addStyle('mpost')->postVar('createCoupon')->id('true')->message('Creating Coupon..')->render();
			$this->exportModal(modal::init()->id('createCoupon')->header("Create new Coupon")->content($this->createCouponForm())->footer($saveCoupon)->render());
			$table = table::init()->headers($headers)->rows($rows)->render();
			$widget = widget::init()->icon('minus')->span(4)->header('Stripe Coupons')->isTable()->rightHeader($addCoupon)->content($table)->render();
			return $widget;
		}
		
		private function createCouponForm()
		{
			$pre = "<p>Coupons are used for monthly credit card plans. If you have a specfic service that you charge, you can apply that plan and also apply a coupon code. The customer can also
					apply the coupon code in their billing preferences, however it will be a dropdown for admins vs. a text field for customers.</p>";
			
			$span = [];
			$fields = [];
			$fields[] = ['type' => 'input', 'text' => 'Coupon ID', 'comment' => 'Enter and id like HALFOFF or something coupon code-like.', 'var' => 'coupon_id'];
			$fields[] = ['type' => 'input', 'text' => 'Discount:', 'comment' => 'Enter a decimal value or a percentage. i.e 5.00 or 25%', 'var' => 'coupon_discount'];
			$span[] = ['span' => 6, 'elements' => $fields];
			$fields = [];
			$opts = [];
			$opts[] = ['val' => 'forever', 'text' => 'Forever (Every Invoice)'];
			$opts[] = ['val' => 'once', 'text' => 'Once (One Invoice Cycle)'];
			$opts[] = ['val' => 'repeating', 'text' => 'Repeating (Repeats for X Months)'];
			$fields[] = ['type' => 'select', 'text' => 'Lifespan:', 'comment' => 'How long will this coupon be in effect?', 'opts' => $opts, 'var' => 'coupon_duration'];
			$fields[] = ['type' => 'input', 'text' => 'Repeating?', 'comment' => 'If repeating, how many months should it repeat?', 'var' => 'coupon_duration_in_months'];
			$fields[] = ['type' => 'input', 'text' => 'Max Redemptions:', 'comment' => 'How many times can this coupon be claimed?', 'var' => 'coupon_max_redemptions'];
			$span[] = ['span' => 6, 'elements' => $fields];
			$form = form::init()->id('createCouponForm')->spanElements($span)->post('/admin/')->render();
			return $pre.$form;
		}
		
		public function createCoupon($content)
		{
			if (!$content['coupon_id'] || !$content['coupon_discount'] || !$content['coupon_max_redemptions'])
				$this->failjson('Unable to add', "You have not entered all required fields.");
			$couponid = strtoupper(reset(str_split($content['coupon_id'], 8)));
			$couponid = str_replace(" ", null, $couponid);
			$coupon = ['id' => $couponid,
					'duration' => $content['coupon_duration'],
					'max_redemptions' => $content['coupon_max_redemptions']];
			
			if (preg_match("/\%/", $content['coupon_discount']))
			{
				$content['coupon_discount'] = str_replace("%", null, $content['coupon_discount']);
				$coupon['percent_off'] = $content['coupon_discount'];
			}
			else 
			{
				$content['coupon_discount'] = str_replace("$", null, $content['coupon_discount']);
				$coupon['amount_off'] = $content['coupon_discount'] * 100;
				$coupon['currency'] = 'usd';
			}
			
			if ($content['coupon_duration'] == 'repeating')
				$coupon['duration_in_months'] = $content['coupon_duration_in_months'];

			$result = $this->stripe_createCoupon($coupon);
			if ($result === true)
			{
				$json = [];
				$json['url'] = '/admin/stripe/';
				$json['action'] = 'reload';
				$this->jsone('success', $json);
			}
			else
				$this->failJson('Unable to Create Coupon', $result);
			
			
		}		
		
		public function stripeMain()
		{
			$data = base::span(4, $this->adminNav('stripe'));
			// A few things here.. 
			// Plan And Coupon Administration, Also Payout Schedules.
			 
			$data .= $this->getStripePlans();
			$data .= $this->getStripeCoupons();
			$this->export(base::row($data, true));
			
			
		}
		
		public function createPlan($content)
		{
			if (!$content['plan_name'] || !$content['plan_amount'] || !$content['plan_interval'] || !$content['plan_interval_count'])
				$this->failJson("Unable to Add", "Some fields are missing.");
			$planid = strtoupper(reset(str_split($content['plan_name'], 8)));
			$planid = str_replace(" ", null, $planid);
			$exists = $this->query("SELECT * from plans WHERE plan_id='$planid'")[0];
			if ($exists) $this->failJSON("Unable to Add", "Plan ID already exists. Please change the name.");
			$plan = [
			'amount' => $content['plan_amount'],
			'interval' => $content['plan_interval'],
			'name' => $content['plan_name'],
			'interval_count' => $content['plan_interval_count'],
			'currency' => 'usd',
			'id' => $planid,
			'trial_period_days' => $content['plan_trial']
			];
			
			$result = $this->stripe_createPlan($plan);
			if ($result === true)
			{
				$json = [];
				$json['action'] = 'reload';
				$json['url'] = '/admin/stripe/';
				$this->jsone('success', $json);
				
			}
			else
				$this->failJson("Plan Creation Failed", $result);
		}

	public function showTransactionLog()
	{
		$data = base::span(4, $this->adminNav('billing'));


		$headers = ['Merchant ID', 'Date', 'Amount', 'Fee', 'Net', 'Merchant', 'Description', 'Ticket', 'Company'];
		$transactions = $this->query("SELECT * from transactions ORDER by transaction_ts DESC");
		$rows = [];
		foreach ($transactions AS $transaction)
			$rows[] = [$transaction['transaction_merchant_id'],
						date("m/d/y h:ia", $transaction['transaction_ts']),
						number_format($transaction['transaction_amount'],2),
						number_format($transaction['transaction_fee'],2),
						number_format($transaction['transaction_net'],2),
						$transaction['transaction_source'],
						$transaction['transaction_desc'],
						"<a href='/ticket/$transaction[ticket_id]/'>$transaction[ticket_id]</a>",
						$this->getCompanyById($transaction['company_id'])
			];
		$table = table::init()->id('tlog')->headers($headers)->rows($rows)->render();
		$data .= widget::init()->span(6)->header('Transaction Log')->content($table)->icon('credit-card')->istable(true)->render();
		$this->exportjs(js::datatable('tlog', 50));
		$headers = ['Payout Schedule', 'Amount'];
		$rows = [];
		$now = time();
		$transfers = $this->query("SELECT * from transfers WHERE transfer_ts > $now ORDER by transfer_ts DESC");
		foreach ($transfers AS $transfer)
			$rows[] = [$this->fbTime($transfer['transfer_ts']), number_format($transfer['transfer_amt']/2,2)];
		$table = table::init()->headers($headers)->rows($rows)->render();
		$data .= widget::init()->span(2)->header('Payout Schedule')->icon('truck')->content($table)->istable(true)->render();
		$this->export(base::row($data, true));		
	}

	public function removeStripePlan($content)
	{
		$planid = $content['removeplan'];
		$result = $this->stripe_deletePlan($planid);
		if ($result === true)
		{
			$json = [];
			$json['url'] = '/admin/stripe/';
			$json['action'] = 'reload';
			$this->jsone('success', $json);
		}
		else
			$this->failJson("Unable to Delete", $result);
	}

	public function removeStripeCoupon($content)
	{
		$couponid = $content['removecoupon'];
		$result = $this->stripe_deleteCoupon($couponid);
		if ($result === true)
		{
			$json = [];
			$json['url'] = '/admin/stripe/';
			$json['action'] = 'reload';
			$this->jsone('success', $json);
		}
		else
			$this->failJson("Unable to Delete", $result);
	}
	
}


$mod = new admin();

// Access Levels
if (isset($_GET['levels']))
	$mod->levels();
else if (isset($_POST['createLevel']))
	$mod->createLevel($_POST);
else if (isset($_GET['editLevel']))
	$mod->editLevelForm($_GET);
else if (isset($_POST['editLevel']))
	$mod->editLevel($_POST);
else if (isset($_GET['deleteLevel']))
	$mod->deleteLevel($_GET);


// Users

else if (isset($_GET['users']))
	$mod->users();
else if (isset($_POST['createUser']))
	$mod->createUser($_POST);
else if (isset($_GET['editUser']))
	$mod->editUserForm($_GET);
else if (isset($_POST['editUser']))
	$mod->editUser($_POST);
else if (isset($_GET['deleteUser']))
	$mod->deleteUser($_GET);


// Queues

else if (isset($_GET['queues']))
	$mod->queues();
else if (isset($_POST['createQueue']))
	$mod->createQueue($_POST);
else if (isset($_GET['editQueue']))
	$mod->editQueueForm($_GET);
else if (isset($_POST['editQueue']))
	$mod->editQueue($_POST);
else if (isset($_GET['deleteQueue']))
	$mod->deleteQueue($_GET);

else if (isset($_GET['settings']))
	$mod->settings();
else if (isset($_POST['saveSettings']))
	$mod->saveSettings($_POST);

// Stripe
else if (isset($_GET['stripe']))
	$mod->stripeMain();
else if (isset($_POST['createPlan']))
	$mod->createPlan($_POST);
else if (isset($_POST['createCoupon']))
	$mod->createCoupon($_POST);
else if (isset($_GET['removeplan']))
	$mod->removeStripePlan($_GET);
else if (isset($_GET['removecoupon']))
	$mod->removeStripeCoupon($_GET);

//Billing
else if (isset($_GET['billing']))
	$mod->showTransactionLog();
else
	$mod->main();