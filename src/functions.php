<?php

require_once('./src/workflows.php');
require_once('./src/alfrednikeplus.php');


/**
 * getaddress function.
 * 
 * @access public
 * @param mixed $lat
 * @param mixed $lng
 * @return void
 */
function getaddress($lat,$lng)
{
	usleep(110000);
	$url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false';
	$json = @file_get_contents($url);
	$data=json_decode($json);
	$status = $data->status;
	if($status=="OK")
		return $data->results[0]->formatted_address;
	else
		return false;
}

/**
 * computeTime function.
 *
 * @access public
 * @return void
 */
function computeTime()
{
	list($msec, $sec) = explode(' ', microtime());
	return ((float) $sec + (float) $msec) ;
}

/**
 * toDecimalPlaces()
 * convert a value to minutes
 *
 * @param float $time
 * @param int $decimalPlaces optional - set the number of decimal places (default is 2), use to improve granularity
 *
 * @return string
 */    
function toTwoDecimalPlaces($number, $decimalPlaces = 2) {
	return number_format((float) $number, $decimalPlaces, '.', ',');
}

/**
 * toHours()
 * convert a value to hours
 *
 * @param float $time
 *
 * @return string
 */
function toHours($time) {
	return intval($time / 3600000) % 60;
}

/**
 * toMinutes()
 * convert a value to minutes
 *
 * @param float $time
 *
 * @return string
 */
function toMinutes($time) {
	return intval($time / 60000) % 60;
}

/**
 * toSeconds()
 * convert a value to seconds
 *
 * @param float $time
 *
 * @return string
 */
function toSeconds($time) {
	return intval($time / 1000) % 60;
}


/**
 * toMiles()
 * Convert a value from Km in to miles
 * 
 * @param float|string $distance
 * @param int $decimalPlaces optional - set the number of decimal places (default is 2), use to improve granularity
 * 
 * @return int
 */
function toMiles($distance, $decimalPlaces = 2) {
	return toTwoDecimalPlaces((float) $distance * 0.6213711922, $decimalPlaces);
}
	
/**
 * padNumber()
 * pad numbers less than 10 to have a leading 0
 * 
 * @param int $number
 * 
 * @return string
 */
function padNumber($number){
	if($number < 10 && $number >= 0) {
		return '0'.$number;
	}
	return $number;
}
	

/**
 * getMonthName function.
 * 
 * @access public
 * @param mixed $month
 * @return void
 */
function getMonthName($month){
	$mons = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");
	
	return $mons[$month];
}	
	
/**
 * formatDuration()
 * convert a duration into minutes and seconds, or
 * hours, minutes and seconds if hours are available
 *
 * @param float $time
 * @param boolean $hideZeroHours - hide the hour figure if it is zero
 *
 * @return string
 */
function formatDuration($time, $hideZeroHours = true, $hideSeconds = false) {
	$hours = toHours($time);
	$minutes = toMinutes($time);
	$seconds = toSeconds($time);
	$formattedTime = padNumber($minutes);
	if(!$hideSeconds) {
		$formattedTime .= ':'.padNumber($seconds);
	}
	if($hours > 0 || !$hideZeroHours) {
		$formattedTime = $hours.':'.$formattedTime;
	}
	return $formattedTime;
}
	
/**
 * calculatePace()
 * calculate the average pace of an activity
 *
 * @param int $duration
 * @param int $distance
 * @param boolean $toMiles optional - the default output is time per kilometer, set to true for time per mile
 *
 * @return float (time)
 */
function calculatePace($duration, $distance, $toMiles = false) {
	if($toMiles) {
		$distance = toMiles($distance);
	}
	$pace = $duration / $distance;
	return formatDuration($pace);
}

/**
 * getFreeTcpPort function.
 *
 * @access public
 * @return void
 */
function getFreeTcpPort()
{
	//avoid warnings like this PHP Warning:  fsockopen(): unable to connect to localhost (Connection refused)
	error_reporting(~E_ALL);

	$from = 10000;
	$to = 20000;

	//TCP ports
	$host = 'localhost';

	for($port = $from; $port <= $to ; $port++)
	{
		$fp = fsockopen($host , $port);
		if (!$fp)
		{
			//port is free
			return $port;
		}
		else
		{
			// port open, close it
			fclose($fp);
		}
	}

	return 17693;
}

/**
 * escapeQuery function.
 *
 * @access public
 * @param mixed $text
 * @return void
 */
