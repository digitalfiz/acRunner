<?php
include "acRunner/acRunner.php";

//This is an example file


// I include mine externally so you wont know them :P
include "mysql.php";

// Comment out the include above and uncomment the lines below to make this script work.
//define('MYSQL_HOST', 'localhost');
//define('MYSQL_PREFIX, 'acRunner');
//define('MYSQL_USERNAME', 'username');
//define('MYSQL_PASSWORD', 'password');
//define('MYSQL_DATABASE', 'database');
//define('SERVER_ID', '1');


// DO NOT CHANGE ANYTHING BELOW THIS LINE UNLESS YOU WANT TO :P


define('VERSION', '1.1'); // Simply for archival purposes and makes it accessible in the database for any guis you want to make/use

$acServer = new acRunner();


?>