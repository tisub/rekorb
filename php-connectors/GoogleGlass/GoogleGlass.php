<?php

define("__CLASSNAME__", "\\GoogleGlass");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class GoogleGlass extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'location':
				$url = 'https://apps.busit.com/google/glass/pull?type=location';
			break;
			case 'timeline':
				$url = 'https://apps.busit.com/google/glass/pull?type=timeline';
			break;
		}
		
		$result = file_get_contents($this->url . '&config=' . urlencode(json_encode($this->config())));
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
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['date'])."'";
				$row = $mysql->selectOne($sql);
				
				if( !$row['buffer_identifier'] )
				{
					$message = com\busit\Factory::message();
					
					if( $out->key == 'location' )
					{
						$content = com\busit\Factory::content(8);
						$content['lat'] = $r['x'];
						$content['long'] = $r['y'];
						$content['timestamp'] = strtotime($r['date']);
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['date']));
					}
					
					if( $out->key == 'timeline' )
					{
						$content = com\busit\Factory::content(3);
						$content['status'] = $r['message'];
						$content['message'] = $r['message'];
						$content['timestamp'] = strtotime($r['date']);
						$content['author'] = $r['creator']['name'];
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['date']));
						
						if( count($r['attachments']) > 0 )
						{
							foreach( $r['attachments'] as $a )
								$message->file($a['name'], $a['binary']);
						}
					}
					
					$message->content($content);
					$messagelist[] = $message;
					
					$sql = "UPDATE buffer SET buffer_time =  '".strtotime($r['date'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
					$mysql->update($sql);
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
			com\busit\HTTP::send('https://apps.busit.com/google/glass/send', array('message'=>json_encode($status),'config'=>json_encode($this->config())), $file);
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