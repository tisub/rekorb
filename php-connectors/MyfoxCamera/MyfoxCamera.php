<?php

define("__CLASSNAME__", "\\MyfoxCamera");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class MyfoxCamera extends com\busit\Connector implements com\busit\Producer, com\busit\Transformer
{
	public function produce($out)
	{
		$url = 'https://apps.busit.com/myfox/camera?type=get_image';
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
	
		$message = com\busit\Factory::message();				
		$content = com\busit\Factory::content(4);
		
		$content['title'] = 'Myfox Camera Picture';
		$content['subject'] = 'Myfox Camera Picture';
		$content['description'] = 'A new photo from the Myfox camera';
		$content['description'] = 'A new photo from the Myfox camera';
		$content['author'] = "Myfox Camera ({$result['siteName']})";
		$content['timestamp'] = time();
		$content['date'] = date('Y-m-d H:i:s', $content['timestamp']);
		$message->content($content);
		$message->file('myfox_camera_' . date('YmdHis') . '.jpg', base64_decode($result['binary']));
		
		return $message;
	}
	
	public function transform($message, $in, $out)
	{
		$url = 'https://apps.busit.com/myfox/camera?type=get_image';
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		$message->file('myfox_camera_' . date('YmdHis') . '.jpg', base64_decode($result['binary']));
		
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
}

?>