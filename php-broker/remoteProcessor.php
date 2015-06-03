#!/usr/bin/env php
<?php

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
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// remove the script name from $argv
$p = new com\busit\broker\RemoteProcessorClient();

?>