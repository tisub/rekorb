<?php

define("__CLASSNAME__", "\\WithingsAura");

use com\busit as cb;
use com\anotherservice\util as cau;

class WithingsAura implements cb\IConnector
{
	private $config;
	private $inputs;
	private $outputs;
	private $message;
	private $status = false;
	private $action = null;
	
	public function init($config, $inputs, $outputs)
	{
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
	}
	
	public function setInput($message, $interfaceId)
	{
		$this->message = $message;
		
		if( $this->inputs[$interfaceId]['key'] == 'push' )
			$this->action = $message->getContentUTF8();
	}
	
	public function getOutput($interfaceId)
	{
		if( $this->action == 'WAKEUP' && $this->outputs[$interfaceId]['key'] == 'wakeup'  )
			return $this->message;
		else if( $this->action == 'SLEEP' && $this->outputs[$interfaceId]['key'] == 'sleep'  )
			return $this->message;
		else
			return null;
	}
}

?>