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
 * @helper button
 */
class button
{
	
	public function __construct()
	{
		$this->element = 'anchor'; // anchor or button		
		$this->url = '#';
		$this->text = 'Button';
		$this->styles = ['btn'];
		$this->id = null;
		$this->formid = null;
		$this->icon = null;
		$this->caret = null;
		$this->toolbar = null;
		$this->dropDownData = null;
		$this->dataToggle = null;
		$this->enableCaret = false;
		$this->isGlyph = false;
		$this->isModalLauncher = false;
		$this->isModalCloser = false;
		$this->isDropDown = false;
		$this->withToolbar = false;
		$this->withGroup = true;
		$this->message = null;
		$this->closerData = null;
		$this->postVar = null; // used for posting using mpost
	}
	public function isModalCloser()
	{
		$this->isModalCloser = true;
		$this->closerData = "data-dismiss='modal'";
		return $this;
	}
	
	public function withGroup($withGroup = true)
	{
		$this->withGroup = $withGroup;
		return $this;
	}
	public function formid($formid)
	{
		$this->formid = '#' . $formid;
		return $this;
	}
	
	static public function init()
	{
		return new button();
	}
	
	public function postVar($postVar)
	{
		$this->postVar = $postVar;
		return $this;
	}
	
	static public function endToolBar()
	{
		return "</div>";

	}
	public function id($id)
	{
		$this->id = $id;
		return $this;
	}
	
	public function text($text)
	{
		$this->text = $text;
		return $this;
	}
	
	
	public function isSecondary()
	{
		$this->toolbar = ' ';
		return $this;
		
	}
	public function withToolbar()
	{
		$this->toolbar = "<div class='btn-toolbar padding-side'>";
		return $this;
		
	}

	public function enableCaret()
	{
		$this->caret = " <span class='caret'></span>";
		return $this;
	}
	
	public function icon($icon)
	{
		$this->icon = "<i class='icon-{$icon}'></i> ";
		return $this;
	}

	public function addStyle($style)
	{
		
		array_push($this->styles, $style);
		return $this;
	}
	
	public function isModalLauncher()
	{
		$this->isModalLauncher = true;
		$this->dataToggle = "data-toggle='modal'";
		return $this;
	}
	public function message($message)
	{
		$this->message = $message;
		return $this;
		
	}
	public function isDropDown($items)
	{
		$this->isDropDown = true;
		$this->dataToggle = "data-toggle='dropdown'";
		array_push($this->styles, 'dropdown-toggle');
		
		$data = "<ul class='dropdown-menu'>";
        foreach ($items AS $item)
        {
			$dt =  ($item['modal']) ? "data-toggle='modal'" : null; 
			if ($item['text'] == 'sep')
				$data .= "<li class='divider'></li>";
			else $data .= "<li><a class='$item[class]' $dt href='$item[link]'><i class='$item[icon]'></i>$item[text]</a></li>";
        }           
		$data .= "</ul>";
		$this->dropDownData = $data;
		return $this;
		
	}
	
	public function element($element)
	{
		$this->element = $element;
		return $this;
		
	}
	
	public function url($url)
	{
		$this->url = $url;
		return $this;
	}
	public function render()
	{
		$message = ($this->message) ? "data-title='$this->message'" : null;
		$postvar = ($this->postVar) ? "rel='$this->postVar'" : null;
		$formid = ($this->formid) ? "data-content='$this->formid'" : null;
		switch ($this->element)
		{
			case 'anchor' : $open = "<a "; $close = "</a>"; $type = null; break;
			case 'button' : $open = "<button "; $close = "</button>"; $type = "type='submit'" ; break;
		}
		if ($this->toolbar)
			$data = $this->toolbar;
		else if ($this->withGroup)
			$data = "<div class='btn-group'>";
		$data .= $open;
		$data .= "class='" . implode(" ", $this->styles). "' href='{$this->url}' {$message} {$postvar} {$type} {$formid} {$this->closerData} {$this->dataToggle} id='{$this->id}'>{$this->icon}{$this->text}{$this->caret}{$close}{$this->dropDownData}";
		if (!$this->toolbar && $this->withGroup)
			$data .= "</div>";
		return $data;
	}
	
}