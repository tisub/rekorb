<?php

define("__CLASSNAME__", "\\Alarm");

use com\anotherservice\util as cau;

class Alarm extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		if( $out->value )
		{
			$message = com\busit\Factory::message();
			$content = com\busit\Factory::content(1);
			$content['message'] = $out->value;
			$content['timestamp'] = time();
			$content['date'] =  date('Y-m-d H:i:s', time());
			
			$message->content($content);
			
			return $message;
		}
	}
	
	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
}

?>