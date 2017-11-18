#!/usr/bin/env php
<?php
/**
*
* Fetch our Regional Trail train info.
*
* A full list of API endpoints can be found at http://www3.septa.org/hackathon/
*
*/


date_default_timezone_set("EST5EDT");


/**
* Fetch our JSON from the API.
*
* @param string $url The URL to fetch from
*
* @return string the JSON that is retrieved.
*/
function get_json($url) {

	$ch = curl_init();

	//
	// Connection timeout
	//
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); 

	//
	// How long, once connected, to get the data.
	//
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  


	$response = curl_exec($ch);

	$curl_errno = curl_errno($ch);
	$curl_error = curl_error($ch);
	if ($curl_errno) {
		throw new Exception("Curl Error on URL '$url': ($curl_errno): $curl_error");
	}

	curl_close($ch);

	return($response);

} // End of get_json()


/**
* Return the current timestamp.
*/
function get_timestamp() {

	$retval = date("Y-m-d H:i:s");

	return($retval);

} // End of get_timestamp()


/**
* This adds a timestamp into a row, but we specifically need the timestamp 
* to be first, so that Splunk will see it when the row is imported.
*
* (Yes, this is duplicated across 2 different PHP scripts. Technical debt. )
*
* @param array $data The current piece of train data we're working with.
*
* @param string $timestamp The timestamp assocaited with this train data.
*
* @return string A JSON-encoded string with the timestamp in the beginning.
*/
function add_timestamp($data, $timestamp) {

	$row = array();
	$row["_timestamp"] = $timestamp;
	foreach ($data as $key => $value) {
		$row[$key] = $value;
	}

	$retval = json_encode($row);

	return($retval);

} // End of add_timestamp()


/**
* Split our JSON into one line per array element (train).
*
* @param string $json The JSON we got from SEPTA
*
* @param string $timestamp The current timestamp
*
* @return string A string with one JSON row per train, with timestamp data added.
*
*/
function parse_line($json, $timestamp) {

	$retval = "";

	$data = json_decode($json, true);
	//$data = "bad data"; // Debugging

	if (is_array($data)) {

		if (!isset($data["error"])) {

			foreach ($data as $key => $value) {
				$row = add_timestamp($value, $timestamp);
				$retval .= $row . "\n";

			}

		} else {
			//
			// We got some kind error, so insert our timestamp and that's it
			//
			$data["_timestamp"] = $timestamp;
			$row = json_encode($data);
			$retval .= $row . "\n";

		}

	} else {
		print "ERROR: I don't know what to do with this data: $data\n";

	}

	return($retval);

} // End of parse_lines()


/**
* Our main entry point.
*/
function main() {

	$url = "http://www3.septa.org/hackathon/TrainView/";
	//$url = "http://www.google.com/notaurl"; // Debugging - 404
	//$url = "http://foobar.localdomain/"; // Debugging - Bad DNS
	//$url = "http://10.255.255.254"; // Debugging - Timeout

	try {
		$json = get_json($url);
		$timestamp = get_timestamp();
		$lines = parse_line($json, $timestamp);

	} catch (Exception $e) {
		print "ERROR: " .$e->getMessage() . "\n";
		exit(1);

	}

	print $lines;

} // End of main()


main();


