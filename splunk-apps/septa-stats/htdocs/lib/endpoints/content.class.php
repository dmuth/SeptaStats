<?php

namespace Septa\Endpoints;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/**
* This class is used to hold our endpoints which generate static content.
*/
class Content {


	/**
	* Our constructor
	*
	* @param object $app Our Slim PHP app.
	* @param object $display Our class for displaying content
	* @param object $line Class to bring up data on specific lines.
	* @param object $stations Our stations object
	* @param object $train Our train object.
	*
	*/
	function __construct($app, $display, $line, $stations, $train) {
		$this->app = $app;
		$this->display = $display;
		$this->line = $line;
		$this->stations = $stations;
		$this->train = $train;
	}


	/**
	* Our main entry point.
	*
	*/
	function go() {

		$display = $this->display;
		$line = $this->line;
		$stations = $this->stations;
		$train = $this->train;

		$this->app->get("/", function (Request $request, Response $response, $args) {

		    return $this->view->render($response, "index.html", [
		        //'name' => $args['name']
		    ]);

		});


		$this->app->get("/stats", function (Request $request, Response $response, $args) {

		    return $this->view->render($response, "stats.html", [
		    	]);

		});


		$this->app->get("/faq", function (Request $request, Response $response, $args) {

		    return $this->view->render($response, "faq.html", [
		    	]);

		});


		$this->app->get("/about", function (Request $request, Response $response, $args) {

		    return $this->view->render($response, "about.html", [
    			]);

		});


		$this->app->get("/api", function(Request $request, Response $response, $args) {

		    return $this->view->render($response, "api.html", [
		    	]);

		});


		$this->app->get("/lines", function (Request $request, Response $response, $args) 
			use ($display, $line) {

			$output = $display->jsonPretty($line->getLines());
			$lines = json_decode($output, true);

		    return $this->view->render($response, "lines.html", [
				"lines" => $lines,
		    	]);

		});


		$this->app->get("/stations", function (Request $request, Response $response, $args) 
			use ($display, $stations) {

			$data = $display->splunkWrapper(function() use ($stations, $display) {

				$data = $stations->getStations();

				return($data);

				}, $response);

			$stations = $data["data"];

		    return $this->view->render($response, "stations.html", [
				"stations" => $stations,
		    	]);

		});


		$this->app->get("/trains", function (Request $request, Response $response, $args) 
			use ($display, $train) {

			$data = $display->splunkWrapper(function() use ($train, $display) {

				$data = $train->getTrains();

				return($data);

				}, $response);

			$trains = $data["data"];

		    return $this->view->render($response, "trains.html", [
				"trains" => $trains,
		    	]);

		});


		$this->app->get("/line/{line}/{direction}", function (Request $request, Response $response, $args) 
			use ($display, $line) {

			//
			// Sanity check our arguments
			//
			$line_name = $line->checkLineKey($args["line"]);
			$direction = $line->checkDirection($args["direction"]);

			if ($line_name && $direction) {

	    	return $this->view->render($response, "line.html", [
				"line_api" => $args["line"],
				"direction_api" => $args["direction"],
				"line" => $line_name,
				"direction" => $direction,
    			]);

			} else {
				$error = sprintf("Line '%s' and/or direction '%s' not found!\n", $args["line"], $args["direction"]);
				$output = array(
					"error" => $error,
					);
				$output_json = $display->jsonPretty($output);
				$new_response = $response->withStatus(404, "Line or direction not found");
				$new_response->getBody()->write($output_json);
				return($new_response);

			}

		});


		$this->app->get("/train/{trainno}", function (Request $request, Response $response, $args) use ($display) {

		    return $this->view->render($response, "train.html", [
				"trainno" => $display->sanitizeInput($args["trainno"]),
		    	]);

		});


		$this->app->get("/station/{station}", function (Request $request, Response $response, $args) use ($display) {

		    return $this->view->render($response, "station.html", [
				"station" => $display->sanitizeInput($args["station"]),
		    	]);

		});


		//
		// Why am I returning a TEXT FILE through the Slim PHP framework?
		// Well, it turns out that for some reason why I just had robots.txt
		// in htdocs/ that it triggered a file download, even through the Content-Type:
		// header was not binary.  In the interest of time, I just put the robots.txt
		// file into the templates directory.  I'll sort this out later if it becomes
		// a serious performance issue.
		//
		$this->app->get("/robots.txt", function (Request $request, Response $response, $args) {

			$response = $response->withHeader("Content-Type", "text/plain");

		    return $this->view->render($response, "robots.txt", [
		    	]);

		});


	} // End of go()


} // End of Content class


