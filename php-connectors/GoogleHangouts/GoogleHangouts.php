<?php

define("__CLASSNAME__", "\\GoogleHangouts");

use com\busit as cb;
use com\anotherservice\util as cau;

class GoogleHangouts extends com\busit\Connector implements com\busit\Consumer
{	
	public function consume($message, $in)
	{
		if( $in->value != null )
			$to = $in->value;
		else
			$to = $this->config('email');
		
		$config = array('hostname' => 'talk.google.com', 'username' => 'xxx', 'password' => 'xxx', 'domain' => 'gmail.com', 'to' => $to);
		
		$content = $message->content();
		
		if( isset($content['message']) && strlen($content['message']) > 0 )
			$data = $content['message'];
		else
			$data = $content->toText();
		
		com\busit\HTTP::send('https://apps.busit.com/xmpp/send', array('message'=>json_encode($data),'config'=>json_encode($config)), $file);
	}
	
	public function test()
	{
		return true;
	}
}

?>