<?php

define("__CLASSNAME__", "\\Instagram");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Instagram extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	public function produce($out)
	{
		$messagelist = com\busit\Factory::messageList();
		
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	
		
		switch( $out->key )
		{
			case 'photos':
				$url = 'https://apps.busit.com/instagram/pull?type=photos';
			break;
			case 'liked':
				$url = 'https://apps.busit.com/instagram/pull?type=liked';
			break;
			case 'tagged':
				$url = 'https://apps.busit.com/instagram/pull?type=tag&tag=' . $out->value;
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
		
		if( count($result) > 0 )
		{
			$result = array_reverse($result);
			foreach( $result as $r )
			{
				if( strlen($r['image']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '{$r['time']}'";
					$row = $mysql->selectOne($sql);
				
					if( !$row['buffer_identifier'] )
					{
						$message = com\busit\Factory::message();				
						$content = com\busit\Factory::content(4);
						$content->compatible(8, true);

						if( $r['caption'] )
						{
							$content['title'] = $r['caption'];
							$content['description'] = $r['caption'];
						}
						else
						{
							$content['title'] = 'No caption';
							$content['description'] = 'No caption';
						}
						
						$content['lat'] = $r['lat'];
						$content['long'] = $r['long'];
						$content['timestamp'] = $r['time'];
						$content['link'] = $r['image'];
						$content['date'] =  date('Y-m-d H:i:s', $r['time']);
						
						if( $r['image'] )
						{
							$content = file_get_contents($r['image']);
							$name = basename($r['image']);
							$message->file($name, $content);
						}
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '{$r['time']}' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
						$mysql->update($sql);
					}
				}
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