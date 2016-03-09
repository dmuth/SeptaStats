
/**
* This object/function is used to retry our access to the API.
*
* I wrote this because sometimes we get a 500 server error from Splunk for
* no reason that I can fathom, and I would rather silently try again and
* log the fact that we did behind the scenes.
*
*/
var tryApi = {

	//
	// Maximum number of tries
	//
	maxCount: 5,

	//
	// Base unit of milliseconds which will be the basis for our exponential backoff
	//
	baseMs: 250,


	/**
	* @param string url The URL we are trying to hit
	* @param object callback Our callback to call when successful
	* @param integer count How many tries have we made?
	* @param integer totalMs How many ms have we delayed?
	*/
	try: function(url, cb, count, totalMs) {

		// Keep me sane
		var self = this;

		//url = "http://httpbin.org/status/500"; // Debugging

		count = count || 1;
		totalMs = totalMs || 0;

		var apiUrl = url + "?apiCount=" + count + "&apiTotalDelayMs=" + totalMs;

		jQuery.ajax({
			url: apiUrl,
			dataType: "json",
			success: cb,

		}).fail(function() {

			if (count >= self.maxCount) {
				console.log("Hit our max of " + count + " tries on URL '" + url + "', stopping.");
				return(null);
			}
			//
			// Calculate our exponential backoff.
			// What we're doing is calculating a random value between 1
			// and the current attempt squared - 1, and then multiplaying
			// that by our base interval.
			//
			// This should hopefully stagger requests from different
			// web browsers in the event that there is high amounts
			// of traffic coming in.
			//
			var delayMultMax = Math.pow(2, count);
			var delayMult = self.getRandomInt(1, delayMultMax);
			var delay = delayMult * self.baseMs;
			console.log("Got error reading '" + url + "', trying again in " + delay + " ms...");

			setTimeout(function() {
				self.try(url, cb, ++count, (totalMs + delay) );
				}, delay);

		});

	}, // End of try()


	/**
	* Get a random integer between min (inclusive) and max (exclusive)
	*/
	getRandomInt: function(min, max) {
		return Math.floor(Math.random() * (max - min)) + min;
	}


} // End of tryApi



