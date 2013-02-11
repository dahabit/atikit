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
 * @class config
 */
class config
{
	const APP_NAME = 'Tikit v1.0';
	const APP_NAMESPACE = 'tikit';
	const LOGIN_URL = 'login/';
	const LOG_FILE = "/tmp/tikit.log";
		
	// Database Configuration
	static public $DB_HOST = "localhost";
	static public $DB_USER = "tikit";
	static public $DB_PASS = "jus7zetu";
	static public $DB_DB = "tikit";
	static public $USE_SLAVES = false;
	static public $dbSlaves = array();
	static public $useMasterAsSlave = true;  
	
	// Memcache Configuration	
	static public $USE_MEMCACHE = true;
	static public $memcache_servers = array("localhost");
	static public $memcache_prefix = "tikit";
    static public $memcache_cachetime = 120;

    // AJAX Uploader Config
    const AJAX_CHUNK_FOLDER = '/tmp/';
    const AJAX_UPLOAD_FOLDER = '/tmp/';
}