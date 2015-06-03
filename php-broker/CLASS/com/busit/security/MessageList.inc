<?php
namespace com\busit\security;
use com\busit as cb;

class MessageList extends \ArrayObject implements cb\IMessage
{
	public function file($name, $data=null)	{ throw new \Exception("Method not available on a Message List"); }
	public function files() 			{ throw new \Exception("Method not available on a Message List"); }
	public function content($data=null)	{ throw new \Exception("Method not available on a Message List"); }
	public function from() 				{ throw new \Exception("Method not available on a Message List"); }
	public function to() 				{ throw new \Exception("Method not available on a Message List"); }
	public function copy() 				{ $ml = new MessageList(); foreach( $this as $m ) $ml[] = $m; return $ml; }
	public function clear()				{ while(count($this) > 0) array_pop($this); }
}

?>