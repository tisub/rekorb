<?php
namespace com\busit\broker;

use com\anotherservice\util as cau;
use com\busit\security as cbs;
use com\busit as cb;

class UncheckedMessage implements cb\IMessage
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
				$this->_content = new cbs\Content(null);
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
		$m = new UncheckedMessage($this->from(), $this->to());
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
	
	public function __construct($from, $to=null)
	{
		if( $to != null )
		{
			$this->_from = $from;
			$this->_to = $to;
			return;
		}
		else if( $from instanceof cb\IMessage )
		{
			$this->content(new cbs\Content($from->content()->toJson()));
			foreach( $from->files() as $name=>$data )
				$this->file($name, $data);
			$this->_from = $from->from();
			$this->_to = $from->to();
			return;
		}
		
		if( is_string($from) )
			$from = json_decode($from, true);
		if( is_array($from) )
		{
			$this->content(new cbs\Content($from["content"]));
			foreach( $from["files"] as $name=>$b64data )
				$this->file($name, base64_decode($b64data));
			$this->_from = $from["from"];
			$this->_to = $from["to"];
			return;
		}
		
		throw new \Exception("failed to construct message");
	}
	
	//=====================================
	// BACK TO MESSAGE
	//=====================================
	
	public function toString()
	{
		$a = array();
		$a["from"] =  $this->from();
		$a["to"] = $this->to();
		$a["content"] = $this->content()->toJson();
		$a["files"] = array();
		foreach( $this->files() as $name=>$data )
			$a["files"][$name] = base64_encode($data);

		return json_encode($a);
	}
	
	public function securize($from, $to)
	{
		throw new \Exception("cannot securize PHP message");
	}
}

?>