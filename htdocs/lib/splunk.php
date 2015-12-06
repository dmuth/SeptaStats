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
		// that is hardcoded here--if the Ansible playbook is used
		// for configuring this server, port 8089 (splunkd) and 8000 (splunkweb)
		// are both blocked from the outside world.
		//
		$this->service = new \Splunk_Service(array(
			"host" => "localhost",
			"port" => "8089",
			"username" => "admin",
			"password" => "adminpw",
			));

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

		//
		// Connect to Splunk
		//
		$this->login();

		$job = $this->service->getJobs()->create($query);

		$start_time = time();
		while (!$job->isDone()) {
    		printf("Progress: %03.1f%%\r\n", $job->getProgress() * 100);
    		usleep(0.5 * 1000000);
    		$job->refresh();
		}

		$end_time = time();

		$results = $job->getResults();

		foreach ($results as $result) {

			$row = array();
    
			if ($result instanceof Splunk_ResultsFieldOrder) {
				// Process the field order
				print "FIELDS: " . implode(',', $result->getFieldNames()) . "\r\n";

			} else if ($result instanceof Splunk_ResultsMessage) {
				// Process a message
				print "[{$result->getType()}] {$result->getText()}\r\n";

			} else if (is_array($result)) {
				foreach ($result as $key => $value) {
					//
					// We don't need meta data for each row.
					//
					if ($key[0] == "_" && $key != "_raw") {
						continue;
					}

					$row[$key] = $value;

				}

				$retval[] = $row;

			} else {
				// Unknown result type
			}

		}

		return($retval);

	} // End of query()



} // End of Splunk class


