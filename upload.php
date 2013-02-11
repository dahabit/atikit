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
 * @class upload
 */
include("classes/core.inc.php");
require_once 'classes/qqFileUploader.php';
class upload extends core
{
	public function main()
	{
		// Post CODE is to let the uploader know what to do with the file when complete.
		$code = $_POST['code'];
		$this->ajax = true;
		// Include the uploader class
		$uploader = new qqFileUploader();
		// Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
		$uploader->allowedExtensions = array();
		// Specify max file size in bytes.
		$uploader->sizeLimit = 10 * 1024 * 1024;
		// Specify the input name set in the javascript.
		$uploader->inputName = 'qqfile';
		// If you want to use resume feature for uploader, specify the folder to save parts.
		$uploader->chunksFolder = config::AJAX_CHUNK_FOLDER;
		// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
		$result = $uploader->handleUpload(config::AJAX_UPLOAD_FOLDER);
		// To save the upload with a specified name, set the second parameter.
		// $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());
		// To return a name used for uploaded file you can use the following line.
		$result['uploadName'] = $uploader->getUploadName();
		if ($result['uploadName'])
			$this->processFile($result['uploadName'], $code);
		// Lets just save this in a universal holding pattern.
		header("Content-Type: text/plain");
		echo json_encode($result);
	}
}
$mod = new upload();
$mod->main();
