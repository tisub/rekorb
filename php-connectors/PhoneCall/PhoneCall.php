<?php

define("__CLASSNAME__", "\\PhoneCall");

use com\busit as cb;
use com\anotherservice\util as cau;

class PhoneCall extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			break;
		}

		$content = $message->content();
		$text = $content->toText();
		
		$config = $this->config();
		if( strlen($in->value) > 0 )
			$config['phone'] = $in->value;
		
		com\busit\HTTP::send('https://apps.busit.com/twilio/call', array('secret'=>'ACbc46d7187ed5b2ea5dfc046bb6391655','message'=>json_encode($text),'config'=>json_encode($config)), $file);
	}
	
	public function test()
	{
		return true;
	}
}

?>