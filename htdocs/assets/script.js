
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

		if (count > self.maxCount) {
			return(null);
		}

		var apiUrl = url + "?apiCount=" + count + "&apiTotalDelayMs=" + totalMs;

		jQuery.ajax({
			url: apiUrl,
			dataType: "json",
			success: cb,

		}).fail(function() {

			setTimeout(function() {
				self.try(url, cb, ++count, (totalMs + 1000) );
				}, 1000);

		});

	} // End of try()


} // End of tryApi

