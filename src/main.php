<?php

// Turn off all error reporting
//error_reporting(0);

require('./src/functions.php');


//$begin_time = computeTime();

// Load and use David Ferguson's Workflows.php class
require_once('./src/workflows.php');
$w = new Workflows('com.vdesabou.nike.plus');

$query = $argv[1];
# thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
$query = iconv('UTF-8-MAC', 'UTF-8', $query);

//
// check for library update in progress
if (file_exists($w->data() . '/update_library_in_progress')) {
	$in_progress_data = $w->read('update_library_in_progress');
	$words = explode('▹', $in_progress_data);

	$elapsed_time = time() - $words[3];

	if (startsWith($words[0],'Init'))
	{
		if($elapsed_time < 300) {
			$w->result(uniqid(), $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time) . ' : ' . floatToSquares(0), 'waiting for Nike Plus web site to return required data', './images/update.png', 'no', null, '');
		}
		else {
			$w->result(uniqid(), '', 'There is a problem, the initialization phase last more than 5 minutes', 'Follow the steps below:', './images/warning.png', 'no', null, '');

			$w->result(uniqid(), '', "Kill update library", "You can kill it by using nikeplus_kill_update command",  'E1E1B9A5-C03D-4072-B6D7-6F6CBBBC112F.png', 'no', null, '');

		}
	}
	else {
		$w->result(uniqid(), $w->data() . '/update_library_in_progress', $words[0] . ' update in progress since ' . beautifyTime($elapsed_time) . ' : '  . floatToSquares(intval($words[1]) / intval($words[2])), $words[1] . '/' . $words[2] . ' activities processed so far (if no progress, use spot_mini_kill_update command to stop it)', './images/update.png', 'no', null, '');
	}

	echo $w->toxml();
	return;
}

//
// Read settings from DB
//
$getSettings = 'select username,use_miles,last_check_update_time from settings';
$dbfile = $w->data() . '/settings.db';

try {
	$dbsettings = new PDO("sqlite:$dbfile","","",array(PDO::ATTR_PERSISTENT => true));
	$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbsettings->query("PRAGMA synchronous = OFF");
	$dbsettings->query("PRAGMA journal_mode = OFF");
	$dbsettings->query("PRAGMA temp_store = MEMORY");
	$dbsettings->query("PRAGMA count_changes = OFF");
	$dbsettings->query("PRAGMA PAGE_SIZE = 4096");
	$dbsettings->query("PRAGMA default_cache_size=700000");
	$dbsettings->query("PRAGMA cache_size=700000");
	$dbsettings->query("PRAGMA compile_options");
} catch (PDOException $e) {
	handleDbIssuePdo($dbsettings);
	$dbsettings=null;
	return;
}

try {
	$stmt = $dbsettings->prepare($getSettings);
	$settings = $stmt->execute();

} catch (PDOException $e) {
	if (file_exists($w->data() . '/settings.db')) {
		unlink($w->data() . '/settings.db');
	}
}

