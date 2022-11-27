<?php session_start();

require_once(__DIR__ . "/vendor/autoload.php");

use \Sabo\Sabo\Router;

// global consts 

define("ROOT",__DIR__ . '/');
define("CONFIG_FILE_TYPE",Router::JSON_ENV);

new Router(debug_mode: true);