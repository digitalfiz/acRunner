<?php
/*~ ScoreKeeper.php
.-----------------------------------------------------------------------------.
|    Software: ScoreKeeper - keeps score for acRunner                         |
|     Version: 1.0.0                                                          |
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


/**
 * This is the ScoreKeeper class that will be responsible for manipulating the database to keep track of scores. Both realtime and long running.
 * @package acRunner
 * @author Marc Seiler
 */
class ScoreKeeper
{
	public $dbprefix;
	/**
	* This is the constructor. Nothing more... nothing less...
	*
	* @access public
	*/
	function __construct()
	{
		$this->db = mysql_connect(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD);
		if (!$this->db) { die('Could not connect: ' . mysql_error()); }
		mysql_select_db(MYSQL_DATABASE);

		
		if(MYSQL_PREFIX == "" || MYSQL_PREFIX == 'MYSQL_PREFIX') { $this->dbprefix = "acRunner_"; }



		// Check for table and if it doesnt exist make it
		self::checkForDatabase();

		// Now lets empty the logs. Comment this out if you dont want logs cleared everytime the script is started.
		self::mysqlQuery("delete from `".$this->dbprefix."logs`");
		self::mysqlQuery("delete from `".$this->dbprefix."current_game`");
	}


	/**
	* Checks the database for needed tables
	*
	* @access public
	*/
	public function checkForDatabase()
	{
		// Creating a function/method for creating a database is probably overkill but
		// I did it for easy of use and extensibility. All your checking for proper database
		// configuration can be done in here now.

		// general log file, just used for now to examine logs
		self::mysqlQuery("CREATE TABLE IF NOT EXISTS `".$this->dbprefix."logs` (`id` bigint(11) NOT NULL AUTO_INCREMENT, `sid` int(11) NOT NULL, `log` longtext NOT NULL, `time` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");


		// Realtime game stats
		self::mysqlQuery("CREATE TABLE IF NOT EXISTS `".$this->dbprefix."current_game` (`cn` INT NOT NULL, `player` VARCHAR(15) NOT NULL, `team` VARCHAR(4) NOT NULL, `flags` INT NOT NULL, `score` INT NOT NULL, `frags` INT NOT NULL, `deaths` INT NOT NULL, `tks` INT NOT NULL, `ping` INT NOT NULL, `role` VARCHAR(6) NOT NULL, `host` VARCHAR(50) NOT NULL, `active` int(11) NOT NULL DEFAULT '1') ENGINE = MyISAM;");


		self::mysqlQuery("CREATE TABLE `".$this->dbprefix."options` (`id` INT NOT NULL, `name` VARCHAR(20) NOT NULL, `value` VARCHAR(255) NOT NULL) ENGINE = MyISAM;");
		self::setOption("current_map", "");
		self::setOption("current_mode", "");

		// Long run stats table
		self::mysqlQuery("CREATE TABLE `".$this->dbprefix."player_stats` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `player` VARCHAR(15) NOT NULL, `frags` INT NOT NULL, `slashes` INT NOT NULL, `headshots` INT NOT NULL, `splatters` INT NOT NULL, `gibs` INT NOT NULL, `flags` INT NOT NULL, `tks` INT NOT NULL, `suicides` INT NOT NULL, `deaths` INT NOT NULL) ENGINE = MyISAM;");

		// chat log table
		self::mysqlQuery("CREATE TABLE `".$this->dbprefix."chat` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `player` VARCHAR(15) NOT NULL, `destination` VARCHAR(5) NOT NULL, `chat` VARCHAR(255) NOT NULL, `ip` VARCHAR(30) NOT NULL, `time` INT NOT NULL) ENGINE = MyISAM;");

	}


	/**
	* Options function for setting generic functions like current_game and such for later usage
	*
	* @param	string $option			option to set
	* @param	string $value			value to set the option
	*
	* @access public
	*/
	public function setOption($option, $value)
	{
		// lets check if it exists already
		$result = self::mysqlQuery("select * from `".$this->dbprefix."options` where `name` = '".$option."'");
		$res = mysql_fetch_assoc($result);

		// if its blank we insert it
		if($res['name'] == '') { self::mysqlQuery("insert into `".$this->dbprefix."options` set `name` = '".$option."', `value` = '".$value."'"); }

		// if its not blank we update it
		if($res['name'] != '') { self::mysqlQuery("update `".$this->dbprefix."options` set `value` = '".$value."' where `name` = '".$option."'"); }
	}


	/**
	* Function to clean empty entries from an arrya
	*
	* @param	string $array			array to clean up
	*
	* @access public
	*/
	public function cleanArray($array)
	{
		foreach($array as $k=>$a)
		{
			if($a != "") { $new[$k] = $a; }
		}
		return $new;
	}


