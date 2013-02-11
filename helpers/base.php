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
 * @helper base
 */

require_once("table.php");
require_once("widget.php");
require_once("form.php");
require_once("button.php");
require_once("modal.php");
require_once("js.php");

class base
{
	public $data;
	static public function init()
	{
		$base = new base();
		$data = $base->headers()->navBar();
		return $data;
		
	}
	
	protected function headers()
	{
		$this->data = "<!DOCTYPE html>
				<html lang='en'>
				<head>
				<meta charset='utf-8'>
				<title>%%TITLE%%</title>
				<meta name='viewport' content='width=device-width, initial-scale=1.0'>
				
				<!-- %%META%% -->
				<link href='/assets/css/font-awesome.css' rel='stylesheet'>
				<!--[if IE 7]><link rel='stylesheet' href='assets/css/font-awesome-ie7.css'><![endif]-->
				<link href='/assets/js/google-code-prettify/prettify.css' rel='stylesheet'>
				<link href='/assets/css/bureau.css' rel='stylesheet'>
				<link href='/assets/css/bureau-additional.css' rel='stylesheet'>
				
				<!--[if IE]>
		        <link rel='stylesheet' type='text/css' href='/assets/css/bureau-ie.css' />
    		    <![endif]-->
				<!--[if lt IE 9]>
       			 <link rel='stylesheet' type='text/css' href='/assets/css/bureau-ie-lt9.css' /
        		<![endif]-->
				<!--[if IE 7]>
        		<link rel='stylesheet' type='text/css' href='/assets/css/bureau-ie7.css'>
        		<![endif]-->
				<link href='/assets/css/bootstrap-responsive.css' rel='stylesheet'>
				<link href='/assets/css/bureau-docs.css' rel='stylesheet'>
				<!--[if lt IE 9]>
          		<script src='http://html5shim.googlecode.com/svn/trunk/html5.js'></script>
		        <![endif]-->
				<!--[if lt IE 9]>
				<script src='http://html5shim.googlecode.com/svn/trunk/html5.js'></script>
				<![endif]-->
				<!-- Google web fonts -->
				<link href='/assets/css/fineuploader.css' rel='stylesheet'>
				<link href='/assets/css/ui.notify.css' rel='stylesheet'>
				<link href='/assets/css/jquery-ui.css' rel='stylesheet'>
				<link href='/assets/css/jquery.dataTables.css' rel='stylesheet'>
				</head>";
		return $this;
	}

	protected function navBar()
	{
		$this->data .= "
				<div id='docNav' class='navbar navbar-fixed-top'>
				<div class='navbar-inner'>
				<div class='container'>
					<button type='button' class='btn btn-navbar' data-toggle='collapse'	data-target='.nav-collapse'>
						<span class='icon-bar'></span> <span class='icon-bar'></span> <span	class='icon-bar'></span>
					</button>
					<a class='brand' href='/'>aTikit</a>
					<div class='nav-collapse collapse'>
						<ul class='nav'>
			
				%%NAV%%
			<li><div class='btn-group dropdown-append'>
				<a data-toggle='dropdown' class='dropdown-toggle btn msgtoggle' href='#' title='Notifications'><span class='notify-tiptop tips-blue notify-count'>%%NOTIFY_COUNT%%</span><i class='icon-inbox'></i></a>
				<div class='dropdown-menu msg-dropdown pull-right'>
				<ul class='msg-list'>
				<li class='clearfix'>
				<h6 class='dropdown-h pull-left'>Notifications</h6>
				<span class='pull-right msg-count'><a href='#'>%%NOTIFY_COUNT%% New</a></span></li>
				%%NOTIFICATIONS%%
				<li class='clearfix'><a href='#' class='btn btn-block view-all'>View All</a></li>
				</ul>
				</div></div>
			</li>
				</ul>
				</div>
				</div>
				</div>
				</div>";
		return $this->data;
	}
	
	static public function subHeader($title, $subtitle)
	{
		$data = "
				
		<header class='jumbotron subhead' id='overview'>
		<div class='container'>
			<h1>{$title}</h1>
			<p class='lead'>{$subtitle}</p>
			</div>
		</header>";
		return $data;
	}
	
