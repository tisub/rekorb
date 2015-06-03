<?php

define("__CLASSNAME__", "\\GTranslate");

use com\busit as cb;
use com\anotherservice\util as cau;

class GTranslate extends com\busit\Connector implements com\busit\Transformer
{
	public function transform($message, $in, $out)
	{
		switch( $out->key )
		{
			case 'transmiten':
				$language = 'EN';
			break;
			case 'transmitfr':
				$language = 'FR';
			break;
			case 'transmites':
				$language = 'ES';
			break;
			case 'transmitde':
				$language = 'DE';
			break;
			default:
				$language = 'EN';
		}
		
		$content = $message->content();
		
		if( $content->compatible(1) )
		{
			$content['message'] = $this->translate($content['message'], $language);
		}
		else if( $content->compatible(2) )
		{
			$content['subject'] = $this->translate($content['subject'], $language);
			$content['body'] = $this->translate($content['body'], $language);
		}	
		else if( $content->compatible(3) )
		{
			$content['status'] = $this->translate($content['status'], $language);
		}
		else if( $content->compatible(5) )
		{
			$content['title'] = $this->translate($content['title'], $language);
			$content['subject'] = $content['title'];
			$content['description'] = $this->translate($content['description'], $language);
		}
		else
		{
			$text = $this->translate($content->toText(), $language);		
			$content = com\busit\Factory::content(1);
			$content['message'] = $text;
			$content['timestamp'] = time();
			$content['date'] = date('Y-m-d H:i:s', $content['timestamp']);
		}
		
		$message->content($content);
		
		return $message;
	}

	public function test()
	{
		return true;
	}
	
	private function translate($text, $language)
	{
		$text = strip_tags($text);
		
		try
		{
			$detect = file_get_contents("https://www.googleapis.com/language/translate/v2/detect?key=".$this->config('apikey')."&q=".urlencode($text));
			$detect = json_decode($detect, true);
			$original_lang = $detect['data']['detections'][0][0]['language'];
			
			if( strtoupper($original_lang) == strtoupper($language) )
				return $text;
			
			$result = file_get_contents("https://www.googleapis.com/language/translate/v2?key=".$this->config('apikey')."&q=".urlencode($text)."&source={$original_lang}&target={$language}");
			$result = json_decode($result, true);
		}
		catch( \Exception $e )
		{
			$this->notifyUser('Unable to translate the message, the text is not clean');
			throw new \Exception("Unable to translate the message, the text is not clean\n" . $e);
		}
		
		return str_replace("&#39;", "'", $result['data']['translations'][0]['translatedText']);
	}
	
}

?>