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


$app->group("/api/train/{trainno}", function() {

	$this->get("", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = "<pre>" . print_r($train->get($args["trainno"]), true) . "</pre>";

	    $response->getBody()->write($output);

	});


	$this->get("/history", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = "<pre>" . print_r($train->getHistoryByDay($args["trainno"]), true) . "</pre>";

	    $response->getBody()->write($output);

	});


	$this->get("/history/average", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$output = "<pre>" . print_r($train->getHistoryHistoricalAvg($args["trainno"]), true) . "</pre>";

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

		$output = "<pre>" . print_r($system->getTopLatestTrains($num_trains, $num_hours, $span_min), true) 
			. "</pre>";

	    $response->getBody()->write($output);

	});

	$this->get("/totals", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\System($splunk);

		$num_days = 7;
		$output = "<pre>" . print_r($system->getTotalMinutesLateByDay($num_days), true) . "</pre>";

	    $response->getBody()->write($output);

	});

});


$app->get("/api/lines", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$output = "<pre>" . print_r($line->getLines(), true) . "</pre>";
	$response->getBody()->write($output);

});


$app->get("/api/line/{line}/{direction}", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$line_name = $line->checkLineKey($args["line"]);
	$direction = $line->checkDirection($args["direction"]);

	if ($line_name && $direction) {
		$output = "$line_name, $direction<br/>\n";
		$response->getBody()->write($output);

	} else {
		$output = sprintf("Line '%s' and/or direction '%s' not found!\n", $args["line"], $args["direction"]);
		$new_response = $response->withStatus(404, $output);
		$new_response->getBody()->write($output);
		return($new_response);

	}

});


$app->group("/api/station", function() {

	$this->get("/{station}/trains", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = "<pre>" . print_r($system->getTrains($station), true) 
			. "</pre>";

	    $response->getBody()->write($output);

	});

	$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = "<pre>" . print_r($system->getTrainsLatest($station), true) 
			. "</pre>";

	    $response->getBody()->write($output);

	});

	$this->get("/{station}/stats", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$output = "<pre>" . print_r($system->getStats($station), true) 
			. "</pre>";

	    $response->getBody()->write($output);

	});


});


$app->get("/api/stations", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Stations($splunk);

	$output = "<pre>" . print_r($line->getStations(), true) . "</pre>";
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