	static public function itemPairs($params)
	{
		$data = null;
		foreach ($params AS $item)
			$data .= "<div class='item-row'>
			<div class='item-label'>
			{$item['label']}
			</div>
			<div class='item-content'>
			{$item['content']}
			</div></div> ";
		return $data;
	}

	static public function begin($fluid = false)
	{
		$fclass = ($fluid) ? '-fluid' : null;
		$data = "<div class='container{$fclass}'>
		<div id='notify-container' style='display:none'>
		 <div id='default'>
                        <h1>#{title}</h1>
                        <p>#{text}</p>
                </div>
		</div>
		";
		return $data;
	}
	
	static public function pageHeader($title, $content, $section = 'pageTitle')
	{
		$data = "
				<section id='{$section}'>
				<div class='page-header'>
					<h1>{$title}</h1>
				</div>
				<p class='lead'>{$content}</p>
				</section>";
		return $data;
	}


	static public function baseScripts()
	{
		$data = "
				<script type='text/javascript' src='http://platform.twitter.com/widgets.js'></script>
				<script src='/assets/js/jquery-1.9.0.min.js'></script>
				<script src='/assets/js/jquery-ui-1.10.0.custom.min.js'></script>
				<script src='/assets/js/google-code-prettify/prettify.js'></script>
				<script src='/assets/js/bootstrap-transition.js'></script>
				<script src='/assets/js/bootstrap-alert.js'></script>
				<script src='/assets/js/bootstrap-modal.js'></script>
				<script src='/assets/js/bootstrap-dropdown.js'></script>
				<script src='/assets/js/bootstrap-scrollspy.js'></script>
				<script src='/assets/js/bootstrap-tab.js'></script>
				<script src='/assets/js/bootstrap-tooltip.js'></script>
				<script src='/assets/js/bootstrap-popover.js'></script>
				<script src='/assets/js/bootstrap-button.js'></script>
				<script src='/assets/js/bootstrap-collapse.js'></script>
				<script src='/assets/js/bootstrap-carousel.js'></script>
				<script src='/assets/js/bootstrap-typeahead.js'></script>
				<script src='/assets/js/bootstrap-affix.js'></script>
				<script src='/assets/js/jquery.dataTables.min.js'></script>
    			<script src='/assets/js/DT_bootstrap.js'></script>
				<script src='/assets/js/application.js'></script>
				<script src='/assets/js/fine/header.js'></script>
		        <script src='/assets/js/fine/util.js'></script>
		        <script src='/assets/js/fine/button.js'></script>
		        <script src='/assets/js/fine/handler.base.js'></script>
		        <script src='/assets/js/fine/handler.form.js'></script>
		        <script src='/assets/js/fine/handler.xhr.js'></script>
		        <script src='/assets/js/fine/uploader.basic.js'></script>
		        <script src='/assets/js/fine/dnd.js'></script>
		        <script src='/assets/js/fine/jquery-plugin.js'></script>
		        <script src='/assets/js/fine/uploader.js'></script>
				<script src='/assets/js/jquery.maskedinput.min.js'></script>
				<script src='/assets/js/jquery.notify.min.js'></script>
		   	 	<script src='/assets/js/jquery.livequery.js'></script>
				<script src='https://js.stripe.com/v1/'></script>
				<script src='/assets/js/c3.js'></script>
				
				";
		return $data;
	}

	static public function footer($scripts = null)
	{
		$data = "</div>

	<!-- Footer
        ================================================== -->
	<footer class='footer'>
		<div id='foo' class='container'>
			<div id='footermessage'>
				<p>Developed by <a href='http://www.core3networks.com/'>Core 3 Networks</a></p>
				<p>
					aTikit v1.0
				</p>
			</div>
			
		</div>
		<div id='copyright'>
			<div class='container'>Copyright &copy; 2013 Core 3 Networks, All rights reserved.</div>
		</div>
	</footer>
				
	". self::baseScripts()."
		
	<script type='text/javascript'>
	$(document).ready(function() {
		{$scripts}
	}); </script></body></html>";
	return $data;
	}
	
	static public function row($content, $fluid = false)
	{
		if ($fluid) $fluid = "-fluid";
		return "<div class='row{$fluid}'>{$content}</div>";
	}
	
