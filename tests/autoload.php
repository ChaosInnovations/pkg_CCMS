<?php

use Pivel\Hydro2\Services\AutoloadService;

$appDir = dirname(__FILE__, 2) . "/app";

require_once ($appDir . "/Pivel/Hydro2/Services/AutoloadService.php");
       
$this->_autoloadService = new AutoloadService($appDir);

$this->_autoloadService->Register();