	/**
	* Function to clean out older chat logs. About an hour is fine no?
	*
	* @access public
	*/
	public function cleanChat()
	{
		// Now lets keep the chat cleaned up. Anything older then an hour is emptied out.
		$time = time() - (60 * 60);
		self::mysqlQuery("delete from `".$this->dbprefix."chat` where `time` < ".$time."");
	}


	/**
	* MySQL query function provided with built in error reporting for easy of use.
	*
	* @param	string $sql			sql to query
	*
	* @access public
	*/
	public function mysqlQuery($sql)
	{
		$results = mysql_query($sql);
		if (mysql_error())
		{
			acRunner::outputLog("MySQL Error: ");
			acRunner::outputLog("	Query: ".$sql);
			acRunner::outputLog("	Error: ".mysql_error());
		}
		return $results;
	}


	/**
	* Processes the log line given
	*
	* @param	string $log			the log line to process
	*
	* @access public
	*/
	public function process($log)
	{
		// You might want to comment this out if you dont have a way to clear out the log. If can make a rather large table relatively fast
		//self::mysqlQuery("insert into `".$this->dbprefix."logs` set `sid` = '".SERVER_ID."', `log` = '".addslashes($log)."', `time` = '".time()."'") or die(mysql_error());
		// Commented out because its rather useless. You can uncomment it if you want.

		// Catch Connections
		if(preg_match("/logged in \(/i", $log) || preg_match("/logged in using/i", $log))
		{
			$e = explode("]", substr($log, 1), 2);
			$ip = $e[0];
			$e = explode("logged in", $e[1], 2);
			$name = trim($e[0]);



			// Lets check if they've already been playing this game
			$result = self::mysqlQuery("select * from `".$this->dbprefix."current_game` where `player` = '".$name."'");
			$res = mysql_fetch_assoc($result);

			// If blank then new player
			if($res['player'] == '')
			{
				self::mysqlQuery("insert into `".$this->dbprefix."current_game` set `player` = '".$name."', `host` = '".$ip."', `role` = 'normal'");
			}
			// if not blank its a returning player
			if($res['player'] != '')
			{
				self::mysqlQuery("update `".$this->dbprefix."current_game` set `active` = '1', `host` = '".$ip."' where `player` = '".$name."'");
			}

			// Now lets do the same for the player stats table
			$result = self::mysqlQuery("select * from `".$this->dbprefix."player_stats` where `player` = '".$name."'");
			$res = mysql_fetch_assoc($result);
			if($res['player'] == '') { self::mysqlQuery("insert into `".$this->dbprefix."player_stats` set `player` = '".$name."'"); }

		}
		// Catch Disconnects
		if(preg_match("/disconnected client/i", $log))
		{
			$e = explode("] disconnected client", substr($log, 1), 2);
			$ip = $e[0];
			$e = explode(" cn ", $e[1], 2);
			$name = trim($e[0]);
			
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `active` = '0' where `player` = '".$name."'");
		}

		// Catch admins
		if(preg_match("/set role of player (.*?) to admin/i", $log, $m))
		{
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `role` = 'normal'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `role` = 'admin' where `player` = '".$m[1]."'");
		}
		if(preg_match("/set role of player (.*?) to normal player/i", $log, $m))
		{
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `role` = 'normal' where `player` = '".$m[1]."'");
		}
		
		
		// Catch New Games
		if(preg_match("/^Game start: (.*?)$/", $log, $m))
		{
			// Get Mode
			list($mode, $rest) = explode(" on ", $m[1], 2);
			self::setOption("current_mode", $mode);
			
			$e = explode(",", $rest);
			
			// Set Map
			$map = trim($e[0]);
			self::setOption("current_map", $map);
			
			// Since its a new game we clear scores
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = 0");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `score` = 0");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = 0");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `tks` = 0");

			// Also for cleanliness lets clear out inactive players.
			self::mysqlQuery("delete from `".$this->dbprefix."current_game` where `active` = 0");


