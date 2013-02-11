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
 * @traid c3db
 */
trait c3db
{
	
	/**
	 * Initialize MySQLi, and Memcache This is the first method called by the
	 * application. It checks for the existence of a valid database connection
	 * and if memcache exists on the hosts we specified. If the mysql host
	 * fails, the application will fail. If a memcache host does not respond,
	 * memcache will be disabled for this session.
	 *
	 * @return void
	 */
	private function initDB ()
	{
		// use master, slave1, slave2, etc.
		// Step 1: Bring up Master. 
		$this->facebookQuery = false;
		$this->db = mysqli_init() or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: init");
		mysqli_options($this->db, MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1') or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Command");
		mysqli_options($this->db, MYSQLI_OPT_CONNECT_TIMEOUT, 5) or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Timeout");
		mysqli_real_connect($this->db, config::$DB_HOST, config::$DB_USER, config::$DB_PASS, config::$DB_DB) or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Connect");
		// Master is started. Lets start up any slaves.
		if (config::$USE_SLAVES == true)
		{
			$sc = 0;
			foreach(config::$dbSlaves AS $slave)
			{
				$sc++;
				$obj = "db". $sc;
				$this->{$obj} = mysqli_init() or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Slave_init");
				mysqli_options($this->{$obj}, MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1') or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Slave_Command");
				mysqli_options($this->{$obj}, MYSQLI_OPT_CONNECT_TIMEOUT, 5) or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Slave_Timeout");
				mysqli_real_connect($this->{$obj}, config::$DB_HOST, config::$DB_USER, config::$DB_PASS, config::$DB_DB) or $this->deadError(config::APP_NAME." is currently undergoing maintenance. Please check back. Stage: Slave_Connect");
			}
			$this->slaveCount = $sc;			
		}
		
	
	
	} // launchDBMemcache
	
	public function initMemcache()
	{
		try
		{
			$this->memcache = new Memcache();
			$this->memcacheDisabled = FALSE;
		} catch (Exception $e)
		{
		
			$this->memcacheDisabled = TRUE;
		}
		foreach (config::$memcache_servers as $server)
			$this->memcache->addServer("$server", 11211);
		
		//$this->getMemcacheUpdateTables(); // Check memcache force tables
		$this->cacheTime = 120;
		
	}	
	
	/**
	 * Returns a single table field value This is a shorthand function you can
	 * use to get single values from fields. Useful for looking up names by id,
	 * etc. 
	 * <code> $value = $this->returnFieldFromTable("username", "users",
	 * "id=1"); </code>
	 *
	 * @param $field string
	 *       	 you are querying
	 * @param $table string
	 *       	 query.
	 * @param $where string
	 *       	 data
	 * @param $cache string
	 *       	 query result?
	 * @return string of field
	 */
	public function returnFieldFromTable ($field, $table, $where, $cache = FALSE)
	{
		$q = "SELECT `{$field}` FROM {$table} WHERE {$where}";
	
		$result = $this->query($q, $cache);
		if ($result)
			return ($result[0][$field]);
	
	} // returnFieldFromTable
	
	/**
	 * Returns the count from the query specified This is a shorthand function
	 * you can use to get the count based on your where statement. <code> $value
	 * = $this->returnCountFromTable("", "users", "id=1"); </code>
	 *
	 * @param $table string
	 *       	 query.
	 * @param $where string
	 *       	 data
	 * @param $cache string
	 *       	 query result?
	 * @return int count returned.
	 */
	public function returnCountFromTable ($table, $where, $cache = FALSE)
	{
		$q = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
		$result = $this->query($q, $cache);
		return ($result[0]['count']);
	
	} // returnCountFromTable
	
	public function isRead($q)
	{
		$data = FALSE;
		if (preg_match("/SELECT/", $q))
				$data = TRUE;
			
		return ($data);
	}
	
	public function dbSave($table, $fields, &$content)
	{
	    $id = (isset($content['id'])) ? $content['id'] : false;
	    // if id is set, then update.
	    if (isset($content['cache']))
	        $cache = true;
	    else $cache = false;
	    
	    $fieldList = null;
	    foreach ($fields AS $field)
	        if ($field != 'id')
	            $fieldList .= "$field = '$content[$field]', ";
	    $fieldList = substr($fieldList, 0, -2);
	    if (!$id)
            $q = "INSERT into $table SET $fieldList";
	    else
	        $q = "UPDATE $table SET $fieldList WHERE id='$id'";

	 
	  
	    $this->query($q, $cache, $cache);
	    if ($this->insert_id)
	    	return ($this->insert_id);
	    else return $id;
	}
		
		
	
	
	/**
	 * Master MySQL Query Routine -- DO NOT UPDATE THIS WITHOUT PRIOR APPROVAL
	 * -- This function is used for all queries for both MySQL and Memcache and
	 * audits user changes if needed. <code> $x = $this->query("SELECT * from
	 * users", "User selected all records"); foreach ($x AS $row) {
	 * print($row["data"]); } </code>
	 *
	 * @param $q string
	 * @param $audit string
	 *       	 to save.
	 * @param $cache boolean
	 *       	 result be cached?
	 * @param $forceCacheUpdate boolean
	 *       	 the cache be updated?
	 * @return array an array of results from the query.
	 */
	public function query ($q, $cache = FALSE, $forceCacheUpdate = FALSE)
	{
		if (!$cache) $this->queryStore[] = $q;
		$readable = $this->isRead($q);
		if ($readable)
			$result = $this->readQuery($q, $cache);
		else
			$result = $this->writeQuery($q, $forceCacheUpdate);
		//$this->cacheTime = config::$memcache_cachetime;
		$this->facebookQuery = false;
		return $result;
	}
	
	public function getMCKey($q)
	{
		$key = md5(config::$memcache_prefix . $q);
		$mcresult = $this->memcache->get($key);
		if ($mcresult)
		{
			$mcresult = unserialize($mcresult);
			return ($mcresult);
		}
		else 
			return false;
	}

	public function setMCKey($q, $data)
	{ 
		
		$data = serialize($data);
		$key = md5(config::$memcache_prefix . $q);
		$this->memcache->set($key, $data, MEMCACHE_COMPRESSED, $this->cacheTime);
	}
	
	public function checkForce($q)
	{
		// SELECT blah FROM blah
		// So that means table is x[3]
		if (!isset($this->memcacheTableQueue)) return false;
		$x = explode(" ", $q);
		$table = $x[3];
		if (isset($this->memCacheTableQueue))
			if (is_array($this->memCacheTableQueue))
			if (in_array($table, $this->memCacheTableQueue))
				return true;

		else
		 return false;
	}
	
	public function getReadObject()
	{
		// Lets use a random server. 
		$rand = array();
		
		if (config::$USE_SLAVES == false)
			return ("db"); // db is master
		
		if (config::$useMasterAsSlave)
			$rand[] = "db";
		for ($i = 1; $i <= $this->slaveCount; $i++)
			$rand[] = "db". $i;
		
		
		shuffle($rand);
		return $rand[0];

	}
	
	
	public function actualDBRead($q)
	{
		// Now we need to know if this is a facebook query or a database query, because we will cache it the same.
		
		if ($this->facebookQuery == true)
		{
			$data = $this->fbQuery($q);
			$this->fbQC++;	
		}
		else
		{
			$dbvar = $this->getReadObject();
			
			$data = array();
			$result = $this->{$dbvar}->query($q);
			if (! $result)
				syslog(LOG_INFO, "Database Error: {$this->{$dbvar}->error} for executed query: $q");
				
			if (!isset($this->{$dbvar})) return null;
			if ($this->{$dbvar}->affected_rows == 0)	return (NULL);
			while ($row = $result->fetch_array())
			{
				foreach ($row as $xkey => $val)
					$row[$xkey] = stripslashes($val);
				$data[] = $row;
			}
			$this->queries++;		
		}		
			return($data);
	}
	
	
	public function readQuery($q, $cache)
	{
		
		// Are we using cache? If so let's ask memcache first.
		if (!$cache)
		{
			
			return($this->actualDBRead($q));
		} 
		// From this point on we can assume that caching is being utilized.
		
		// We have to check our force tables to see if this is in there. 
		
		
		$hasToForce = $this->checkForce($q);
		if (!$hasToForce)
		{
			$mcresult = $this->getMCKey($q); 	
			if ($mcresult) 
				{
					$this->cacheHits ++;
					return $mcresult;
				} // found memcache result we can now exit.
		}
		
		// If we are here, then we obviously didn't have the result in cache. 
		
		$data = $this->actualDBRead($q);
		// $data now has our data. We need to store this in memcache. 
		$this->setMCKey($q, $data);
		return($data); // We set the key, lets return it and go.
	}
	
	
	public function writeQuery($q, $updateCache = false)
	{
		// Is we are forcing an update we need to tell all cached read queries that they need to update for a particular table.
		if ($updateCache)
		{
			$x = explode(" ", $q);
			if (preg_match("/UPDATE/", $q))
				$table = $x[1];
			else
				if (preg_match("/INSERT/", $q))
				$table = $x[2];
			else
				if (preg_match("/REPLACE/", $q))
				$table = $x[2];
			 $this->addToMemcacheTable($table);
		}

		
		$result = $this->db->query($q);
		if (!$result) $this->log("Database Error: " . $this->db->error . " for executed query: $q");
		$this->insert_id = $this->db->insert_id;
		return $result;
	}
		
	public function addToMemcacheTable ($table)
	{
		$tables = $this->returnFieldFromTable("tables", "memcache", "1=1");
		$tables = unserialize($tables);
		if (! $tables)
			$tables = array();
		if (! in_array($table, $tables))
		{
			$tables[] = $table;
			$data = serialize($tables);
			if ($table != "*")
				$this->query("UPDATE memcache SET tables='$data'");
		}
	
	}

	
	public function getMemcacheUpdateTables ()
	{
		if ($this->memcacheTableJobRan)
			return ($this->memcacheTableQueue);
	
		$this->memcacheTableJobRan = true;
		if (! isset($this->memcacheTableQueue))
		{
			$tables = $this->returnFieldFromTable("tables", "memcache", "1=1");
			$tables = unserialize($tables);
			if (is_array($tables))
			{
				$this->memcacheTableQueue = $tables;
				$this->query("UPDATE memcache SET tables = ''"); // set the variable and then dump the table data
			}
			else
				$this->memcacheTableQueue = null;
		} else
			return ($this->memcacheTableQueue);
	}
	
	public function closeDatabases()
	{
	    mysqli_close($this->db);
	    $sc = 0;
	    if (config::$USE_SLAVES == true)
	    {
	    	for ($i = 1; $i <= $this->slaveCount; $i++)
	    	$obj = "db".$i;
	    	@mysql_close($this->{$obj});
	    }
	    if (! $this->memcacheDisabled)
	        $this->memcache->close();
	       
	}
	
}