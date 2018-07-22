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
		$self = $this;


		$app->get("/api/current/trains", function(Request $request, Response $response, $args) 
			use ($self, $display, $train) {

			$response = $self->setJsonAndCorsHeadersGet($response);

			$output = $display->jsonPretty($train->getTrains());
			$response->getBody()->write($output);

			return($response);

		});


		$app->group("/api/current/train/{trainno}", function() use ($self, $display, $train) {

			$this->get("", function(Request $request, Response $response, $args) 
				use ($self, $display, $train) {

				$response = $self->setJsonAndCorsHeadersGet($response);

		    	$trainno = $display->sanitizeInput($request->getAttribute("trainno"));

				$result = $display->splunkWrapper(function() 
					use ($args, $train, $response, $display, $trainno) {

					$output = $display->jsonPretty($train->get($trainno));
			    	$response->getBody()->write($output);

					return($response);

				}, $response);

				return($response);

			});


			$this->get("/history", function(Request $request, Response $response, $args) 
				use ($self, $display, $train) {

				$response = $self->setJsonAndCorsHeadersGet($response);

		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() 
					use ($args, $train, $response, $display) {

					$output = $display->jsonPretty($train->getHistoryByDay($args["trainno"]));
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});


			$this->get("/latest", function(Request $request, Response $response, $args) 
				use ($self, $display, $train) {

				$response = $self->setJsonAndCorsHeadersGet($response);

				//
				// Grab our trains, explode on commas, can cap the array at 20
				// trains so we don't murder the Splunk process. We'll also sort the
				// array so we don't obliterate Redis.
				//
		    	$trainno = $request->getAttribute("trainno");
				$trains = explode(",", $trainno);
				$trains = array_slice($trains, 0, 20);
				sort($trains);

				$result = $display->splunkWrapper(function() 
					use ($trains, $train, $response, $display) {

					$output = $display->jsonPretty($train->getLatest($trains));
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});


			$this->get("/history/average", function(Request $request, Response $response, $args) 
				use ($self, $display, $train) {

				$response = $self->setJsonAndCorsHeadersGet($response);

		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() use($args, $train, $response, $display) {

					$output = $display->jsonPretty($train->getHistoryHistoricalAvg($args["trainno"]));
	    			$response->getBody()->write($output);

					}, $response);

				return($response);

			});

		});


		$app->group("/api/current/system", function() 
			use ($self, $display, $system) {

			$this->get("", function(Request $request, Response $response, $args) 
				use ($self, $display, $system) {

				$response = $self->setJsonAndCorsHeadersGet($response);

				$num_trains = 10;
				$num_hours = 1;
				$span_min = 10;

				$result = $display->splunkWrapper(function() 
					use ($system, $response, $num_trains, $num_hours, $span_min, $display) {

					$output = $display->jsonPretty($system->getTopLatestTrains($num_trains, $num_hours, $span_min));
			    	$response->getBody()->write($output);

					}, $response);

					return($response);

			});

			$this->get("/latest", function(Request $request, Response $response, $args) 
				use ($self, $display, $system) {
	
				$response = $self->setJsonAndCorsHeadersGet($response);

				$num_trains = 10;
				$num_hours = 1;
				$span_min = 10;

				$result = $display->splunkWrapper(function() 
					use ($system, $response, $num_trains, $num_hours, $span_min, $display) {

					$output = $display->jsonPretty($system->getLatestTrains());
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});


			$this->get("/latest/stats", function(Request $request, Response $response, $args) 
				use ($self, $display, $system) {
	
				$response = $self->setJsonAndCorsHeadersGet($response);

				$num_trains = 10;
				$num_hours = 1;
				$span_min = 10;

				$result = $display->splunkWrapper(function() 
					use ($system, $response, $num_trains, $num_hours, $span_min, $display) {

					$output = $display->jsonPretty($system->getLatestTrainsStats());
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});


			$this->get("/totals", function(Request $request, Response $response, $args) 
				use ($self, $display, $system) {

				$response = $self->setJsonAndCorsHeadersGet($response);

				$num_days = 7;

				$result = $display->splunkWrapper(function() 
					use ($response, $args, $system, $num_days, $display) {

					$output = $display->jsonPretty($system->getTotalMinutesLateByDay($num_days));
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});

		});


		$app->get("/api/current/lines", function(Request $request, Response $response, $args) 
			use ($self, $display, $line) {

			$response = $self->setJsonAndCorsHeadersGet($response);

			$output = $display->jsonPretty($line->getLines());
			$response->getBody()->write($output);

			return($response);

		});


		$app->get("/api/current/line/{line}/{direction}", function(Request $request, Response $response, $args) 
			use ($self, $display, $line) {

			$response = $self->setJsonAndCorsHeadersGet($response);

			$line_name = $line->checkLineKey($args["line"]);
			$direction = $line->checkDirection($args["direction"]);

			if ($line_name && $direction) {

				$data = $line->getTrains($line_name, $direction, 1, 10);
				$response->getBody()->write($display->jsonPretty($data));
				return($response);

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


		$app->get("/api/current/line/{line}/{direction}/latest", function(Request $request, Response $response, $args) 
			use ($self, $display, $line) {

			$response = $self->setJsonAndCorsHeadersGet($response);

			$line_name = $line->checkLineKey($args["line"]);
			$direction = $line->checkDirection($args["direction"]);

			if ($line_name && $direction) {

				$data = $line->getTrainsLatest($line_name, $direction, 1, 10);
				$response->getBody()->write($display->jsonPretty($data));
				return($response);

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


		$app->group("/api/current/station", function() use ($self, $display, $station) {

			$this->get("/{station}/trains", function(Request $request, Response $response, $args) 
				use ($self, $display, $station) {
	
				$response = $self->setJsonAndCorsHeadersGet($response);

				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->jsonPretty($station->getTrains($station_name));
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});

			$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) 
				use ($self, $display, $station) {
	
				$response = $self->setJsonAndCorsHeadersGet($response);

				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->jsonPretty($station->getTrainsLatest($station_name));
			    	$response->getBody()->write($output);

					}, $response);

				return($response);

			});

			$this->get("/{station}/stats", function(Request $request, Response $response, $args) 
				use ($self, $display, $station) {
	
				$response = $self->setJsonAndCorsHeadersGet($response);

				$station_name = $display->sanitizeInput($args["station"]);

				$result = $display->splunkWrapper(function() 
					use ($response, $station, $station_name, $display) {

					$output = $display->jsonPretty($station->getStats($station_name));
	    			$response->getBody()->write($output);

					}, $response);

				return($response);

			});

		});


		$app->get("/api/current/stations", function(Request $request, Response $response, $args) 
			use ($self, $stations, $display) {

			$response = $self->setJsonAndCorsHeadersGet($response);

			$output = $display->splunkWrapper(function() use ($stations, $display) {

				$data = $stations->getStations();

				$output = $display->jsonPretty($data);

				return($output);

				}, $response);

			$response->getBody()->write($output);
			return($response);

		});


	/**
	* Our Handler for any OPTIONS request that starts with /api/, but not /api.
	*/
	$app->options("/api/{id}[/{id2}[/{id3}[/{id4}[/{id5}]]]]", function(Request $request, Response $response, $args)  // Works
		use ($self) {

			$response = $response->withHeader("Access-Control-Allow-Origin", "*");
			$response = $response->withHeader("Access-Control-Allow-Credentials", "true");
			$response = $response->withHeader("Access-Control-Max-Age", 1728000);
			$response = $response->withHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
			$response = $response->withHeader("Access-Control-Allow-Headers", "Authorization,Content-Type,Accept,Origin,User-Agent,DNT,Cache-Control,X-Mx-ReqToken,Keep-Alive,X-Requested-With,If-Modified-Since");
			$response = $response->withHeader("Content-Length", 0);
			$response = $response->withHeader("Content-Type", "text/plain charset=UTF-8");

			$response = $response->withStatus(204, "OK");

			return($response);

		});


	} // End of go()


	/**
	* Set our JSON and CORS headers for a GET request.
	*/
	private function setJsonAndCorsHeadersGet($response) {

		$response = $response->withHeader("Access-Control-Allow-Origin", "*");
		$response = $response->withHeader("Access-Control-Allow-Credentials", "true");
		$response = $response->withHeader('Content-type', 'application/json');

		return($response);

	} // End of setJsonAndCoresHeaders()


} // End of Content class


