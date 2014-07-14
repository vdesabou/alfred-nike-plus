<?php

require_once('./src/workflows.php');
require_once('./src/nikeplusphp.4.5.1.php');


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
 * getTrackOrAlbumArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $spotifyURL
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getTrackOrAlbumArtwork($w,$theme, $spotifyURL, $fetchIfNotPresent)
{

	$hrefs = explode(':', $spotifyURL);

	$isAlbum = false;
	if ($hrefs[1] == "album") {
		$isAlbum = true;
	}

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "/" . "$hrefs[2].png";
	$artwork = "";

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
			$artwork = getTrackArtworkURL($w, $hrefs[1], $hrefs[2]);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
				endif;
				$fp = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp
				);

				$w->request("$artwork", $options);
			}
		} else {
			if ($isAlbum) {
				return "images/" . $theme . "/albums.png";
			} else {
				return "images/" . $theme . "/tracks.png";
			}
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			if ($isAlbum) {
				return "images/" . $theme . "/albums.png";
			} else {
				return "images/" . $theme . "/tracks.png";
			}
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		if ($isAlbum) {
			return "images/" . $theme . "/albums.png";
		} else {
			return "images/" . $theme . "/tracks.png";
		}
	} else {
		return $currentArtwork;
	}
}


/**
 * getPlaylistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $playlistURI
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getPlaylistArtwork($w, $theme, $playlistURI, $fetchIfNotPresent)
{

	$hrefs = explode(':', $playlistURI);

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	// examples of playlists URI
	// spotify:user:@:playlist:20SZdrktr658JNa42Lt1vV
	// spotify:user:@cf86d5f3b8f0b11bc0e70d7fa3661dc8:playlist:3vxotOnOGDlZXyzJPLFnm2

	// need to translate to http://open.spotify.com/user/xxxxusernamexxx/playlist/6orFdd91Cb0fwB2kyUFCKX


	// spotify:user:@:starred
	// spotify:user:117875373:starred

	// need to translate to http://open.spotify.com/user/xxxxusernamexxx/starred


	if (count($hrefs) == 5) {

		$filename = "" . $hrefs[2] . "_" . $hrefs[4];
		$url = "http://open.spotify.com/user/" . $hrefs[2] . "/playlist/" . $hrefs[4];
	} else {
		//starred playlist
		$filename = "" . $hrefs[2] . "_" . $hrefs[3];
		$url = "http://open.spotify.com/user/" . $hrefs[2] . "/" . $hrefs[3];
	}


	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "/" . "$filename.png";

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
			$artwork = getPlaylistArtworkURL($w, $url);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $filename . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "'");
				endif;
				$fp = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp
				);

				$w->request("$artwork", $options);
			}
		} else {

			return "images/" . $theme . "/playlists.png";
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			return "images/" . $theme . "/playlists.png";
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		return "images/" . $theme . "/playlists.png";
	} else {
		return $currentArtwork;
	}
}

/**
 * getArtistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $artist
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getArtistArtwork($w, $theme, $artist, $fetchIfNotPresent)
{
	$parsedArtist = urlencode($artist);

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";
	$artwork = "";

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
			$artwork = getArtistArtworkURL($w, $artist);
			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
				endif;
				$fp = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp
				);
				$w->request("$artwork", $options);
			}
		} else {
			return "images/" . $theme . "/artists.png";
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			return "images/" . $theme . "/artists.png";
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		return "images/" . $theme . "/artists.png";
	} else {
		return $currentArtwork;
	}
}

/**
 * getTrackArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $type
 * @param mixed $id
 * @return void
 */
function getTrackArtworkURL($w, $type, $id)
{
	$html = $w->request("http://open.spotify.com/$type/$id");

	if (!empty($html)) {
		preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
		return (isset($m[1][0])) ? $m[1][0] : 0;
	}

	return 0;
}

/**
 * getPlaylistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $url
 * @return void
 */
function getPlaylistArtworkURL($w, $url)
{
	$html = $w->request($url);

	if (!empty($html)) {
		preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
		return (isset($m[1][0])) ? $m[1][0] : 0;
	}

	return 0;
}

/**
 * getArtistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @return void
 */
