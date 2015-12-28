<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on the entire train system.
*/
class System extends Base {


	function __construct($splunk) {
		parent::__construct($splunk);
	} // End of __construcr()


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

		$query = 'search index="septa_analytics" earliest=-' . $num_hours . 'h '
			. 'late != 0 late != 999 '
			. '| eval id = trainno . "-" . dest '
			. '| timechart span=' . $span_min . 'm max(late) by id'
			;

		$retval = $this->query($query);
		$retval["metadata"]["_comment"] = "The overall status of the train system";

		return($retval);

	} // End of getTopLatestTrains()


	/**
	* Get the day over day list of total minutes late of the entire system.
	*
	* @param integer $num_days How many days to go back?
	*
	*/
	function getTotalMinutesLateByDay($num_days) { 

		$retval = array();

		$query = 'search index="septa_analytics" late != 0 late != 999 '
			. 'earliest=-' . $num_days . 'd@d '
			. '| eval id = trainno . "-" . dest '
			. '| timechart span=1h eval(sum(late)/60) AS "Minutes Late" '
			. '| timewrap d';

		$retval = $this->query($query);
		$retval["metadata"]["_comment"] = "Day over day list of total minutes late for the system";

		return($retval);

	} // End of getTotalMinutesLateByDay()


} // End of class Train


