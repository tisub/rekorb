<?php
namespace com\busit;

class HTTP
{
	public static function send($url, $params = array(), $file = null, $headers = array(), $method='POST')
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
 
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.5 (Busit) Connectors/1.0', 'method' => $method ));
 
		if( isset($file['name']) )
			$file = array($file);
		
		if( $file !== null )
			$request['http']['content'] = self::buildMultipartQuery($params, $file);
		else
			$request['http']['content'] = http_build_query($params);
		
		$request['http']['header']  = 'Content-Length: ' . mb_strlen($request['http']['content'], '8bit') . "\r\n";
		
		foreach( $headers as $h => $v )
			$request['http']['header'] .= $h . ': ' . $v . "\r\n";
		
		if( $file !== null )
			$request['http']['header'] .= 'Content-Type: multipart/form-data, boundary=' . $boundary . "\r\n";
		else
			$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
 
		try
		{
			$fh = fopen($url, 'r', false, stream_context_create( $request ));
			if( $fh === false )
				throw new Exception("Internal communication error :: 500 :: The upstream API did not respond to");
 
			$response = stream_get_contents($fh);
			
			fclose($fh);
			
			return $response;
		}
		catch(Exception $e)
		{
			throw new Exception("Internal communication error :: 500 :: Upstream API failure :: ". $e->getMessage());
		}
	}
	
	public static function buildMultipartQuery($params, $file)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
		$content = '--' . $boundary . "\n";
		
		foreach( $params as $key => $value )
			$content .= 'content-disposition: form-data; name="' . $key . '"' . "\n\n" . $value . "\n" . '--' . $boundary . "\n";
		
		$i = 0;
		foreach( $file as $f )
		{
			if( $i == 0 )
				$name = 'file';
			else
				$name = 'file' . $i;
			
			$content .= 'content-disposition: form-data; name="'.$name.'"; filename="' . $f['name'] . '"' . "\n";
			$content .= 'Content-Type: ' . $f['mime'] . "\n";
			$content .= 'Content-Transfer-Encoding: binary' . "\n\n";
			$content .= $f['binary'];
			$content .= "\n" . '--' . $boundary . "\n";
			$i++;
		}
		
		return $content;
	}
}

?>