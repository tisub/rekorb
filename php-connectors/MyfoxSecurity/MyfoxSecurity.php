<?php

define("__CLASSNAME__", "\\MyfoxSecurity");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class MyfoxSecurity extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $producing = true;
	
	public function produce($out)
	{
		if( $this->producing == false && $out->key != 'alarmstatus' )
			return null;
		
		switch( $out->key )
		{
			case 'intrusion':
				$url = 'https://apps.busit.com/myfox/security?type=intrusion_events';
			break;
			case 'security':
				$url = 'https://apps.busit.com/myfox/security?type=security_events';
			break;
			case 'alarmstatus':
				$url = 'https://apps.busit.com/myfox/security?type=security_status';
			break;
		}
		
		$result = file_get_contents($url . '&config=' . urlencode(json_encode($this->config())));
		$result = json_decode($result, true);
		
		if( $out->key == 'alarmstatus' )
		{
			if( !isset($result['statusLabel']) || strlen($result['statusLabel']) == 0 )
				return null;
		
			$message = com\busit\Factory::message();
			$content = com\busit\Factory::content(21);
			
			$content['subject'] = 'Myfox Alarm Status';
			$content['switch_name'] = $result['siteLabel'];
			$content['switch_status'] = $result['statusLabel'];
			$content['timestamp'] = time();
			$content['date'] = date('Y-m-d H:i:s', $content['timestamp']);
			
			$message->content($content);
			
			return $message;
		}
		else
		{
			$messagelist = com\busit\Factory::messageList();
			$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');

			// first time
			$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
			$row = $mysql->selectOne($sql);
			
			if( !$row['buffer_identifier'] )
			{
				$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', '".time()."')";
				$mysql->insert($sql);
			}
			// end

			if( count($result['items']) > 0 )
			{
				$result['items'] = array_reverse($result['items']);
				foreach( $result['items'] as $r )
				{
					if( strlen($r['label']) > 1 )
					{
						$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($r['createdAt'])."'";
						$row = $mysql->selectOne($sql);
						
						if( !$row['buffer_identifier'] )
						{
							$message = com\busit\Factory::message();
							$content = com\busit\Factory::content(22);

							if( !isset($r['label']) || strlen($r['label']) == 0 )
								continue;

							$content['subject'] = 'Myfox Alarm Event';
							$content['alarm_name'] = $result['siteLabel'];
							$content['alarm_event'] = $r['label'];
							$content['timestamp'] = strtotime($r['createdAt']);
							$content['date'] = date('Y-m-d H:i:s', strtotime($r['createdAt']));
							
							$message->content($content);
							$messagelist[] = $message;
							
							$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['createdAt'])."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
							$mysql->update($sql);
						}
					}
				}
			}
			
			return $messagelist;
		}
	}
	
	public function consume($message, $in)
	{
		if( $in->key == 'trigger' )
			return;
		else
			$this->producing = false;
		
		switch( $in->key )
		{
			case 'protectiondisable':
				$status = array('status' => 'disarmed');
			break;
			case 'protectionfull':
				$status = array('status' => 'armed');
			break;
			case 'protectionpartial':
				$status = array('status' => 'partial');
			break;		
		}

		com\busit\HTTP::send('https://apps.busit.com/myfox/security?type=security_change', array('message'=>json_encode($status),'config'=>json_encode($this->config())));
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