	static public function alert($type, $header, $message, $block = false)
	{
		switch ($type)
		{
			case 'info' : $style = 'alert-info'; break;
			case 'success' : $style = 'alert-success'; break;
			case 'warning' : $style = null; break;
			case 'error'  : $style = 'alert-error'; break;
		}
		$bclass = ($block) ? 'alert-block' : null;
		$data .= "
				<div class='alert {$bclass} {$style} fade in'>
				<button data-dismiss='alert' class='close' type='button'>×</button>
				<h4 class='alert-heading'>{$header}</h4>
				<p>{$message}</p>
				</div>";
		return $data;
	
	}
	
	static public function hero ($title, $content)
	{
		$data = "<div class='hero-unit'><h1>{$title}</h1><p>{$content}</p></div>";
		return $data;
	}
	
	static public function crumbs($crumbs)
	{
		$max = count($crumbs);
		
		$data = "
				<section id='breadcrumbs'>
					
					<ul style='margin-bottom: 5px;' class='breadcrumb'>
						<li><a href='/'>Home</a> <span class='divider'>/</span></li>";
		$x = 0;
		foreach ($crumbs AS $crumb)
		{
			$x++;
			if ($x < $max)
				$data .= "<li><a href='$crumb[link]'>$crumb[text]</a> <span class='divider'>/</span></li>";
			else
				$data .= "<li class='active'>$crumb[text]</li>";
				
		}
	
		$data .= "</ul></section>";
		return $data;
	}

	static public function span($span, $content)
	{
		return "<div class='span{$span}'>$content</div>";
	}
	
	static public function notifications($notifications)
	{
		$data = null;
		foreach ($notifications AS $notification)
		{
			$data .= "
						<li class='clearfix'><a href='$notification[link]'><span class='dropdown-thumb'><img align='left' src='$notification[thumb]' width='60' height='60' alt='Avatar' style='padding-right:5px;'></span>
						<span>$notification[body]</span>
						<span class='notification-meta'>
						$notification[time]</span></a></li>";
		}	
		return $data;
	}
	
	static public function popover($title, $content, $loc = 'top')
	{
		$content = str_replace("'", '"', $content);
		$title = str_replace("'", '"', $title);
		$data = "rel='popover-hover' data-placement='{$loc}' data-original-title='{$title}' data-content='$content'";
		return $data;	
	}
	
	static public function feed($params, $class = "updatelist")
	{
		$data = "<div class='bs-docs-example'><ul class='{$class}'>";
		$x=0;
		foreach ($params AS $item)
		{
			
			if ($item['well'])
			{
				$welldata = "<div class='well'>$item[well]</div>";
			}
				
			else
				$welldata = null;
				
			$x++;
			$bg = ($item['internal']) ? "style='border: 2px solid #aa0000;'" : null;
			$thumb = $item['thumb'];
			$data .= "<li class='feedUpdate' id='$x' $bg>
					<div class='updatethumb'><img class='img-rounded' width='50px' height='50px' src='/$thumb' alt='$item[alt]' /></div>
					<div class='updatecontent'>
					<div class='top'>
					<a href='$item[url]' class='user'>$item[author]</a> <span><b>$item[ago]</b> $item[admin]</span>
					</div><!--top-->
					<div class='text'>
					$item[post]
					</div><!--text-->
					$welldata
					</div><!--updatecontent-->
					</li>";
			}
	
			$data .= "</ul></div>";
			return $data;
	} //feed
	
	static public function singleFeed($item)
	{
		$x = 500;
		$welldata = null;
		$bg = ($item['internal']) ? "style='border: 2px solid #aa0000;'" : null;
		$thumb = $item['thumb'];
		$data .= "<li class='feedUpdate' id='$x' $bg>
		<div class='updatethumb'><img class='img-rounded' width='50px' height='50px' src='/$thumb' alt='$item[alt]' /></div>
		<div class='updatecontent'>
		<div class='top'>
		<a href='$item[url]' class='user'>$item[author]</a><span> <b>$item[ago]</b> $item[admin]</span>
		</div><!--top-->
		<div class='text'>
		$item[post]
		</div><!--text-->
		$welldata
		</div><!--updatecontent-->
		</li>";
		return $data;
		
	}
}