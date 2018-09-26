<?php

namespace Septa;


require("splunk/Splunk.php");


/**
* This class wraps queries to Splunk
*/
class Splunk {


	//
	// The object for our Splunk Service.
	//
	var $service;

	//
	// Our metadata from the most recent search.
	//
	var $metadata;


	function __constructor() {
	}


	/**
	* Log into Splunk if we haven't already done so.
	*/
	private function login() {

		//
		// If we've already logged in, stop here.
		//	
		if ($this->service) {
			return(null);
		}

		//
		// Don't freak out TOO much over the horribly insecure password
		// that is hardcoded here--this is all in Docker, so the Splunk instance
		// won't be exposed to the outside world.
		//
		$config = array(
			"host" => "splunk",
			"port" => "8089",
			"username" => "admin",
			//"password" => "password",
			"password" => getenv("SPLUNK_PASSWORD")
			);

		$this->service = new \Splunk_Service($config);

		$this->service->login();

	} // End of login()


	/**
	* Run a query and return the results.
	*
	* @param string $query The query we are sending into Splunk
	*
	* @return array An array of our results.
	*/
	function query($query) {

		$retval = array();
		$this->metadata = array();

		//
		// Connect to Splunk
		//
		$this->login();

		$job = $this->service->getJobs()->create($query);

		$max = 30;
		//$max = 0; // Debugging
		$start_time = microtime(true);
		while (!$job->isDone()) {

			$elapsed = time() - $start_time;
			if ($elapsed >= $max) {
				throw new \Exception("Query '$query' is taking too long!");
			}

    			//printf("Progress: %03.1f%%\r\n", $job->getProgress() * 100);
    			usleep(0.5 * 1000000);
    			$job->refresh();

		}

		$end_time = microtime(true);
		$diff = $end_time - $start_time;
		$this->metadata["elapsed"] = $diff;

		$results = $job->getResults();

		foreach ($results as $result) {

			$row = array();
    
			if (is_array($result)) {
				foreach ($result as $key => $value) {
					//
					// We don't need meta data for each row.
					//
					if ($key[0] == "_" && $key != "_raw" && $key != "_time") {
						continue;
					}

					$row[$key] = $value;

				}

				$retval[] = $row;

			} else {
				// Unknown result type
				//print_r($result); // Debugging

			}

		}

		return($retval);

	} // End of query()


	/**
	* This function returns the metadata from a search.
	*
	* @return array
	*/
	function getResultsMeta() {

		return($this->metadata);

	} // End of getResultsMeta()


} // End of Splunk class


