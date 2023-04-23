<?php

use \Pivel\Hydro2\Hydro2;

// Set DOCUMENT_ROOT to the correct directory
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);
/**
 * @var string
 */
$webDir = dirname(__FILE__);
/**
 * @var string
 */
$appDir = dirname(__FILE__, 2) . '/app';
/**
 * @var string[]
 */
$additionalAppDirs = [];

// Manually require Autoloader.php, because we don't have a working autoloader yet
require_once $appDir."/Pivel/Hydro2/Hydro2.php";

Hydro2::CreateHydro2App($webDir, $appDir, $additionalAppDirs)->Run()->Dispose();