<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Support both standard Laravel structure (../) and cPanel split-folder structure (../ticketlaravel/)
$basePath = file_exists(__DIR__.'/../vendor/autoload.php')
    ? dirname(__DIR__)
    : (file_exists(__DIR__.'/../ticketlaravel/vendor/autoload.php')
        ? dirname(__DIR__).'/ticketlaravel'
        : dirname(__DIR__));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

// Ensure public_path() resolves accurately on cPanel (both direct and split-folder setups)
if (is_dir(__DIR__.'/build')) {
    $app->usePublicPath(__DIR__);
}

$app->handleRequest(Request::capture());
