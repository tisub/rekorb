<?php

define("__CLASSNAME__", "\\ImageShack");

use com\busit as cb;
use com\anotherservice\util as cau;

class ImageShack extends com\busit\Connector implements com\busit\Transformer
{
	public function transform($message, $in, $out)
	{
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			break;
		}

		$result = com\busit\HTTP::send('http://www.imageshack.us/upload_api.php', array('key'=>'0345BOPX05cb1505a1b43a9af43172c3ce806437'), $file);
		
		$xml = simplexml_load_string($result);
		$link = $xml->links->image_link;
		
		$content = $message->content();
		$content['imageshack_link'] = $link;
		$content->textFormat($content->textFormat() . "
		{{imageshack_link}}
		");
		$content->htmlFormat($content->htmlFormat() . "<br />
		{{imageshack_link}}
		");
		
		$message->content($content);
		
		return $message;
	}
}

?>