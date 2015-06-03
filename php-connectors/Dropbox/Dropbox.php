<?php

define("__CLASSNAME__", "\\Dropbox");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Dropbox extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'notifications':
				$url = 'https://apps.busit.com/dropbox/pull?type=notifications';
			break;
		}
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		// end
		
		if( $row['buffer_value'] )
			$result = file_get_contents($url . '&cursor=' .urlencode($row['buffer_value']) . '&config=' . urlencode(json_encode($this->config())));
		else
			$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		
		$result = json_decode($result, true);
		
		if( count($result['entries']) > 0 )
		{
			foreach( $result['entries'] as $r )
			{
				$r = $r[1];
				
				if( strlen($r['path']) > 1 )
				{
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['modified'])."'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{
						$message = com\busit\Factory::message();				
						$content = com\busit\Factory::content(18);
						
						$content['is_directory'] = $r['is_dir'];
						if( $r['is_dir'] == false )
						{
							$content['size'] = $r['size'];
							$content['number'] = $r['size'];
						}
						else
							$content['size'] = '0';
						$content['timestamp'] = strtotime($r['modified']);
						$content['date'] =  date('Y-m-d H:i:s', strtotime($r['modified']));
						$content['user'] = 'Anonymous';
						
						$message->content($content);
						$messagelist[] = $message;
						
						$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['modified'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
						$mysql->update($sql);
					}
				}
			}
			
			$sql = "UPDATE buffer SET buffer_value = '{$result['cursor']}' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
			$mysql->update($sql);
		}
		
		return $messagelist;
	}
	
	public function consume($message, $in)
	{
		if( $in->key == 'trigger' )
			return;
		else
			$this->producing = false;
		
		if( $in->value == null )
			$directory = 'busit';
		else
			$directory = $in->value;
		$infos = array('directory' => $directory);

		$content = $message->content();
		$data = $content->toText();

		$message->file('content_' . date('YmdHis') . uniqid('_') . '.txt', $data);
		
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			
			com\busit\HTTP::send('https://apps.busit.com/dropbox/send', array('message'=>json_encode($infos),'config'=>json_encode($this->config())), $file);
		}
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