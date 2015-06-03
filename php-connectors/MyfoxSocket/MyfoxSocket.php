<?php

define("__CLASSNAME__", "\\MyfoxSocket");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class MyfoxSocket extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $producing = true;
	
	public function produce($out)
	{
		if( $this->producing == false )
			return null;
		
		$message = com\busit\Factory::message();
		$url = 'https://apps.busit.com/myfox/socket?type=socket_status';
		
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		if( !isset($result['stateLabel']) || strlen($result['stateLabel']) == 0 )
			return null;
		
		$content = com\busit\Factory::content(21);
		$content['subject'] = 'Myfox Socket Status';
		$content['switch_name'] = $result['siteLabel'];
		$content['switch_status'] = $result['stateLabel'];
		$content['timestamp'] = strtotime($result['createdAt']);
		$content['date'] = date('Y-m-d H:i:s', strtotime($result['createdAt']));
		
		$message->content($content);
		
		return $message;
	}
	
	public function consume($message, $in)
	{
		if( $in->key == 'trigger' )
			return;
		else
			$this->producing = false;
		
		$content = $message->content();
		
		if( $content['switch_status'] )
			$status = array('status' => $content['switch_status']);
		else
		{
			switch( $in->key )
			{
				case 'on':
					$status = array('status' => 'on');
				break;
				case 'off':
					$status = array('status' => 'off');
				break;			
			}
		}
		
		com\busit\HTTP::send('https://apps.busit.com/myfox/socket?type=socket_change', array('message'=>json_encode($status),'config'=>json_encode($this->config())));
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