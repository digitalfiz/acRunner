<?php
/*~ Database.php
.-----------------------------------------------------------------------------.
|    Software: Database - database connector for acRunner                     |
|     Version: 1.0                                                            |
|     Contact: via irc on irc.gamesurge.net as YMH|Fiz or just Fiz            |
| IRC Support: #acRunner @ irc.gamesurge.net                                  |
| --------------------------------------------------------------------------- |
|    Author: Marc Seiler (project admininistrator)                            |
| Copyright (c) 20010, Marc Seiler. All Rights Reserved.                      |
| --------------------------------------------------------------------------- |
|   License: Distributed under the Lesser General Public License (LGPL)       |
|            http://www.gnu.org/copyleft/lesser.html                          |
| This program is distributed in the hope that it will be useful - WITHOUT    |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or       |
| FITNESS FOR A PARTICULAR PURPOSE.                                           |
| --------------------------------------------------------------------------- |
| I offer a number of paid services (digital-focus.us):                       |
| - Web Hosting on highly optimized fast and secure servers                   |
| - Technology Consulting                                                     |
| - Oursourcing (highly qualified programmers and graphic designers)          |
'-----------------------------------------------------------------------------'
 */





$this->db = new Database();
add_hook('init', array($this->db, 'init'));


class Database
{
	public $dbprefix;
	/**
	* This is the constructor. Nothing more... nothing less...
	*
	* @access public
	*/
	function __construct() { }


	public function init()
	{
		outputLog("Connecting to the database...");
		$this->db = mysql_connect(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD);
		if (!$this->db) { die('Could not connect: ' . mysql_error()); }
		mysql_select_db(MYSQL_DATABASE);
	}

	public function dbprefix() { return "acRunner".SERVER_ID."_"; }

	/**
	* Clean up a string
	*
	* @param	string $log			the log line to process
	*
	* @access public
	*/
	public function cleanString($string)
	{
		$string = stripslashes($string);
		$string = mysql_real_escape_string($string);
		return $string;
	}


	/**
	* MySQL query function provided with built in error reporting for easy of use.
	*
	* @param	string $sql			sql to query
	*
	* @access public
	*/
	public function query($sql)
	{
		$results = mysql_query($sql);
		if (mysql_error())
		{
			$error = mysql_error();
			
			outputLog("MySQL Error: ");
			outputLog("	     Query: ".$sql);
			outputLog("	     Error: ".$error);
			
			
			if(preg_match('/server has gone away/i', $error))
			{
				Database::init();
			}
			
			
		}
		return $results;
	}


	public function logEvent($type, $subject, $action, $ip = '')
	{
		Database::query("INSERT INTO `".Database::dbprefix()."event_log` set `type` = '".Database::cleanString($type)."', `subject` = '".Database::cleanString($subject)."', `action` = '".Database::cleanString($action)."', `ip` = '".$this->cleanString($ip)."', `time` = '".time()."'");	
	}



}


?>