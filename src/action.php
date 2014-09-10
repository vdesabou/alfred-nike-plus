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
	
	updateLibrary(15);
	return;
}else if ($other_action == "credentials") {
  
	$command_output = exec("Authenticate.app/Contents/MacOS/Authenticate 2>&1");
	return;	
}else if ($other_action == "enable_use_miles") {
	$setSettings = "update settings set use_miles=1";
	$dbfile = $w->data() . "/settings.db";
	exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	displayNotificationWithArtwork("Miles is now used",'./images/check.png');
	return;
} else if ($other_action == "disable_use_miles") {
	$setSettings = "update settings set use_miles=0";
	$dbfile = $w->data() . "/settings.db";
	exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	displayNotificationWithArtwork("KM is now used",'./images/uncheck.png');
	return;
}else if ($other_action == "check_for_update") {
	if(! $w->internet()) {
		displayNotificationWithArtwork("Error: No internet connection",'./images/warning.png');
		return;
	}
	
	$dbfile = $w->data() . '/settings.db';
	
	try {
		$dbsettings = new PDO("sqlite:$dbfile","","",array(PDO::ATTR_PERSISTENT => true));
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		handleDbIssuePdo($dbsettings);
		$dbsettings=null;
		return;
	}
	$check_results = checkForUpdate($w,0,$dbsettings);
	if($check_results != null && is_array($check_results)) {
		displayNotificationWithArtwork('New version ' . $check_results[0] . ' is available in Downloads directory ','./images/check_update.png');
	}
	else if ($check_results == null) {
			displayNotificationWithArtwork('No update available','./images/check_update.png');
	}

}

if($url != "") {
	exec("open \"" . $url . "\""); 
}			
			
?>
