<?php

namespace Septa\Query;


/**
* This class provides common functionality for our query classes.
*/
class Base {

	/**
	* Our Splunk object.
	*/
	protected $splunk;


	/**
	* Our constructor.
	*
	* @param object $splunk Our Splunk search object.
	*/
	function __construct($splunk) {

		$this->splunk = $splunk;

	} // End of __constructor()


	/**
	* Wrapper to run our query then get metadata.
	*
	* @param string $query Our Splunk query.
	*
	* @return array An array of metadata about the search then our regular data.
	*/
	protected function query($query) {

		$retval = array();

		//
		// We want metadata to be first, as it makes debugging a little bit easier.
		//
		$data = $this->splunk->query($query);
		$retval["metadata"] = $this->splunk->getResultsMeta();
		$retval["data"] = $data;

		return($retval);

	} // End of query()


} // End of Base class


