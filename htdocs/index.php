<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

require("./lib/splunk.php");
require("./lib/query/train.class.php");
require("./lib/query/line.class.php");
require("./lib/query/system.class.php");
require("./lib/query/station.class.php");
require("./lib/query/stations.class.php");


$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);


$app->get("/", function (Request $request, Response $response) {

	// Testing/debugging
	$urls = array(
		"/api/train/521",
		"/api/train/521/history",
		"/api/train/521/history/average",
		"/api/system",
		"/api/system/totals",
		"/api/lines",
		"/api/line/paoli-thorndale/outbound",
		"/api/line/paoli-thorndale/inbound",
		"/api/line/paoli-thorndale/foobar",
		"/api/line/foobar/foobar",
		"/api/station/ardmore/trains",
		"/api/station/ardmore/trains/latest",
		"/api/station/ardmore/stats",
		"/api/stations",
		);

	$output = "";
	foreach ($urls as $key) {
		$output .= "<a href=\"$key\">$key</a><br/>\n";
	}

    $response->getBody()->write($output);

});


/**
* Helper function to return prettified JSON data.
*/
function json_pretty($data) {
	return(json_encode($data, JSON_PRETTY_PRINT));
}


$app->group("/api/train/{trainno}", function() {

	$this->get("", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = json_pretty($train->get($args["trainno"]));

	    $response->getBody()->write($output);

	});


	$this->get("/history", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = json_pretty($train->getHistoryByDay($args["trainno"]));

	    $response->getBody()->write($output);

	});


	$this->get("/history/average", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = json_pretty($train->getHistoryHistoricalAvg($args["trainno"]));

	    $response->getBody()->write($output);

	});

});


$app->group("/api/system", function() {

	$this->get("", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\System($splunk);

		$num_trains = 10;
		$num_hours = 1;
		$span_min = 10;

		$output = json_pretty($system->getTopLatestTrains($num_trains, $num_hours, $span_min));

	    $response->getBody()->write($output);

	});

	$this->get("/totals", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\System($splunk);

		$num_days = 7;
		$output = json_pretty($system->getTotalMinutesLateByDay($num_days));

	    $response->getBody()->write($output);

	});

});


$app->get("/api/lines", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$output = json_pretty($line->getLines());
	$response->getBody()->write($output);

});


$app->get("/api/line/{line}/{direction}", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$line_name = $line->checkLineKey($args["line"]);
	$direction = $line->checkDirection($args["direction"]);

	if ($line_name && $direction) {
		$data= array(
			"metadata" => array(
				),
			"data" => array("$line_name, $direction"),
			);
		$response->getBody()->write(json_pretty($data));

	} else {
		$error = sprintf("Line '%s' and/or direction '%s' not found!\n", $args["line"], $args["direction"]);
		$output = array(
			"error" => $error,
			);
		$output_json = json_pretty($output);
		$new_response = $response->withStatus(404, "Line or direction not found");
		$new_response->getBody()->write($output_json);
		return($new_response);

	}

});


$app->group("/api/station", function() {

	$this->get("/{station}/trains", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = json_pretty($system->getTrains($station));

	    $response->getBody()->write($output);

	});

	$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = json_pretty($system->getTrainsLatest($station));

	    $response->getBody()->write($output);

	});

	$this->get("/{station}/stats", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = json_pretty($system->getStats($station));

	    $response->getBody()->write($output);

	});


});


$app->get("/api/stations", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Stations($splunk);

	$data = $line->getStations();
	foreach ($data["data"] as $key => $value) {
		unset($data["data"][$key]["_raw"]);
		unset($data["data"][$key]["_time"]);
	}

	$output = json_pretty($data);

	$response->getBody()->write($output);

});


/**
* This endpoint is used for testing and development.
*/
$app->get("/test", function(Request $request, Response $response, $args) {

	$output = "testing";
	$newResponse = $response->withStatus(404, $output);
	$newResponse->getBody()->write($output);
	return($newResponse);

});

$app->run();



