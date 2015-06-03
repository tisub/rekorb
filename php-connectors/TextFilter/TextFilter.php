<?php

define("__CLASSNAME__", "\\TextFilter");

use com\busit as cb;
use com\anotherservice\util as cau;

class TextFilter extends com\busit\Connector implements com\busit\Transformer
{
	public function transform($message, $in, $out)
	{
		$content = $message->content();
		$text = $content->toText();
		
		if( stripos($text, $out->value) !== false )
			return $message;
		else
			return null;
	}
	
	public function test()
	{
		return true;
	}
}

?>