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
 * @helper widget
 */
class widget
{
	// Standard Widgets
	public $icon = 'fire';
	public $header = "Widget Header";
	public $content = "Widget Content";
	public $styles = null;	
	public $footer = null;
	public $toolbar = null;
	public $span = null;
	public $rightHeader = null;
	public $tabHeader = null;
	public $tabContent = null;
	public $maxHeight = null;
	
	
	public function init()
	{
	    return new widget();
		
	}
	
	public function maxHeight($maxHeight)
	{
		$this->maxHeight = $maxHeight;
		return $this;
	}
	
	public function isDark()
	{
		$this->styles .= 'bs-docs-example';
		return $this;
		
	}
	
	public function icon($icon)
	{
		$this->icon = $icon;
		return $this;
	}
	
	
	public function rightHeader($rightHeader)
	{
		$this->rightHeader = "<div class='pull-right'>{$rightHeader}</div>";
		return $this;
	}
	
	public function header($header)
	{
		$this->header = $header;
		return $this;
	}
	
	public function isTabs($tabs, $id = 'myTabs')
	{
		$this->tabHeader = "<div class='widget-tabs'><ul class='nav nav-tabs' id='{$id}'>";
		$this->tabContent = "<div class='widget-body tab-content'>";

							
		foreach ($tabs AS $tab)
		{
			$this->tabHeader .= "<li class='$tab[class]'><a data-toggle='tab' href='#$tab[id]'><i class='icon-$tab[icon]'></i> $tab[title]</a></li>";
			$aclass = ($tab['class']) ? 'active in' : null;
			$this->tabContent .= "<div class='tab-pane fade {$aclass}' id='$tab[id]'><p>$tab[content]</p></div>";
		}
		$this->tabHeader .= "</ul>";
		$this->tabContent .= "</div></div>";
		return $this;
	}
	
	
	
	public function content($content)
	{
		$this->content = $content;
		return $this;
		
	}
	public function span($span)
	{
		$this->span = $span;
		return $this;
	}
	
	public function isTable()
	{
		$this->styles .= 'widget-table';
		return $this;		
	}
	
	public function footer($footer)
	{
		$this->footer = "<div class='widget-footer'>{$footer}</div>";
		return $this;
	}
	
	public function toolbar($toolbar)
	{
		$this->toolbar = "<div class='widget-toolbar clearfix'>{$toolbar}</div>";
		return $this;
	}
	
	public function render()
	{
		$spanStart = ($this->span) ? "<div class='span{$this->span}'>" : null;
		$spanEnd = ($this->span) ? "</div>" : null;
		$max = ($this->maxHeight) ? "style='max-height: {$this->maxHeight}px'" : null;
		$content = ($this->tabContent) ? $this->tabContent : "<div class='widget-body'>{$this->content}</div>";
		$data = "
		{$spanStart}
		<div class='widget {$this->styles}' $max>
			<div class='widget-header clearfix '>
				<h3><i class='icon-{$this->icon}'></i>&nbsp; {$this->header} </h3>
			{$this->rightHeader}
			</div>
			{$this->toolbar}
			{$this->tabHeader}
			{$content}
			{$this->footer}
		</div>
		{$spanEnd}";
		return $data;		
		
	}
	
	
	
	
}