<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on specific lines.
*/
class Line extends Base {


	private $lines = array(
		"Airport" => 1,
		"Chestnut Hill East" => 1,
		"Chestnut Hill West" => 1,
		"Cynwyd" => 1,
		"Fox Chase" => 1,
		"Glenside" => 1,
		"Lansdale/Doylestown" => 1,
		"Manayunk/Norristown" => 1,
		"Media/Elwyn" => 1,
		"Paoli/Thorndale" => 1,
		"Trenton" => 1,
		"Warminster" => 1,
		"West Trenton" => 1,
		"Wilmington/Newark" => 1,
		);


	function __construct($splunk) {
		parent::__construct($splunk);
	} // End of __construcr()


	/**
	* Get all late trains for a specific line.
	*
	* @param integer $line What line are we searching?
	*
	* @param integer $span_min How many minutes does each point in the graph span?
	*
	* @return array An array of the latest trains over time.
	*/
	function getLateTrains($line, $num_hours, $span_min = 10) {

		$retval = array();

		$query = 'search index="septa_analytics" earliest=-' . $num_hours . 'h '
			. 'train_line="' . $line . '" late != 0 late != 999 '
			. '| eval id = trainno . "-" . dest '
			. '| timechart span=' . $span_min . 'm max(late) by id';

		$retval = $this->query($query);
		$retval["metadata"]["line"] = $line;

		return($retval);

	} // End of getLateTrains()


	/**
	* Return an array of all train line names.
	* These are the basenames, excluding the " (Inbound)" and " (Outbound)" suffixes.
	*/
	function getLines() {

		$retval = array();

		//
		// Go through our list of lines and create keys which are lowercased 
		// and have non-alphas replaced with dashes
		//
		foreach ($this->lines as $key => $value) {

			$key2 = strtolower($key);
			$key2 = preg_replace("|[^a-zA-Z]|", "-", $key2);

			$retval[$key2] = $key;

		}


		return($retval);

	} // End of getLines()


} // End of class Line


