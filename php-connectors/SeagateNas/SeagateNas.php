<?php

define("__CLASSNAME__", "\\SeagateNas");

use com\anotherservice\util as cau;

class SeagateNas extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $original = null;
	
	public function consume($message, $in)
	{
		switch( $in->key )
		{
			case 'push':
				$this->original = $message;
			break;
			case 'store':
				// todo
			break;
		}
	}
	
	public function produce($out)
	{
		if( $this->original == null )
			return null;
		
		$content = $this->original->content();
		$original = json_decode($content['data'], true);
		
		$message = com\busit\Factory::message();
		
		// if the received data corresponding to the current producing output
		if( $original['type'] == $out->key || $out->key == 'all' )
		{
			$content = com\busit\Factory::content(1);
			$content['message'] = $original['message'];
			$content['author'] = $original['sender'];
			$content['date'] = date('Y-m-d H:i:s', $original['timestamp']);
			$content['timestamp'] = $original['timestamp'];
			
			$message->content($content);

			return $message;
		}
		else
			return null;
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