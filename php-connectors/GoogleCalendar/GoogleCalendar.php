<?php

define("__CLASSNAME__", "\\GoogleCalendar");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class GoogleCalendar extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $producing = true;
	
	public function produce($out)
	{
		if( $this->producing == false )
			return null;
		
		$messagelist = com\busit\Factory::messageList();
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		switch( $out->key )
		{
			case 'event':
				$url = 'https://apps.busit.com/google/calendar/pull';
			break;
		}
		
		$result = file_get_contents($url . '?config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', '".time()."')";
			$mysql->insert($sql);
		}
		// end

		if( strlen($out->value) > 0 )	
			$minutes = $out->value;
		else
			$minutes = 15;
		
		if( count($result) > 0 )
		{
			foreach( $result as $r )
			{
				if( strtotime($r['start']['dateTime']) < time()+$minutes*60 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['start']['dateTime'])."'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{
						$message = com\busit\Factory::message();
						$content = com\busit\Factory::content(10);
						
						$content['description'] = $r['summary'];
						$content['location'] = $r['location'];
						$content['timestamp'] = strtotime($r['start']['dateTime']);
						$content['organizer'] = $r['organizer']['displayName'];
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['start']['dateTime']));
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['start']['dateTime'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
						$mysql->update($sql);
					}
				}
			}
		}
		
		return $messagelist;
	}
	
	public function consume($message, $in)
	{
		if( $in->key == 'trigger' )
			return;
		else
			$this->producing = false;
		
		$content = $message->content();
		if( $content->compatible(10) )
			$text = $content['description'] . ' ' . $content['date'];
		else
			$text = $content->toText();
		
		com\busit\HTTP::send('https://apps.busit.com/google/calendar/create', array('message'=>json_encode(array('string'=>$text)),'config'=>json_encode($this->config())));
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