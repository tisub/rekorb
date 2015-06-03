<?php

define("__CLASSNAME__", "\\Bitly");

use com\busit as cb;
use com\anotherservice\util as cau;

class Bitly extends com\busit\Connector implements com\busit\Transformer
{
	private $url = 'http://api.bitly.com';
	private $key = 'R_ab7421d24ce0496ab2800add78097d08';
	
	public function transform($message, $in, $out)
	{
		$content = $message->content();
		
		if( isset($content['message']) && strlen($content['message']) > 0 )
			$content['message'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['message']);
		if( isset($content['description']) && strlen($content['description']) > 0 )
			$content['description'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['description']);
		if( isset($content['bodyHtml']) && strlen($content['bodyHtml']) > 0 )
			$content['bodyHtml'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['bodyHtml']);
		if( isset($content['bodyText']) && strlen($content['bodyText']) > 0 )
			$content['bodyText'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['bodyText']);
		if( isset($content['link']) && strlen($content['link']) > 0 )
			$content['link'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['link']);
		if( isset($content['url']) && strlen($content['url']) > 0 )
			$content['url'] = preg_replace_callback("#http://[^\s\"'<>]*#i", array($this, 'shortUrl'), $content['url']);
		
		$message->content($content);
		
		return $message;
	}
	
	public function shortUrl($url)
	{
		$handle = @fopen("http://api.bit.ly/v3/shorten?login=olympe&apiKey={$this->key}&longUrl=".urlencode($url[0])."&format=json", 'rb');
		$result = @json_decode(@stream_get_contents($handle), true);
		$short_url = $result['data']['url'];

		return $short_url;
	}
}

?>