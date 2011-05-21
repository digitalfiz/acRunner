<?php




function outputLog($log)
{
		echo date("[m/d/Y H:i:s] ").$log."\n";
}


function add_hook($hook, $callback)
{
	$_SESSION['hooks'][$hook][] = $callback;
}

function run_hook($hook, $data = '')
{
	if(is_array($_SESSION['hooks'][$hook]))
	{
		foreach($_SESSION['hooks'][$hook] as $hook)
		{
			call_user_func($hook, $data);
		}
	}
}




?>