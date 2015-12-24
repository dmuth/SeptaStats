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
		return($this->lines);
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


