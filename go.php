<?php
include "acRunner.php";




// I include mine externally so you wont know them :P
include "mysql.php";

// Comment out the include above and uncomment the lines below to make this script work.
//define('MYSQL_HOST', 'localhost');
//define('MYSQL_USERNAME', 'username');
//define('MYSQL_PASSWORD', 'password');
//define('MYSQL_DATABASE', 'database');
//define('SERVER_ID', '1');


// I did this because mines in a subdir :)
$acServer = new acRunner('sh ../server.sh');

// You can do this if you extract the files in the same folder
// $acServer = new acRunner();


?>