			// We do this to make sure that it gets set. had some instances where if a game started over 
			//right at the new round part it would stay in that mode
			$this->newround = FALSE;
		}


		// Catch New Round
		if(preg_match("/^Game status: (.*?)$/", $log, $m)) { $this->newround = TRUE; }
		// unset new round crap
		if(preg_match("/Team  CLA:/", $log, $m) && $this->newround == TRUE) { $this->newround = FALSE; }


		if($this->newround == TRUE)
		{
			$ex = explode(" ", $log);
			// Now lets clean the empty spaces, very important!
			$ex = self::cleanArray($ex);

			$number = array_shift($ex);
			$name = array_shift($ex);
			$team = array_shift($ex);

			// if this is a number it must be their cn
			if(is_numeric($number)) { self::mysqlQuery("update `".$this->dbprefix."current_game` set `cn` = '".$number."', `team` = '".$team."' where `player` = '".$name."'"); }



		}


		//********************************************************************************//
		//********************************************************************************//
		//
		//					Scoring Below
		//
		//********************************************************************************//
		//********************************************************************************//


		// Catch suicides
		if(preg_match("/^\[(.*?)\] (.*?) suicided$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`-1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[2]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `suicides` = `suicides`+1 where `player` = '".$m[2]."'");
		}


		//
		// None Teammate kills
		//

		// Catch Frags
		if(preg_match("/^\[(.*?)\] (.*?) fragged (.*?)$/", $log, $m) && !preg_match("/fragged teammate/", $log))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");
		}
		// Catch slashes
		if(preg_match("/^\[(.*?)\] (.*?) slashed (.*?)$/", $log, $m) && !preg_match("/slashed teammate/", $log))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+2 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `slashes` = `slashes`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");
		}
		// Catch headshots
		if(preg_match("/^\[(.*?)\] (.*?) headshot (.*?)$/", $log, $m) && !preg_match("/headshot teammate/", $log))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+2 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `headshots` = `headshots`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");
		}
		// Catch splatters
		if(preg_match("/^\[(.*?)\] (.*?) splattered (.*?)$/", $log, $m) && !preg_match("/splattered teammate/", $log))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `splatters` = `splatters`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");
		}
		// Catch gibs
		if(preg_match("/^\[(.*?)\] (.*?) gibbed (.*?)$/", $log, $m) && !preg_match("/gibbed teammate/", $log))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `gibs` = `gibs`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");
		}



		//
		// Teammate kills
		//

		// Catch Frags
		if(preg_match("/^\[(.*?)\] (.*?) fragged his teammate (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`-1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `tks` = `tks`+1 where `player` = '".$m[2]."'");
		}
		// Catch slashes
		if(preg_match("/^\[(.*?)\] (.*?) slashed his teammate (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`-1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `tks` = `tks`+1 where `player` = '".$m[2]."'");
		}
		// Catch headshots
		if(preg_match("/^\[(.*?)\] (.*?) headshot his teammate (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`-1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `tks` = `tks`+1 where `player` = '".$m[2]."'");
		}
		// Catch splatters
		if(preg_match("/^\[(.*?)\] (.*?) splattered his teammate (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `tks` = `tks`+1 where `player` = '".$m[2]."'");
		}
		// Catch gibs
		if(preg_match("/^\[(.*?)\] (.*?) gibbed his teammate (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `frags` = `frags`+1 where `player` = '".$m[2]."'");
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `deaths` = `deaths`+1 where `player` = '".$m[3]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `tks` = `tks`+1 where `player` = '".$m[2]."'");
		}

		
		//************************ FLAGS **********************************//

		// Catch KTF Scores
		if(preg_match("/^\[(.*?)\] (.*?) scored, carrying for (.*?) seconds, new score (.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `flags` = `flags`+1 where `player` = '".$m[2]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `flags` = `flags`+1 where `player` = '".$m[2]."'");
		}

		// Catch CTF Scores
		if(preg_match("/^\[(.*?)\] (.*?) scored with the flag for (.*?), new score(.*?)$/", $log, $m))
		{
			// Realtime stuff
			self::mysqlQuery("update `".$this->dbprefix."current_game` set `flags` = `flags`+1 where `player` = '".$m[2]."'");

			// Long Running Stats stuff
			self::mysqlQuery("update `".$this->dbprefix."player_stats` set `flags` = `flags`+1 where `player` = '".$m[2]."'");
		}


		
		//************************ SPEECH **********************************//

		// Catch speech to all
		if(preg_match("/^\[(.*?)\] (.*?) says\: \'(.*?)\'$/", $log, $m))
		{
			self::mysqlQuery("insert into `".$this->dbprefix."chat` set `player` = '".$m[2]."', `chat` = '".addslashes($m[3])."', `ip` = '".$m[1]."', `destination` = 'ALL', `time` = '".time()."'");
			self::cleanChat();
		}
		// Catch speech to RVSF
		if(preg_match("/^\[(.*?)\] (.*?) says to team RVSF\: \'(.*?)\'$/", $log, $m))
		{
			self::mysqlQuery("insert into `".$this->dbprefix."chat` set `player` = '".$m[2]."', `chat` = '".addslashes($m[3])."', `ip` = '".$m[1]."', `destination` = 'RVSF', `time` = '".time()."'");
			self::cleanChat();
		}
		// Catch speech to CLA
		if(preg_match("/^\[(.*?)\] (.*?) says to team CLA\: \'(.*?)\'$/", $log, $m))
		{
			self::mysqlQuery("insert into `".$this->dbprefix."chat` set `player` = '".$m[2]."', `chat` = '".addslashes($m[3])."', `ip` = '".$m[1]."', `destination` = 'CLA', `time` = '".time()."'");
			self::cleanChat();
		}



	}
}

?>