<?php

define("__CLASSNAME__", "\\Redmine");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Redmine extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
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
			case 'issues':
				$t = 'issue';
				$url = "https://apps.busit.com/redmine/pull?type=issues&project={$out->value}";
			break;
			case 'times':
				$t = 'time';
				$url = "https://apps.busit.com/redmine/pull?type=times&project={$out->value}";
			break;
			case 'projects':
				$t = 'project';
				$url = "https://apps.busit.com/redmine/pull?type=projects";
			break;
			case 'myissues':
				$t = 'issue';
				$url = "https://apps.busit.com/redmine/pull?type=myissues";
			break;
		}
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', '".time()."')";
			$mysql->insert($sql);
		}
		// end

		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		if( count($result) > 0 )
		{
			$result = array_reverse($result);
			foreach( $result as $r )
			{
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['created_on'])."'";
				$row = $mysql->selectOne($sql);
				
				if( !$row['buffer_identifier'] )
				{
					$message = com\busit\Factory::message();
					
					if( $t == 'project' ) 
					{
						$content = com\busit\Factory::content(11);
						$content['name'] = $r['name'];
						$content['description'] = $r['comments'];
						$content['link'] = $this->config('url') . '/projects/' . $r['identifier'];
					}
					else
					{
						$content = com\busit\Factory::content(12);
						if( $t == 'time' )
						{
							$content['hours'] = $r['hours'];
							$content['number'] = $r['hours'];
							$content['subject'] = $r['activity']['name'];
							$content['activity'] = $r['activity']['name'];
							$content['activity_id'] = $r['activity']['id'];
							$content['description'] = $r['comments'];
							$content['author'] = $r['user']['name'];
							$content['link'] = $this->config('url') . '/projects/' . $out->value . '/time_entries';
						}
						else if( $t == 'issue' )
						{
							$content['subject'] = $r['subject'];
							$content['description'] = $r['description'];
							$content['link'] = $this->config('url') . '/issues/' . $r['id'];
							$content['author'] = $r['author']['name'];
							$content['type'] = $r['tracker']['name'];
						}
					}
					
					$content['project'] = $r['project']['name'];
					$content['timestamp'] = strtotime($r['created_on']);
					$content['date'] =  date('Y-m-d H:i:s', strtotime($r['created_on']));
					
					$message->content($content);
					$messagelist[] = $message;
					
					$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['created_on'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
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
		
		switch( $in->key )
		{
			case 'newissue':
				$t = 'issue';
				$url = "https://apps.busit.com/redmine/send?type=issue";
			break;
			case 'newtime':
				$t = 'time';
				$url = "https://apps.busit.com/redmine/send?type=time";
			break;
			case 'newproject':
				$t = 'project';
				$url = "https://apps.busit.com/redmine/send?type=project";
			break;
		}
		
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;		
			break;
		}
		
		$content = $message->content();
		
		$data = array();
		if( $t == 'project' )
		{
			if( $content->compatible(11) )
			{
				$data['name'] = $content['name'];
				$data['description'] = $content['description'];
			}
			else
			{
				$data['name'] = substr($content->toText(), 0, 200);
				$data['description'] = 'New project from Busit';
			}
		}
		else if( $t == 'issue' )
		{
			$data['project'] = $in->value;
			if( $content->compatible(12) )
			{
				$data['subject'] = $content['subject'];
				$data['description'] = $content['description'];
			}
			else
			{
				$data['subject'] = 'New issue from Busit';
				$data['description'] = $content->toText();
			}
		}
		
		com\busit\HTTP::send($url, array('message'=>json_encode($data),'config'=>json_encode($this->config)), $file);
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