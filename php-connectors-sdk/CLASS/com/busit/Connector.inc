<?php
namespace com\busit;

use com\anotherservice\util as cau;

abstract class Connector implements IConnector, ConnectorHelper
{
	// ===========================================
	// LOCAL
	// ===========================================
	private $conf = array();
	private $in = array();
	private $out = array();
	private $msg = null;
	private $_internal_push_in = null;
	
	public function push($message, $in)
	{
		$this->currentInterface = $in;
		if( $this instanceof Consumer )
			$this->consume($message, $in);
		if( $this instanceof Transformer )
			$this->_internal_push_in = $in;
		if( !($this instanceof Consumer) && !($this instanceof Transformer) )
			throw new \Exception("Push is not supported");
	}
	
	public function pull($out)
	{
		$this->currentInterface = $out;
		if( $this instanceof Producer )
		{
			$result = $this->produce($out);
			if( $result != null )
				return $result;
		}
		if( $this instanceof Transformer && $this->_internal_push_in != null )
			return $this->transform($this->msg->copy(), $this->_internal_push_in, $out);
		else if( $this instanceof Producer )
			return null; // the producer did return null before but we had to check for Transformer first
		
		// not a Producer and not a Transformer
		throw new \Exception("Pull is not supported");
	}
	
	public abstract function test();
	
	// ===========================================
	// CONNECTOR HELPER
	// ===========================================
	public function input($key=null)
	{
		if( $key == null || strlen(key) == 0 )
		{
			$list = array();
			foreach( $this->in as $name=>$i )
				$list[] = new ConnectorInterface($name, $i);
			return $list;
		}
		else
		{
			foreach( $this->in as $name=>$i )
				if( $key == $i["key"] )
					return new ConnectorInterface($name, $i);
			return null;
		}
	}
	
	public function output($key=null)
	{
		if( $key == null || strlen(key) == 0 )
		{
			$list = array();
			foreach( $this->out as $name=>$o )
				$list[] = new ConnectorInterface($name, $o);
			return $list;
		}
		else
		{
			foreach( $this->out as $name=>$o )
				if( $key == $o["key"] )
					return new ConnectorInterface($name, $o);
			return null;
		}
	}
	
	public function config($key=null)
	{
		if( $key == null || strlen($key == null) )
			return $this->conf;
		else
			return $this->conf[$key];
	}
	
	private $currentInterface = null;
	public function notifyUser($message)
	{
		cau\Logger::info(json_encode(array(
			"notifyUser" => array(
				"interface" => ($this->currentInterface != null ? $this->currentInterface->name : ''),
				"message" => $message)
			))
		);
	}
	
	public function notifyOwner($message, $data)
	{
		cau\Logger::info(json_encode(array(
			"notifyOwner" => array(
				"interface" => ($this->currentInterface != null ? $this->currentInterface->key : ''),
				"message" => $message,
				"data" => $data)
			))
		);
	}
	
	public function locale()
	{
		return $this->config("__locale");
	}
	
	public function id()
	{
		return $this->config("__uid");
	}
	
	// ===========================================
	// ICONNECTOR
	// ===========================================
	public function init($config, $inputs, $outputs)
	{
		$this->conf = $config;
		$this->in = $inputs;
		$this->out = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
		$this->msg = $message;
		if( isset($this->in[$interfaceId]) ) 
			$this->push($this->msg, new ConnectorInterface($interfaceId, $this->in[$interfaceId]));
		else if( !isset($this->out[$interfaceId]) ) 
			throw new \Exception("Invalid cron interface " . $interfaceId);
		
		// in case cron output, do nothing... just do it in pull()
	}
	
	public function setInput($message, $interfaceId)
	{
		if( !isset($this->in[$interfaceId]) ) throw new \Exception("Invalid input interface " . $interfaceId);
		$this->msg = $message;
		$this->push($this->msg, new ConnectorInterface($interfaceId, $this->in[$interfaceId]));
	}
	
	public function getOutput($interfaceId)
	{
		if( !isset($this->out[$interfaceId]) ) throw new \Exception("Invalid output interface " . $interfaceId);
		return $this->pull(new ConnectorInterface($interfaceId, $this->out[$interfaceId]));
	}
	
	public function getSampleData($message, $interfaceId)
	{
		$this->currentInterface = new ConnectorInterface($interfaceId, $this->out[$interfaceId]);
		if( $this instanceof Producer )
			return $this->sample($this->currentInterface);
		else
			return null;
	}
	
	public function isFunctional($config, $inputs, $outputs)
	{
		$this->conf = $config;
		$this->in = $inputs;
		$this->out = $outputs;
		
		return test();
	}
}

?>