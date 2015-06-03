<?php

define("__CLASSNAME__", "\\Linkedin");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Linkedin extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'home':
				$url = 'https://apps.busit.com/linkedin/pull?type=home';
			break;
			case 'me':
				$url = 'https://apps.busit.com/linkedin/pull?type=me';
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
		
		if( count($result['updates']['values']) > 0 )
		{
			$result = array_reverse($result['updates']['values']);
			foreach( $result as $r )
			{
				if( strlen($r['updateContent']['person']['currentShare']['comment']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".round($r['timestamp']/1000)."'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{						
						$message = com\busit\Factory::message();
						$content = com\busit\Factory::content(3);
						
						$content['status'] = $r['updateContent']['person']['currentShare']['comment'];
						$content['message'] = $r['updateContent']['person']['currentShare']['comment'];
						$content['author'] = $r['updateContent']['person']['firstName'] . ' ' . $r['updateContent']['person']['lastName'];
						$content['timestamp'] = round($r['timestamp']/1000);
						$content['date'] = date('Y-m-d H:i:s', round($r['timestamp']/1000));
						
						if( $r['updateContent']['person']['currentShare']['content']['thumbnailUrl'] )
						{
							$content['image'] = $r['updateContent']['person']['currentShare']['content']['thumbnailUrl'];
							$content = file_get_contents($r['updateContent']['person']['currentShare']['content']['thumbnailUrl']);
							$headers = get_headers($r['updateContent']['person']['currentShare']['content']['thumbnailUrl']);
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
															
							$message->file($name, $content);
						}
						else
							$content['image'] = 'https://images.busit.com/connectors/19_100.png';
						
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '".round($r['timestamp']/1000)."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
			com\busit\HTTP::send('https://apps.busit.com/linkedin/send', array('message'=>json_encode($status),'config'=>json_encode($this->config())), $file);		
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