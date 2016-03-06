<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used to bring up data on specific lines.
*/
class Line extends Base {

	//
	// The names of all lines.
	// The key is something we can safely have in a URL which can 
	// then be searched on.  The value is the human-readable name of the line.
	//
	// Note that these do not include the "(Inbound)" or " (Outbound)" suffix.
	//
	private $lines = array(
		"airport" => "Airport",
		"chestnut-hill-east" => "Chestnut Hill East",
		"chestnut-hill-west" => "Chestnut Hill West",
		"cynwyd" => "Cynwyd",
		"fox-chase" => "Fox Chase",
		"glenside" => "Glenside",
		"lansdale-doylestown" => "Lansdale/Doylestown",
		"manayunk-norristown" => "Manayunk/Norristown",
		"media-elwyn" => "Media/Elwyn",
		"paoli-thorndale" => "Paoli/Thorndale",
		"trenton" => "Trenton",
		"warminster" => "Warminster",
		"west-trenton" => "West Trenton",
		"wilmington-newark" => "Wilmington/Newark",
		);


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


	/**
	* Get all late trains for a specific line.
	*
	* @param string $line What line are we searching?
	*
	* @param string $direction What direction are we going in?
	*
	* @param integer $span_min How many minutes does each point in the graph span?
	*
	* @return array An array of the latest trains over time.
	*/
	function getTrains($line, $direction, $num_hours = 1, $span_min = 10) {

		$retval = array();
		$redis_key = "line/getTrains-" . $line . "-" . $direction;

		if ($retval = $this->redisGet($redis_key)) {
			return($retval);

		} else {

		$query = 'search index="septa_analytics" earliest=-' . $num_hours . 'h '
			. 'train_line="' . $line . ' (' . $direction .')" '
			. 'late != 999 '
			. '| eval id = trainno . "-" . dest  ' // Debugging
			. '| timechart span=' . $span_min . 'm max(late) by id';
		//print $query; // Debugging

		$retval = $this->query($query);
		$retval["metadata"]["line"] = $line;
		$retval["metadata"]["direction"] = $direction;

			$this->redisSet($redis_key, $retval);

		return($retval);

		}

	} // End of getTrains()


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

		$retval = array(
			"metadata" => array(
				"_comment" => "All train lines",
				),
			"data" => $this->lines,
			);

		return($retval);

	} // End of getLines()


	/**
	* Checks the key of a line (which comes from the URL) and returns the 
	* name of the line if there is a match or null if it does not.
	*/
	function checkLineKey($key) {

		$retval = null;

		if (isset($this->lines[$key])) {
			$retval = $this->lines[$key];
		}

		return($retval);

	} // End of checkLineKey()


	/**
	* Checks the key of the direction (which comes from the URL) and returns
	* the human-readable name of the direction if there is a match or null if there is not.
	*/
	function checkDirection($key) {

		$retval = null;

		if ($key == "inbound") {
			$retval = "Inbound";

		} else if ($key == "outbound") {
			$retval = "Outbound";

		}

		return($retval);

	} // End of checkDirection()


} // End of class Line


