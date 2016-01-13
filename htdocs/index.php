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

//
// Add a "view" call back to the app container to load a Twig template.
//
$container = $app->getContainer();

$container["view"] = function ($container) {
    $view = new \Slim\Views\Twig("templates", [
        //"cache" => "templates/cache",
		"debug" => true,
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container["router"],
        $container["request"]->getUri()
    ));

	$view->addExtension(new Twig_Extension_Debug());

    return $view;
};

$app->get("/", function (Request $request, Response $response, $args) {

    return $this->view->render($response, "index.html", [
        //'name' => $args['name']
    ]);

});

$app->get("/faq", function (Request $request, Response $response, $args) {

    return $this->view->render($response, "faq.html", [
    	]);

});

$app->get("/lines", function (Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$output = json_pretty($line->getLines());
	$lines = json_decode($output, true);

    return $this->view->render($response, "lines.html", [
		"lines" => $lines,
    	]);

});



/**
* Helper function to return prettified JSON data.
*/
function json_pretty($data) {
	return(json_encode($data, JSON_PRETTY_PRINT));
}

/**
* Wrap all of our calls to Splunk, so that if it fails, we can return a nice error in JSON format.
*/
function splunkWrapper($cb, $response) {

	try {
		return($cb());

	} catch (Exception $e) {
		//
		// Was there a problem querying Splunk?
		// Log it, and then throw a 5XX error.
		//
		syslog(LOG_ERR, "splunkWrapper(): " . $e->getMessage());

		$data = array(
			"error" => "Unable to connect to our data store. (is it running?)",
			);

		$output = json_pretty($data);
		$newResponse = $response->withStatus(500, "Server Error");
		$newResponse->getBody()->write($output);
		return($newResponse);

	}

} // End of splunkWrapper()


$app->group("/api/current/train/{trainno}", function() {

	$this->get("", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$result = splunkWrapper(function() use ($args, $train, $response) {

			$output = json_pretty($train->get($args["trainno"]));
	    	$response->getBody()->write($output);

			return($response);
			}, $response);

		return($result);

	});


	$this->get("/history", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$result = splunkWrapper(function() use ($args, $train, $response) {

			$output = json_pretty($train->getHistoryByDay($args["trainno"]));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});


	$this->get("/history/average", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$train = new Septa\Query\Train($splunk);
    	$trainno = $request->getAttribute("trainno");

		$result = splunkWrapper(function() use($args, $train, $response) {

			$output = json_pretty($train->getHistoryHistoricalAvg($args["trainno"]));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});

});


$app->group("/api/current/system", function() {

	$this->get("", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\System($splunk);

		$num_trains = 10;
		$num_hours = 1;
		$span_min = 10;

		$result = splunkWrapper(function() use ($system, $response, $num_trains, $num_hours, $span_min) {

			$output = json_pretty($system->getTopLatestTrains($num_trains, $num_hours, $span_min));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});

	$this->get("/totals", function(Request $request, Response $response, $args) {

		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\System($splunk);

		$num_days = 7;

		$result = splunkWrapper(function() use ($response, $args, $system, $num_days) {

			$output = json_pretty($system->getTotalMinutesLateByDay($num_days));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});

});


$app->get("/api/current/lines", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$output = json_pretty($line->getLines());
	$response->getBody()->write($output);

});


$app->get("/api/current/line/{line}/{direction}", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Line($splunk);

	$line_name = $line->checkLineKey($args["line"]);
	$direction = $line->checkDirection($args["direction"]);

	if ($line_name && $direction) {

		$data = $line->getTrains($line_name, $direction, 1, 10);
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


$app->group("/api/current/station", function() {

	$this->get("/{station}/trains", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$result = splunkWrapper(function() use ($system, $response, $station) {

			$output = json_pretty($system->getTrains($station));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});

	$this->get("/{station}/trains/latest", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$result = splunkWrapper(function() use ($system, $response, $station) {

			$output = json_pretty($system->getTrainsLatest($station));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});

	$this->get("/{station}/stats", function(Request $request, Response $response, $args) {
	
		$splunk = new \Septa\Splunk();
		$system = new Septa\Query\Station($splunk);

		$station = $args["station"];

		$result = splunkWrapper(function() use ($system, $response, $station) {

			$output = json_pretty($system->getStats($station));
	    	$response->getBody()->write($output);

			}, $response);

		return($result);

	});


});


$app->get("/api/current/stations", function(Request $request, Response $response, $args) {

	$splunk = new \Septa\Splunk();
	$line = new Septa\Query\Stations($splunk);

	$output = splunkWrapper(function() use ($line) {

		$data = $line->getStations();
		foreach ($data["data"] as $key => $value) {
			unset($data["data"][$key]["_raw"]);
			unset($data["data"][$key]["_time"]);
		}

		$output = json_pretty($data);

		return($output);

		}, $response);

	$response->getBody()->write($output);

});


/**
* This endpoint is used for testing and development.
*/
$app->get("/test", function(Request $request, Response $response, $args) {

	$urls = array(
		"/api/current/train/521",
		"/api/current/train/521/history",
		"/api/current/train/521/history/average",
		"/api/current/system",
		"/api/current/system/totals",
		"/api/current/lines",
		"/api/current/line/paoli-thorndale/outbound",
		"/api/current/line/paoli-thorndale/inbound",
		"/api/current/line/paoli-thorndale/foobar",
		"/api/current/line/foobar/foobar",
		"/api/current/station/ardmore/trains",
		"/api/current/station/ardmore/trains/latest",
		"/api/current/station/ardmore/stats",
		"/api/current/stations",
		);

	$output = "";
	foreach ($urls as $key) {
		$output .= "<a href=\"$key\">$key</a><br/>\n";
	}

    $response->getBody()->write($output);

});

$app->run();



