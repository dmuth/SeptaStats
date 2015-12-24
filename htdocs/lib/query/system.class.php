<?php

namespace Septa\Query;

/**
* This class is used to bring up data on the entire train system.
*/
class System  {


	/**
	* Our Splunk object.
	*/
	private $splunk;


	/**
	* Our constructor.
	*
	* @param object $splunk Our Splunk search object.
	*/
	function __construct($splunk) {

		$this->splunk = $splunk;

	} // End of __constructor()


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

		$len = count($retval["data"]);
		$time_t = time();

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

		return($retval);

	} // End of getTotalMinutesLateByDay()


	/**
	* Wrapper to run our query then get metadata.
	*
	* @param string $query Our Splunk query.
	*
	* @return array An array of metadata about the search then our regular data.
	*/
	private function query($query) {

		$retval = array();

		//
		// We want metadata to be first, as it makes debugging a little bit easier.
		//
		$data = $this->splunk->query($query);
		$retval["metadata"] = $this->splunk->getResultsMeta();
		$retval["data"] = $data;

		return($retval);

	} // End of query()


} // End of class Train


