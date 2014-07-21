<?php

require_once('./src/nikeplusphp.4.5.1.php');


class AlfredNikePlusPHP extends NikePlusPHP {

// Thanks to https://github.com/Schnitzel/nikeplus_fuelband

/**
   * Returns the last full activity for own user account.
   *
   * @param int $week_offset
   *   The offset of the current week to search in. Used for recursion.
   *
   * @param bool $monday_had_fuelpoints
   *   If monday of next week of requested week had fuelpoints.
   *   Used for recursion. Defaults to FALSE.
   *
   * @return bool|object
   *   If the activity was found, it will return an object containing the
   *   information about that activity. Otherwise, returns FALSE.
   */
  public function getMyLastFullActivity($week_offset = 0, $monday_had_fuelpoints = FALSE) {

	
    // Load the timestamp from monday of the requested week.
    if ($week_offset == 0) {
      $monday_of_week_timestamp = strtotime('monday this week');
    }
    else {
      $monday_of_week_timestamp = strtotime('- ' . $week_offset . ' week', strtotime('monday this week'));
    }

    // Build the Year, Month and Monday strings for GMT for requested week.
    $year = gmdate("Y", $monday_of_week_timestamp);
    $month = gmdate("n", $monday_of_week_timestamp);
    $monday = gmdate("j", $monday_of_week_timestamp);

    // Build URL for request
    $url = 'https://secure-nikeplus.nike.com/plus/' . 'activity/fuelband/' . rawurlencode($this->userId) . '/week/' . $year . '/' . $month . '/' . $monday;

	echo "$url\n";
    $html = $this->_getHtml($url);

    $activities = $this->searchActivity($html, 'baked_data');
    

    // If $monday_had_fuelpoints == TRUE we were called recursively and the next day
    // (Monday next week) has fuelpoints, so we can directly return the last day of
    // this week (Sunday).
    if ($monday_had_fuelpoints) {
      return $activities->weeklyList[0]->dataPoints[6];
    }

    // $activities always contains a full week. We go backwards through it and search
    // for an activity with fuelpoints.
    for ($i=6; $i >= 0; $i--) {
    	
    	$tmp = $activities->weeklyList[0]->dataPoints[$i]->fuel;
      echo "--- $tmp\n";
      if ($activities->weeklyList[0]->dataPoints[$i]->fuel != 0 && $i == 0) {
        // Found activity with fuelpoints but we are already on the Monday of the week.
        // But because we cannot return one activity earlier, we load one week
        // earlier and tell the function that Monday has fuelpoints.
        
        echo "Found activity with fuelpoints but we are already on the Monday of the week\n";
        return $this->getMyLastFullActivity($week_offset + 1, TRUE);

      }
      elseif ($activities->weeklyList[0]->dataPoints[$i]->fuel != 0 && $i != 0) {
        // Found activity with fuelpoints and we are not on Monday.
        // Return one activity older than the found one.
        
        echo "Found activity with fuelpoints and we are not on Monday\n";
        return $activities->weeklyList[0]->dataPoints[$i-1];
      }
    }
    echo "No activity with fuelpoints this week, we load one week earlier.\n";
    // No activity with fuelpoints this week, we load one week earlier.
    return $this->getMyLastFullActivity($week_offset + 1, FALSE);
  }

/*
  public function getAllFuelband($week_offset = 0, $monday_had_fuelpoints = FALSE) {
	
	$loop=false
	$thismonth = strtotime('this month');

    // Build the Year, Month and Monday strings for GMT for requested week.
    $year = gmdate("Y", $monday_of_week_timestamp);	
	$month = gmdate("n", $monday_of_week_timestamp);
	
	
	while($loop == true) {
	    // Build URL for request
	    $url = 'https://secure-nikeplus.nike.com/plus/' . 'activity/fuelband/' . rawurlencode($this->userId) . '/week/' . $year . '/' . $month;
	
		echo "$url\n";
	    $html = $this->_getHtml($url);
	
	    $activities = $this->searchActivity($html, 'baked_data');
	    
	    
		if(isset($results->activities)) {
			foreach($results->activities as $activity) {
				$this->activities[$activity->activity->activityId] = $activity->activity;
			}
			$start += $increment + 1;
			$limit += $start;
		} else {
			$loop = false;
			break;
		}
	}
				

    // Build URL for request
    $url = 'https://secure-nikeplus.nike.com/plus/' . 'activity/fuelband/' . rawurlencode($this->userId) . '/week/' . $year . '/' . $month;

	echo "$url\n";
    $html = $this->_getHtml($url);

    $activities = $this->searchActivity($html, 'baked_data');
    

    // If $monday_had_fuelpoints == TRUE we were called recursively and the next day
    // (Monday next week) has fuelpoints, so we can directly return the last day of
    // this week (Sunday).
    if ($monday_had_fuelpoints) {
      return $activities->weeklyList[0]->dataPoints[6];
    }

    // $activities always contains a full week. We go backwards through it and search
    // for an activity with fuelpoints.
    for ($i=6; $i >= 0; $i--) {
    	
    	$tmp = $activities->weeklyList[0]->dataPoints[$i]->fuel;
      echo "--- $tmp\n";
      if ($activities->weeklyList[0]->dataPoints[$i]->fuel != 0 && $i == 0) {
        // Found activity with fuelpoints but we are already on the Monday of the week.
        // But because we cannot return one activity earlier, we load one week
        // earlier and tell the function that Monday has fuelpoints.
        
        echo "Found activity with fuelpoints but we are already on the Monday of the week\n";
        return $this->getMyLastFullActivity($week_offset + 1, TRUE);

      }
      elseif ($activities->weeklyList[0]->dataPoints[$i]->fuel != 0 && $i != 0) {
        // Found activity with fuelpoints and we are not on Monday.
        // Return one activity older than the found one.
        
        echo "Found activity with fuelpoints and we are not on Monday\n";
        return $activities->weeklyList[0]->dataPoints[$i-1];
      }
    }
    echo "No activity with fuelpoints this week, we load one week earlier.\n";
    // No activity with fuelpoints this week, we load one week earlier.
    return $this->getMyLastFullActivity($week_offset + 1, FALSE);
  }
*/
  
  
  
  /**
   * Parses an html string to return the details of a specific activity.
   *
   * @param string $html
   *   The html to search in.
   * @param string $activity
   *   What activity (javascript object name) to search for,
   *   'window.np.' will be prefixed.
   * @return object
   *   A object with the details of the activity.
   */
  private function searchActivity($html, $activity) {
    // @todo: refactor this code.
    $words = explode('window.np.' . $activity . ' = ', $html);
    $words = explode('</script>', $words[1]);
    // Remove the ";" from the end of the string.
    $words[0] = substr($words[0], 0, strlen($words[0]) - 2);
    return json_decode($words[0]);
  }

  private function _getHtml($path) {
		//$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent);
		curl_setopt($ch, CURLOPT_URL, $path);
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_errno = curl_errno($ch);
		curl_close($ch);
		
	    if ($curl_errno == 0) {
	      return $data;
	    }
	    else {
	      // @todo: handle errors here?
	      return FALSE;
	    }		
  }
}
?>