<?php
define("__CLASSNAME__", "\\TextToSpeech");

use com\busit as cb;
use com\anotherservice\util as cau;

class TextToSpeech extends cb\Connector implements cb\Transformer
{
	public function transform($message, $in, $out)
	{
		$key = '09184b9cfea64388b3ba713cf2220d86';
		if( $this->config('key') != null && strlen($this->config('key')) > 0 )
			$key = $this->config('key');
		
		$mp3 = $this->send(
				"https://voicerss-text-to-speech.p.mashape.com/?key={$key}", 
				array('hl'=>'fr-fr', 'src'=>$message->content()->toText(), 'c'=>'MP3', 'f'=>'48khz_16bit_stereo', 'r'=>'0'),
				array('X-Mashape-Key'=>'S8qwuUUX71mshdmRJn303zWE5fp5p1NWjzCjsncMej60RmrTRt')
				);
		$message->file("text.mp3", $mp3);
		return $message;
	}
	
	public function test()
	{
		try
		{
			$this->send(
				"https://voicerss-text-to-speech.p.mashape.com/?key={$key}", 
				array('hl'=>'fr-fr', 'src'=>'bonjour', 'c'=>'MP3', 'f'=>'48khz_16bit_stereo', 'r'=>'0'),
				array('X-Mashape-Key'=>'S8qwuUUX71mshdmRJn303zWE5fp5p1NWjzCjsncMej60RmrTRt')
				);
			return true;
		}
		catch(\Exception $e)
		{
			return false;
		}
	}
	
	private function send($url, $params=array(), $headers=array(), $method='POST')
	{
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (SYS) API/1.0', 'method' => $method, 'timeout' => 10.0, 'header' => '' ));
		$request['http']['content'] = http_build_query($params);
		$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
		$request['http']['header'] .= 'Content-Length: ' . mb_strlen($request['http']['content'], '8bit') . "\r\n";
		foreach( $headers as $h=>$v )
			$request['http']['header'] .= $h . ': ' . $v . "\r\n";
		
		$fh = fopen($url, 'r', false, stream_context_create( $request ));
				
		if( $fh === false )
			throw new \Exception("Internal communication error");

		$response = stream_get_contents($fh);
		fclose($fh);
		return $response;
	}
	
	/*
	curl -X POST --include "https://t2s.p.mashape.com/speech/" \
  -H "X-Mashape-Key: S8qwuUUX71mshdmRJn303zWE5fp5p1NWjzCjsncMej60RmrTRt" \
  -d "lang=en" \
  -d "text=Hello world"
  
  curl --get --include "https://montanaflynn-text-to-speech.p.mashape.com/speak?text=Talk+to+me!" \
  -H "X-Mashape-Key: S8qwuUUX71mshdmRJn303zWE5fp5p1NWjzCjsncMej60RmrTRt"
  
	curl --get --include "https://tts.p.mashape.com/v1/tts?key=undefined&format=mp3&language=The+language+you+wish+to+use+(default+is+usenglish)&text=example&voice=The+name+of+the+voice+you+wish+to+use." \
  -H "X-Mashape-Key: S8qwuUUX71mshdmRJn303zWE5fp5p1NWjzCjsncMej60RmrTRt"
	*/
}
?>