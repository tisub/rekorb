<?php
namespace com\anotherservice\io;
use com\anotherservice\util as cau;

class RestApi
{
	private static $auth = "";
	private static $url = "";
	
	public static function initialize($url, $auth)
	{
		self::$url = $url;
		self::$auth = $auth;
	}
	
	public static function request($action, $params = null)
	{
		if( $params == null )
			$params = array();
		if( !array_key_exists("f", $params) )
			$params["f"] = "json";
		if( !array_key_exists("auth", $params) && self::$auth != null && strlen(self::$auth) > 0 )
			$params["auth"] = self::$auth;
		if( $action == null )
			$action = "";
		
		return self::send($action, $params);

		return response;
	}
	
	private static function send($action='', $params=array(), $file=false)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (Olympe) API/1.0', 'method' => 'POST', 'timeout' => 30.0 ));

		if( is_array($file) )
			$request['http']['header'] = 'Content-Type: multipart/form-data, boundary=' . $boundary . "\r\n";
		else
			$request['http']['header'] = 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
			
		if( is_array($file) )
			$request['http']['content'] = self::buildMultipartQuery($params, $file, $boundary);
		else
			$request['http']['content'] = http_build_query($params);
		
		$request['http']['header']  .= 'Content-Length: ' . mb_strlen($request['http']['content'], '8bit') . "\r\n";

		if( preg_match("/^https?:\\/\\/.*/", $action) || self::$url == null )
			$url = $action;
		else
			$url = self::$url . $action;

		$fh = fopen($url, 'r', false, stream_context_create( $request ));

		if( $fh === false )
			throw new \Exception("Internal communication error");

		$raw = stream_get_contents($fh);
		fclose($fh);
		$response = json_decode($raw, true);
		if( isset($response['error']) )
			throw new \Exception("Api error : " . $response['error']['message']);
		if( !isset($response['response']) )
			return $raw;

		return $response['response'];
	}
	
	private static function buildMultipartQuery($params, $file, $boundary)
	{
		$content = "";
		
		foreach( $params as $key => $value )
		{
			$content .= "\r\n--{$boundary}\r\n" .
						"Content-Disposition: form-data; name=\"{$key}\"\r\n" . 
						"\r\n" . 
						$value;
		}
		
		$content .= "\r\n--{$boundary}\r\n" .
					"Content-Disposition: form-data; name=\"upload\"; filename=\"{$file['name']}\"\r\n" .
					"Content-Type: {$file['type']}\r\n" . 
					"\r\n" .
					$file['binary'];
					
		$content .= "\r\n--{$boundary}--";

		return $content;
	}
}

?>