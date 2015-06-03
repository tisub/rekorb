<?php

define("__CLASSNAME__", "\\Bundesliga");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Bundesliga implements cb\IConnector
{
	private $config;
	private $inputs;
	private $outputs;
	private $uid;
	private $message;
	private $status = false;
	private $messagelist;
	
	public function init($config, $inputs, $outputs)
	{
		$this->uid = $config['uid'];
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
		$this->message = $message;
		$this->messagelist = new cb\MessageList();
		
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		$url = 'http://www.matchendirect.fr/rss/foot-barclays-premiership-premier-league-c15.xml';
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}'";
		$row = $mysql->selectOne($sql);
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('{$this->uid}', '".time()."')";
			$mysql->insert($sql);
		}
		// end
		
		$content = file_get_contents("{$url}");  
		$news = array();
		if( $content )
		{
			$x = new SimpleXmlElement($content);
			
			foreach( $x->channel->item as $i )
				$news[] = array('title' => $i->title[0] . '', 'link' => $i->link[0] . '', 'timestamp' => strtotime($i->pubDate[0]), 'date' => date('Y-m-d H:i:s', strtotime($i->pubDate[0])), 'description' => $i->description[0] . '');
		}
						
		$news = array_reverse($news);
		if( count($news) > 0 )
		{
			$this->messagelist = new cb\MessageList();
			
			foreach( $news as $n )
			{
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}' AND buffer_time >= '{$n['timestamp']}'";
				$row = $mysql->selectOne($sql);
				
				if( !$row['buffer_identifier'] && ( $this->config['team'] == 'All' || $this->config['team'] == 'Toutes' || strpos($n['title'], $this->config['team']) !== false ) && strpos($n['title'], 'final' ) !== false )
				{
					$type = new cb\KnownType();
					$type->wkid(4);
					$type->name('RSSEntry');
					$type->format('[@title] - [@link]');
					$type->compatibility(array(4));
					
					$type['title'] = $n['title'];
					$type['link'] = $n['link'];
					$type['timestamp'] = $n['timestamp'];
					$type['date'] =  $n['date'];
					$type['description'] = $n['description'];
					
					$this->message->setKnownType($type);
					$this->messagelist[] = $this->message->duplicate();
					$this->status = true;
					
					$sql = "UPDATE buffer SET buffer_time = '{$n['timestamp']}' WHERE buffer_identifier = '{$this->uid}'";
					$mysql->update($sql);
				}
			}
		}
	}
	
	public function setInput($message, $interfaceId)
	{
		throw new Exception("Unsupported operation");
	}
	
	public function getOutput($interfaceId)
	{	
		if( $this->status == true )
			return $this->messagelist;
		else
			return null;
	}
}

?>