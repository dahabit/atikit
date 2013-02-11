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

class login extends core
{
	public function __construct()
	{
		parent::__construct(true);
	}	

	public function loginForm()
	{
		$company = $this->getSetting("mycompany");
		$this->export(base::subHeader($company, "Support and Project Management"));
		$button = button::init()->addStyle('post')->addStyle('btn-primary')->text('Login')->icon('arrow-right')->render();
		$button .= "<span class='pull-right'>".button::init()->addStyle('btn-info')->icon('edit')->text('Create Account')->url('/login/signup/')->render()."</span>";
		// Fix this... This is pitiful.
		$this->export("
				 <style type='text/css'>
            body {
                padding-top: 40px;
                padding-bottom: 40px;
            }
            .form-signin,
            #signup {
                max-width: 300px;
                margin: 0 auto 20px;
            }
            .form-signin {
                padding: 19px 29px 29px;
                margin: 0 auto 20px;
                background-color: #ffffff;
                background-image: -moz-linear-gradient(top, #ffffff, #f2f2f2);
                background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff),
                    to(#f2f2f2) );
                background-image: -webkit-linear-gradient(top, #ffffff, #f2f2f2);
                background-image: -o-linear-gradient(top, #ffffff, #f2f2f2);
                background-image: linear-gradient(to bottom, #ffffff, #f2f2f2);
                filter: progid : dximagetransform.microsoft.gradient ( startColorstr =
                    '#ffffffff', endColorstr = '#fff2f2f2', GradientType = 0 );
                background-repeat: repeat-x;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
                -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
            }
            .form-signin .form-signin-heading {
                margin-bottom: 20px;
            }           
            .form-signin .checkbox {
                margin-bottom: 10px;
            }
            .form-signin input[type='text'],
            .form-signin input[type='password'] {
                height: auto;
                margin-bottom: 15px;
                padding: 7px 9px;
            }
            #signup {
                text-align: center;
            }
        </style>
            <div class='row'>
				<form class='form-signin' action='/login/' method='post' id='loginForm'>
                <fieldset>
                    <legend class='form-signin-heading'>Login or Create an Account</legend>
                    <input type='text' name='email' class='input-block-level' placeholder='Email Address'>
                    <input type='password' name='password' class='input-block-level' placeholder='Password'>
                    <label class='checkbox'>
                        <input type='checkbox' value='remember-me'> Remember me
                    </label>
                    {$button}
                </fieldset>
            </form>

            <div id='signup'>
                <p><a href='#'>Forgot your password?</a></p>
                
            </div>
				</div>");
		
	}
	
	public function signup()
	{
		$this->exportJS("$('#docNav').hide();");
		$data = base::subHeader("Create an Account", "Add your Company to the Support System");
		
		$data .= base::begin();
		$data .= base::pageHeader("Before you Begin", "You are about to create an account. If your company is already in our system, please have your company administrator add your account. You will only be able to add your company once.");
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'user_name', 'text' => 'Full Name:', 'comment' => 'Main contact for account'];
		$elements[] = ['type' => 'input', 'var' => 'user_email', 'text' => 'E-mail Address:', 'comment' => 'Also your login to this system'];
		$elements[] = ['type' => 'password', 'var' => 'user_password', 'text' => 'Password:'];
		$elements[] = ['type' => 'input', 'var' => 'user_phone', 'text' => 'Phone Number (and Extension):', 'comment' => 'xxx.xxx.xxxx ext. xxx'];
		$elements[] = ['type' => 'input', 'var' => 'user_title', 'text' => 'Your Title:', 'comment' => 'Leave blank if individual'];
		$span = [];
		$span[] = ['span' => 6, 'elements' => $elements];
		$elements = [];
		$elements[] = ['type' => 'input', 'var' => 'company_name', 'text' => 'Company Name:', 'comment' => 'If individual leave this blank'];
		$elements[] = ['type' => 'input', 'var' => 'company_address', 'text' => 'Address:', 'comment' => 'Where to mail invoices if required?'];
		$elements[] = ['type' => 'input', 'var' => 'company_address2', 'text' => 'Address Line 2:', 'comment' => 'Suite, etc.'];
		$elements[] = ['type' => 'input', 'var' => 'company_city', 'text' => 'City:'];
		$elements[] = ['type' => 'input', 'var' => 'company_state', 'text' => 'State:'];
		$elements[] = ['type' => 'input', 'var' => 'company_zip', 'text' => 'Zip:'];
		$span[] = ['span' => 6, 'elements' => $elements];
		$form = form::init()->spanElements($span)->id('createAccount')->post('/login/')->render();
		$button = button::init()->formid('createAccount')->text('Create My Account')->addStyle('post')->addStyle('btn-primary')->icon('fire')->message('Creating Account..')->postVar('createAccount')->render();
		$save = "<div class='pull-right'>$button</div>";
		$data .= widget::init()->icon('share-alt')->span(12)->header('Account Details')->content($form)->footer($save)->render();
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
		$this->query("INSERT into companies SET company_phone='$content[user_phone]', company_name='$content[company_name]', company_address='$content[company_address]', company_address2='$content[company_address2]',
					company_city='$content[company_city]', company_state='$content[company_state]', company_zip='$content[company_zip]', company_admin='$uid'");
		$cid = $this->insert_id;
		$this->query("UPDATE users SET company_id='$cid' WHERE id='$uid'"); // Assign the company to that user.

		// Check to see if this is the first company? or if we have a provider yet? 
		$ccount = $this->returnCountFromTable("companies", "company_isprovider = true");
		if ($ccount == 0)
		{
			$this->query("UPDATE companies SET company_isprovider = true where id='$cid'");
			$this->querY("UPDATE users SET user_isadmin=TRUE where id='$uid'");
		}
		$_SESSION[config::APP_NAMESPACE] = $uid;
		$json = [];
		$json['gtitle'] = 'Account Created';
		$json['gbody'] = 'Your account has been created.';
		$json['action'] = 'waitload';
		$json['url'] = '/';
		$this->jsone('success', $json);
	}
	
	public function processLogin($content)
	{
		$password = md5($content['password']);
		$id = $this->query("SELECT id from users WHERE user_email='$content[email]' AND user_password='$password'")[0];
		if (!$id)
			$this->failJson('Unable to Login', 'The e-mail address or password was not correct.');
		$_SESSION[config::APP_NAMESPACE] = $id['id'];
		$json = [];
		$json['action'] = 'reload';
		$json['url'] = '/';
		$this->jsone('success', $json);
	}
}

$mod = new login();
if (isset($_POST['email']))
	$mod->processLogin($_POST);
else if (isset($_GET['signup']))
	$mod->signup();
else if (isset($_POST['createAccount']))
	$mod->createAccount($_POST);
else 
	$mod->loginForm();