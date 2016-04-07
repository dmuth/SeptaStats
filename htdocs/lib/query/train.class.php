<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used for searching data for a specific train.
*/
class Train extends Base {


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


	/**
	* Retreive a list of all trains seen in the last 24 hours
	*
	* @return array An array of trains
	*/
	function getTrains() {

		$retval = array();
		$redis_key = "trains";
		$redis_ttl = 600;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" earliest=-24h '
				. '| fields trainno SOURCE dest'
				. '| sort 0 trainno '
				. '| dedup trainno '
				;

			$retval = $this->query($query);

			//
			// Go through our results and extract just the fields we want, as
			// well as 
			//
			$data = array();

			foreach ($retval["data"] as $key => $value) {

				unset($value["_raw"]);
				unset($value["_time"]);

				if (!isset($value["SOURCE"])) {
					continue;
				}

				$value["source"] = $value["SOURCE"];
				unset($value["SOURCE"]);
				$data[$key] = $value;

			}

			$retval["data"] = $data;

			$this->redisSetEx($redis_key, $retval, $redis_ttl);
			return($retval);

		}


	} // End of getTrains()


	/**
	* Retrieve details for a specific train.
	*
	* @param integer $trainno Our train number.
	*
	* @return array An array of stops this train made and how 
	*	many minutes late it was for each stop.
	*/
	function get($trainno) {

		$retval = array();
		$redis_key = "train/get-${trainno}";
		//$redis_key .= time(); // Debugging

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" earliest=-20h '
				. 'trainno=' . $trainno 
				. '| eval time=strftime(_time,"%Y-%m-%dT%H:%M:%S") '
				. '| chart latest(late) AS "Minutes Late", latest(time), '
				. 'latest(lat) AS lat, latest(lon) AS lon '
				. 'by nextstop, '
				. '| sort latest(time) '
				. '| fields nextstop "Minutes Late" lat lon'
				;
			//print $query; // Debugging

			$retval = $this->query($query);
			$retval["metadata"]["trainno"] = $trainno;
			$retval["metadata"]["_comment"] = "What stops did train '$trainno' make, and how late was it each stop?";

			$this->redisSet($redis_key, $retval);		
			return($retval);

		}


	} // End of get()


	/**
	* Retrieve details for a specific train.
	*
	* @param integer $trainno Our train number.
	*
	* @return array An array of stops this train made and how 
	*	many minutes late it was for each stop.
	*/
	function getLatest($trains) {

		$retval = array();
		$redis_key = "train/get-latest-" . join(",", $trains);
		//$redis_key .= time(); // Debugging

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			//
			// Loop through our array and create a base query string for train
			//
			$query_parts = array();
			foreach ($trains as $key => $value) {

				$query = 'search index="septa_analytics" earliest=-20h '
					. 'trainno=' . $value . ' '
					. '| eval time=strftime(_time,"%Y-%m-%dT%H:%M:%S") '
					. '| eval source=SOURCE '
					. '| fields time trainno nextstop source dest late lat lon'
					. '| head 1'
					;

				$query_parts[] = $query;

			}

			//
			// Now go through our query parts and glue them into a proper array.
			//
			$query = "";
			foreach ($query_parts as $key => $value) {

				if ($key == 0) {
					$query .= $value;

				} else {
					$query .= "| append [ $value ] ";

				}

			}

			//print $query; // Debugging

			$retval = $this->query($query);

			foreach ($retval["data"] as $key => $value) {
				unset($retval["data"][$key]["_raw"]);
				unset($retval["data"][$key]["_time"]);
			}

			$retval["metadata"]["trains"] = join(",", $trains);
			$retval["metadata"]["_comment"] = "What is the latest datapoint for each train?";

			$this->redisSet($redis_key, $retval);		
			return($retval);

		}


	} // End of get()


	/**
	* Retrieve history for a specific train.
	*
	* @param integer $trainno Our train number.
	*
	* @return array An array of stops and lateness by day.
	*/
	function getHistoryByDay($trainno) {

		$retval = array();
		$redis_key = "train/getHistoryByDay-${trainno}";
		//$redis_key .= time(); // Debugging

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d '
				. '| eval late0=late '
				. '| append [search index="septa_analytics" trainno=' . $trainno .' earliest=-1d@d latest=-0d@d |eval late1=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-2d@d latest=-1d@d |eval late2=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-3d@d latest=-2d@d |eval late3=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-4d@d latest=-3d@d |eval late4=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-5d@d latest=-4d@d |eval late5=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-6d@d latest=-5d@d |eval late6=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-7d@d latest=-6d@d |eval late7=late] '
				. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
				. '| chart latest(late0) AS "Minutes Late", latest(late1) AS "Minutes Late - Yesterday", latest(late2) AS "Minutes Late - 2 Days Ago", latest(late3) AS "Minutes Late - 3 Days Ago", latest(late4) AS "Minutes Late - 4 Days Ago", latest(late5) AS "Minutes Late - 5 Days Ago", latest(late6) AS "Minutes Late - 6 Days Ago", latest(late7) AS "Minutes Late - 7 Days Ago", '
				. 'latest(lat) AS lat, latest(lon) AS lon '
				. 'latest(time) by nextstop '
				. '| sort latest(time) '
				. '| fields nextstop "Minutes Late" "Minutes Late - Yesterday" "Minutes Late - 2 Days Ago" "Minutes Late - 3 Days Ago" "Minutes Late - 4 Days Ago" "Minutes Late - 5 Days Ago" "Minutes Late - 6 Days Ago" "Minutes Late - 7 Days Ago" '
				. 'lat lon '
				;
			//print $query; // Debugging

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Multiple days worth of stops and lateness for train '$trainno'";
	
			$this->redisSet($redis_key, $retval);
			return($retval);

		}

	} // End of getHistoryByDay()


	/**
	* Get our historical average lateness and compare it to current lateness.
	*
	* @param integr $trainno Our train number.
	*/
	function getHistoryHistoricalAvg($trainno) {
	
		$retval = array();
		$redis_key = "train/getHistoryHistoricalAvg-${trainno}";
		//$redis_key .= time();

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d |eval late0=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-1d@d latest=-0d@d |eval late1=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-2d@d latest=-1d@d |eval late2=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-3d@d latest=-2d@d |eval late3=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-4d@d latest=-3d@d |eval late4=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-5d@d latest=-4d@d |eval late5=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-6d@d latest=-5d@d |eval late6=late] '
				. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-7d@d latest=-6d@d |eval late7=late] '
				. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
				. '| chart latest(late0) AS "Minutes Late", latest(late1) AS "Minutes Late - Yesterday", latest(late2) AS "Minutes Late - 2 Days Ago", latest(late3) AS "Minutes Late - 3 Days Ago", latest(late4) AS "Minutes Late - 4 Days Ago", latest(late5) AS "Minutes Late - 5 Days Ago", latest(late6) AS "Minutes Late - 6 Days Ago", latest(late7) AS "Minutes Late - 7 Days Ago", '
				. 'latest(lat) AS lat, latest(lon) AS lon, '
				. 'latest(time) by nextstop '
				. '| sort latest(time) '
				. '| eval "Average Minutes Late"= (if(isnotnull($Minutes Late - Yesterday$), $Minutes Late - Yesterday$, 0) + if(isnotnull($Minutes Late - 2 Days Ago$), $Minutes Late - 2 Days Ago$, 0) + if(isnotnull($Minutes Late - 3 Days Ago$), $Minutes Late - 3 Days Ago$, 0) + if(isnotnull($Minutes Late - 4 Days Ago$), $Minutes Late - 4 Days Ago$, 0) + if(isnotnull($Minutes Late - 5 Days Ago$), $Minutes Late - 5 Days Ago$, 0) + if(isnotnull($Minutes Late - 6 Days Ago$), $Minutes Late - 6 Days Ago$, 0) + if(isnotnull($Minutes Late - 7 Days Ago$), $Minutes Late - 7 Days Ago$, 0) ) / 7 '
				. '| fields nextstop "Average Minutes Late" "Minutes Late" lat lon'
				;

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Average lateness compared to current lateness for train '$trainno'";

			$this->redisSet($redis_key, $retval);
			return($retval);

		}

	} // End of getHistoryHistoricalAvg()


} // End of class Train


