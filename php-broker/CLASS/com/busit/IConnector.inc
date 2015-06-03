<?php
namespace com\busit;

interface IConnector
{
	const VERSION = 3;
	
	public function init($config, $inputs, $outputs);
	public function cron($message, $interfaceId);
	public function setInput($message, $interfaceId);
	public function getOutput($interfaceId);
	public function getSampleData($message, $interfaceId);
	public function isFunctional($config, $inputs, $outputs);
	public function notifyUser($message);
	public function notifyOwner($message, $data);
}

?>