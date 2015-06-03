<?php

define("__CLASSNAME__", "\\LeFigaro");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class LeFigaro extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	public function produce($out)
	{
		// create an empty message list
		$messagelist = com\busit\Factory::messageList();
		
		// connect to SQL for timestamps storage
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		// switch between outputs
		switch( $out->key )
		{
			case 'une':
				$url = 'http://www.lefigaro.fr/rss/figaro_une.xml';
			break;
			case 'politic':
				$url = 'http://www.lefigaro.fr/rss/figaro_politique.xml';
			break;
			case 'international':
				$url ='http://www.lefigaro.fr/rss/figaro_international.xml';
			break;
			case 'economy':
				$url ='http://www.lefigaro.fr/rss/figaro_economie.xml';
			break;
			case 'sport':
				$url = 'http://www.lefigaro.fr/rss/figaro_sport.xml';
			break;
			case 'technology':
				$url = 'http://www.lefigaro.fr/rss/figaro_hightech.xml';
			break;
			case 'science':
				$url = 'http://www.lefigaro.fr/rss/figaro_sciences.xml';
			break;
			default:
				$url = 'http://www.lefigaro.fr/rss/figaro_une.xml';
		}
		
		// first time, we insert an unique entry in the buffer table with the current timestamp
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		// end
		
		// get current news (cached)
		$data = file_get_contents("http://cache.busit.com/?url=".urlencode($url));
		$news = array();
		if( $data )
		{
			$x = new SimpleXmlElement($data);
			
			foreach( $x->channel->item as $i )
				$news[] = array('title' => $i->title[0] . '', 'link' => $i->link[0] . '', 'timestamp' => strtotime($i->pubDate[0]), 'date' => date('Y-m-d H:i:s', strtotime($i->pubDate[0])), 'description' => $i->description[0] . '');
		
			// reverse to order it from old to recent
			$news = array_reverse($news);
			if( count($news) > 0 )
			{
				foreach( $news as $n )
				{	
					// chack if news is more recent than the last one
					$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '{$n['timestamp']}'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['buffer_identifier'] )
					{
						$message = com\busit\Factory::message();
						$content = com\busit\Factory::content(5);
						
						$content['title'] = $n['title'];
						$content['subject'] = $n['title'];
						$content['description'] = strip_tags($n['description']);
						$content['link'] = $n['link'];
						$content['timestamp'] = $n['timestamp'];
						$content['date'] =  $n['date'];
						$content['author'] = 'Le Figaro';
						$content['image'] = 'https://images.busit.com/connectors/130_100.png';
						
						$message->content($content);
						$messagelist[] = $message;

						$sql = "UPDATE buffer SET buffer_time = '{$n['timestamp']}' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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