function getArtistArtworkURL($w, $artist)
{
	$parsedArtist = urlencode($artist);
	$html = $w->request("http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json");
	$json = json_decode($html, true);
	// make more resilient to empty json responses
	if (!is_array($json) || empty($json['artist']['image'][1]['#text'])) {
		return '';
	}
	return $json[artist][image][1]['#text'];
}


/**
 * updateLibrary function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updateLibrary($jsonData)
{
	$w = new Workflows('com.vdesabou.nike.plus');
	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$getSettings = 'select use_miles,theme from settings';
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
	$theme = $setting[1];
	endforeach;

	$words = explode('▹', $in_progress_data);


	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');


	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		if (file_exists($w->data() . "/library.db")) {
			unlink($w->data() . "/library.db");
		}
		touch($w->data() . "/library.db");

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table activities (activityId text PRIMARY KEY NOT NULL, heartrate boolean, gps boolean, activityType text, activeTime int, startTimeUtc text, latitude text, longitude text, timeZone text, dstOffset text, deviceType text, timeZoneId text, name text, status text,weather text,emotion text,note text,terrain text,shoes_activityCount int, shoes_percentage float, shoes_name text, shoes_distance float, shoes_retired boolean, averageHeartRate int,distance float, maximumHeartRate int, fuel int, steps int, calories int, duration int, minimumHeartRate int, address text)"';
		exec($sql);

/*
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "CREATE INDEX IndexArtistName ON tracks (artist_name)"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "CREATE INDEX IndexAlbumName ON tracks (album_name)"';
		exec($sql);


		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table user (uri text, username text, name text, image text)"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean)"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table artists (artist_name text, artist_uri text, artist_artwork_path text, artist_biography text, artist_popularity int, artist_years_from text, artist_years_to text, related_artist_name text, related_artist_uri text, related_artist_artwork_path text, PRIMARY KEY (artist_name, related_artist_name))"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "CREATE INDEX indexArtistName ON artists (artist_name)"';
		exec($sql);
*/

		$nb_activities=0;
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
			
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into activities values (' . '\"' . $activity['activityId'] . '\",' . $heartrate. ',' . $gps . ',\"' . $activity['activityType'] . '\",' . $activity['activeTime'] . ',\"' . $activity['startTimeUtc'] . '\",\"' . $latitude . '\",\"' . $longitude. '\",\"' . $activity['timeZone'] . '\",\"' . $dstOffset . '\"' . ',\"' . $activity['deviceType'] . '\"' . ',\"' . $timeZoneId . '\"' . ',\"' . $activity['name'] . '\"' . ',\"' . $activity['status'] . '\"' . ',\"' . $weather . '\"' . ',\"' . $emotion . '\"' . ',\"' . $note . '\"' . ',\"' . $terrain . '\"' . ',' . $shoes_activityCount . '' . ',' . $shoes_percentage . '' . ',\"' . $shoes_name . '\"' . ',' . $shoes_distance . '' . ',' . $shoes_retired . '' . ',' . $averageHeartRate . '' . ',' . $distance . '' . ',' . $maximumHeartRate . '' . ',' . $fuel . '' . ',' . $steps . '' . ',' . $calories . '' . ',' . $duration . '' . ',' . $minimumHeartRate . ',' . '\"'. $address . '\"' .')"';

			exec($sql);
			
			$nb_activities++;
			
			//echo "$sql\n";				
/*
			$playlist_artwork_path = getPlaylistArtwork($w,'black',$playlist['uri'], true);

			if ($playlist['ownedbyuser'] == true) {
				$ownedbyuser = 1;
			} else {
				$ownedbyuser = 0;
			}

			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\",' . $ownedbyuser . ')"';
			exec($sql);

			foreach ($playlist['tracks'] as $track) {

				if ($track['starred'] == true) {
					$starred = 1;
				} else {
					$starred = 0;
				}

				if ($track['playable'] == true) {
					$playable = 1;
				} else {
					$playable = 0;
				}

				//
				// Download artworks
				$track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
				$artist_artwork_path = getArtistArtwork($w,$theme,$track['artist_name'], true);
				$album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

				$album_year = 1995;

				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';

				exec($sql);

				$nb_track++;
				if ($nb_track % 10 === 0) {
					$w->write('Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
				}
			}
*/
		}// end playlists