function escapeQuery($text)
{
	$text = str_replace("'", "’", $text);
	$text = str_replace('"', "’", $text);
	$text = str_replace("&apos;", "’", $text);
	$text = str_replace("`", "’", $text);
	$text = str_replace("&amp;", "and", $text);
	$text = str_replace("&", "and", $text);
	$text = str_replace("\\", " ", $text);
	$text = str_replace("$", "\\$", $text);
	return $text;
}

/**
 * checkIfResultAlreadyThere function.
 *
 * @access public
 * @param mixed $results
 * @param mixed $title
 * @return void
 */
function checkIfResultAlreadyThere($results, $title)
{
	foreach ($results as $result) {
		if ($result['title']) {
			if ($result['title'] == $title) {
				return true;
			}
		}
	}
	return false;
}

/**
 * displayNotification function.
 *
 * @access public
 * @param mixed $output
 * @return void
 */
function displayNotification($output)
{
	exec('./terminal-notifier.app/Contents/MacOS/terminal-notifier -title "Nike Plus" -sender com.nike.plus -message "' .  $output . '"');
}

/**
 * displayNotificationWithArtwork function.
 *
 * @access public
 * @param mixed $output
 * @param mixed $artwork
 * @return void
 */
function displayNotificationWithArtwork($output,$artwork)
{
	if($artwork != "") {
		copy($artwork,"/tmp/tmp");
	}

	exec("./terminal-notifier.app/Contents/MacOS/terminal-notifier -title 'Nike Plus' -sender 'com.nike.plus' -contentImage '/tmp/tmp' -message '" .  $output . "'");
}

/**
 * displayNotificationForStarredTrack function.
 *
 * @access public
 * @param mixed $track_name
 * @param mixed $track_uri
 * @return void
 */
function displayNotificationForStarredTrack($track_name,$track_uri)
{
	$w = new Workflows('com.vdesabou.nike.plus');
	displayNotificationWithArtwork('⭐️ ' . $track_name . ' has been starred',getTrackOrAlbumArtwork($w,'black',$track_uri,true));
}

/**
 * displayNotificationForUnstarredTrack function.
 *
 * @access public
 * @param mixed $track_name
 * @param mixed $track_uri
 * @return void
 */
function displayNotificationForUnstarredTrack($track_name,$track_uri)
{
	$w = new Workflows('com.vdesabou.nike.plus');
	displayNotificationWithArtwork('❌ ' . $track_name . ' has been unstarred',getTrackOrAlbumArtwork($w,'black',$track_uri,true));
}


