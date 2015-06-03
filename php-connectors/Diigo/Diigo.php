<?php

define("__CLASSNAME__", "\\Diigo");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Diigo extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
		
		switch( $out->key )
		{
			case 'transmit':
				$url = 'https://apps.busit.com/diigo/pull?type=bookmarks';
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
		
		$result = array_reverse($result);
		if( count($result) > 0 )
		{
			foreach( $result as $r )
			{
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['created_at'])."'";
				$row = $mysql->selectOne($sql);
				
				if( !$row['buffer_identifier'] )
				{
					$message = com\busit\Factory::message();
					$content = com\busit\Factory::content(9);

					$content['title'] = $r['title'];
					$content['description'] = $r['desc'];
					$content['link'] = $r['url'];
					$content['timestamp'] = strtotime($r['created_at']);
					$content['date'] =  date('Y-m-d H:i:s', strtotime($r['created_at']));

					$message->content($content);
					$messagelist[] = $message;
					
					$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['created_at'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
		
		$content = $message->content();
		
		if( $content->compatibility(9) )
		{
			$infos = array();
			$infos['title'] = $content['title'];
			$infos['description'] = $content['description'];
			$infos['link'] = $content['link'];
		}
		else
			$infos = $content->toText();
		
		com\busit\HTTP::send('https://apps.busit.com/diigo/send', array('message'=>json_encode($infos),'config'=>json_encode($this->config())), $file);
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