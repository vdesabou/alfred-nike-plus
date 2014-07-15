<?php

// Turn off all error reporting
error_reporting(0);

require('./src/functions.php');

// Load and use David Ferguson's Workflows.php class
require_once('./src/workflows.php');
$w = new Workflows('com.vdesabou.nike.plus');

displayNotificationWithArtwork("Update library was killed!",'E1E1B9A5-C03D-4072-B6D7-6F6CBBBC112F.png');
if ( file_exists($w->data() . "/update_library_in_progress") )
	unlink($w->data() . "/update_library_in_progress");

exec("kill -9 $(ps -efx | grep \"php\" | egrep \"update_|update.php\" | grep -v grep | awk '{print $2}')");    

?>