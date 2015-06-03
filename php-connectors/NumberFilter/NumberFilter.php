<?php

define("__CLASSNAME__", "\\NumberFilter");

use com\busit as cb;
use com\anotherservice\util as cau;

class NumberFilter extends com\busit\Connector implements com\busit\Transformer
{
	public function transform($message, $in, $out)
	{
		$content = $message->content();
		
		if( !isset($content['number']) || strlen($content['number']) == 0 )
		{
			$this->notifyUser('The provided value or message does not contain any number');
			return null;
		}
		
		// Store the current number in Memcache and get the previous number
		$m = new Memcache();
		$m->connect('bi-001.vlan-101', 11211);
		$previous = $m->get($this->id());
		$m->set($this->id(), $content['number'], 0, 360000); // 100 hours validity
		$m->close();
		
		switch( $out->key )
		{
			case 'equals':
				if( $content['number'] == $out->value )
					return $message;
			break;
			case 'greater':
				if( $content['number'] > $out->value )
					return $message;
			break;
			case 'smaller':
				if( $content['number'] < $out->value )
					return $message;
			break;
			case 'between':
				$parts = preg_split("/[\s;:\\/]/i", $out->value);
				if( $content['number'] > $parts[0] && $content['number'] < $parts[1] )
					return $message;
			break;
			case 'more':
				if( $previous < $out->value && $content['number'] > $out->value )
					return $message;
			break;
			case 'less':
				if( $previous > $out->value && $content['number'] < $out->value )
					return $message;
			break;
		}
		
		return null;
	}
	
	public function test()
	{
		return true;
	}
}

?>