//
// Create settings.db with default values if needed
//
if (!file_exists($w->data() . '/settings.db')) {
	touch($w->data() . '/settings.db');
	try {
		$dbsettings = new PDO("sqlite:$dbfile","","",null);
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$dbsettings->exec("create table settings (username,use_miles boolean, last_check_update_time int)");
		$dbsettings->exec("insert into settings values (\"\",0,0)");

		$dbsettings->query("PRAGMA synchronous = OFF");
		$dbsettings->query("PRAGMA journal_mode = OFF");
		$dbsettings->query("PRAGMA temp_store = MEMORY");
		$dbsettings->query("PRAGMA count_changes = OFF");
		$dbsettings->query("PRAGMA PAGE_SIZE = 4096");
		$dbsettings->query("PRAGMA default_cache_size=700000");
		$dbsettings->query("PRAGMA cache_size=700000");
		$dbsettings->query("PRAGMA compile_options");

		$stmt = $dbsettings->prepare($getSettings);

		$w->result(uniqid(), '', 'Settings have been reset to default values', 'Please invoke again the workflow now to enjoy the Nike Plus workflow', './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;

	} catch (PDOException $e) {
		handleDbIssuePdo($dbsettings);
		return;
	}
}

try {
	$setting = $stmt->fetch();
}
catch (PDOException $e) {
	handleDbIssuePdo($dbsettings);
	return;
}
$username = $setting[0];
$use_miles = $setting[1];
$last_check_update_time = $setting[2];

$unit="km";
if($use_miles==1){
	$unit = "miles";
}
// check for correct configuration
if (file_exists($w->data() . '/library.db')) {

	$dbfile = $w->data() . '/library.db';

	try {
		$db = new PDO("sqlite:$dbfile","","",array(PDO::ATTR_PERSISTENT => true));

		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->query("PRAGMA synchronous = OFF");
		$db->query("PRAGMA journal_mode = OFF");
		$db->query("PRAGMA temp_store = MEMORY");
		$db->query("PRAGMA count_changes = OFF");
		$db->query("PRAGMA PAGE_SIZE = 4096");
		$db->query("PRAGMA default_cache_size=700000");
		$db->query("PRAGMA cache_size=700000");
		$db->query("PRAGMA compile_options");
	} catch (PDOException $e) {
		handleDbIssuePdo($db);
		return;
	}

}
else {
	$w->result(uniqid(), '', 'Workflow is not configured', '', './images/warning.png', 'no', null, '');
	
	$username = exec("Authenticate.app/Contents/MacOS/Authenticate -get username");
	if($username == "") {
		$w->result(uniqid(), serialize(array('credentials' /*other_action*/ ,'' /* url */)), "Set you Nike Plus credentials", "Your password will be stored safely in your keychain", '', 'yes', null, '');
		echo $w->toxml();		
	}

	if (!file_exists($w->data() . '/library.db')) {
		$w->result(uniqid(), serialize(array('update_library' /*other_action*/ ,'' /* url */)), 'Install library', "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
		echo $w->toxml();
	}
	return;
}

$check_results = checkForUpdate($w,$last_check_update_time,$dbsettings);
if($check_results != null && is_array($check_results))
{
	$w->result(uniqid(), '', 'New version ' . $check_results[0] . ' is available', $check_results[2], './images/' . $theme . '/' . 'info.png', 'no', null, '');
	$w->result(uniqid(), '', 'Please install the new version in Downloads directory', $check_results[1], 'fileicon:'.$check_results[1], 'no', null, '' );

	echo $w->toxml();
	return;
}

// set the default timezone to use.
date_default_timezone_set('UTC');

// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding('UTF-8');
if (mb_strlen($query) < 3 ||
	((substr_count($query, '▹') == 1) && (strpos('Settings▹', $query) !== false))
) {
	if (substr_count($query, '▹') == 0) {

		$getLifetime = 'select * from lifetime';
		try {
			$stmt = $db->prepare($getLifetime);

			$stmt->execute();
			$lifetime = $stmt->fetch();

		} catch (PDOException $e) {
			handleDbIssuePdo($db);
			return;
		}

		$averageDistance = $lifetime[0];
		
		
		$totalDistance = $use_miles ? round($lifetime[32]* 0.6213711922,0) : round($lifetime[32],0);
		
		if(!$use_miles) {
			if($lifetime[32] < 50 ) {
				$nikelevel='yellow';
			}else if($lifetime[32] < 250 ) {
				$nikelevel='orange';
			}else if($lifetime[32] < 1000 ) {
				$nikelevel='green';
			}else if($lifetime[32] < 2500 ) {
				$nikelevel='blue';
			}else if($lifetime[32] < 5000 ) {
				$nikelevel='purple';
			}else if($lifetime[32] < 15000 ) {
				$nikelevel='black';
			}else {
				$nikelevel='volte';
			}
		}
		else {
			if($lifetime[32] < 50 * 0.6213711922 ) {
				$nikelevel='yellow';
			}else if($lifetime[32] < 250* 0.6213711922 ) {
				$nikelevel='orange';
			}else if($lifetime[32] < 1000* 0.6213711922 ) {
				$nikelevel='green';
			}else if($lifetime[32] < 2500* 0.6213711922 ) {
				$nikelevel='blue';
			}else if($lifetime[32] < 5000* 0.6213711922 ) {
				$nikelevel='purple';
			}else if($lifetime[32] < 15000* 0.6213711922 ) {
				$nikelevel='black';
			}else {
				$nikelevel='volte';
			}			
		}

		if($lifetime[11] != 0) {
			$w->result(uniqid(), '', 'Total Distance: ' . $totalDistance . " " . $unit . " ● Total Runs: " . $lifetime[11] . " ● Total Duration: " . round($lifetime[40]/1000/60/60,0) . " hours", " Average Pace: " . calculatePace($lifetime[40],$lifetime[32],$use_miles) . " min/" . $unit . " ● Average Distance: " . round($totalDistance/$lifetime[11],1) . $unit . " ● Average Fuel: " . round($lifetime[38]/$lifetime[11],0), './images/' . $nikelevel . '.png', 'no', null, '');
		} 
		$totalDistanceTreadmill = $use_miles ? round($lifetime[0]* 0.6213711922,0) : round($lifetime[0],0);
		$totalDistanceBeach = $use_miles ? round($lifetime[1]* 0.6213711922,0) : round($lifetime[1],0);
		$totalDistanceRoad = $use_miles ? round($lifetime[2]* 0.6213711922,0) : round($lifetime[2],0);
		$totalDistanceTrail = $use_miles ? round($lifetime[3]* 0.6213711922,0) : round($lifetime[3],0);
		
		$maxValue = max(array($totalDistanceTreadmill, $totalDistanceBeach, $totalDistanceRoad,$totalDistanceTrail));
		if($maxValue == $totalDistanceTreadmill) {
			$terrain = 'treadmill';
		}else if($maxValue == $totalDistanceBeach) {
			$terrain = 'beach';
		}else if($maxValue == $totalDistanceRoad) {
			$terrain = 'road';
		}else if($maxValue == $totalDistanceTrail) {
			$terrain = 'trail';
		}

			
		$w->result(uniqid(), serialize(array('' /*other_action*/ ,'https://secure-nikeplus.nike.com/plus/activity/running/' . $username . '/lifetime' /* url */)), 'Road: ' . $totalDistanceRoad . " " . $unit . " ● Beach: " . $totalDistanceBeach . " " . $unit . " ● Trendmill: " . $totalDistanceTreadmill . " " . $unit . " ● Trail: " . $totalDistanceTrail . " " . $unit, 'Morning: ' . round($lifetime[59],0) . " % ● Afternoon: " . round($lifetime[57],0) . " % ● Evening: " . round($lifetime[56],0) . " % ● Night: " . round($lifetime[58],0) . " %", './images/' . $terrain . '.png', 'yes', null, '');
		
		$title="";
		if(!$use_miles) {
			if($lifetime[41] != "") {
				$title = "1K: " . formatDuration($lifetime[41], true, false);
			}
			
			if($lifetime[18] != "") {
				$title = $title . " ● 5K: " . formatDuration($lifetime[18], true, false);
			}
			
			if($lifetime[21] != "") {
				$title = $title . " ● 10K: " . formatDuration($lifetime[21], true, false);
			}
		}
		else {
			if($lifetime[46] != "") {
				$title = "1M: " . formatDuration($lifetime[46], true, false);
			}		
		}
		
			
		if($lifetime[25] != "") {
			$title = $title . " ● Half-Marathon: " . formatDuration($lifetime[25], true, false);
		}
		
		if($lifetime[9] != "") {
			$title = $title . " ● Marathon: " . formatDuration($lifetime[9], true, false);
		}
		
		
		$subtitle = "";
		if($lifetime[22] != "") {
			$runFarthest = $use_miles ? round($lifetime[22]* 0.6213711922,2) : round($lifetime[22],2);
			$subtitle = $subtitle . "Longest distance: " . $runFarthest . " " . $unit;		
		}
		
		if($lifetime[47] != "") {
			$subtitle = $subtitle . " ● Longest Duration: " . formatDuration($lifetime[47], true, false);
			
		}
		
		if($lifetime[48] != "") {
			$subtitle = $subtitle . " ● Most Calories: " . $lifetime[48];
			
		}
		$w->result(uniqid(), serialize(array('' /*other_action*/ ,'https://secure-nikeplus.nike.com/plus/profile/' . $username /* url */)),$title,$subtitle, './images/trophee.png', 'yes', null, '');		
				
		$w->result(uniqid(), '', 'Browse your activities for ' . getMonthName(intval(date("m"))), 'Browse current month', './images/' . date("m") . '.png', 'no', null, 'Year▹' . date("Y") . '▹' . date("m") . '▹' );
		
		$w->result(uniqid(), '', 'Browse all your runs', 'Browse by year and then by month', './images/' . date("Y") . '.png', 'no', null, 'Year▹');
		
		$w->result(uniqid(), '', 'Get your shoes stats', 'Browse all your shoes', './images/shoes.png', 'no', null, 'Shoes▹');
		

		$w->result(uniqid(), '', 'Settings', 'Search scope=<all>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Alfred Playlist is <' . $alfred_playlist_state . '>', './images/credentials.png', 'no', null, 'Settings▹');		
		
	}
	//
	// Settings
	//
	elseif (substr_count($query, '▹') == 1) {

		$w->result(uniqid(), serialize(array('get_latest_activities' /*other_action*/ ,'' /* url */)), 'Update Library (get up to last 5 activities)', "When done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
		
		$w->result(uniqid(), serialize(array('update_library' /*other_action*/ ,'' /* url */)), 'Reset Library (delete and recreate your entire library)', "When done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');

		$w->result(uniqid(), serialize(array('credentials' /*other_action*/ ,'' /* url */)), 'Change your Nike Plus credentials', "Your password will be stored safely in your keychain", './images/credentials.png', 'yes', null, '');
		
		if ($use_miles == true) {
			$w->result(uniqid(), serialize(array('disable_use_miles' /*other_action*/ ,'' /* url */)), "Disable use of Miles", array(
					"Use KM instead of Miles",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './images/uncheck.png', 'yes', null, '');
		} else {
			$w->result(uniqid(), serialize(array('enable_use_miles' /*other_action*/ ,'' /* url */)), "Enable use of Miles", array(
					"Use Miles instead of KM",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './images/check.png', 'yes', null, '');
		}
		
		$w->result(uniqid(), serialize(array('check_for_update' /*other_action*/ ,'' /* url */)), 'Check for workflow update', array(
				"Note this is automatically done otherwise once per day",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'), './images/check_update.png', 'yes', null, '');
	}
} else {
	////////////
	//
	// NO DELIMITER
	//
	////////////
	if (substr_count($query, '▹') == 0) {
		//
		// Search categories for fast access
		//
		if (strpos(strtolower('playlists'), strtolower($query)) !== false) {
			$w->result(uniqid(), '', 'Playlists', 'Browse by playlist', './images/' . $theme . '/' . 'playlists.png', 'no', null, 'Playlist▹');
		} else if (strpos(strtolower('albums'), strtolower($query)) !== false) {
				$w->result(uniqid(), '', 'Albums', 'Browse by album', './images/' . $theme . '/' . 'albums.png', 'no', null, 'Album▹');
			} else if (strpos(strtolower('artists'), strtolower($query)) !== false) {
				$w->result(uniqid(), '', 'Artists', 'Browse by artist', './images/' . $theme . '/' . 'artists.png', 'no', null, 'Artist▹');
			} else if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
				$w->result(uniqid(), '', 'Alfred Playlist (currently set to <' . $alfred_playlist_name . '>)' , 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/' . $theme . '/' . 'alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
			} else if (strpos(strtolower('settings'), strtolower($query)) !== false) {
				$w->result(uniqid(), '', 'Settings', 'Go to settings', './images/' . $theme . '/' . 'settings.png', 'no', null, 'Settings▹');
			}

	} ////////////
	//
	// FIRST DELIMITER
	//
	////////////
	elseif (substr_count($query, '▹') == 1) {
		$words = explode('▹', $query);

		$kind = $words[0];

		if ($kind == "Year") {

			//
			// Browse by years
			//
			try {
	
				$getActivitiesByYears = "select distinct(strftime('%Y',startTimeUtc)) from activities order by startTimeUtc desc";
	
				$stmt = $db->prepare($getActivitiesByYears);
				$stmt->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}
	
			// display all years
			$noresult=true;
			while ($activityByYear = $stmt->fetch()) {
	
				$noresult=false;
				
				try {
		
					$getActivities = "select * from activities where strftime('%Y',startTimeUtc)=:year";
		
					$stmt2 = $db->prepare($getActivities);
					$stmt2->bindValue(':year', '' . $activityByYear[0] . '');	
					$stmt2->execute();
		
				} catch (PDOException $e) {
					handleDbIssuePdo($db);
					return;
				}
	
				// get all activities for year
				$total_duration=0;
				$total_distance=0;
				$total_activities=0;
				$total_fuel=0;
				$total_calories=0;
				while ($activity = $stmt2->fetch()) {
		
					$noresult=false;
					$total_duration+=$activity[29];
					$total_distance+=$activity[24];
					$total_activities++;
					$total_fuel+=$activity[26];
					$total_calories+=$activity[28];
				}
				$distance = $use_miles ? round($total_distance* 0.6213711922,2) : round($total_distance,2);
				$w->result(uniqid(), '', "Runs: " . $total_activities  . " ● Distance: " . $distance . " " . $unit . " ● Average Pace: " . calculatePace($total_duration,$total_distance,$use_miles) . " min/" . $unit . "", "Fuel: " . $total_fuel . " ● Calories: " . $total_calories, './images/' . $activityByYear[0] . '.png', 'no', null, "Year▹" . $activityByYear[0] . "▹");
				
			}
	
			if($noresult) {
				$w->result(uniqid(), 'help', "There is no result. Go for a run man!", "", './images/warning.png', 'no', null, '');
			}
		} else if ($kind == "Shoes") {

			//
			// Browse by shoes
			//
			try {
	
				$getShoes = "select shoes_name,shoes_distance,shoes_activityCount,shoes_retired,shoes_percentage from activities group by shoes_name order by shoes_distance desc";
	
				$stmt = $db->prepare($getShoes);
				$stmt->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}
	
			// display all shoes
			$noresult=true;
			while ($shoe = $stmt->fetch()) {
	
				$noresult=false;
				
				$distance = $use_miles ? round($shoe[1]* 0.6213711922,2) : round($shoe[1],2);
				$retired = $shoe[3] ? "true" : "false";
				$w->result(uniqid(), '', $shoe[0]  . " ● Distance: " . $distance . " " . $unit . " ● Runs: " . $shoe[2], "Retired: " . $retired, './images/shoes.png', 'no', null, '');
				
			}
	
			if($noresult) {
				$w->result(uniqid(), 'help', "There is no result. Go for a run man!", "", './images/warning.png', 'no', null, '');
			}
		}
	} 
	////////////
	//
	// SECOND DELIMITER
	//
	////////////
	elseif (substr_count($query, '▹') == 2) {

		$words = explode('▹', $query);

		$kind = $words[0];
		
		
		/////
		//
 		//  Year
		////
		if ($kind == "Year") {

			$year = $words[1];
			$month = $words[2];
			
			//
			// Browse by months
			//
			try {
	
				$getActivitiesByMonths = "select distinct(strftime('%m',startTimeUtc)) from activities where strftime('%Y',startTimeUtc)=:year order by startTimeUtc desc";
	
				$stmt = $db->prepare($getActivitiesByMonths);
				$stmt->bindValue(':year', '' . $year . '');
				$stmt->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}
			
			// summary of the month
			try {
	
				$getActivities = "select * from activities where strftime('%Y',startTimeUtc)=:year";
	
				$stmt2 = $db->prepare($getActivities);
				$stmt2->bindValue(':year', '' . $year . '');	
				$stmt2->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}

			// get all activities for year
			$total_duration=0;
			$total_distance=0;
			$total_activities=0;
			$total_fuel=0;
			$total_calories=0;
			while ($activity = $stmt2->fetch()) {
	
				$noresult=false;
				$total_duration+=$activity[29];
				$total_distance+=$activity[24];
				$total_activities++;
				$total_fuel+=$activity[26];
				$total_calories+=$activity[28];
			}
			$distance = $use_miles ? round($total_distance* 0.6213711922,2) : round($total_distance,2);
			$w->result(uniqid(), '', "TOTAL 🏃 Runs: " . $total_activities  . " ● Distance: " . $distance . " " . $unit . " ● Average Pace: " . calculatePace($total_duration,$total_distance,$use_miles) . " min/" . $unit . "", "Fuel: " . $total_fuel . " ● Calories: " . $total_calories, './images/' . $year . '.png', 'no', null, "Year▹" . $activityByYear[0] . "▹");
	
			// display all months
			$noresult=true;
			while ($activityByMonth = $stmt->fetch()) {
	
				$noresult=false;
				
				try {
		
					$getActivities = "select * from activities where (strftime('%m',startTimeUtc)=:month and strftime('%Y',startTimeUtc)=:year)";
		
					$stmt2 = $db->prepare($getActivities);
					$stmt2->bindValue(':year', '' . $year . '');
					$stmt2->bindValue(':month', '' . $activityByMonth[0] . '');	
					$stmt2->execute();
		
				} catch (PDOException $e) {
					handleDbIssuePdo($db);
					return;
				}
	
				// get all activities for month
				$total_duration=0;
				$total_distance=0;
				$total_activities=0;
				$total_fuel=0;
				$total_calories=0;
				while ($activity = $stmt2->fetch()) {
		
					$noresult=false;
					$total_duration+=$activity[29];
					$total_distance+=$activity[24];
					$total_activities++;
					$total_fuel+=$activity[26];
					$total_calories+=$activity[28];
				}			
	
				$distance = $use_miles ? round($total_distance* 0.6213711922,2) : round($total_distance,2);
				$w->result(uniqid(), '', "Runs: " . $total_activities  . " ● Distance: " . $distance . " " . $unit . " ● Average Pace: " . calculatePace($total_duration,$total_distance,$use_miles) . " min/" . $unit . "", "Fuel: " . $total_fuel . " ● Calories: " . $total_calories, './images/' . $activityByMonth[0] . '.png', 'no', null, "Year▹" . $year . "▹" . $activityByMonth[0] . "▹");
	
			}
	
			if($noresult) {
				$w->result(uniqid(), 'help', "There is no result. Go for a run man!", "", './images/warning.png', 'no', null, '');
			}

		} // end of years
	}
	////////////
	//
	// THIRD DELIMITER
	//
	////////////
	elseif (substr_count($query, '▹') == 3) {


		$words = explode('▹', $query);
		
		$kind = $words[0];

		
		if ($kind == "Year") {

			//
			// Get all activities for month
			//

			$year = $words[1];
			$month = $words[2];
				
			//
			
			try {
	
				$getActivities = "select * from activities where (strftime('%m',startTimeUtc)=:month and strftime('%Y',startTimeUtc)=:year) order by startTimeUtc desc";
	
				$stmt = $db->prepare($getActivities);
				$stmt->bindValue(':year', '' . $year . '');
				$stmt->bindValue(':month', '' . $month . '');	
				$stmt->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}
				
			// display summary of month
			try {
	
				$getActivities = "select * from activities where (strftime('%m',startTimeUtc)=:month and strftime('%Y',startTimeUtc)=:year)";
	
				$stmt2 = $db->prepare($getActivities);
				$stmt2->bindValue(':year', '' . $year . '');
				$stmt2->bindValue(':month', '' . $month . '');	
				$stmt2->execute();
	
			} catch (PDOException $e) {
				handleDbIssuePdo($db);
				return;
			}

			$total_duration=0;
			$total_distance=0;
			$total_activities=0;
			$total_fuel=0;
			$total_calories=0;
			while ($activity = $stmt2->fetch()) {
	
				$noresult=false;
				$total_duration+=$activity[29];
				$total_distance+=$activity[24];
				$total_activities++;
				$total_fuel+=$activity[26];
				$total_calories+=$activity[28];
			}			

			$distance = $use_miles ? round($total_distance* 0.6213711922,2) : round($total_distance,2);
			$w->result(uniqid(), '', "TOTAL 🏃 Runs: " . $total_activities  . " ● Distance: " . $distance . " " . $unit . " ● Average Pace: " . calculatePace($total_duration,$total_distance,$use_miles) . " min/" . $unit . "", "Fuel: " . $total_fuel . " ● Calories: " . $total_calories, './images/' . $month . '.png', 'no', null, "Year▹" . $year . "▹" . $activityByMonth[0] . "▹");
			
			// display all activities
			$noresult=true;
			while ($activity = $stmt->fetch()) {
	
	
				$noresult=false;
				
				$distance = $use_miles ? round($activity[24]* 0.6213711922,2) : round($activity[24],2);
				
				$weather="❔";
				if($activity[14]=="sunny") {
					$weather = "☀️";
				} else if($activity[14]=="partly_sunny") {
					$weather = "⛅️";
				} else if($activity[14]=="cloudy") {
					$weather = "☁️";
				} else if($activity[14]=="rainy") {
					$weather = "☔️";
				} else if($activity[14]=="snowy") {
					$weather = "❄️";
				}

				$emotion="❔";
				if($activity[15]=="unstoppable" || $activity[15]=="superhero") {
					$emotion = "😄";
				} else if($activity[15]=="great") {
					$emotion = "😃";
				} else if($activity[15]=="so_so") {
					$emotion = "😔";
				} else if($activity[15]=="tired") {
					$emotion = "😞";
				} else if($activity[15]=="injured" || $activity[15]=="amped") {
					$emotion = "😵";
				}
				
				$address = explode(',', $activity[31]);
				
				$subtitle = "Fuel: " . $activity[26] . " ● Calories: " . $activity[28];
				
				if($address[1] != "") {
					$subtitle = $subtitle . " ● City: " . ltrim($address[1], ' 0123456789');
				}

				
				if($activity[16] != "" && $activity[16] != "note") {
					$subtitle = $subtitle . " ● Note: " . $activity[16];
				}				 
				
				if($activity[20] != "") {
					$subtitle = $subtitle . " ● Shoes: " . $activity[20];
/*
					if($activity[21] != "") {
						$shoes_distance = $use_miles ? round($activity[21]* 0.6213711922,2) : round($activity[21],2);
						$subtitle = $subtitle . "(" . $shoes_distance . " " . $unit . ")";
						
					}	
*/				
				}
				
				$title = $weather . $emotion . ' ';
				$title = $title . date("l jS", strtotime($activity[5]));
				$tilte = $title . " ● Distance: " . $distance . " " . $unit . " ● Average Pace: " . calculatePace($activity[29],$activity[24],$use_miles) . " min/" . $unit . "";

	
				$w->result(uniqid(), serialize(array('' /*other_action*/ ,'https://secure-nikeplus.nike.com/plus/activity/running/' . $username . '/detail/' . $activity[0] /* url */)),$tilte,$subtitle, './images/' . $activity[17] . '.png', 'yes', null, '');
			}
	
			if($noresult) {
				$w->result(uniqid(), 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
			}
		}
	}

}

echo $w->toxml();

//$end_time = computeTime();
//$total_temp = ($end_time-$begin_time);
//echo "$total_temp\n";

?>