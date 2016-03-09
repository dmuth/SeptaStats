
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
	// Keep me sane
	//
	self: this,

	//
	// Maximum number of tries
	//
	maxCount: 5,

	try: function(url, cb, count) {

		url = "http://httpbin.org/status/500"; // Debugging

		count = count || 0;

		if (count >= self.maxCount) {
			return(null);
		}

		jQuery.ajax({
			url: url,
			dataType: "json",
			success: cb,

		}).fail(function() {

			setTimeout(function() {
				self.try(url, cb, ++count);
				}, 1000);

		});

	} // End of try()


} // End of tryApi

