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
 * @helper table
 */
class table
{
	public function __construct()
	{
		$this->tableClasses = ['table', 'table-striped', 'table-hover', 'table-bordered'];
		
		$this->footer = null;	
	}
	
	static public function init()
	{
		return new table();
	}
	
	public function footer($footer)
	{
		$this->footer = $footer;
		return $this;
	}
	
	
	
	public function headers($headers)
	{
		$this->headers = $headers;
		return $this;
	}
	
	public function id($id)
	{
		$this->id = $id;
		return $this;
		
	}
	public function rows($rows)
	{
		$this->rows = $rows;
		return $this;
	}
	/*
	 * .table	default table style
		
		.table-striped	Adds zebra-striping to any table row within the tbody
		.table-bordered	Add borders and rounded corners to the table.
		.table-hover	Enable a hover state on table rows within a tbody
		
	 */
	
	public function addStyle($style)
	{
		array_push($this->tableClasses, $style);
		return $this;
	}
	
	public function render()
	{
		$tableClasses = implode(" ", $this->tableClasses);		
		$data = "
		<table id='{$this->id}' class='{$tableClasses}'>
		
		<thead><tr>";
		
		foreach ($this->headers AS $header)
		$data .= "<th>{$header}</th>";
		$data .= "</tr></thead>";
		
		if ($this->footer) $data .= $this->footer;
		
		$data .= "<tbody>";
		
		foreach ($this->rows AS $row)
		{
			$maxCols = count($row) - 1;
			$color = $row[$maxCols];
			switch ($color)
			{
				
				case 'green' : $c = "class='success'"; break;
				case 'blue'  : $c = "class='info'"; break;
				case 'yellow': $c = "class='warning'"; break;
				case 'red'	 : $c = "class='error'"; break;
				default : $c = null; break;
			}
			if ($c)
				unset($row[$maxCols]); // Don't print the word green...etc.
			$data .= "<tr {$c}>";
			foreach ($row AS $rowdata)
				$data .= "<td>$rowdata</td>";
			$data .= "</tr>";
		}
		
		$data .= "</tbody></table>";
		return $data;	
	}

}