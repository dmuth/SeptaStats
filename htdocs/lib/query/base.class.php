<?php

namespace Septa\Query;


/**
* This class provides common functionality for our query classes.
*/
class Base {


	//
	// Our Splunk object.
	//
	protected $splunk;


	//
	// Our Redis client
	//
	protected $redis;


	//
	// Set our Redis TTL to 60 seconds to start with
	//
	//protected $redis_ttl = 60;
// TEST
	protected $redis_ttl = 10;


	/**
	* Our constructor.
	*
	* @param object $splunk Our Splunk search object.
	*/
	function __construct($splunk, $redis) {

		$this->splunk = $splunk;
		$this->redis = $redis;

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


	/**
	* Wrapper to get keys from Redis
	*/
	protected function redisGet($key) {
		$retval = $this->redis->get($key);
		$retval = json_decode($retval, true);
		return($retval);
	}

	/**
	* Wrapper to set keys in Redis with a default TTL.
	*/
	protected function redisSet($key, $value) {
		$value = json_encode($value);
		$this->redis->setEx($key, $this->redis_ttl, $value);
	}


	/**
	* Wrapper to set keys in Redis with specified TTL.
	*/
	protected function redisSetEx($key, $value, $ttl) {
		$value = json_encode($value);
		$this->redis->setEx($key, $ttl, $value);
	}


} // End of Base class


