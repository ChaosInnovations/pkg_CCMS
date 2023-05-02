<?php

use Pivel\Hydro2\Services\AutoloadService;

$appDir = dirname(__FILE__, 2) . "/app";
$testsDir = dirname(__FILE__, 2) . "/tests";

require_once ($appDir . "/Pivel/Hydro2/Services/AutoloadService.php");
       
$_autoloadService = new AutoloadService($appDir);

$_autoloadService->AddDir($testsDir);

$_autoloadService->Register();