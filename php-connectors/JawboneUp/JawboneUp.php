<?php

define("__CLASSNAME__", "\\JawboneUp");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class JawboneUp implements cb\IConnector
{
	private $url;
	private $config;
	private $inputs;
	private $outputs;
	private $uid;
	private $cronInterface;
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
		
		$this->url = "https://apps.busit.com/up/pull?type={$this->outputs[$interfaceId]['key']}";
		
		$result = $this->send(array('config' => json_encode($this->config)));
		$result = json_decode($result, true);

		if( count($result) > 0 )
		{
			$result = array_reverse($result);
			foreach( $result as $key => $value )
			{
				$message = null;
				$keyword = null;
				$number = null;
				
				switch( $this->outputs[$interfaceId]['key'] )
				{
					case 'distance':
						$data = $value['distance'];
						$keyword = $key;
						$format = "Just did [@distance] meters";
					break;
					case 'wakeup':
						if( $value['time_completed'] <= time() ) 
						{
							$data = 'UP';
							$format = "Just wake up!";
							$keyword = $value['xid'];
						}
					break;
					case 'mood':
						$format = "You feel [ ";
						$data = $result['title'];
						$keyword = $result['time_updated'];
					break;
					case 'calories':
						$format = "Just spent [@calories] calories";
						$data = $value['calories'];
						$keyword = $key;
					break;
					case 'sleep':
						$format = "Slept during [@duration]";
						$data = $value['details']['duration'];
						$keyword = $value['xid'];
					break;
					case 'meal':
						$format = "Just eat [@meal]";
						$data = $value['title'];
						$keyword = $value['xid'];
					break;
					case 'mealcal':
						$format = "Just eat [@calories] calories";
						$data = $value['calories'];
						$keyword = $value['xid'];
					break;
				}
				
				if( $data != null && $keyword != null )
				{
					if( get_magic_quotes_gpc() )
						$msg = stripslashes($data);
					else
						$msg = addslashes($data);
							
					$sql = "SELECT * FROM up WHERE up_identifier = '{$this->uid}' AND up_type = '{$this->outputs[$interfaceId]['key']}' AND up_key = '{$keyword}' AND up_value = '{$msg}'";
					$row = $mysql->selectOne($sql);
					
					if( !$row['up_value'] )
					{
						$sql = "INSERT INTO up (up_identifier, up_date, up_type, up_key, up_value) VALUES ('{$this->uid}', UNIX_TIMESTAMP(), '{$this->outputs[$interfaceId]['key']}', '{$keyword}', '{$msg}')";
						$mysql->insert($sql);
						
						$type = new cb\KnownType();
						$type->wkid(18);
						$type->name('Activity');
						$type->format($format);
						$compat = array(18);
						if( is_numeric($data) )
						{
							$type['number'] = $data;
							$compat[] = 2;
						}
						$type->compatibility($compat);
						
						if( $value['distance'] )
							$type['distance'] = $value['distance'];
						if( $value['calories'] )
							$type['calories'] = $value['calories'];
						if( $value['steps'] )
							$type['steps'] = $value['steps'];
						if( $value['duration'] )
							$type['duration'] = $value['active_time'];
						
						$type['timestamp'] = strtotime(time());
						$type['date'] =  date('Y-m-d H:i:s', strtotime(time()));
						
						$this->message->setKnownType($type);
						$this->messagelist[] = $this->message->duplicate();
						$this->status = true;
						
						$delete = time()-(3600*24*1);
						$sql = "DELETE FROM up WHERE up_identifier = '{$this->uid}' AND up_date < {$delete}";
						$mysql->delete($sql);
					}
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
	
	public function send($params = array(), $file = null)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
 
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (Bus IT) API/1.0', 'method' => 'POST' ));
 
		if( $file !== null )
			$request['http']['content'] = self::buildMultipartQuery($params, $file);
		else
			$request['http']['content'] = http_build_query($params);
		
		$request['http']['header']  = 'Content-Length: ' . strlen($request['http']['content']) . "\r\n";
		
		if( $file !== null )
			$request['http']['header'] .= 'Content-Type: multipart/form-data, boundary=' . $boundary . "\r\n";
		else
			$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
 
		try
		{
			$fh = fopen($this->url, 'r', false, stream_context_create( $request ));
			if( $fh === false )
				throw new Exception("Internal communication error :: 500 :: The upstream API did not respond to");
 
			$response = stream_get_contents($fh);
			
			fclose($fh);
			
			return $response;
		}
		catch(Exception $e)
		{
			// get the E_WARNING from the fopen
			throw new Exception("Internal communication error :: 500 :: Upstream API failure :: ". $e->getMessage());
		}
	}
	
	public function buildMultipartQuery($params, $file)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
		$content = '--' . $boundary . "\n";
		
		foreach( $params as $key => $value )
			$content .= 'content-disposition: form-data; name="' . $key . '"' . "\n\n" . $value . "\n" . '--' . $boundary . "\n";
		
		$content .= 'content-disposition: form-data; name="file"; filename="' . $file['name'] . '"' . "\n";
		$content .= 'Content-Type: ' . $file['mime'] . "\n";
		$content .= 'Content-Transfer-Encoding: binary' . "\n\n";
		$content .= $file['binary'];
		$content .= "\n" . '--' . $boundary . "\n";
 
		return $content;
	}
}

?>