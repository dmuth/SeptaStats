<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class System extends Base {


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


	/**
	* Get the top most latest trains over a specific period of time.
	*
	* @param integer $num_trains How many "top trains" do we want?
	* 
	* @param integer $num_hours How many hours do we want to go back?
	*
	* @param integer $span_min How many minutes does each point in the graph span?
	*
	* @return array An array of the latest trains over time.
	*/
	function getTopLatestTrains($num_trains, $num_hours, $span_min = 10) {

		$retval = array();
		$redis_key = "system/getTopLatestTrains";

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" earliest=-' . $num_hours . 'h '
				. 'late != 0 late != 999 '
				. '| eval id = trainno . "-" . dest '
				. '| timechart span=' . $span_min . 'm max(late) by id'
				;

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "The overall status of the train system";
			$this->redisSet($redis_key, $retval);

			return($retval);

		}
		
	} // End of getTopLatestTrains()


	/**
	* 
	* Get stats on trains across the entire system over the last 5 minutes
	*
	* @return array An array of all trains currently running.
	*/
	function getLatestTrains() {

		$retval = array();
		$redis_key = "system/getLatestTrains";
		//$redis_key .= time(); // Debugging
		$redis_ttl = 300;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" earliest=-5m '
				. 'late != 999 '
				. '| eval id = trainno . "-" . dest '
				. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
				. '| eval source=SOURCE '
				. '| stats '
				. 'latest(time) AS time, '
				. 'latest(late) AS late, '
				. 'latest(lat) AS lat, latest(lon) AS lon, '
				. 'latest(nextstop) AS nextstop, '
				. 'latest(source) AS source, '
				. 'latest(dest) AS dest '
				. 'by id '
				;

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Info on all currently running trains.";
			$this->redisSetEx($redis_key, $retval, $redis_ttl);

			return($retval);

		}
		
	} // End of getLatestTrains()


	/**
	* This is a wrapper to getLatestTrains(), which then summarizes that data and returns it.
	* This is so people who want stats don't need to keep hammering the endpoint that returns
	* all of the the train data. 
	*
	* @return array An associative array with stats of all trains.
	*/
	function getLatestTrainsStats() {

		$retval = array();
		$redis_key = "system/getLatestTrainsStats";
		//$redis_key .= time(); // Debugging
		$redis_ttl = 300;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$data = $this->getLatestTrains();

			$retval["data"] = array();
			$retval["data"]["num_trains"] = 0;
			$retval["data"]["total_min_late"] = 0;
			$retval["data"]["avg_min_late"] = 0;
			$retval["data"]["num_trains_over_5_min_late"] = 0;
			$retval["data"]["num_trains_over_15_min_late"] = 0;
			$retval["data"]["num_trains_over_30_min_late"] = 0;
			$retval["data"]["timestamp"] = 0;

			foreach ($data["data"] as $key => $value) {

				$retval["data"]["num_trains"]++;

				$late = $value["late"];
				//$late = 22; // Debugging
				$retval["data"]["total_min_late"] += $late;

				if ($late >= 5) {
					$retval["data"]["num_trains_over_5_min_late"]++;
				}

				if ($late >= 15) {
					$retval["data"]["num_trains_over_15_min_late"]++;
				}

				if ($late >= 30) {
					$retval["data"]["num_trains_over_30_min_late"]++;
				}

			}

			$retval["data"]["avg_min_late"] = sprintf("%.1f", 
				$retval["data"]["total_min_late"] / $retval["data"]["num_trains"]);

			$retval["metadata"] = array();
			$retval["metadata"]["_comment"] = "Statas on all currently running trains.";

			$this->redisSetEx($redis_key, $retval, $redis_ttl);

			return($retval);

		}
		
	} // End of getLatestTrainsStats()


	/**
	* Get the day over day list of total minutes late of the entire system.
	*
	* @param integer $num_days How many days to go back?
	*
	*/
	function getTotalMinutesLateByDay($num_days) { 

		$retval = array();
		$redis_key = "system/getTotalMinutesLateByDay";
		$redis_ttl = 1800;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {
			$query = 'search index="septa_analytics" late != 0 late != 999 '
				. 'earliest=-' . $num_days . 'd@d '
				. '| eval id = trainno . "-" . dest '
				. '| timechart span=1h eval(sum(late)/60) AS "Minutes Late" '
				. '| timewrap d';

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Day over day list of total minutes late for the system";

			$this->redisSetEx($redis_key, $retval, $redis_ttl);
			return($retval);

		}

	} // End of getTotalMinutesLateByDay()


} // End of class Train


