<?php

define("__CLASSNAME__", "\\Steam");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Steam extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	public function produce($out)
	{
		$messagelist = com\busit\Factory::messageList();
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		switch( $out->key )
		{
			case 'achievements':
				$url = 'https://apps.busit.com/steam/pull?type=achievements';
			break;
			case 'news':
				$url = 'https://apps.busit.com/steam/pull?type=news';
			break;
			case 'last':
				$url = 'https://apps.busit.com/steam/pull?type=last';
			break;
			case 'playtime':
				$url = 'https://apps.busit.com/steam/pull?type=playtime';
			break;
		}
		
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
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
		
		if( $out->key == 'playtime' )
			$result = array($result);
		
		$result = array_reverse($result);
		foreach( $result as $r )
		{
			if( !$r['date'] )
				$r['date'] = time();

			$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '{$r['date']}'";
			$row = $mysql->selectOne($sql);
		
			if( !$row['buffer_identifier'] )
			{
				$message = com\busit\Factory::message();
						
				if( $out->key == 'news' )
				{
					$content = com\busit\Factory::content(5);
					$content['title'] = $r['title'];
					$content['description'] = $r['description'];
					$content['link'] = $r['url'];
					$content['timestamp'] = $r['date'];
					$content['date'] =  date('Y-m-d H:i:s', $r['date']);
					$content['author'] = 'Steam';
					$content['image'] = 'https://images.busit.com/connectors/92_100.png';
				}
				
				if( $out->key == 'playtime' )
				{
					$content = com\busit\Factory::content(1);
					$content['message'] = $r['playtime_forever'];
					$content['timestamp'] = $r['date'];
					$content['date'] =  date('Y-m-d H:i:s', $r['date']);
					$content['author'] = 'Steam';
				}
				
				$message->content($content);
				$messagelist[] = $message;
		
				$sql = "UPDATE buffer SET buffer_time = '{$r['date']}' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
				$mysql->update($sql);
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