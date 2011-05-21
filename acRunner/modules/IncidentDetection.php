<?php
/*~ IncidentDetection.php
.-----------------------------------------------------------------------------.
|    Software: IncidentDetection - Record incidents for acRunner              |
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





$this->incident = new IncidentDetection();
add_hook('init', array($this->incident, 'init'));
add_hook('buffer_loop', array($this->incident, 'process'));

class IncidentDetection
{
	public function init()
	{
		outputLog("Incident Detector starting...");
	}

	public function process()
	{
		// Coming soon!
	}

}




?>