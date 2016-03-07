<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class Station extends Base {


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


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

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" '
				. 'earliest=-24h late != 999 '
				. 'nextstop="' . $station . '" '
				. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
				. '| stats max(late) AS "Minutes Late", max(time) AS "time", max(train_line) AS "Train Line" by trainno '
				. '| sort time desc';

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Most recent trains that have arrived at the station named '$station'";

			$this->redisSet($redis_key, $retval);
			return($retval);

		}


	} // End of getTrains()


	/**
	* Get the latest trains for this station.
	*
	* @param string $station Which station do we want trains from?
	*
	* @return array
	*/
	function getTrainsLatest($station) {

		$retval = array();
		$redis_key = "station/getTrainsLatest-${station}";

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {
			$query = 'search index="septa_analytics" '
					. 'earliest=-24h late != 999 '
					. 'nextstop="' . $station . '" '
					. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
					. '| eval id = trainno . "-" . dest '
					. '| stats max(late) AS "Minutes Late", max(time) AS "time", max(train_line) AS "Train Line" by id '
					. '| sort "Minutes Late" desc '
					. '| head '
					. '| chart max("Minutes Late") AS "Minutes Late" by id '
					. '| sort "Minutes Late" desc';

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Latest trains that have arrived at the station named '$station'";

			$this->redisSet($redis_key, $retval);
			return($retval);

		}

	} // End of getTrainsLatest()


	/**
	* Get stats for this station: how many trains per hour versus 
	* total minutes late that hour.
	*
	* @param string $station Which station do we want trains from?
	*
	* @return array
	*/
	function getStats($station) {

		$retval = array();
		$redis_key = "station/getStats-${station}";
		$redis_ttl = 300;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

			$query = 'search index="septa_analytics" '
				. 'earliest=-24h late != 999 '
				. 'nextstop="' . $station . '" '
				. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
				. '| eval id = trainno . "-" . dest | '
				. 'timechart span=1h latest(late) AS late by id '
				. '| addtotals '
				. '| timechart span=1h latest(Total) AS "Total Minutes Late" '
				. '| join _time [search index="septa_analytics" late != 999 nextstop="Ardmore" '
					. '| eval id = trainno . "-" . dest '
					. '| timechart span=1h count(id) AS "# Trains"'
					. ']'
				;

			$retval = $this->query($query);
			$retval["metadata"]["_comment"] = "Stats for station '$station'. How many trains per hour versus total minutes late that hour.";

			$this->redisSetEx($redis_key, $retval, $redis_ttl);
			return($retval);

		}

	} // End of getTrainsLatest()



} // End of class Train


