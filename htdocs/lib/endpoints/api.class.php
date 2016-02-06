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
	*
	*/
	function __construct($app, $display, $line, $train, $system) {
		$this->app = $app;
		$this->display = $display;
		$this->line = $line;
		$this->train = $train;
		$this->system = $system;
	}


	/**
	* Our main entry point.
	*
	*/
	function go() {

		$app = $this->app;
		$display = $this->display;
		$line = $this->line;

		$app->group("/api/current/train/{trainno}", function() {

			$this->get("", function(Request $request, Response $response, $args) {

				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$train = new \Septa\Query\Train($splunk);
		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() use ($args, $train, $response, $display) {

					$output = $display->json_pretty($train->get($args["trainno"]));
			    	$response->getBody()->write($output);

					return($response);

				}, $response);

				return($result);

			});


			$this->get("/history", function(Request $request, Response $response, $args) {

				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$train = new \Septa\Query\Train($splunk);
		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() use ($args, $train, $response, $display) {

					$output = $display->json_pretty($train->getHistoryByDay($args["trainno"]));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});


			$this->get("/history/average", function(Request $request, Response $response, $args) {

				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$train = new \Septa\Query\Train($splunk);
		    	$trainno = $request->getAttribute("trainno");

				$result = $display->splunkWrapper(function() use($args, $train, $response, $display) {

					$output = $display->json_pretty($train->getHistoryHistoricalAvg($args["trainno"]));
	    			$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->group("/api/current/system", function() {

			$this->get("", function(Request $request, Response $response, $args) {
	
				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$system = new \Septa\Query\System($splunk);

				$num_trains = 10;
				$num_hours = 1;
				$span_min = 10;

				$result = $display->splunkWrapper(function() use ($system, $response, $num_trains, $num_hours, $span_min, $display) {

					$output = $display->json_pretty($system->getTopLatestTrains($num_trains, $num_hours, $span_min));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/totals", function(Request $request, Response $response, $args) {

				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$system = new \Septa\Query\System($splunk);

				$num_days = 7;

				$result = $display->splunkWrapper(function() use ($response, $args, $system, $num_days, $display) {

					$output = $display->json_pretty($system->getTotalMinutesLateByDay($num_days));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->get("/api/current/lines", function(Request $request, Response $response, $args) use ($display) {

			$splunk = new \Septa\Splunk();
			$line = new \Septa\Query\Line($splunk);

			$output = $display->json_pretty($line->getLines());
			$response->getBody()->write($output);

		});


		$app->get("/api/current/line/{line}/{direction}", function(Request $request, Response $response, $args) {

			$display = new \Septa\Display();
			$splunk = new \Septa\Splunk();
			$line = new \Septa\Query\Line($splunk);

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


		$app->group("/api/current/station", function() {

			$this->get("/{station}/trains", function(Request $request, Response $response, $args) {
	
				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$system = new \Septa\Query\Station($splunk);

				$station = $args["station"];

				$result = $display->splunkWrapper(function() use ($system, $response, $station, $display) {

					$output = $display->json_pretty($system->getTrains($station));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) {
	
				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$system = new \Septa\Query\Station($splunk);

				$station = $args["station"];

				$result = $display->splunkWrapper(function() use ($system, $response, $station, $display) {

					$output = $display->json_pretty($system->getTrainsLatest($station));
			    	$response->getBody()->write($output);

					}, $response);

				return($result);

			});

			$this->get("/{station}/stats", function(Request $request, Response $response, $args) {
	
				$display = new \Septa\Display();
				$splunk = new \Septa\Splunk();
				$system = new \Septa\Query\Station($splunk);

				$station = $args["station"];

				$result = $display->splunkWrapper(function() use ($system, $response, $station, $display) {

					$output = $display->json_pretty($system->getStats($station));
	    			$response->getBody()->write($output);

					}, $response);

				return($result);

			});

		});


		$app->get("/api/current/stations", function(Request $request, Response $response, $args) {

			$display = new \Septa\Display();
			$splunk = new \Septa\Splunk();
			$line = new \Septa\Query\Stations($splunk);

			$output = $display->splunkWrapper(function() use ($line, $display) {

				$data = $line->getStations();

				$output = $display->json_pretty($data);

				return($output);

				}, $response);

			$response->getBody()->write($output);

		});


	} // End of go()


} // End of Content class


