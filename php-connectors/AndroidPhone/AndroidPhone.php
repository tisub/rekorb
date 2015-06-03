<?php

define("__CLASSNAME__", "\\AndroidPhone");

use com\anotherservice\util as cau;

class AndroidPhone extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $notificationUrl = 'https://android.googleapis.com/gcm/send';
	private $apiKey = 'AIzaSyCHsaS1d-tZyMY9GZYD2GoP9EaVDJ5HYdw';
	private $original = null;
	
	public function consume($message, $in)
	{
		switch( $in->key )
		{
			case 'push':
				$this->original = $message;
			break;
			case 'ring':
				
			break;
			case 'notification': 
				$notification = array();
				$content = $message->content();
				
				if( isset($content['title']) && strlen($content['title']) > 0 )
					$notification['title'] = $content['title'];
				else
					$notification['title'] = 'New message from Busit';
				
				if( isset($content['description']) && strlen($content['description']) > 0 )
					$notification['message'] = $content['description'];
				else if( isset($content['message']) && strlen($content['message']) > 0 )
					$notification['message'] = $content['message'];
				else
					$notification['message'] = $content->toText();
				
				$this->sendNotification($notification);
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
		if( $original['type'] == $out->key )
		{
			switch( $original['type'] )
			{
				case 'sms':
					$content = com\busit\Factory::content(7);
					$content['message'] = $original['message'];
					$content['sender'] = $original['sender'];
					$content['date'] = $original['date'];
					$content['timestamp'] = strtotime($original['date']);
				break;
				case 'gps':
					$content = com\busit\Factory::content(8);
					$content['lat'] = $original['lat'];
					$content['long'] = $original['long'];
					$content['date'] = $original['date'];
					$content['timestamp'] = strtotime($original['date']);
				break;
				case 'photo':
					$content = com\busit\Factory::content(4);
					$content['title'] = 'New photo';
					$content['author'] = 'Android App';
					$content['date'] = $original['date'];
					$content['timestamp'] = strtotime($original['date']);
				break;
				case 'message':
					$content = com\busit\Factory::content(1);
					$content['message'] = $original['message'];
					$content['date'] = $original['date'];
					$content['timestamp'] = strtotime($original['date']);
				break;
			}
			
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
	
	private function sendNotification($notification)
	{
		$data = array('message' => $notification['message'], 'title' => $notification['title']);
		$ids = array($this->config('pushid'));

		$post = array(
			'registration_ids'  => $ids,
			'data'              => $data,
		);

		$headers = array( 
			'Authorization: key=' . $this->apiKey,
			'Content-Type: application/json'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->notificationUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
		$result = curl_exec($ch);
		curl_close($ch);
	}
}

?>