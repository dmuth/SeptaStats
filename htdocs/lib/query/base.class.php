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
	protected $redis_ttl = 60;


	/**
	* Our constructor.
	*
	* @param object $splunk Our Splunk search object.
	*/
	function __construct($splunk, $redis) {

		$this->splunk = $splunk;
		$this->redis = $redis;

		//
		// Connect to syslog in case we're debugging
		//
		openlog("septa-stats", LOG_PID, LOG_LOCAL0);

	} // End of __constructor()


	/**
	* A wrapper to log things to syslog.
	* 
	* Initially, I'm going to use this only in developement, but might
	* have production use.
	*/
	function log($str) {
		//syslog(LOG_INFO, $str);
	}


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

		if ($retval) {
			$this->log("Redis GET: $key");
		} else {
			$this->log("Redis GET MISS: $key");
		}

		return($retval);
	}

	/**
	* Wrapper to set keys in Redis with a default TTL.
	*/
	protected function redisSet($key, $value) {
		$value = json_encode($value);
		$this->redis->setEx($key, $this->redis_ttl, $value);
		$this->log("Redis SET: $key");
	}


	/**
	* Wrapper to set keys in Redis with specified TTL.
	*/
	protected function redisSetEx($key, $value, $ttl) {
		$value = json_encode($value);
		$this->redis->setEx($key, $ttl, $value);
		$this->log("Redis SETEX: $key, $ttl");
	}


} // End of Base class


