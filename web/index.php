<?php

use \Pivel\Hydro2\Services\Autoloader;
use \Pivel\Hydro2\Services\Hydro2;

// Set DOCUMENT_ROOT to the correct directory
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);
$web_dir = dirname(__FILE__);
$app_dir = dirname(__FILE__, 2) . '/app';

// Manually require Autoloader.php, because we don't have a working autoloader yet
require_once $app_dir."/Pivel/Hydro2/Services/Autoloader.php";

// Set up Autoloader
$loader = new Autoloader($app_dir);
$loader->Register();

// Process incoming request
Hydro2::$app = new Hydro2($web_dir, $app_dir);
$request = Hydro2::$app->buildRequest();
$response = Hydro2::$app->processRequest($request);
$response->send(false);
Hydro2::$app->dispose();