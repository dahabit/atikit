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
 * @helper form
 */
class form
{
	
	public function __construct()
	{
		$this->isVertical = true; 		// by default make forms vertical
		$this->isModal = false;			// by Default we are not in a modal
		$this->id = 'form';				// Form ID for controls
		$this->addClass = null;			// Any additional class specifications?
		$this->post = null;				// Form posts to where
		$this->elementData = null;		// Returned by {elements}
		$this->legend = null;			// No legend by default.
		$this->formTermination = null;	// No buttons to save or anything by default.
	}
	
	static public function init()
	{
		return new form();
	}
	
	public function id ($id)
	{
		$this->id = $id;
		return $this;
	}
	
	public function isVertical($isVertical = true)
	{
		$this->isVertical = $isVertical;
		return $this;
	}
	
	public function isModal($isModal = false)
	{
		$this->isModal = $isModal;
		return $this;
	}
	
	public function legend ($legend)
	{
		$this->legend = $legend;
		return $this;		
		
	}
	
	public function addClass ($addClass)
	{
		$this->addClass = $addClass;
		return $this;
		
	}	
	
	public function post($post)
	{
		$this->post = $post;
		return $this;
	}
	
	
	public function spanElements($params)
	{
		/* [span = 6] - elements = fields
		 * [6] - fields, etc.
		 * 
		 */
		$data = "<div class='row-fluid'>";
		foreach ($params AS $span)
		{
			$data .= "<div class='span{$span['span']}'>";
			$this->elements($span['elements']);
			$data .= $this->elementData;
			$data .= "</div>";
		}
		
		$data .= "</div>";
		$this->elementData = $data;
		return $this;
	}	
	
	
	public function elements($elements)
	{
		// Array of elements, similar to how they were done before.
		$data = null;
		foreach ($elements AS $element)
		{
			switch($element['type'])
			{
				case 'raw' : 	$data .= $element['data']; break;
				case 'input' :	$span = ($element['span']) ? "span{$element['span']}" : null;
								$inline = ($element['inline']) ? "<span class='help-block'>$element[inline]</span>" : null;
								$disabled = ($element['disabled']) ? "disabled" : null;
								$comment = ($element['comment']) ? "<span class='help-block'>$element[comment]</span>" : null;
								if ($element['prepend'])
								{
									$data .= "<label for='$element[var]'>$element[text] {$inline}</label>
                                        	<div class='input-prepend'> <span class='add-on'>$element[prepend]</span>
                                        	<input {$disabled} id='$element[id]' type='text' class='$element[class] $span' name='$element[var]' value='$element[val]' placeholder='$element[placeholder]'>
                                        	</div>
											{$comment}";
											
								}
								else
								{	
									if ($this->isVertical)
										$data .= "<label for='$element[var]'>$element[text] {$inline}</label>
	                                        	<input {$disabled} id='$element[id]' type='text' class='$element[class] $span' name='$element[var]' value='$element[val]' placeholder='$element[placeholder]'>
												{$comment}";
									else
										$data .= "<div class='control-group'>
													<label for='$element[var]'>$element[text] {$inline}</label>
													 <div class='controls'>
													 	<input id='$element[id]' type='text' class='$element[class] $span' name='$element[var]' value='$element[val]' placeholder='$element[placeholder]'>
													 	{$comment}
													 </div>
													</div>
												";
								}
								break;
								
				case 'password' :	$span = ($element['span']) ? "span{$element['span']}" : null;
								$inline = ($element['inline']) ? "<span class='help-block'>$element[inline]</span>" : null;
								$comment = ($element['comment']) ? "<span class='help-block'>$element[comment]</span>" : null;
								if ($this->isVertical)
									$data .= "<label for='$element[var]'>$element[text] {$inline}</label>
									<input id='$element[id]' type='password' class='$element[class] $span' name='$element[var]' value='$element[val]' placeholder='$element[placeholder]'>
									{$comment}";
									else
										$data .= "<div class='control-group'>
										<label for='$element[var]'>$element[text] {$inline}</label>
										<div class='controls'>
										<input id='$element[id]' type='password' class='$element[class] $span' name='$element[var]' value='$element[val]' placeholder='$element[placeholder]'>
										{$comment}
										</div>
										</div>
										";
										break;								

				case 'checkbox' : $item = null;
									foreach ($element['opts'] AS $opt)
									{
										$checked = $opt['checked'] ? 'checked' : null;
										$item .= "<label class='checkbox'>
										<input type='checkbox' name='$element[var]' id='$element[id]' value='$opt[val]' $checked> $opt[text]
										</label>";
									}
										
									$data .= $item;
								break;
				
						
				case 'select' : 	
								$span = ($element['span']) ? "span{$element['span']}" : null;
								$inline = ($element['inline']) ? "<span class='help-block'>$element[inline]</span>" : null;
								$sclass = ($element['sclass']) ? "-".$element['sclass'] : null;
								$comment = ($element['comment']) ? "<span class='help-block'>$element[comment]</span>" : null;
								$opts = null;
								foreach ($element['opts'] AS $opt)
									$opts .= "<option value='$opt[val]'>$opt[text]</option>";
								
								if ($this->isVertical)
									$data .= "<label for='$element[var]'>$element[text] {$inline}</label>
                                        	<select class='selectpicker{$sclass} $span' id='$element[id]' name='$element[var]'>$opts</select>
											{$comment}";
								else
									$data .= "<div class='control-group'>
												<label for='$element[var]'>$element[text] {$inline}</label>
												 <div class='controls'>
												 	<select class='selecttwo selectpicker{$sclass} $span' id='$element[id]' name='$element[var]'>$opts</select>
												 	{$comment}
												 </div>
												</div>
											";
								break;
								
								
				case 'textarea' : $span = ($element['span']) ? "span{$element['span']}" : null;
								$inline = ($element['inline']) ? "<span class='help-block'>$element[inline]</span>" : null;
								$disabled = ($element['disabled']) ? "disabled" : null;
								$comment = ($element['comment']) ? "<span class='help-block'>$element[comment]</span>" : null;
								$data .= "<label for='$element[var]'>$element[text] {$inline}</label>
                                        	<textarea rows='$element[rows]' {$disabled} id='$element[id]' type='text' class='$element[class] $span' name='$element[var]' placeholder='$element[placeholder]'>$element[val]</textarea>
											{$comment}";
												
			case 'actions' : $this->formTermination = $element['buttons']; break; // these are buttons
			case 'ajax' : $data .= "<label for='$element[var]'>$element[text] {$inline}</label><a class='btn btn-primary' id='$element[id]'></a>"; break;
			case 'hidden' : $data .= "<input type='hidden' name='$element[var]' value='$element[val]'>"; break;	
				
			} // switch
		
		
		} // fe		
		$this->elementData = $data;
		return $this;
	}
	
	
	
	public function render()
	{
		$data = "
		<form id='{$this->id}' method='post' action='$this->post' class=''>
			<div class='{$this->id}_msg'></div>
		<fieldset>";
		if ($this->legend)
			$data .= "<legend>{$this->legend}</legend>";
		
		$data .= $this->elementData;		
		$data .= "</fieldset>";
		if ($this->formTermination)
			$data .= "<div class='form-actions'>{$this->formTermination}</div>";
		
		$data .= "</form>";
		return $data;		
	}
}