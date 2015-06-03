<?php

// ====================================
// SETUP THE ENVIRONMENT
// ====================================

function __autoload($class_name)
{
	require_once __DIR__ . '/CLASS/' . implode("/", explode("\\", $class_name)) . '.inc';
}

function nanotime()
{
	return microtime(true) * /*MICRO*/ 1000000 * /*NANO*/ 1000;
}

function error_handler($errno, $errstr, $errfile, $errline)
{
	if( ($errno & error_reporting()) > 0 )
		throw new \ErrorException($errstr, 500, $errno, $errfile, $errline);
	else
		return false;
}
set_error_handler('error_handler');
date_default_timezone_set('Europe/Paris');
error_reporting(E_ALL | E_NOTICE | E_STRICT | E_DEPRECATED);
session_start();

// ====================================
// START PROCESSING
// ====================================

use com\anotherservice\util as cau;
use com\busit\local as cbl;

try
{
	cau\Logger::instance()->logLevel = error_reporting();
	$stream = fopen('TEMP/busit.log', 'a+');
	if( $stream )
		cau\Logger::instance()->logStream = $stream;
	else
		throw new Exception('Impossible to open log file');
	
	$step = basename($_GET['__rewrite']);
	if( is_numeric($step) && file_exists($step.'.php') )
		cbl\Template::output(require_once($step.'.php'));
	else
		cbl\Template::output(require_once('1.php'));
}
catch(\Exception $e)
{
	cau\Logger::severe($e);
	cbl\Template::error();
}

if( cau\Logger::instance()->logStream ) fclose(cau\Logger::instance()->logStream);

?>