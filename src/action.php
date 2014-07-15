<?php

// Turn off all error reporting
//error_reporting(0);

require('./src/functions.php');

// Load and use David Ferguson's Workflows.php class
require_once('./src/workflows.php');
$w = new Workflows('com.vdesabou.nike.plus');

$query = $argv[1];


$arg = mb_unserialize($query);

//var_dump($arg);

$other_action = $arg[0];
$url = $arg[1];


if ($other_action == "update_library") {
	if(! $w->internet()) {
		displayNotificationWithArtwork("Error: No internet connection",'./images/warning.png');
		return;
	}
	
	updateLibrary(0);
	return;
}else if ($other_action == "get_latest_activities") {
	if(! $w->internet()) {
		displayNotificationWithArtwork("Error: No internet connection",'./images/warning.png');
		return;
	}
	
	updateLibrary(5);
	return;
}else if ($other_action == "credentials") {
  
	$command_output = exec("Authenticate.app/Contents/MacOS/Authenticate 2>&1");
	return;	
}

if($url != "") {
	exec("open \"" . $url . "\""); 
}			
			
?>
