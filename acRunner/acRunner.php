<?php
/*~ acRunner.php
.-----------------------------------------------------------------------------.
|    Software: acRunner                                                       |
|     Version: 1.0.1                                                          |
|     Contact: via irc on irc.gamesurge.net as Fiz                            |
| IRC Support: #AssultCubePHP @ irc.gamesurge.net                             |
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
 * This is the main acRunner class that contains all the main features of the toolset
 * @package acRunner
 * @author Marc Seiler
 */
class acRunner
{
	public $cmd;
	public $cwd;
	public $db;
	public $pipes;
	public $acRunner_pid;
	public $server_pid;
	public $fp;
	public $read_error = true;
	public $read_output = true;


	/**
	* This is the constructor. Nothing more... nothing less...
	*
	* @param	string $cmd		Command to run. Default should be fine but override is provided encase you put acRunner in a subdir of the ac server.
	* @param	string $cwd		Current working Directory. This is for being able to move demos around and such.
	* @access public
	*/
	function __construct($cmd='sh ./server.sh', $cwd='')
	{
		if($cwd == '') { $cwd = __DIR__; }

		$this->cwd = $cwd;
		$this->cmd = $cmd;

		self::outputLog($cwd);


		declare(ticks = 1);
		$this->acRunner_pid = posix_getpid();
		
		$this->db = mysql_connect(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD);
		if (!$this->db) { die('Could not connect: ' . mysql_error()); }
		mysql_select_db(MYSQL_DATABASE);

		// Check for table and if it doesnt exist make it
		self::checkForDatabase();

		// Now lets empty the logs. Comment this out if you dont want logs cleared everytime the script is started.
		mysql_query("delete from `logs`");
		
		
		self::outputLog("acRunner PID: ".$this->acRunner_pid."\n");

		// This will keep track of how many times server is restart
		$restarts = 0;

		while(true)
		{
			self::outputLog("Starting the server");
			self::startServer();
			self::outputLog("Server died lets restart it!");
			$restarts++;
			// Something went wrong we should just stop and notify the owner
			if($restarts > 10) { break; }
		}
		$this->closeUp();



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
		mysql_query("CREATE TABLE IF NOT EXISTS `logs` (`id` bigint(11) NOT NULL AUTO_INCREMENT, `sid` int(11) NOT NULL, `log` longtext NOT NULL, `time` int(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");
	}


	/**
	* Starts the server and runs it until it dies
	*
	* @access public
	*/
	public function startServer()
	{

		$this->fp = proc_open($this->cmd,
		  array(
		    array("pipe","r"),
		    array("pipe","w"),
		    array("pipe","w")
		  ),
		  $this->pipes);


		$status = proc_get_status($this->fp);
		$this->server_pid = $status['pid'];

		self::outputLog("Server PID: ".$this->server_pid);

		pcntl_signal(SIGUSR1, array($this, 'closeUp'));
		pcntl_signal(SIGTERM, array($this, 'closeUp'));
		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);

		while ($this->read_error != false or $this->read_output != false)
		{


			
			//
			// This num_changed_streams if/else was suggested by Billy{BoB} thanks :D
			//
			$read   = array($this->pipes[1], $this->pipes[2]);
			$write  = NULL;
			$except = NULL;
			if (false === ($num_changed_streams = stream_select($read, $write, $except, 3000)))
			{
				// Dont really do anything here unless you want to its idle time.
			}
			elseif ($num_changed_streams > 0)
			{
				// Check if the server is still running and if not breaks out of the loop so it can be restarted
				$stat=proc_get_status($this->fp);
				if($stat['running']==FALSE) { break; }
				
				// Gather new data
				$buffer = trim(fgets($this->pipes[1], 4096));
				$err = trim(fgets($this->pipes[2], 4096));

				// Not doing anything with errors right now but possible for future
				if($err != '') { self::outputLog("Error: ".$err); }

				// Process buffer
				if($buffer != '')
				{
					mysql_query("insert into `logs` set `sid` = '".SERVER_ID."', `log` = '".addslashes($buffer)."', `time` = '".time()."'") or die(mysql_error());
					self::process($buffer);
					self::outputLog($buffer);
				}

			}
		}

		// While Loop was exited lets make sure everything is closed.
		$status = proc_get_status($this->fp);
		posix_kill($status['pid'], SIGTERM);
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

		// Catch Connections
		if(preg_match("/logged in \(/i", $log) || preg_match("/logged in using/i", $log))
		{
			$e = explode("]", substr($log, 1));
			$ip = $e[0];
			$e = explode("logged in", $e[1]);
			$name = trim($e[0]);
			
			// havent decided what to do with this.
		}
		// Catch Disconnects
		if(preg_match("/disconnected client/i", $log))
		{
			$e = explode("] disconnected client", substr($log, 1));
			$ip = $e[0];
			$e = explode(" cn ", $e[1]);
			$name = trim($e[0]);
			
			// havent decided what to do with this.
		}


		// Catch suicides



		// Catch Frags




		// Catch New Games




	}


	/**
	* Spits out a console log of whatever is in $log
	*
	* @param	string $log		The log line to output
	*
	* @access public
	*/
	public function outputLog($log)
	{
		echo date("[m/d/Y H:i:s] ").$log."\n";
	}


	/**
	* Old clean up function. Not sure it will be needed soon
	*
	* @access public
	*/
	public function closeUp()
	{
		echo "done!\nClosing Mysql..";
		mysql_close($this->db);
		echo "done!\n";
		die();
	}



}


?>