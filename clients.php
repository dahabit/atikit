<?php
require_once("classes/core.inc.php");

class clients extends core
{
	
	public function __construct()
	{
		parent::__construct();
		if (!$this->isProvidingCompany())		// Only accessible by provider
			$this->reloadTarget();
		
		$data = base::subHeader("Client List", "View/Create/List Clients");
		$data .= base::begin();
		$this->export($data);
	}
	
	public function main()
	{
		$this->listClients();
		
	}
	
	
	public function createClient()
	{
		// Going to pretty much copy the end-user signup form.
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'user_name', 'text' => 'Full Name:', 'comment' => 'Main contact for account'];
		$elements[] = ['type' => 'input', 'var' => 'user_email', 'text' => 'E-mail Address:', 'comment' => 'Also your login to this system'];
		$elements[] = ['type' => 'password', 'var' => 'user_password', 'text' => 'Password:'];
		$elements[] = ['type' => 'input', 'var' => 'user_phone', 'text' => 'Phone Number (and Extension):', 'comment' => 'xxx.xxx.xxxx ext. xxx'];
		$elements[] = ['type' => 'input', 'var' => 'user_title', 'text' => 'Title:', 'comment' => 'Leave blank if individual'];
		$span = [];
		$span[] = ['span' => 6, 'elements' => $elements];
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'company_name', 'text' => 'Company Name:', 'comment' => 'If individual leave this blank'];
		$elements[] = ['type' => 'input', 'var' => 'company_address', 'text' => 'Address:', 'comment' => 'Where to mail invoices if required?'];
		$elements[] = ['type' => 'input', 'var' => 'company_address2', 'text' => 'Address Line 2:', 'comment' => 'Suite, etc.'];
		$elements[] = ['type' => 'input', 'var' => 'company_city', 'text' => 'City:'];
		$elements[] = ['type' => 'input', 'var' => 'company_state', 'text' => 'State:', 'class' => 'state'];
		$elements[] = ['type' => 'input', 'var' => 'company_zip', 'text' => 'Zip:'];
		$span[] = ['span' => 6, 'elements' => $elements];
		$form = form::init()->spanElements($span)->id('createAccount')->post('/clients/')->render();
		$button = button::init()->formid('createAccount')->text('Create New Account')->addStyle('post')->addStyle('btn-primary')->icon('fire')->message('Creating Account..')->postVar('createAccount')->render();
		$save = "<div class='pull-right'>$button</div>";
		$data .= widget::init()->icon('share-alt')->span(12)->header('Account Details')->content($form)->footer($save)->render();
		$this->exportJS(js::maskInput('state', "**"));
		$this->export($data);
	}
	
	public function createAccount($content)
	{
		$exists = $this->returnCountFromTable("users", "user_email='$content[user_email]'");
		if ($exists > 0)
			$this->failJson('Unable to Create Account', 'Account already exists.');
		if ($content['company_name'])
		{
			$exists = $this->returnCountFromTable("companies", "company_name='$content[company_name]'");
			if ($exists > 0)
				$this->failJson('Unable to Create Account', 'Company already exists.');
		}
		if (!$content['user_name'] || !$content['user_email'] || !$content['user_password'])
			$this->failJson("Unable to Create Account", "You must fill in all appropriate fields.");
		$password = md5($content['user_password']);
		$this->query("INSERT into users SET user_name='$content[user_name]', user_password='$password', user_email='$content[user_email]',
				user_phone='$content[user_phone]', user_title='$content[user_title]'");
		$uid = $this->insert_id;
		if (!$content['company_name'])
			$content['company_name'] = $content['user_name'];
		$now = time();
		$this->query("INSERT into companies SET company_since='$now', company_phone='$content[user_phone]', company_name='$content[company_name]', company_address='$content[company_address]', company_address2='$content[company_address2]',
				company_city='$content[company_city]', company_state='$content[company_state]', company_zip='$content[company_zip]', company_admin='$uid'");
		$cid = $this->insert_id;
		$this->query("UPDATE users SET company_id='$cid' WHERE id='$uid'"); // Assign the company to that user.
	
			
		// In the admin side we want to notify customers they have an account vs on the login
		// page where we just notified the company.
		$weare = $this->getSetting("mycompany");
		$url = $this->getSetting("atikit_url");
		$defaultEmail = $this->getSetting("defaultEmail");
		$this->mailCompany($cid, "New Account Created with {$weare}", 
"This email is to inform you that you have been created a new account on the support system, aTikit, currently used by {$weare}. Here are the details to your new account!

URL/Link: $url
E-mail Address: $content[user_email]
Password: $content[user_password]

You can login and change your password by going to the options menu and clicking My Profile. 
If you have any questions please feel free to email $defaultEmail");
		
		$json = [];
		$json['gtitle'] = 'Account Created';
		$json['gbody'] = 'New Client Account has been created.';
		$json['action'] = 'reload';
		$json['url'] = '/clients/';
		$this->jsone('success', $json);
	}
	
	public function listClients()
	{
		// At a glance - Let's view the client's basic info, If you can see billing, then show subscription
		// and show total amount collected.  Put in a datatable so we can search.
		$headers = ["Client", "Address", "VIP", "Tickets"];
		if ($this->canSeeBilling())
		{
			$billing = ['Subscription', 'Total Income'];
			$headers = array_merge($headers, $billing);
		}		
		
		$rows = [];
		
		$companies = $this->query("SELECT * from companies ORDER by company_since DESC");
		foreach ($companies AS $company)
		{
			$tcontent = null;
			$ticks = $this->query("SELECT id,ticket_title FROM tickets WHERE company_id='$company[id]' ORDER by ticket_opents DESC LIMIT 10");
			foreach ($ticks AS $tick)
				$tcontent .= "<a href='/ticket/$tick[id]/'>$tick[ticket_title]</a><br/>";
			$ticketblock = base::popover("Ticket History", $tcontent, 'right');
			$row = ["<a href='/client/$company[id]/'>$company[company_name]</a>",
					"$company[company_address], $company[company_city], $company[company_state]",
					($company['company_vip']) ? "Yes" : "No",
					"<a href='#' $ticketblock>".$this->returnCountFromTable("tickets", "company_id='$company[id]'")."</a>"
					
			];
			if ($this->canSeeBilling())
			{
				$plan = $this->query("SELECT * from plans WHERE id='$company[company_plan]'", true)[0];
				if (!$plan) 
					$plandata = "No Subscription";
				else
					$plandata = "$plan[plan_name] ($plan[plan_amount])";
				
				$ttl = 0;
				$transactions = $this->query("SELECT transaction_amount FROM transactions WHERE company_id='$company[id]'");
				foreach ($transactions AS $transaction)
					$ttl += $transaction['transaction_amount'];
				$ttl = "$" . number_format($ttl,2);
				$new = [$plandata, $ttl];
				$row = array_merge($row, $new);
			}
			$rows[] = $row;
			
			
		}
		$this->exportJS(js::datatable('clientList'));
		$addButton = button::init()->text("Add Account")->icon('plus')->addStyle('btn-success')->url('/clients/create/')->render();
		$table = table::init()->headers($headers)->rows($rows)->id('clientList')->render();
		$widget = widget::init()->header("Customer List")->content($table)->isTable()->icon('user')->rightHeader($addButton)->render();
		$this->export(base::row($widget));
		
	}
	
	public function showClient($content)
	{
		$id = $content['showClient'];
		$company = $this->query("SELECT * from companies WHERE id='$id'")[0];
		$user = $this->query("SELECT * from users WHERE company_id='$company[id]'")[0];
		// Going to pretty much copy the end-user signup form. (and change our postvars)
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'user_name', 'text' => 'Full Name:', 'comment' => 'Main contact for account', 'val' => $user['user_name']];
		$elements[] = ['type' => 'input', 'var' => 'user_email', 'text' => 'E-mail Address:', 'comment' => 'Also your login to this system', 'val' => $user['user_email']];
		$elements[] = ['type' => 'password', 'var' => 'user_password', 'text' => 'Password:'];
		$elements[] = ['type' => 'input', 'var' => 'user_phone', 'text' => 'Phone Number (and Extension):', 'comment' => 'xxx.xxx.xxxx ext. xxx', 'val' => $user['user_phone']];
		$elements[] = ['type' => 'input', 'var' => 'user_title', 'text' => 'Title:', 'comment' => 'Leave blank if individual', 'val' => $user['user_title']];
		$span = [];
		$span[] = ['span' => 6, 'elements' => $elements];
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'company_name', 'text' => 'Company Name:', 'comment' => 'If individual leave this blank', 'val' => $company['company_name']];
		$elements[] = ['type' => 'input', 'var' => 'company_address', 'text' => 'Address:', 'comment' => 'Where to mail invoices if required?', 'val' => $company['company_address']];
		$elements[] = ['type' => 'input', 'var' => 'company_address2', 'text' => 'Address Line 2:', 'comment' => 'Suite, etc.', 'val' => $company['company_address2']];
		$elements[] = ['type' => 'input', 'var' => 'company_city', 'text' => 'City:', 'val' => $company['company_city']];
		$elements[] = ['type' => 'input', 'var' => 'company_state', 'text' => 'State:', 'class' => 'state', 'val' => $company['company_state']];
		$elements[] = ['type' => 'input', 'var' => 'company_zip', 'text' => 'Zip:', 'val' => $company['company_zip']];
		$span[] = ['span' => 6, 'elements' => $elements];
		$form = form::init()->spanElements($span)->id('editAccount')->post('/clients/')->render();
		$button = button::init()->formid('editAccount')->text('Edit Account')->addStyle('post')->addStyle('btn-primary')->icon('fire')->message('Modifying Account..')->postVar('editAccount')->id($company['id'])->render();
		$save = "<div class='pull-right'>$button</div>";
		$data .= widget::init()->icon('share-alt')->span(12)->header('Account Details')->content($form)->footer($save)->render();
		$this->exportJS(js::maskInput('state', "**"));
		$this->export($data);
	}
	
	public function editClient($content)
	{
		$id = $content['editAccount'];
		$uid = $this->returnFieldFromTable("id", "users", "company_id='$id'");
		$this->query("UPDATE users SET user_name='$content[user_name]', user_email='$content[user_email]', user_phone='$content[user_phone]', 
				user_title='$content[user_title]' WHERE id='$uid'");
		
		$this->query("UPDATE companies SET company_phone='$content[user_phone]', company_name='$content[company_name]', company_address='$content[company_address]', company_address2='$content[company_address2]',
				company_city='$content[company_city]', company_state='$content[company_state]', company_zip='$content[company_zip]' WHERE id='$id'");
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = '/clients/';
		$this->jsonE('success', $json);
	}
}

$mod = new clients();
if (isset($_GET['createClient']))
	$mod->createClient();
else if (isset($_POST['createAccount']))
	$mod->createAccount($_POST);
else if (isset($_GET['showClient']))
	$mod->showClient($_GET);
else if (isset($_POST['editAccount']))
	$mod->editClient($_POST);

else
	$mod->main();