/*
		$getCount = "select count(distinct uri) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

		$getCount = "select count(distinct uri) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

		$getCount = "select count(distinct artist_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

		$getCount = "select count(distinct artist_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

		$getCount = "select count(distinct album_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

		$getCount = "select count(distinct album_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
		exec($sql);

		$getCount = "select count(*) from playlists";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

		// update counters for playlists
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
		exec($sql);
*/

		$elapsed_time = time() - $words[3];
		displayNotification("Library has been created (" . $nb_activities . " activities) - it took " . beautifyTime($elapsed_time));

		unlink($w->data() . "/update_library_in_progress");

	} else {
		unlink($w->data() . "/update_library_in_progress");
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
}

/**
 * updatePlaylist function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updatePlaylist($jsonData)
{
	$w = new Workflows('com.vdesabou.nike.plus');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$getSettings = 'select theme from settings';
	$dbfile = $w->data() . '/settings.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

	if ($returnValue != 0) {
		displayNotification("Error: cannot read settings");
		unlink($w->data() . "/update_library_in_progress");
		return;
	}


	foreach ($settings as $setting):

		$setting = explode("	", $setting);

	$theme = $setting[0];
	endforeach;

	$words = explode('▹', $in_progress_data);

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$nb_tracktotal = 0;
		foreach ($json as $playlist) {

			$nb_tracktotal += count($playlist['tracks']);

		}
		$w->write('Playlist▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "drop table counters"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
		exec($sql);

		$nb_track = 0;

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from tracks where playlist_uri=\"' . $playlist['uri'] . '\""';
		exec($sql);

		foreach ($json as $playlist) {
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "update playlists set nb_tracks=' . count($playlist['tracks']) . ' where uri=\"' . $playlist['uri'] . '\""';
			exec($sql);


			foreach ($playlist['tracks'] as $track) {

				if ($track['starred'] == true) {
					$starred = 1;
				} else {
					$starred = 0;
				}

				if ($track['playable'] == true) {
					$playable = 1;
				} else {
					$playable = 0;
				}

				//
				// Download artworks
				$track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
				$artist_artwork_path = getArtistArtwork($w,$theme,$track['artist_name'], true);
				$album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

				$album_year = 1995;

				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';
				exec($sql);

				$nb_track++;
				if ($nb_track % 10 === 0) {
					$w->write('Playlist▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
				}
			}
		}

		$getCount = "select count(distinct uri) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

		$getCount = "select count(distinct uri) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

		$getCount = "select count(distinct artist_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

		$getCount = "select count(distinct artist_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

		$getCount = "select count(distinct album_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

		$getCount = "select count(distinct album_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
		exec($sql);

		$getCount = "select count(*) from playlists";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

		// update counters for playlists
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
		exec($sql);

		$elapsed_time = time() - $words[3];

		displayNotificationWithArtwork("\nPlaylist " . $playlist['name'] . " has been updated (" . $nb_track . " tracks) - it took " . beautifyTime($elapsed_time),getPlaylistArtwork($w,'black', $playlist['uri'], true));

		unlink($w->data() . "/update_library_in_progress");
	} else {
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
 * updatePlaylistList function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updatePlaylistList($jsonData)
{
	$w = new Workflows('com.vdesabou.nike.plus');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$getSettings = 'select theme from settings';
	$dbfile = $w->data() . '/settings.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

	if ($returnValue != 0) {
		displayNotification("Error: cannot read settings");
		unlink($w->data() . "/update_library_in_progress");
		return;
	}


	foreach ($settings as $setting):

		$setting = explode("	", $setting);

	$theme = $setting[0];
	endforeach;

	$words = explode('▹', $in_progress_data);

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$nb_playlist_total = count($json);

		$w->write('Playlist List▹0▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "drop table counters"';
		exec($sql);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
		exec($sql);

		foreach ($json as $playlist) {
			$playlists = array();
			$getPlaylists = "select * from playlists where name='" . escapeQuery($playlist['name']) . "'" . " and username='" . $playlist['username'] . "'";
			$dbfile = $w->data() . "/library.db";
			exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

			$nb_playlist++;
			if ($nb_playlist % 4 === 0) {
				$w->write('Playlist List▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');
			}

			if ($returnValue != 0) {
				displayNotification("ERROR: when processing playlist" . escapeQuery($playlist['name']) . " with uri " . $playlist['uri'] . "\n");
				continue;
			}

			// Add the new playlist
			if (count($playlists) == 0) {
				displayNotification("Added playlist " . $playlist['name'] . "\n");
				$playlist_artwork_path = getPlaylistArtwork($w,'black', $playlist['uri'], true);

				if ($playlist['ownedbyuser'] == true) {
					$ownedbyuser = 1;
				} else {
					$ownedbyuser = 0;
				}

				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\",' . $ownedbyuser . ')"';
				exec($sql);

				foreach ($playlist['tracks'] as $track) {

					if ($track['starred'] == true) {
						$starred = 1;
					} else {
						$starred = 0;
					}

					if ($track['playable'] == true) {
						$playable = 1;
					} else {
						$playable = 0;
					}

					//
					// Download artworks
					$track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
					$artist_artwork_path = getArtistArtwork($w, $theme, $track['artist_name'], true);
					$album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

					$album_year = 1995;

					$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';

					exec($sql);

				}
			} else {
				continue;
			}
		}

		// check for deleted playlists
		$playlists = array();

		$getPlaylists = "select * from playlists";

		$dbfile = $w->data() . "/library.db";

		exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

		if ($returnValue != 0) {
			displayNotification("ERROR: when checking deleted playlist");
		} else {
			foreach ($playlists as $pl):
				$pl = explode("	", $pl);

			$found = 0;
			foreach ($json as $playlist) {
				if (escapeQuery($playlist['name']) == escapeQuery($pl[1]) && $playlist['username'] == $pl[4]) {
					$found = 1;
					break;
				}
			}
			if ($found != 1) {
				displayNotification("Playlist " . escapeQuery($pl[1]) . " was removed" . "\n");
				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from playlists where uri=\"' . $pl[0] . '\""';
				exec($sql);
				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from tracks where playlist_uri=\"' . $pl[0] . '\""';
				exec($sql);
			}
			endforeach;
		}

		$getCount = "select count(distinct uri) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

		$getCount = "select count(distinct uri) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

		$getCount = "select count(distinct artist_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

		$getCount = "select count(distinct artist_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

		$getCount = "select count(distinct album_name) from tracks";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

		$getCount = "select count(distinct album_name) from tracks where starred=1";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
		exec($sql);

		$getCount = "select count(*) from playlists";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

		// update counters for playlists
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
		exec($sql);

		$elapsed_time = time() - $words[3];
		displayNotification("Playlist list has been updated - it took " . beautifyTime($elapsed_time));

		unlink($w->data() . "/update_library_in_progress");
	} else {
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
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
 * getArtistUriFromName function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $artist
 * @return void
 */
