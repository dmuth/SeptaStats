#!/usr/bin/env php
<?php
/**
* This script is used to split up a log that was created with 
* get-regional-rail-trainview.sh and store it in a log file that 
* can then be loaded into Splunk.  This is useful for uploading old
* train data to a separate Splunk instance.
*
*/


error_reporting(E_ALL);


/**
* Parse our command line arguments.
* 
* @param array $argv Our arguments
*
* @return array An array of parsed arguments
*/
function parse_args($argv) {

	$retval = array();

	if (!isset($argv[1])) {
		$error = sprintf("Syntax: %s file_to_read", $argv[0]);
		print "!\n";
		print "! $error\n";
		print "!\n";
		exit(1);
	}

	$retval["filename"] = $argv[1];

	return($retval);

} // End of parse_args()


/**
* Open a file and return the file pointer.
*/
function file_open($filename) {

	if (!is_readable($filename)) {
		$error = "File '$filename' does not exist or is not readable!";
		throw new Exception($error);
	}

	$retval = fopen($filename, "r");

	if (!$retval) {
		throw new Exception("Unable to open file '$filename'");
	}

	return($retval);

} // End of file_open()


/**
* Process our line--grab the timestamp and split up the JSON
* into separate rows, each with the timestamp.
*
* @param string $line A line containing a date stamp and JSON.
*
* @return string A string containing many lines to print.
*/
function process_line($line) {

	$retval = "";

	$pattern = "/^([^\[]+) (.*)$/";
	$pattern = "/^([^(\[|{)]+) (.*)$/";
	preg_match($pattern, $line, $results);

	$timestamp = $results[1];
	$json = $results[2];

	$data = json_decode($json, true);

	if (is_array($data)) {

		if (!isset($data["error"])) {

			foreach ($data as $key => $value) {
				$value["_timestamp"] = $timestamp;
				$row = json_encode($value);
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
		print "ERROR: I don't know what to do with this data: $line\n";

	}

	return($retval);

} // End of process_line()


/**
* Close out our file pointer.
*/
function file_close($fp) {
	fclose($fp);
}


/**
* Our main entry point.
*/ 
function main($argv) {

	$params = parse_args($argv);

	$fp = file_open($params["filename"]);

	while ($line = fgets($fp)) {

		$lines = process_line($line);
		print $lines;
	}

	file_close($fp);

} // End of main()

main($argv);



