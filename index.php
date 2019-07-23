<?php

use \Package\CCMS\Autoloader;
use \Package\CCMS\CCMSCore;

// Set DOCUMENT_ROOT to the correct root document
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);

// Manually require Autoloader.php, because we don't have a working autoloader yet
require_once $_SERVER["DOCUMENT_ROOT"]."/pkg/CCMS/Autoloader.php";

// Add namespaces to Autoloader
$loader = new Autoloader;
$loader->register();
$loader->addNamespace("Package", $_SERVER["DOCUMENT_ROOT"]."/pkg");

// Process incoming request
$core = new CCMSCore();
$request = $core->buildRequest();
$response = $core->processRequest($request);
$response->send(false);
$core->dispose();

// Force HTTPS
/*
$httpsURL = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!=='on'){
	if(count($_POST)>0) {
		die('Page should be accessed with HTTPS, but a POST Submission has been sent here. Adjust the form to point to '.$httpsURL);
	}
	header("Status: 301 Moved Permanently");
	header("Location: {$httpsURL}");
	exit();
}
*/