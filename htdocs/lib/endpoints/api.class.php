<?php

namespace Septa\Endpoints;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/**
* This class is used to hold endpoints for our API.
*/
class Api {


	/**
	* Our constructor
	*
	* @param object $app Our Slim PHP app.
	* @param object $display Our class for displaying content
	* @param object $line Class to bring up data on specific lines.
	* @param object $train Class to query train info
	* @param object $system Class to query the entire train system's info.
	* @param object $station Class to query station data
	* @param object $statiosn Class to query all stations
	*
	*/
	function __construct($app, $display, $line, $train, $system, $station, $stations) {
		$this->app = $app;
		$this->display = $display;
		$this->line = $line;
		$this->train = $train;
		$this->system = $system;
		$this->station = $station;
		$this->stations = $stations;
	}


	/**
	* Our main entry point.
	*
	*/
	function go() {

		$app = $this->app;
		$display = $this->display;
		$line = $this->line;
		$train = $this->train;
		$system = $this->system;
		$station = $this->station;
		$stations = $this->stations;

		$app->group("/api/current/train/{trainno}", function() use ($display, $train) {

			$this->get("", function(Request $request, Response $response, $args) 
				use ($display, $train) {

		    	$trainno = $display->sanitizeInput($request->getAttribute("trainno"));

				$result = $display->splunkWrapper(function() 
					use ($args, $train, $response, $display, $trainno) {

					$output = $display->json_pretty($train->get($trainno));
			    	$response->getBody()->write($output);

					return($response);

				}, $response);

				return($result);

			});


			$this->get("/history", function(Request $request, Response $response, $args) 
				use ($display, $train) {

		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() 
					use ($args, $train, $response, $display) {

					$output = $display->json_pretty($train->getHistoryByDay($args["trainno"]));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});


			$this->get("/history/average", function(Request $request, Response $response, $args) 
				use ($display, $train) {

		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() use($args, $train, $response, $display) {

					$output = $display->json_pretty($train->getHistoryHistoricalAvg($args["trainno"]));
	    			$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->group("/api/current/system", function() 
			use ($display, $system) {

			$this->get("", function(Request $request, Response $response, $args) 
				use ($display, $system) {
	
				$num_trains = 10;
				$num_hours = 1;
				$span_min = 10;

				$result = $display->splunkWrapper(function() 
					use ($system, $response, $num_trains, $num_hours, $span_min, $display) {

					$output = $display->json_pretty($system->getTopLatestTrains($num_trains, $num_hours, $span_min));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/totals", function(Request $request, Response $response, $args) 
				use ($display, $system) {

				$num_days = 7;

				$result = $display->splunkWrapper(function() 
					use ($response, $args, $system, $num_days, $display) {

					$output = $display->json_pretty($system->getTotalMinutesLateByDay($num_days));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->get("/api/current/lines", function(Request $request, Response $response, $args) 
			use ($display, $line) {

			$output = $display->json_pretty($line->getLines());
			$response->getBody()->write($output);

		});


		$app->get("/api/current/line/{line}/{direction}", function(Request $request, Response $response, $args) 
			use ($display, $line) {

			$line_name = $line->checkLineKey($args["line"]);
			$direction = $line->checkDirection($args["direction"]);

			if ($line_name && $direction) {

				$data = $line->getTrains($line_name, $direction, 1, 10);
				$response->getBody()->write($display->json_pretty($data));

			} else {
				$error = sprintf("Line '%s' and/or direction '%s' not found!\n", $args["line"], $args["direction"]);
				$output = array(
					"error" => $error,
					);
				$output_json = $display->json_pretty($output);
				$new_response = $response->withStatus(404, "Line or direction not found");
				$new_response->getBody()->write($output_json);

				return($new_response);

			}

		});


		$app->group("/api/current/station", function() use ($display, $station) {

			$this->get("/{station}/trains", function(Request $request, Response $response, $args) 
				use ($display, $station) {
	
				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->json_pretty($station->getTrains($station_name));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) 
				use ($display, $station) {
	
				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->json_pretty($station->getTrainsLatest($station_name));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/{station}/stats", function(Request $request, Response $response, $args) 
				use ($display, $station) {
	
				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->json_pretty($station->getStats($station_name));
	    			$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->get("/api/current/stations", function(Request $request, Response $response, $args) 
			use ($stations, $display) {

			$output = $display->splunkWrapper(function() use ($stations, $display) {

				$data = $stations->getStations();

				$output = $display->json_pretty($data);

				return($output);

				}, $response);

			$response->getBody()->write($output);

		});


	} // End of go()


} // End of Content class


