<?php

define("__CLASSNAME__", "\\MyfoxShutter");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class MyfoxShutter implements cb\IConnector
{
	private $url = 'https://apps.busit.com/myfox/shutter?type=shutter_change';
	private $config;
	private $inputs;
	private $outputs;
	private $status = false;
	private $messagelist;
	private $message;
	private $uid;
	
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
		
		$this->url = 'https://apps.busit.com/myfox/shutter?type=shutter_status';
		$result = file_get_contents($this->url . '&config=' . urlencode(json_encode($this->config)));
		$result = json_decode($result, true);
		
		$type = new cb\KnownType();
		$type->wkid(23);
		$type->name('ShutterStatus');
		$type->compatibility(array(23));
		$type->format('[@message]');
		$type['message'] = $r['label'];
		$type['timestamp'] = strtotime($r['createdAt']);
		$type['date'] = date('Y-m-d H:i:s', strtotime($r['createdAt']));
		$type['socket_status'] = $result['stateLabel'];
		
		$this->message->setKnownType($type);
		$this->status = true;
	}
	
	public function setInput($message, $interfaceId)
	{
		$status = false;
		$msg = $message->getKnownType();
		if( $msg === null )
			$msg = $message->getContentUTF8();
		else
		{
			if( $msg['electric_status'] )
				$status = array('status' => $msg['electric_status']);
			else
			{
				$msg = $msg->toString();
				
				if( strtolower($msg) == 'on' )
					$status = array('status' => 'on');
				else if( strtolower($msg) == 'off' )
					$status = array('status' => 'off');
			}
		}

		if( !$status )
		{	
			switch( $this->inputs[$interfaceId]['key'] )
			{
				case 'on':
					$status = array('status' => 'on');
				break;
				case 'off':
					$status = array('status' => 'off');
				break;			
			}
		}
		
		$this->send(array('message'=>json_encode($status),'config'=>json_encode($this->config)));
	}
	
	public function getOutput($interfaceId)
	{
		if( $this->status == true )
			return $this->message;
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