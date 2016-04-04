
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
	* @param integer start Our starting timestamp in ms
	* @param integer totalMs How many ms have we delayed?
	*/
	try: function(url, cb, count, start, totalMs) {

		// Keep me sane
		var self = this;

		//url = "http://httpbin.org/status/500"; // Debugging
		console.log("Trying URL '" + url + "'..."); 

		count = count || 1;
		start = start || new Date().getTime();
		totalMs = totalMs || 0;

		var elapsed = new Date().getTime() - start;
		var apiUrl = url + "?apiCount=" + count + "&apiTotalDelayMs=" + totalMs;

		jQuery.ajax({
			url: apiUrl,
			dataType: "json",
			success: function(data) {
				var elapsed = new Date().getTime() - start;
				console.log("Success on URL '" + url + "' in " + elapsed + " ms!");
				cb(data);
				},

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
				self.try(url, cb, ++count, start, (totalMs + delay) );
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


/**
* Logic to check our window width and display an alert if the 
* screen is too narrow.
*/
var checkWidth = {

	go: function() {

		if ( $(window).width() <= 600) {
			$(".width-alert").slideDown(1000);

		} else {
			$(".width-alert").slideUp(1000);

		}
	}

} // End of checkWidth


/**
* Parse a timestamp returned from an API call.  Because Safari's Date.parse() function
* is weird about dates, the API returns dates in YYYY-MM-DDTHH:MM:SS format.
* Unfortunately, the timezone is treated as UTC with local conversion then applied.
* This is an issue, since all timestamps from SEPTA come in EST, and are therefore
* re-converted to EST, which causes them to be a few hours in the past.  So part
* of this function's purpose is to then re-add the GMT offset since it was 
* improperly applied in the first place.
*
* @param string The timestamp returned from the API
*
* @return array A data structure of the timestamp, as well as zero-padded values
*	for the date and time.
*
*/
var parseTimestamp = function(timestamp) {

	var retval = {};

	//
	// Parse our date and then re-apply the GMT offset so we get the
	// actual proper timestamp.
	//
	var date = new Date(timestamp);
	var tz_offset_seconds = date.getTimezoneOffset() * 60;
	retval["date"] = date = new Date( date.getTime() + (tz_offset_seconds * 1000 ) );

	retval["day"] = date.getFullYear() 
		+ "-" + ("0" + date.getMonth()).slice(-2) 
		+ "-" + ("0" + date.getDate()).slice(-2)
		;

	retval["time"] = ("0" + date.getHours()).slice(-2) 
		+ ":" + ("0" + date.getMinutes()).slice(-2) 
		+ ":" + ("0" + date.getSeconds()).slice(-2)
		;

	//
	// How old is the data?  If it's too old, then we'll let the user know.
	//
	retval["diff"] = (new Date() - date) / 1000;

	return(retval);

} // End of parseTimestamp()



