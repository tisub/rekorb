<?php

define("__CLASSNAME__", "\\Logmatic");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Logmatic extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{		
		$content = $message->content();
		$content = json_decode($content->toJson(), true);
		$content['content_id'] = $content['id'];
		unset($content['id']);
		$content['data']['timestamp'] = $content['data']['timestamp']*1000;
		
		$data = json_encode($content);
		$ch = curl_init();
		$category = curl_escape($ch, $in->value);
		curl_setopt($ch, CURLOPT_URL, "https://api.logmatic.io/v1/input/". $this->config('apikey') ."?instance_id=". $this->config('__instance')."&category={$category}");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json'
		));
		curl_exec($ch);
	}
	
	public function test()
	{
		return true;
	}
}

?>