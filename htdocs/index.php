<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

require("./lib/display.class.php");
require("./lib/splunk.php");
require("./lib/endpoints/content.class.php");
require("./lib/endpoints/api.class.php");
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


$display = new Septa\Display();
$splunk = new \Septa\Splunk();
$line = new \Septa\Query\Line($splunk);
$train = new \Septa\Query\Train($splunk);
$system = new \Septa\Query\System($splunk);
$station = new \Septa\Query\Station($splunk);
$stations = new Septa\Query\Stations($splunk);

$endpoints_content = new \Septa\Endpoints\Content($app, $display, $line);
$endpoints_content->go();

$endpoints_api = new \Septa\Endpoints\Api($app, $display, $line, $train, $system, $station, $stations);
$endpoints_api->go();



/**
* This endpoint is used for testing and development.
*/
$app->get("/test", function(Request $request, Response $response, $args) {

	$output = "";

	$urls = [
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
		];

	$output .= "<h2>Production URLs</h2>";
	foreach ($urls as $key) {
		$output .= "<a href=\"$key\">$key</a><br/>\n";
	}

	$urls_test = [
		"/train/4324%3Cscript%3Ealert('test')%3C%2fscript%3E",
		"/station/West%20Trenton%3Cscript%3Ealert('test');%3C%2fscript%3E",
		"/api/current/train/%3Cscript%3Ealert('test');%3C%2fscript%3E",
		"/api/current/station/%3Cscript%3Ealert('test');%3C%2fscript%3E/trains",
		"/api/current/station/%3Cscript%3Ealert('test');%3C%2fscript%3E/trains/latest",
		"/api/current/station/%3Cscript%3Ealert('test');%3C%2fscript%3E/stats",
		"/api/current/station/test%22/stats",
		];

	$output .= "<h2>Test URLs</h2>";
	foreach ($urls_test as $key) {
		$output .= "<a href=\"$key\">$key</a><br/>\n";
	}

    $response->getBody()->write($output);

});

$app->run();



