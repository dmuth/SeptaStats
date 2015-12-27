<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class Stations extends Base {


	function __construct($splunk) {
		parent::__construct($splunk);
	} // End of __construcr()


	/**
	* Get the most recent trains that have arrived at the station.
	*
	* @return array
	*/
	function getStations() {

		$retval = array();

		$query = 'search index="septa_analytics" earliest=-24h '
			. '| fields nextstop '
			. '| sort 0 nextstop '
			. '| dedup nextstop'
			;

		$retval = $this->query($query);

		return($retval);

	} // End of getTrains()


} // End of class Stations


