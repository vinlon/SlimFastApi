<?php
date_default_timezone_set('PRC'); //设置中国时区

//Require the lib that managed by comoser
require 'vendor/autoload.php';

// Create and configure Slim app
$app = new \Slim\App;

// Create and configure Slim app
$app = new Slim\App([
    'settings' => [
        // Only set this if you need access to route within middleware
        'determineRouteBeforeAppMiddleware' => true
    ]
]);

//error handle
require "app/error_handler.php";
//require route definition
require "app/route/route.php";

// Run app
$app->run();
?>
