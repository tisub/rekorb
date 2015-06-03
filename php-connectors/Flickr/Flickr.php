<?php

define("__CLASSNAME__", "\\Flickr");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Flickr extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'photos':
				$url = 'https://apps.busit.com/flickr/pull?type=photos';
			break;
			case 'galleries':
				$url = 'https://apps.busit.com/flickr/pull?type=galleries';
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
				if( strlen($r['title']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '{$r['date']}'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{		
						$message = com\busit\Factory::message();				
						$content = com\busit\Factory::content(4);
						$content->compatible(3, true);
						$content->compatible(8, true);
						
						$content['title'] = $r['title'];
						$content['description'] = $r['description'];
						$content['status'] = $r['description'];
						$content['lat'] = $r['latitude'];
						$content['long'] = $r['longitude'];
						$content['timestamp'] = $r['date'];
						$content['author'] = $r['author'];
						$content['link'] = $r['url'];
						$content['image'] = $r['image'];
						$content['date'] =  date('Y-m-d H:i:s', $r['date']);
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '{$r['date']}' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
		if( $content->compatible(4) )
		{
			$title = $content['title'];
			$description = $content['description'];
		}
		else
		{
			$title = "New image from Busit";
			$description = $content->toText();
		}
		
		$data = array('title' => $title, 'description' => $description);
		
		com\busit\HTTP::send('https://apps.busit.com/flickr/send', array('message'=>json_encode($data),'config'=>json_encode($this->config())), $file);	
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