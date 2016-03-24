<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class Stations extends Base {


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


	/**
	* Get the most recent trains that have arrived at the station.
	*
	* @return array
	*/
	function getStations() {

		$retval = array();
		$redis_key = "stations";

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" earliest=-24h '
				. '| fields nextstop '
				//
				// Zero returns all results, otherwise there is a 
				// default limit of 10,000. (whyyy?)
				//
				. '| sort 0 nextstop '
				. '| dedup nextstop'
				;

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "A list of all stations";

			$stations = array();
			foreach ($retval["data"] as $key => $value) {
				$station = $value["nextstop"];
				$stations[$station] = $station;
			}

			$retval["data"] = $stations;
			$this->redisSet($redis_key, $retval);

			return($retval);

		}

	} // End of getTrains()


} // End of class Stations


