<?php

define("__CLASSNAME__", "\\FoscamCamera");

use com\busit as cb;
use com\anotherservice\util as cau;

class FoscamCamera implements cb\IConnector
{
	private $config;
	private $inputs;
	private $outputs;
	private $message;
	
	public function init($config, $inputs, $outputs)
	{
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
		throw new Exception("Unsupported operation");
	}
	
	public function setInput($message, $interfaceId)
	{	
		$this->message = $message;
	}
	
	public function getOutput($interfaceId)
	{
		return $this->message;
	}
}

?>