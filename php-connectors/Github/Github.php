define("__CLASSNAME__", "\\Github");

use com\busit as cb;
use com\anotherservice\util as cau;

class Github implements cb\IConnector
{
	private $url = 'https://apps.busit.com/facebook/send';
	private $config;
	private $inputs;
	private $outputs;
	
	public function init($config, $inputs, $outputs)
	{
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
	}
	
	public function setInput($message, $interfaceId)
	{
		$names = $message->getAttachmentNames();
		
		if( count($names) > 0 )
		{
			$n = $names[0];			
		
			$file['name'] = basename(str_replace("\\", "/", $n));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $message->getAttachment($n);			
		}

		$this->send(array('message'=>json_encode($message->getContentUTF8()),'config'=>json_encode($this->config)), $file);
				
		cau\Logger::info("Message posted to Facebook: " . $message->getContentUTF8());
	}
	
	public function getOutput($interfaceId)
	{
		
	}
	
	public function send($params = array(), $file = null)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
 
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (Bus IT) API/1.0', 'method' => 'POST' ));
 
		if( $file !== null )
			$request['http']['content'] = self::buildMultipartQuery($params, $file);
		else
			$request['http']['content'] = http_build_query($params);
		
		$request['http']['header']  = 'Content-Length: ' . strlen($request['http']['content']) . "\r\n";
		
		if( $file !== null )
			$request['http']['header'] .= 'Content-Type: multipart/form-data, boundary=' . $boundary . "\r\n";
		else
			$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
 
		try
		{
			$fh = fopen($this->url, 'r', false, stream_context_create( $request ));
			if( $fh === false )
				throw new Exception("Internal communication error :: 500 :: The upstream API did not respond to");
 
			$response = stream_get_contents($fh);
			fclose($fh);
		}
		catch(Exception $e)
		{
			// get the E_WARNING from the fopen
			throw new Exception("Internal communication error :: 500 :: Upstream API failure :: ". $e->getMessage());
		}
	}
	
	public function buildMultipartQuery($params, $file)
	{
		$boundary = "trlalaaaaaaaaaaaaaaaaalalalaalalaaaaaaaaaaa";
		$content = '--' . $boundary . "\n";
		
		foreach( $params as $key => $value )
			$content .= 'content-disposition: form-data; name="' . $key . '"' . "\n\n" . $value . "\n" . '--' . $boundary . "\n";
		
		$content .= 'content-disposition: form-data; name="file"; filename="' . $file['name'] . '"' . "\n";
		$content .= 'Content-Type: ' . $file['mime'] . "\n";
		$content .= 'Content-Transfer-Encoding: binary' . "\n\n";
		$content .= $file['binary'];
		$content .= "\n" . '--' . $boundary . "\n";
 
		return $content;
	}
}