<?php

define("__CLASSNAME__", "\\Foursquare");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Foursquare extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	public function produce($out)
	{
		$messagelist = com\busit\Factory::messageList();
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	

		$sql = "SELECT * FROM foursquare WHERE user = '". $this->config('user') ."' AND ack = 0";		
		$result = $mysql->select($sql);

		foreach( $result as $r )
		{
			if( $r['date'] )
			{
				$sql = "UPDATE foursquare SET ack = 1 WHERE user = '". $this->config('user') ."' AND date = '{$r['date']}'";
				$mysql->update($sql);
				
				$data = json_decode($r['message'], true);
				
				$message = com\busit\Factory::message();
				$content = com\busit\Factory::content(13);
				
				$content['lat'] = $data['venue']['location']['lat'];
				$content['long'] = $data['venue']['location']['lng'];
				$content['address'] = $data['venue']['location']['address'];
				$content['city'] = $data['venue']['location']['city'];
				$content['country'] = $data['venue']['location']['country'];
				$content['date'] = $r['date'];
				$content['timestamp'] = strtotime($r['date']);
				
				$message->content($content);
				$messagelist[] = $message;
			}
		}
		
		return $messagelist;
	}
	
	public function consume($message, $in)
	{
		return;
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