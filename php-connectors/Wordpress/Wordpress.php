<?php

define("__CLASSNAME__", "\\Wordpress");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Wordpress implements cb\IConnector
{
	private $url = 'https://apps.busit.com/wordpress/send';
	private $config;
	private $inputs;
	private $outputs;
	private $status = false;
	private $uid;
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
		
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	
		$this->url = 'https://apps.busit.com/wordpress/pull';
		
		$result = file_get_contents($this->url . '?config=' . urlencode(json_encode($this->config)));
		$result = json_decode($result, true);
		
		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('{$this->uid}', '".time()."')";
			$mysql->insert($sql);
		}
		// end
		
		$result = array_reverse($result);
		if( count($result) > 0 )
		{
			foreach( $result as $r )
			{
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}' AND buffer_time >= '".strtotime($r['date'])."'";
				$row = $mysql->selectOne($sql);
			
				if( !$row['buffer_identifier'] )
				{
					$type = new cb\KnownType();
					$type->wkid(8);
					$type->name('Article');
					$type->format('[@title] - [@link]');
					$type->compatibility(array(8));
					
					$type['title'] = $r['title'];
					$type['content'] = $r['content'];
					$type['link'] = $r['url'];
					$type['timestamp'] = strtotime($r['date']);
					$type['date'] =  date('Y-m-d H:i:s', strtotime($r['date']));
					$type['description'] = $r['description'];
					
					$this->message->setKnownType($type);
					$this->messagelist[] = $this->message->duplicate();
					$this->status = true;
					
					$sql = "UPDATE buffer SET buffer_time = '".strtotime($r['date'])."' WHERE buffer_identifier = '{$this->uid}'";
					$mysql->update($sql);
				}
			}
		}
	}
	
	public function setInput($message, $interfaceId)
	{
		$names = $message->getAttachmentNames();
		
		if( count($names) > 0 )
		{
			$n = $names[0];			
		
			$file['name'] = basename(str_replace("\\", "/", $n));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $message->getAttachment($n);			
		}
		
		$msg = $message->getKnownType();
		if( $msg === null )
			$msg = $message->getContentUTF8();
		else
		{
			if( $msg->isCompatibleWith(8) )
				$msg = array('title'=>$msg['title'], 'content'=>$msg['content'], 'description'=>$msg['description']);
			else
				$msg = array('title'=>'Post from Bus IT', 'content'=>$msg->toString());
		}

		$this->send(array('message'=>json_encode($msg),'config'=>json_encode($this->config)), $file);
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