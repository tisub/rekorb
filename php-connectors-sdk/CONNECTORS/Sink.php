<?php

define("__CLASSNAME__", "\\Sink");

class Sink extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{
		$this->notifyOwner($message->content()->toText(), null);
	}

	public function test()
	{
		return true;
	}
}

?>