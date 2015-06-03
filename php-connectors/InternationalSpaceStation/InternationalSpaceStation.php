<?php

define("__CLASSNAME__", "\\InternationalSpaceStation");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class InternationalSpaceStation extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = "http://api.open-notify.org/iss-now.json";
		$data = file_get_contents("http://cache.busit.com/?url=".urlencode($url));
		$data = json_decode($content, true);
		
		$message = com\busit\Factory::message();
		
		switch( $out->key )
		{
			case 'position':
				if( strlen($data['iss_position']['latitude']) == 0 || strlen($data['iss_position']['longitude']) == 0 )
					return null;
				$content = com\busit\Factory::content(8);
				$content['lat'] = $data['iss_position']['latitude'];
				$content['long'] = $data['iss_position']['longitude'];
				$content['timestamp'] = $data['timestamp'];
				$content['date'] = date('Y-m-d H:i:s', $data['timestamp']);

				$message->content($content);
				
				return $message;
			break;
		}
		
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