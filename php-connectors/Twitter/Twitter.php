<?php

define("__CLASSNAME__", "\\Twitter");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;
	
class Twitter extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'mentions':
				$url = 'https://apps.busit.com/twitter/pull?type=mentions';
			break;
			case 'timeline':
				$url = 'https://apps.busit.com/twitter/pull?type=timeline';
			break;
			case 'home':
				$url = 'https://apps.busit.com/twitter/pull?type=home';
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
				if( strlen($r['text']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['created_at'])."'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{	
						$message = com\busit\Factory::message();
						$content = com\busit\Factory::content(3);
						$content['status'] = $r['text'];
						$content['message'] = $r['text'];
						$content['timestamp'] = strtotime($r['created_at']);
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['created_at']));
						$content['author'] = $r['user']['screen_name'];
						$content['image'] = 'https://images.busit.com/connectors/12_100.png';
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['created_at'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
			com\busit\HTTP::send('https://apps.busit.com/twitter/send', array('message'=>json_encode(substr($status, 0, 160)),'config'=>json_encode($this->config())), $file);		
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