/**
 * updateLibrary function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updateLibrary($limit = 0)
{
	$w = new Workflows('com.vdesabou.nike.plus');
	
	touch($w->data() . "/update_library_in_progress");
	$w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');
	
	$username = exec("Authenticate.app/Contents/MacOS/Authenticate -get username");
	
	$password = exec("Authenticate.app/Contents/MacOS/Authenticate -get password");
		
	$n = new AlfredNikePlusPHP($username, $password);
	
	// FIX THIS : error handling

	$setSettings = "update settings set username='" . $n->userId . "'";
	$dbfile = $w->data() . "/settings.db";
	exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	
	
	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$getSettings = 'select use_miles from settings';
	$dbfile = $w->data() . '/settings.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

	if ($returnValue != 0) {
		displayNotification("Error: cannot read settings");
		unlink($w->data() . "/update_library_in_progress");
		return;
	}


	foreach ($settings as $setting):

		$setting = explode("	", $setting);
		$use_miles = $setting[0];
	endforeach;

	$words = explode('▹', $in_progress_data);


	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	// get all activities
	
	$jsonData = json_encode($n->activities($limit,false));
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
	
		if($limit == 0) {
			if (file_exists($w->data() . "/library.db")) {
				unlink($w->data() . "/library.db");
			}
			touch($w->data() . "/library.db");
	
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table activities (activityId text PRIMARY KEY NOT NULL, heartrate boolean, gps boolean, activityType text, activeTime int, startTimeUtc text, latitude text, longitude text, timeZone text, dstOffset text, deviceType text, timeZoneId text, name text, status text,weather text,emotion text,note text,terrain text,shoes_activityCount int, shoes_percentage float, shoes_name text, shoes_distance float, shoes_retired boolean, averageHeartRate int,distance float, maximumHeartRate int, fuel int, steps int, calories int, duration int, minimumHeartRate int, address text)"';
			exec($sql);
	
	
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "CREATE INDEX IndexStartTimeUtc ON activities (startTimeUtc)"';
			exec($sql);
		}

		$nb_activities=0;
		$nb_activitiestotal=0;
		
		foreach ($json as $activity) {
			$nb_activitiestotal++;
		}
		
		$w->write('Library▹0▹' . $nb_activitiestotal . '▹' . $words[3], 'update_library_in_progress');
		
		foreach ($json as $activity) {
		
			$latitude="";
			$longitude="";
			$weather="";
			$shoes_activityCount = 0;
			$shoes_percentage = 0.0;
			$timeZoneId="";
			$dstOffset="";
			$averageHeartRate=0;
			$maximumHeartRate=0;
			$minimumHeartRate=0;
			$note="";
			$address="";
			
			if ($activity['gps'] == true) {
				$gps = 1;
				if (isset($activity['latitude']) && isset($activity['longitude'])) {
					$latitude = $activity['latitude'];
					$longitude = $activity['longitude'];

					$address= getaddress($latitude,$longitude);
					if($address)
					{
					//	echo $address;
					}
					else
					{
						$address="";
					}

				}
			} else {
				$gps = 0;
			}

			if ($activity['heartrate'] == true) {
				$heartrate = 1;
			} else {
				$heartrate = 0;
			}

			if (isset($activity['timeZoneId'])) {
				$timeZoneId = $activity['timeZoneId'];
			}

			if (isset($activity['dstOffset'])) {
				$dstOffset = $activity['dstOffset'];
			}
				
			if (isset($activity['tags']['emotion'])) {
				$emotion = $activity['tags']['emotion'];
			}
			
			if (isset($activity['tags']['weather'])) {
				$weather = $activity['tags']['weather'];
			}

			if (isset($activity['tags']['note'])) {
				$note = $activity['tags']['note'];
			}
			
			if (isset($activity['tags']['terrain'])) {
				$terrain = $activity['tags']['terrain'];
			}
			
			if (isset($activity['tags']['SHOES'])) {
				if (isset($activity['tags']['SHOES']['activityCount'])) {
					$shoes_activityCount = $activity['tags']['SHOES']['activityCount'];
				}
				
				if (isset($activity['tags']['SHOES']['percentage'])) {
					$shoes_percentage = $activity['tags']['SHOES']['percentage'];
				}
				
				if (isset($activity['tags']['SHOES']['name'])) {
					$shoes_name = $activity['tags']['SHOES']['name'];
				}
				
				if (isset($activity['tags']['SHOES']['distance'])) {
					$shoes_distance = $activity['tags']['SHOES']['distance'];
				}
				
				if (isset($activity['tags']['SHOES']['retired'])) {
					if ($activity['tags']['SHOES']['retired'] == true) {
						$shoes_retired = 1;
					} else {
						$shoes_retired = 0;
					}
				}
				
			}

			if (isset($activity['metrics'])) {
			
				if (isset($activity['metrics']['averageHeartRate'])) {
					$averageHeartRate = $activity['metrics']['averageHeartRate'];
				}
				
				if (isset($activity['metrics']['duration'])) {
					$duration = $activity['metrics']['duration'];
				}

				if (isset($activity['metrics']['maximumHeartRate'])) {
					$maximumHeartRate = $activity['metrics']['maximumHeartRate'];
				}		
				
				if (isset($activity['metrics']['fuel'])) {
					$fuel = $activity['metrics']['fuel'];
				}
				
				if (isset($activity['metrics']['steps'])) {
					$steps = $activity['metrics']['steps'];
				}
				
				if (isset($activity['metrics']['distance'])) {
					$distance = $activity['metrics']['distance'];
				}
				
				if (isset($activity['metrics']['calories'])) {
					$calories = $activity['metrics']['calories'];
				}
				if (isset($activity['metrics']['minimumHeartRate'])) {
					$minimumHeartRate = $activity['metrics']['minimumHeartRate'];
				}
								
			}
			
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert or ignore into activities values (' . '\"' . $activity['activityId'] . '\",' . $heartrate. ',' . $gps . ',\"' . $activity['activityType'] . '\",' . $activity['activeTime'] . ',\"' . $activity['startTimeUtc'] . '\",\"' . $latitude . '\",\"' . $longitude. '\",\"' . $activity['timeZone'] . '\",\"' . $dstOffset . '\"' . ',\"' . $activity['deviceType'] . '\"' . ',\"' . $timeZoneId . '\"' . ',\"' . $activity['name'] . '\"' . ',\"' . $activity['status'] . '\"' . ',\"' . $weather . '\"' . ',\"' . $emotion . '\"' . ',\"' . $note . '\"' . ',\"' . $terrain . '\"' . ',' . $shoes_activityCount . '' . ',' . $shoes_percentage . '' . ',\"' . $shoes_name . '\"' . ',' . $shoes_distance . '' . ',' . $shoes_retired . '' . ',' . $averageHeartRate . '' . ',' . $distance . '' . ',' . $maximumHeartRate . '' . ',' . $fuel . '' . ',' . $steps . '' . ',' . $calories . '' . ',' . $duration . '' . ',' . $minimumHeartRate . ',' . '\"'. $address . '\"' .')"';

			exec($sql);
			
			$nb_activities++;
			
			if ($nb_activities % 5 === 0) {
				$w->write('Library▹' . $nb_activities . '▹' . $nb_activitiestotal . '▹' . $words[3], 'update_library_in_progress');
			}
			
			//echo "$sql\n";				

		}
	} else {
		unlink($w->data() . "/update_library_in_progress");
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
	
	// get lifetime
	
	$jsonData = json_encode($n->allTime());
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
	

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "drop table lifetime"';
		exec($sql);
		
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table lifetime (treadmill float, beach float, road float,trail float,fuelHeartRate float,distanceHeartRate float,averagePace2 float,durationCardio int, earlyMorningCount int, fastestMarathon int, workoutCardio int, run int, stepWeekAverage float,heartRateAverage float, earlyAfternoonCount int, lateAfternoonCount int, workoutPedo int, lastUpdated int, fastest5K int, runPerWeekAverage float, userActivityLifetimeId text, fastest10K int, runFarthest float,durationHeartRate float, distanceCardio int, fastestHalfMarathon int, earlyEveningCount int, stepDateLongest int, caloriePedo int, runFarthestCardio int, maxRunsPerWeek int, durationPedo int, distance float, calorieHeartRate float, earlyNightCount int, stepCountAverage float, paceHeartRate float, daytimeCount int, totalFuel int, lateMorningCount int, duration int, fastest1K int, heartRateRun int, brightAndEarlyCount int, workoutHeartRate float, smallHoursCount int, fastest1M int, longestRunDuration int, mostCaloriesBurnedSingleRun int, morningCount int, calorie int, stepTotal int, stepCountLongest int, calorieCardio int, caloriePedoTotal int, eveningCount int, night float, afternoon float, smallHours float, morning float)"';
		exec($sql);

		
		$treadmill = 0.0;
		$beach = 0.0;
		$road = 0.0;
		$trail = 0.0;
		
		if (isset($json['terrains'])) {
			
			if (isset($json['terrains']['treadmill'])) {
				$treadmill = $json['terrains']['treadmill'];
			}
			
			if (isset($json['terrains']['beach'])) {
				$beach = $json['terrains']['beach'];
			}
			
			if (isset($json['terrains']['road'])) {
				$road = $json['terrains']['road'];
			}
			
			if (isset($json['terrains']['trail'])) {
				$trail = $json['terrains']['trail'];
			}						
		}


		$fuelHeartRate = 0.0;
		$distanceHeartRate = 0.0;
		$averagePace2 = 0.0;
		$durationCardio = 0;
		$earlyMorningCount = 0;
		$fastestMarathon = 0;
		$workoutCardio = 0;
		$run = 0;
		$stepWeekAverage = 0.0;
		
		
		$heartRateAverage = 0.0;
		$earlyAfternoonCount = 0;
		$lateAfternoonCount = 0;
		$workoutPedo = 0;
		$lastUpdated = 0;
		$fastest5K = 0;
		$runPerWeekAverage = 0.0;
		$userActivityLifetimeId = "";	
		$fastest10K = 0;
		$runFarthest = 0.0;
		$durationHeartRate = 0.0;
		$distanceCardio = 0;
		$fastestHalfMarathon = 0;
		$earlyEveningCount = 0;
		$stepDateLongest = 0;
		$caloriePedo = 0;
		$runFarthestCardio = 0;
		$maxRunsPerWeek = 0;
		$durationPedo = 0;
		$distance = 0.0;
		$calorieHeartRate = 0.0;
		$earlyNightCount = 0;
		$stepCountAverage = 0.0;
		$paceHeartRate = 0.0;
		$daytimeCount = 0;
		$lateMorningCount = 0;
		$totalFuel = 0;
		$lateNightCount = 0;
		$duration = 0;
		$fastest1K = 0;
		$heartRateRun = 0;
		$brightAndEarlyCount = 0;
		$workoutHeartRate = 0.0;
		$smallHoursCount = 0;
		$fastest1M = 0;
		$longestRunDuration = 0;
		$mostCaloriesBurnedSingleRun = 0;
		$morningCount = 0;
		$calorie = 0;
		$stepTotal = 0;
		$stepCountLongest = 0;
		$calorieCardio = 0;
		$caloriePedoTotal = 0;
		$eveningCount = 0;
		
			
		if (isset($json['lifetimeTotals'])) {
			
			if (isset($json['lifetimeTotals']['fuelHeartRate'])) {
				$fuelHeartRate = $json['lifetimeTotals']['fuelHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['distanceHeartRate'])) {
				$distanceHeartRate = $json['lifetimeTotals']['distanceHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['averagePace'])) {
				$averagePace2 = $json['lifetimeTotals']['averagePace'];
			}
			
			if (isset($json['lifetimeTotals']['durationCardio'])) {
				$durationCardio = $json['lifetimeTotals']['durationCardio'];
			}
			
			if (isset($json['lifetimeTotals']['earlyMorningCount'])) {
				$earlyMorningCount = $json['lifetimeTotals']['earlyMorningCount'];
			}
			
			if (isset($json['lifetimeTotals']['fastestMarathon'])) {
				$fastestMarathon = $json['lifetimeTotals']['fastestMarathon'];
			}
			
			if (isset($json['lifetimeTotals']['workoutCardio'])) {
				$workoutCardio = $json['lifetimeTotals']['workoutCardio'];
			}
			
			if (isset($json['lifetimeTotals']['run'])) {
				$run = $json['lifetimeTotals']['run'];
			}
			
			if (isset($json['lifetimeTotals']['stepWeekAverage'])) {
				$stepWeekAverage = $json['lifetimeTotals']['stepWeekAverage'];
			}
			
			if (isset($json['lifetimeTotals']['heartRateAverage'])) {
				$heartRateAverage = $json['lifetimeTotals']['heartRateAverage'];
			}
			
			if (isset($json['lifetimeTotals']['earlyAfternoonCount'])) {
				$earlyAfternoonCount = $json['lifetimeTotals']['earlyAfternoonCount'];
			}
			
			if (isset($json['lifetimeTotals']['lateAfternoonCount'])) {
				$lateAfternoonCount = $json['lifetimeTotals']['lateAfternoonCount'];
			}
			
			if (isset($json['lifetimeTotals']['workoutPedo'])) {
				$workoutPedo = $json['lifetimeTotals']['workoutPedo'];
			}
			
			if (isset($json['lifetimeTotals']['lastUpdated'])) {
				$lastUpdated = $json['lifetimeTotals']['lastUpdated'];
			}
			
			if (isset($json['lifetimeTotals']['fastest5K'])) {
				$fastest5K = $json['lifetimeTotals']['fastest5K'];
			}
			
			if (isset($json['lifetimeTotals']['runPerWeekAverage'])) {
				$runPerWeekAverage = $json['lifetimeTotals']['runPerWeekAverage'];
			}
			
			if (isset($json['lifetimeTotals']['userActivityLifetimeId'])) {
				$userActivityLifetimeId = $json['lifetimeTotals']['userActivityLifetimeId'];
			}
			
			if (isset($json['lifetimeTotals']['fastest10K'])) {
				$fastest10K = $json['lifetimeTotals']['fastest10K'];
			}
			
			if (isset($json['lifetimeTotals']['runFarthest'])) {
				$runFarthest = $json['lifetimeTotals']['runFarthest'];
			}
			
			if (isset($json['lifetimeTotals']['durationHeartRate'])) {
				$durationHeartRate = $json['lifetimeTotals']['durationHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['distanceCardio'])) {
				$distanceCardio = $json['lifetimeTotals']['distanceCardio'];
			}
			
			if (isset($json['lifetimeTotals']['fastestHalfMarathon'])) {
				$fastestHalfMarathon = $json['lifetimeTotals']['fastestHalfMarathon'];
			}
			
			if (isset($json['lifetimeTotals']['earlyEveningCount'])) {
				$earlyEveningCount = $json['lifetimeTotals']['earlyEveningCount'];
			}
			
			if (isset($json['lifetimeTotals']['stepDateLongest'])) {
				$stepDateLongest = $json['lifetimeTotals']['stepDateLongest'];
			}
			
			if (isset($json['lifetimeTotals']['caloriePedo'])) {
				$caloriePedo = $json['lifetimeTotals']['caloriePedo'];
			}
			
			if (isset($json['lifetimeTotals']['runFarthestCardio'])) {
				$runFarthestCardio = $json['lifetimeTotals']['runFarthestCardio'];
			}
			
			if (isset($json['lifetimeTotals']['maxRunsPerWeek'])) {
				$maxRunsPerWeek = $json['lifetimeTotals']['maxRunsPerWeek'];
			}
			
			if (isset($json['lifetimeTotals']['durationPedo'])) {
				$durationPedo = $json['lifetimeTotals']['durationPedo'];
			}
			
			if (isset($json['lifetimeTotals']['distance'])) {
				$distance = $json['lifetimeTotals']['distance'];
			}
			
			if (isset($json['lifetimeTotals']['calorieHeartRate'])) {
				$calorieHeartRate = $json['lifetimeTotals']['calorieHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['earlyNightCount'])) {
				$earlyNightCount = $json['lifetimeTotals']['earlyNightCount'];
			}
			
			if (isset($json['lifetimeTotals']['stepCountAverage'])) {
				$stepCountAverage = $json['lifetimeTotals']['stepCountAverage'];
			}	
			
			if (isset($json['lifetimeTotals']['paceHeartRate'])) {
				$paceHeartRate = $json['lifetimeTotals']['paceHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['daytimeCount'])) {
				$daytimeCount = $json['lifetimeTotals']['daytimeCount'];
			}
			
			if (isset($json['lifetimeTotals']['lateMorningCount'])) {
				$lateMorningCount = $json['lifetimeTotals']['lateMorningCount'];
			}	

			if (isset($json['lifetimeTotals']['totalFuel'])) {
				$totalFuel = $json['lifetimeTotals']['totalFuel'];
			}
			
			if (isset($json['lifetimeTotals']['lateNightCount'])) {
				$lateNightCount = $json['lifetimeTotals']['lateNightCount'];
			}
			
			if (isset($json['lifetimeTotals']['duration'])) {
				$duration = $json['lifetimeTotals']['duration'];
			}	
			
			if (isset($json['lifetimeTotals']['fastest1K'])) {
				$fastest1K = $json['lifetimeTotals']['fastest1K'];
			}
			
			if (isset($json['lifetimeTotals']['heartRateRun'])) {
				$heartRateRun = $json['lifetimeTotals']['heartRateRun'];
			}
			
			if (isset($json['lifetimeTotals']['brightAndEarlyCount'])) {
				$brightAndEarlyCount = $json['lifetimeTotals']['brightAndEarlyCount'];
			}	
			
			if (isset($json['lifetimeTotals']['workoutHeartRate'])) {
				$workoutHeartRate = $json['lifetimeTotals']['workoutHeartRate'];
			}
			
			if (isset($json['lifetimeTotals']['smallHoursCount'])) {
				$smallHoursCount = $json['lifetimeTotals']['smallHoursCount'];
			}
			
			if (isset($json['lifetimeTotals']['fastest1M'])) {
				$fastest1M = $json['lifetimeTotals']['fastest1M'];
			}	
			
			if (isset($json['lifetimeTotals']['longestRunDuration'])) {
				$longestRunDuration = $json['lifetimeTotals']['longestRunDuration'];
			}
			
			if (isset($json['lifetimeTotals']['mostCaloriesBurnedSingleRun'])) {
				$mostCaloriesBurnedSingleRun = $json['lifetimeTotals']['mostCaloriesBurnedSingleRun'];
			}
			
			if (isset($json['lifetimeTotals']['morningCount'])) {
				$morningCount = $json['lifetimeTotals']['morningCount'];
			}
			
			if (isset($json['lifetimeTotals']['calorie'])) {
				$calorie = $json['lifetimeTotals']['calorie'];
			}		
			
			if (isset($json['lifetimeTotals']['stepTotal'])) {
				$stepTotal = $json['lifetimeTotals']['stepTotal'];
			}	
			
			if (isset($json['lifetimeTotals']['stepCountLongest'])) {
				$stepCountLongest = $json['lifetimeTotals']['stepCountLongest'];
			}
			
			if (isset($json['lifetimeTotals']['calorieCardio'])) {
				$calorieCardio = $json['lifetimeTotals']['calorieCardio'];
			}		
			
			if (isset($json['lifetimeTotals']['caloriePedoTotal'])) {
				$caloriePedoTotal = $json['lifetimeTotals']['caloriePedoTotal'];
			}	
			
			if (isset($json['lifetimeTotals']['eveningCount'])) {
				$eveningCount = $json['lifetimeTotals']['eveningCount'];
			}
					
		}
		
		$morning = 0.0;
		$night = 0.0;
		$afternoon = 0.0;
		$smallHours = 0.0;
		
		if (isset($json['timeOfDay'])) {
			
			if (isset($json['timeOfDay']['morning'])) {
				$morning = $json['timeOfDay']['morning'];
			}
			
			if (isset($json['timeOfDay']['night'])) {
				$night = $json['timeOfDay']['night'];
			}
			
			if (isset($json['timeOfDay']['afternoon'])) {
				$afternoon = $json['timeOfDay']['afternoon'];
			}
			
			if (isset($json['timeOfDay']['smallHours'])) {
				$smallHours = $json['timeOfDay']['smallHours'];
			}						
		}
		
		
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into lifetime values (' 
		. $treadmill
		. ',' . $beach
		. ',' . $road
		. ',' . $trail
		. ',' . $fuelHeartRate
		. ',' . $distanceHeartRate
		. ',' . $averagePace2
		. ',' . $durationCardio
		. ',' . $earlyMorningCount
		. ',' . $fastestMarathon
		. ',' . $workoutCardio
		. ',' . $run
		. ',' . $stepWeekAverage
		. ',' . $heartRateAverage
		. ',' . $earlyAfternoonCount
		. ',' . $lateAfternoonCount
		. ',' . $workoutPedo
		. ',' . $lastUpdated
		. ',' . $fastest5K
		. ',' . $runPerWeekAverage
		. ',\"' . $userActivityLifetimeId
		. '\",' . $fastest10K
		. ',' . $runFarthest
		. ',' . $durationHeartRate
		. ',' . $distanceCardio
		. ',' . $fastestHalfMarathon
		. ',' . $earlyEveningCount
		. ',' . $stepDateLongest
		. ',' . $caloriePedo
		. ',' . $runFarthestCardio
		. ',' . $maxRunsPerWeek
		. ',' . $durationPedo
		. ',' . $distance
		. ',' . $calorieHeartRate
		. ',' . $earlyNightCount
		. ',' . $stepCountAverage
		. ',' . $paceHeartRate
		. ',' . $daytimeCount
		. ',' . $totalFuel
		. ',' . $lateMorningCount
		. ',' . $duration
		. ',' . $fastest1K
		. ',' . $heartRateRun
		. ',' . $brightAndEarlyCount
		. ',' . $workoutHeartRate
		. ',' . $smallHoursCount
		. ',' . $fastest1M
		. ',' . $longestRunDuration
		. ',' . $mostCaloriesBurnedSingleRun
		. ',' . $morningCount
		. ',' . $calorie
		. ',' . $stepTotal
		. ',' . $stepCountLongest
		. ',' . $calorieCardio
		. ',' . $caloriePedoTotal
		. ',' . $eveningCount
		. ',' . $night
		. ',' . $afternoon
		. ',' . $smallHours
		. ',' . $morning
		.')"';

		exec($sql);
		
		
		echo "$sql\n";				

		
		$elapsed_time = time() - $words[3];
		if($limit == 0) {
			displayNotification("Library has been created (" . $nb_activities . " activities) - it took " . beautifyTime($elapsed_time));
		}
		else {
			displayNotification("Library has been updated (" . $nb_activities . " activities) - it took " . beautifyTime($elapsed_time));
		}
		

		unlink($w->data() . "/update_library_in_progress");

	} else {
		unlink($w->data() . "/update_library_in_progress");
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}

}


/**
 * removeUpdateLibraryInProgressFile function.
 *
 * @access public
 * @return void
 */