function getArtistUriFromName($w,$theme,$artist) {
	$getArtists = "select artist_uri,artist_artwork_path,artist_biography from artists where artist_name='" . $artist . "'";

	$dbfile = $w->data() . "/library.db";
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getArtists\" 2>&1", $artists, $returnValue);

	if ($returnValue != 0) {
		handleDbIssue($theme);
		return "";
	}

	if (count($artists) > 0) {

		$theartist = explode("	", $artists[0]);
		return $theartist[0];
	}
	return "";
}

/**
 * getAlbumUriFromName function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $album
 * @param mixed $artist
 * @return void
 */
function getAlbumUriFromName($w,$theme,$album,$artist) {
	$getTracks = "select album_uri from tracks where album_name='" . $album . "' and artist_name='" . $artist . "'";

	$dbfile = $w->data() . "/library.db";
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

	if ($returnValue != 0) {
		handleDbIssue($theme);
		return "";
	}

	if (count($tracks) > 0) {

		$thealbum = explode("	", $tracks[0]);
		return $thealbum[0];
	}
	return "";
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

/*


This function was mostly taken from SpotCommander.

SpotCommander is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

SpotCommander is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with SpotCommander.  If not, see <http://www.gnu.org/licenses/>.

Copyright 2013 Ole Jon Bjørkum

 * getLyrics function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @param mixed $title
 * @return void
 */
function getLyrics($w,$artist,$title) {
	$query_artist = $artist;
	$query_title = $title;

	if(stristr($query_artist, 'feat.'))
	{
		$query_artist = stristr($query_artist, 'feat.', true);
	}
	elseif(stristr($query_artist, 'featuring'))
	{
		$query_artist = stristr($query_artist, 'featuring', true);
	}
	elseif(stristr($query_title, ' con '))
	{
		$query_title = stristr($query_title, ' con ', true);
	}
	elseif(stristr($query_artist, ' & '))
	{
		$query_artist = stristr($query_artist, ' & ', true);
	}

	$query_artist = str_replace('&', 'and', $query_artist);
	$query_artist = str_replace('$', 's', $query_artist);
	$query_artist = strip_string(trim($query_artist));
	$query_artist = str_replace(' - ', '-', $query_artist);
	$query_artist = str_replace(' ', '-', $query_artist);

	$query_title = str_ireplace(array('acoustic version', 'new album version', 'original album version', 'album version', 'bonus track', 'clean version', 'club mix', 'demo version', 'extended mix', 'extended outro', 'extended version', 'extended', 'explicit version', 'explicit', '(live)', '- live', 'live version', 'lp mix', '(original)', 'original edit', 'original mix edit', 'original version', '(radio)', 'radio edit', 'radio mix', 'remastered version', 're-mastered version', 'remastered digital version', 're-mastered digital version', 'remastered', 'remaster', 'remixed version', 'remix', 'single version', 'studio version', 'version acustica', 'versión acústica', 'vocal edit'), '', $query_title);

	if(stristr($query_title, 'feat.'))
	{
		$query_title = stristr($query_title, 'feat.', true);
	}
	elseif(stristr($query_title, 'featuring'))
	{
		$query_title = stristr($query_title, 'featuring', true);
	}
	elseif(stristr($query_title, ' con '))
	{
		$query_title = stristr($query_title, ' con ', true);
	}
	elseif(stristr($query_title, '(includes'))
	{
		$query_title = stristr($query_title, '(includes', true);
	}
	elseif(stristr($query_title, '(live at'))
	{
		$query_title = stristr($query_title, '(live at', true);
	}
	elseif(stristr($query_title, 'revised'))
	{
		$query_title = stristr($query_title, 'revised', true);
	}
	elseif(stristr($query_title, '(19'))
	{
		$query_title = stristr($query_title, '(19', true);
	}
	elseif(stristr($query_title, '(20'))
	{
		$query_title = stristr($query_title, '(20', true);
	}
	elseif(stristr($query_title, '- 19'))
	{
		$query_title = stristr($query_title, '- 19', true);
	}
	elseif(stristr($query_title, '- 20'))
	{
		$query_title = stristr($query_title, '- 20', true);
	}

	$query_title = str_replace('&', 'and', $query_title);
	$query_title = str_replace('$', 's', $query_title);
	$query_title = strip_string(trim($query_title));
	$query_title = str_replace(' - ', '-', $query_title);
	$query_title = str_replace(' ', '-', $query_title);
	$query_title = rtrim($query_title, '-');

	$uri = strtolower('http://www.lyrics.com/' . $query_title .'-lyrics-' . $query_artist . '.html');

	$error = false;
	$no_match = false;

	$file = $w->request($uri);

	preg_match('/<div id="lyric_space">(.*?)<\/div>/s', $file, $lyrics);

	$lyrics = (empty($lyrics[1])) ? '' : $lyrics[1];

	if(empty($file))
	{
		$error = true;
	}
	elseif(empty($lyrics) || stristr($lyrics, 'we do not have the lyric for this song') || stristr($lyrics, 'lyrics are currently unavailable') || stristr($lyrics, 'your name will be printed as part of the credit'))
	{
		$no_match = true;
	}
	else
	{
		if(strstr($lyrics, 'Ã') && strstr($lyrics, '©')) $lyrics = utf8_decode($lyrics);

		$lyrics = trim(str_replace('<br />', '<br>', $lyrics));

		if(strstr($lyrics, '<br>---')) $lyrics = strstr($lyrics, '<br>---', true);
	}

	if($error)
	{
		displayNotification("Timeout or failure. Try again");
	}
	elseif($no_match)
	{
		displayNotification("Sorry there is no match for this track");
	}
	else
	{
		$lyrics = strip_tags($lyrics);

		//$lyrics = (strlen($lyrics) > 1303) ? substr($lyrics,0,1300).'...' : $lyrics;

		if($lyrics=="")
		{
			displayNotification("Sorry there is no match for this track");
		}
		else
		{
			echo "🎤 $title by $artist\n---------------------------\n$lyrics";
		}
	}
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