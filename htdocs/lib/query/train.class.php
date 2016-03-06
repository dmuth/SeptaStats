<?php

namespace Septa\Query;

require_once("base.class.php");


/**
* This class is used for searching data for a specific train.
*/
class Train extends Base {


	function __construct($splunk, $redis) {
		parent::__construct($splunk, $redis);
	} // End of __construct()


	/**
	* Retrieve details for a specific train.
	*
	* @param integer $trainno Our train number.
	*
	* @return array An array of stops this train made and how 
	*	many minutes late it was for each stop.
	*/
	function get($trainno) {

		$retval = array();

		$query = 'search index="septa_analytics" earliest=-20h '
			. 'trainno=' . $trainno 
			. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
			. '| chart latest(late) AS  "Minutes Late", latest(time) by nextstop '
			. '| sort latest(time) | fields nextstop "Minutes Late"'
			;

		$retval = $this->query($query);
		$retval["metadata"]["trainno"] = $trainno;
		$retval["metadata"]["_comment"] = "What stops did train '$trainno' make, and how late was it each stop?";
		
		return($retval);

	} // End of get()


	/**
	* Retrieve history for a specific train.
	*
	* @param integer $trainno Our train number.
	*
	* @return array An array of stops and lateness by day.
	*/
	function getHistoryByDay($trainno) {

		$retval = array();

		$query = 'search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d '
			. '| eval late0=late '
			. '| append [search index="septa_analytics" trainno=' . $trainno .' earliest=-1d@d latest=-0d@d |eval late1=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-2d@d latest=-1d@d |eval late2=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-3d@d latest=-2d@d |eval late3=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-4d@d latest=-3d@d |eval late4=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-5d@d latest=-4d@d |eval late5=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-6d@d latest=-5d@d |eval late6=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-7d@d latest=-6d@d |eval late7=late] '
			. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
			. '| chart latest(late0) AS "Minutes Late", latest(late1) AS "Minutes Late - Yesterday", latest(late2) AS "Minutes Late - 2 Days Ago", latest(late3) AS "Minutes Late - 3 Days Ago", latest(late4) AS "Minutes Late - 4 Days Ago", latest(late5) AS "Minutes Late - 5 Days Ago", latest(late6) AS "Minutes Late - 6 Days Ago", latest(late7) AS "Minutes Late - 7 Days Ago", latest(time) by nextstop '
			. '| sort latest(time) '
			. '| fields nextstop "Minutes Late" "Minutes Late - Yesterday" "Minutes Late - 2 Days Ago" "Minutes Late - 3 Days Ago" "Minutes Late - 4 Days Ago" "Minutes Late - 5 Days Ago" "Minutes Late - 6 Days Ago" "Minutes Late - 7 Days Ago"'
			;

		$retval = $this->query($query);
		$retval["metadata"]["_comment"] = "Multiple days worth of stops and lateness for train '$trainno'";

		return($retval);

	} // End of getHistoryByDay()


	/**
	* Get our historical average lateness and compare it to current lateness.
	*
	* @param integr $trainno Our train number.
	*/
	function getHistoryHistoricalAvg($trainno) {
	
		$retval = array();

		$query = 'search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-0d@d |eval late0=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-1d@d latest=-0d@d |eval late1=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-2d@d latest=-1d@d |eval late2=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-3d@d latest=-2d@d |eval late3=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-4d@d latest=-3d@d |eval late4=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-5d@d latest=-4d@d |eval late5=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-6d@d latest=-5d@d |eval late6=late] '
			. '| append [search index="septa_analytics" trainno=' . $trainno . ' earliest=-7d@d latest=-6d@d |eval late7=late] '
			. '| eval time=strftime(_time,"%Y-%m-%d %H:%M:%S") '
			. '| chart latest(late0) AS "Minutes Late", latest(late1) AS "Minutes Late - Yesterday", latest(late2) AS "Minutes Late - 2 Days Ago", latest(late3) AS "Minutes Late - 3 Days Ago", latest(late4) AS "Minutes Late - 4 Days Ago", latest(late5) AS "Minutes Late - 5 Days Ago", latest(late6) AS "Minutes Late - 6 Days Ago", latest(late7) AS "Minutes Late - 7 Days Ago", latest(time) by nextstop '
			. '| sort latest(time) '
			. '| eval "Average Minutes Late"= (if(isnotnull($Minutes Late - Yesterday$), $Minutes Late - Yesterday$, 0) + if(isnotnull($Minutes Late - 2 Days Ago$), $Minutes Late - 2 Days Ago$, 0) + if(isnotnull($Minutes Late - 3 Days Ago$), $Minutes Late - 3 Days Ago$, 0) + if(isnotnull($Minutes Late - 4 Days Ago$), $Minutes Late - 4 Days Ago$, 0) + if(isnotnull($Minutes Late - 5 Days Ago$), $Minutes Late - 5 Days Ago$, 0) + if(isnotnull($Minutes Late - 6 Days Ago$), $Minutes Late - 6 Days Ago$, 0) + if(isnotnull($Minutes Late - 7 Days Ago$), $Minutes Late - 7 Days Ago$, 0) ) / 7 '
			. '| fields nextstop "Average Minutes Late" "Minutes Late"'
			;

		$retval = $this->query($query);
		$retval["metadata"]["_comment"] = "Average lateness compared to current lateness for train '$trainno'";

		return($retval);

	} // End of getHistoryHistoricalAvg()


} // End of class Train


