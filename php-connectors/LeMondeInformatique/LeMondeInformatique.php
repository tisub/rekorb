<?php

define("__CLASSNAME__", "\\LeMondeInformatique");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class LeMondeInformatique extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'general':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/toutes-les-actualites/rss.xml';
			break;
			case 'business':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/business/rss.xml';
			break;
			case 'cloud':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/le-monde-du-cloud-computing/rss.xml';
			break;
			case 'datacenter':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/datacenter/rss.xml';
			break;
			case 'emploi':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/emploi/rss.xml';
			break;
			case 'hardware':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/hardware/rss.xml';
			break;
			case 'internet':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/internet/rss.xml';
			break;
			case 'logiciel':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/logiciel/rss.xml';
			break;
			case 'pme':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/pme/rss.xml';
			break;
			case 'reseau':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/reseaux/rss.xml';
			break;
			case 'securite':
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/securite/rss.xml';
			break;
			default:
				$url = 'http://www.lemondeinformatique.fr/flux-rss/thematique/toutes-les-actualites/rss.xml';
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
						$content['author'] = 'Le Monde Informatique';
						$content['image'] = 'https://images.busit.com/connectors/323_100.png';
						
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