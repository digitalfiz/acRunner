<?php
/*~ acRunner.php
.-----------------------------------------------------------------------------.
|    Software: acRunner                                                       |
|     Version: 1.0.2                                                          |
|     Contact: via irc on irc.gamesurge.net as Fiz                            |
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

		// Required by php for some reason
		declare(ticks = 1);
		$this->acRunner_pid = posix_getpid();

		// Lets write it to a file for other scripts to use
		$fp = fopen($this->cwd."/server.pid", "w");
		fputs($fp, $this->acRunner_pid);
		fclose($fp);
		
		
		
		// Lets include the includes...
		$d = dir($this->cwd."/includes");
		while (false !== ($entry = $d->read()))
		{
			if(preg_match("/\.php/i", $entry))
			{
				include $this->cwd."/includes/".$entry;
			}
		}
		$d->close();
		
		// Lets include the modules...
		$d = dir($this->cwd."/modules");
		while (false !== ($entry = $d->read()))
		{
			if(preg_match("/\.php/i", $entry))
			{
				include $this->cwd."/modules/".$entry;
			}
		}
		$d->close();
		
		run_hook('init');
		
	
		
		outputLog("acRunner PID: ".$this->acRunner_pid."\n");

		// This will keep track of how many times server is restart
		$restarts = 0;

		while(true)
		{
			outputLog("Starting the server");
			self::startServer();
			outputLog("Server died lets restart it!");
			$restarts++;
			// Something went wrong we should just stop and notify the owner
			if($restarts > 10) { break; }
		}
		$this->closeUp();



	}


	/**
	* Starts the server and runs it until it dies
	*
	* @access public
	*/
	public function startServer()
	{
		run_hook('before_start');

		$this->fp = proc_open($this->cmd,
		  array(
		    array("pipe","r"),
		    array("pipe","w"),
		    array("pipe","w")
		  ),
		  $this->pipes);


		$status = proc_get_status($this->fp);
		$this->server_pid = $status['pid'];

		outputLog("Server PID: ".$this->server_pid);

		pcntl_signal(SIGUSR1, array($this, 'closeUp'));
		pcntl_signal(SIGTERM, array($this, 'closeUp'));
		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);
		
		run_hook('after_start', $this);

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
				if($err != '') { outputLog("Error: ".$err); }

				// Process buffer
				if($buffer != '')
				{		
					preg_match("/^(\w+) (\d+) (\d+)\:(\d+)\:(\d+) (.*?)$/i", $buffer, $m);
					$buffer = $m[6];
					run_hook('buffer_loop', $buffer);
					outputLog($buffer);
				}

			}
		}

		// While Loop was exited lets make sure everything is closed.
		$status = proc_get_status($this->fp);
		posix_kill($status['pid'], SIGTERM);
	}


	/**
	* Old clean up function.
	*
	* @access public
	*/
	public function closeUp()
	{
		$status = proc_get_status($this->fp);
		posix_kill($status['pid'], SIGTERM);
		echo "done!\n";
		die();
	}



}


?>