function removeUpdateLibraryInProgressFile()
{
	$w = new Workflows('com.vdesabou.nike.plus');
	unlink($w->data() . "/update_library_in_progress");
}

/**
 * handleDbIssue function.
 *
 * @access public
 * @param mixed $theme
 * @return void
 */
function handleDbIssue($theme) {
	$w = new Workflows('com.vdesabou.nike.plus');
	$w->result(uniqid(), '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', null, '');

	$w->result(uniqid(), serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/' . $theme . '/' . 'update.png', 'yes', null, '');

	echo $w->toxml();
}


/**
 * handleDbIssuePdo function.
 *
 * @access public
 * @param mixed $theme
 * @param mixed $dbhandle
 * @return void
 */
function handleDbIssuePdo($theme,$dbhandle) {
	$w = new Workflows('com.vdesabou.nike.plus');
	$w->result(uniqid(), '', 'Database Error: ' . $dbhandle->errorInfo()[0] . ' ' . $dbhandle->errorInfo()[1] . ' ' . $dbhandle->errorInfo()[2], '', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/' . $theme . '/' . 'update.png', 'yes', null, '');
	echo $w->toxml();
}

/**
 * floatToSquares function.
 *
 * @access public
 * @param mixed $decimal
 * @return void
 */
function floatToSquares($decimal)
{
	$squares = ($decimal < 1) ? floor($decimal * 10) : 10;
	return str_repeat("◼︎", $squares) . str_repeat("◻︎", 10 - $squares);
}


/**
 * Mulit-byte Unserialize
 *
 * UTF-8 will screw up a serialized string
 *
 * @access private
 * @param string
 * @return string
 */
// thanks to http://stackoverflow.com/questions/2853454/php-unserialize-fails-with-non-encoded-characters
function mb_unserialize($string) {
	$string = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $string);
	return unserialize($string);
}

/**
 * strip_string function.
 *
 * @access public
 * @param mixed $string
 * @return void
 */
function strip_string($string)
{
	return preg_replace('/[^a-zA-Z0-9-\s]/', '', $string);
}

/**
 * checkForUpdate function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $last_check_update_time
 * @return void
 */
function checkForUpdate($w,$last_check_update_time,$dbsettings) {

	if(time()-$last_check_update_time > 86400)
	{
		// update last_check_update_time
		$setSettings = "update settings set last_check_update_time=" . time();
		$dbsettings->exec($setSettings);

		if(! $w->internet()) {
			displayNotificationWithArtwork("Check for update error:
No internet connection",'./images/warning.png');
			return;
		}

		// get local information
		if (!file_exists('./packal/package.xml')) {
			displayNotification("Error: this release has not been downloaded from Packal");
			return 1;
		}
		$xml = $w->read('./packal/package.xml');
		$workflow= new SimpleXMLElement($xml);
		$local_version = $workflow->version;
		$remote_json = "https://raw.githubusercontent.com/vdesabou/alfred-spotify-mini-player/master/remote.json";

		// get remote information
		$jsonDataRemote = $w->request($remote_json);

		if (empty($jsonDataRemote)) {
			displayNotification("Check for update error:
the export.json " . $remote_json . " file cannot be found");
			return 1;
		}

		$json = json_decode($jsonDataRemote,true);
		if (json_last_error() === JSON_ERROR_NONE) {
			$download_url = $json['download_url'];
			$remote_version = $json['version'];
			$description = $json['description'];

			if($local_version < $remote_version) {

				$workflow_file_name = exec('printf $HOME') . '/Downloads/spotify-app-miniplayer-' . $remote_version . '.alfredworkflow';
				$fp = fopen($workflow_file_name , 'w+');
				$options = array(
					CURLOPT_FILE => $fp
				);
				$w->request("$download_url", $options);

				return array($remote_version,$workflow_file_name,$description);
			}

		}
		else {
			displayNotification("Check for update error:
remote.json error");
			return 1;
		}

	}
}

/**
 * beautifyTime function.
 *
 * @access public
 * @param mixed $seconds
 * @return void
 */
function beautifyTime($seconds) {
	$m = floor($seconds / 60);
	$s = $seconds % 60;
	$s = ($s < 10) ? "0$s" : "$s";
	return  $m . "m" . $s . "s";
}

/**
 * startswith function.
 *
 * @access public
 * @param mixed $haystack
 * @param mixed $needle
 * @return void
 */
function startswith($haystack, $needle) {
	return substr($haystack, 0, strlen($needle)) === $needle;
}

?>