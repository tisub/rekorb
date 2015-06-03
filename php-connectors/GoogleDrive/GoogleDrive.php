<?php

define("__CLASSNAME__", "\\GoogleDrive");

use com\busit as cb;
use com\anotherservice\util as cau;

class GoogleDrive extends com\busit\Connector implements com\busit\Consumer
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
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			
			com\busit\HTTP::send('https://apps.busit.com/google/drive/send', array('message'=>json_encode($infos),'config'=>json_encode($this->config())), $file);
		}
	}
	
	public function test()
	{
		return true;
	}
}

?>