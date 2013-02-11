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
 * @helper modal
 */
class modal
{
	public function __construct()
	{
		$this->header = "Modal header";
		$this->content = "Modal Content";
		$this->footer = "<a href='#' data-dismiss='modal' class='btn'><i class='icon-remove'></i> Close</a> ";
		$this->isInline = false;   //Used when sending in ajax data.
		$this->onlyConstruct = false; // Used when building the modal struct but no data.
		$this->autoLoad = false; // Send back some js for autoloading this modal.
		$this->styles = ['modal', 'hide', 'fade']; 	// Modal init style
		$this->fade = true;
		$this->backdrop = true;
		$this->id = 'modal';
		$this->dynamic = false;
		$this->width = false;
		$this->pre = null; //predata
		
	}
	
	public function backdrop($state = true)
	{
		if (!$state)
			array_push($this->styles, 'static');
		return $this;
	}
	static public function init()
	{
		return new modal();
		
	}
	public function header($header)
	{
		$this->header = $header;
		return $this;
	}

	public function width($width)
	{
		$this->width = $width;
		return $this;
	}
	
	public function content($content)
	{
		$this->content = $content;
		return $this;
	}
	
	public function footer($footer)
	{
		$this->footer = $footer;
		return $this;
	}
	
	public function id ($id)
	{
		$this->id = $id;
		return $this;
		
	}
	
	public function fade ($state = true)
	{
		if ($state)
			array_push($this->styles, 'fade');
		else
		{
			$new = [];
			foreach ($this->styles AS $id => $style)
				if ($style == 'fade')
					unset($this->styles[$id]);
		}
		
		return $this;
	}
	
	public function pre($pre)
	{
		$this->pre = $pre;
		return $this;
	}
	
	public function hide ($state = true)
	{
		if ($state)
			array_push($this->styles, 'hide');
		else
		{
			$new = [];
			foreach ($this->styles AS $id => $style)
				if ($style == 'hide')
				unset($this->styles[$id]);
		}
		
		return $this;
	}

	public function autoLoad()
	{
		$this->autoLoad = true;
		return $this;
	}
	
	public function dynamic ($state = true)
	{
		if ($state)
			array_push($this->styles, 'container');
		else
		{
			$new = [];
			foreach ($this->styles AS $id => $style)
				if ($style == 'container')
				unset($id);
		}
		$this->styles = $style;
		return $this;
	}
	
	
	
	public function isInline()
	{
		$this->isInline = true;
		return $this;
	}
	
	public function onlyConstruct()
	{
		$this->onlyConstruct = true;
		return $this;
		
	}
	
	public function render()
	{
		$styles = implode(" ", $this->styles);
		$width = ($this->width) ? "data-width='$this->width'" : null;
		if ($this->onlyConstruct)
					return "<div class='{$styles}' id='{$this->id}' tabindex='-1' $width></div>";
		
		if ($this->isInline)
			return "<div class='modal-header'>
		        			<button type='button' class='close' data-dismiss='modal' area-hidden='true'>&times;</button>
		        			<h4>{$this->header}</h4>
	    				</div>
					    <div class='modal-body'>
					    {$this->pre}{$this->content}
	    				</div>
	    				<div class='modal-footer'>
	        				{$this->footer}
	    				</div>";
	     // If we haven't returned yet, we need the entire modal init.
			$data = "
					<div class='{$styles}' $width id='{$this->id}' tabindex='-1'>
	    				<div class='modal-header'>
		        			<button type='button' class='close' data-dismiss='modal' area-hidden='true'>&times;</button>
		        			<h4>{$this->header}</h4>
	    				</div>
					    <div class='modal-body'>
					    {$this->pre}{$this->content}
	    				</div>
	    				<div class='modal-footer'>
	        				{$this->footer}
	    				</div>
					</div>";
	return $data;
	}
	
	
	
	
	
	
}