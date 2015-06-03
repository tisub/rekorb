<?php

define("__CLASSNAME__", "\\Box");

use com\busit as cb;
use com\anotherservice\util as cau;

class Box extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{		
		if( $in->value == null )
			$directory = 'busit';
		else
			$directory = $in->value;
		$infos = array('directory' => $directory);

		$content = $message->content();
		$data = $content->toText();

		$message->file('content_' . date('YmdHis') . uniqid('_') . '.txt', $data);
		
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $n));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $message->getAttachment($n);
			
			com\busit\HTTP::send('https://apps.busit.com/box/send', array('message'=>json_encode($infos),'config'=>json_encode($this->config())), $file);
		}
	}

	public function test()
	{
		return true;
	}
}

?>