<?php

define("__CLASSNAME__", "\\HelloWorld");

class HelloWorld extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$message = com\busit\Factory::message();
		$content = $message->content();
		$content['data'] = "Hello " . $this->config('name') . " !";
		
		$this->notifyUser("test user");
		$this->notifyOwner("test owner", array('lala'=>'lili'));
		
		return $message;
	}

	public function test()
	{
		return true;
	}
	
	public function sample($out)
	{
		return $this->produce($out);
	}
}

?>