<?php
namespace com\busit\local;

use com\anotherservice\util as cau;
use com\busit as cb;

class LocalMessage implements cb\IMessage
{
	//=====================================
	// IMessage
	//=====================================
	
	private $_files = array();
	
	public function file($name, $data=null)
	{
		if( $data != null )
		{
			if( $name != null && strlen($name) > 0 && $data != null )
				$this->_files[$name] = $data;
		}
		else
		{
			if( $name == null || strlen($name) == 0 || !isset($this->_files[$name]) )
				return null;
			
			return $this->_files[$name];
		}
	}
	
	public function files()
	{
		return $this->_files;
	}
	
	private $_content = null;
	
	public function content($data=null)
	{
		if( $data != null )
		{
			$this->_content = $data;
		}
		else
		{
			if( $this->_content == null )
				$this->_content = new Content(null);
			return $this->_content;
		}
	}
	
	public function clear()
	{
		$this->_content = null;
		$this->_files = array();
	}
	
	public function copy()
	{
		$m = new LocalMessage($this->from(), $this->to());
		$m->content($this->content());
		foreach( $this->files() as $name=>$data )
			$m->file($name, $data);
		return $m;
	}
	
	private $_from = null;
	
	public function from()
	{
		return $this->_from;
	}
	
	private $_to = null;
	
	public function to()
	{
		return $this->_to;
	}
	
	//=====================================
	// CONSTRUCTOR
	//=====================================
	
	public function __construct($from, $to)
	{
		if( $to != null )
		{
			$this->_from = $from;
			$this->_to = $to;
			return;
		}
		
		throw new \Exception("failed to construct message");
	}
}

?>