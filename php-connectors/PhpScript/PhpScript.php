<?php

define("__CLASSNAME__", "\\PhpScript");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class PhpScript extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $original = null;
	
	public function consume($message, $in)
	{
		switch( $in->key )
		{
			case 'transmit':
				$this->original = $message;
			break;
			case 'push': 
				$this->sendData($message->content()->toJson());
			break;
		}
	}
	
	public function produce($out)
	{
		if( $this->original == null )
			return null;

		$content = $this->original->content();
		$data = json_decode($content['data'], true);
		if( $data === null )
			$data = $content['data'];
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(1);
		$content['timestamp'] = time();
		$content['date'] = date('Y-m-d H:i:s', $content['timestamp']);
		
		if( is_array($data) )
		{
			foreach( $data as $key => $value )
			{
				$content[$key] = $value;
			}
		}
		else
			$content['message'] = $data;
		
		$message->content($content);	
		return $message;		
	}
	
	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
	
	private function sendData($data)
	{
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		$data = cad\mysql::escape($data);
		
		$sql = "INSERT INTO php (message_date, message_instance, message_password, message_content) VALUES (UNIX_TIMESTAMP(), '" . $this->config('__instance') . "', '" . $this->config('password') . "', '{$data}')";
		$mysql->insert($sql);
	}
}

?>