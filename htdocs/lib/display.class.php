<?php


namespace Septa;


/**
* This class is used to cold display-related functions.
*
* If a bunch of functions accumulate here, I will refactor as necessary.
*/
class Display {

	/**
	* Helper function to return prettified JSON data.
	*/
	function json_pretty($data) {
		return(json_encode($data, JSON_PRETTY_PRINT));
	}


	/**
	* Wrap all of our calls to Splunk, so that if it fails, we can return a nice error in JSON format.
	*/
	function splunkWrapper($cb, $response) {

		try {
			return($cb());

		} catch (Exception $e) {
			//
			// Was there a problem querying Splunk?
			// Log it, and then throw a 5XX error.
			//
			syslog(LOG_ERR, "splunkWrapper(): " . $e->getMessage());

			$data = array(
				"error" => "Unable to connect to our data store. (is it running?)",
				);
	
			$output = json_pretty($data);
			$newResponse = $response->withStatus(500, "Server Error");
			$newResponse->getBody()->write($output);
			return($newResponse);

		}


	} // End of splunkWrapper()


	/**
	* Sanitize anything the user sent us.  This means we'll be removing
	* everything but pre-defined values such as letters, numbers, and
	* some punctuation.
	*/
	function sanitizeInput($s) {

		$retval = preg_replace("/[^a-z0-9- \.]/i", "", $s);

		return($retval);

	} // End of sanitize()


} // End of Display class


