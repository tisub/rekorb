<?php

define("__CLASSNAME__", "\\Facebook");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Facebook extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $producing = true;
	
	public function produce($out)
	{
		if( $this->producing == false )
			return null;
		
		// create an empty message list
		$messagelist = com\busit\Factory::messageList();
		
		// connect to SQL for timestamps storage
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		// switch between outputs
		switch( $out->key )
		{
			case 'wall':
				$url = 'https://apps.busit.com/facebook/pull?type=wall';
			break;
			case 'home':
				$url = 'https://apps.busit.com/facebook/pull?type=home';
			break;
		}
		
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		// end
		
		if( count($result) > 0 )
		{
			$result = array_reverse($result);
			foreach( $result as $r )
			{
				if( strlen($r['message']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['created_time'])."'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{
						$message = com\busit\Factory::message();
						$content = com\busit\Factory::content(3);
						
						$content['status'] = $r['message'];
						$content['message'] = $r['message'];
						$content['timestamp'] = strtotime($r['created_time']);
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['created_time']));
						$content['author'] = $r['user']['screen_name'];
						
						if( isset($r['link']) && strlen($r['link']) > 0 )
							$content['link'] = $r['link'];
						else
							$content['link'] = $r['actions'][0]['link'];
						
						if( isset($r['picture']) && strlen($r['picture']) > 0 )
						{
							$content['image'] = $r['picture'];
							
							$binary = file_get_contents($r['picture']);
							$headers = get_headers($r['picture']);
							foreach( $headers as $h ) 
							{
								if( stripos($h, 'Content-Type') !== false )
								{
									$type = explode(':', $h);
									$type = explode('/', $type[1]);
									$type = $type[1];
									break;
								}
							}
							
							if( strlen($type) > 0 )
								$name = 'content_' . date('YmdHis') . uniqid('_') . '.' . $type;
							else
								$name = 'content_' . date('YmdHis') . uniqid('_');
							
							$message->file($name, $binary);
						}
						else
							$content['image'] = 'https://images.busit.com/connectors/11_100.png';
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['created_time'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
		
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			break;
		}
		
		$content = $message->content();
		if( $content->compatible(3) )
			$status = $content['status'];
		else
			$status = $content->toText();
		
		if( strlen($status) > 0 )
			com\busit\HTTP::send('https://apps.busit.com/facebook/send', array('message'=>json_encode($status),'config'=>json_encode($this->config())), $file);
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