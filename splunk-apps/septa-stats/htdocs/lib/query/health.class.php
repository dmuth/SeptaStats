<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class Health extends Base {


	function __construct($splunk) {
		parent::__construct($splunk, null);
	} // End of __construct()


	/**
	* Perform a basic health check against Splunk.
	*
	* @return array This has a field called "ok" which is either true or false.
	*
	*/
	function getHealth() {

		$retval = array();

		$query = 'search index="septa_analytics" '
			//
			// Have a day-long window here to account for trains legitimately not running for
			// extended periods.  If Splunk fails to search (due to disk space), it will return
			// zero results in any case.
			//
			. 'earliest=-24h late != 999 '
			//. ' foobar ' // Debugging
			. '| head 1 '
			;

		$retval = $this->query($query);
		$retval["metadata"]["_comment"] = "Health Check";

		$retval["ok"] = false;
		if (isset($retval["data"]) && count($retval["data"])) {
			$retval["ok"] = true;
		}
		
		return($retval);

	} // End of getHealth()


	/**
	* Get the most recent trains that have arrived at the station.
	*
	* @param string $station Which station do we want trains from?
	*
	* @return array
	*/
	function getTrains($station) {

		$retval = array();
		$redis_key = "station/getTrains-${station}";
		//$redis_key .= time(); // Debugging

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" '
				. 'earliest=-24h late != 999 '
				. 'nextstop="' . $station . '" '
				. '| eval time=strftime(_time,"%Y-%m-%dT%H:%M:%S") '
				. '| stats max(late) AS "Minutes Late", max(time) AS "time", max(train_line) AS "Train Line" by trainno '
				. '| sort time desc';

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Most recent trains that have arrived at the station named '$station'";

			$this->redisSet($redis_key, $retval);
			return($retval);

		}


	} // End of getTrains()


} // End of class Health


