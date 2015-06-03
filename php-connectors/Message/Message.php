<?php

define("__CLASSNAME__", "\\Message");

use com\anotherservice\util as cau;

class Message extends com\busit\Connector implements com\busit\Transformer
{
	public function transform($message, $in, $out)
	{
		$content = $message->content();
		if( isset($content['message']) && strlen($content['message']) > 0 )
			$original = $content['message'];	
		else
			$original = $content->toText();
		
		$content['message'] = $out->value;
		$content['original'] = $original;
		$message->content($content);
		
		return $message;
	}
	
	public function test()
	{
		return true;
	}
}

?>