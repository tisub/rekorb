<?php

define("__CLASSNAME__", "\\WebMonitor");

use com\busit as cb;
use com\anotherservice\util as cau;

class WebMonitor extends com\busit\Connector implements com\busit\Producer
{
	private $requestTime = array();
	private $successCount = 0;

	public function produce($out)
	{
		for( $i = 0; $i < min(10, $this->config('retry')); $i++ )		
			$this->requestTime[] = $this->wget($this->config('url'));
		
		$ratio = floor(($this->successCount / min(10, $this->config('retry'))) * 100);
		$tolerance = max(0, min(100, $this->config('tolerance')));
		
		if( ($out->key == 'success' && $ratio >= $tolerance) || ($out->key == 'error' && $ratio < $tolerance) || $out->key == 'time' )
		{
			$message = com\busit\Factory::message();
			$content = com\busit\Factory::content(20);

			$content['status'] = $out->key;
			$content['type'] = 'Check';
			$content['proto'] = 'http';
			$content['url'] = $this->config('url');
			$content['ratio'] = $ratio;
			$content['responsetime'] = $this->requestTime[0];
			$content['number'] = $this->requestTime[0];
			$content['timestamp'] = time();
			$content['date'] =  date('Y-m-d H:i:s', time());

			$message->content($content);

			return $message;
		}
	}
	
	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
	
	private function wget()
	{
		try
		{
			$request = array( 'http' => array( 'user_agent' => 'PHP/5 (Bus IT) WebMonitor/1.0', 'method' => 'GET', 'timeout' => 10.0 ));
			
			$start = microtime(true);
			$fh = @fopen($this->config('url'), 'r', false, stream_context_create( $request ));
			
			if( $fh === false )
				throw new \Exception("request failed");
			
			stream_get_contents($fh);
			fclose($fh);
				
			$end = microtime(true);
			$this->successCount++;
			return ceil(($end - $start) * 1000);
		}
		catch(Exception $e)
		{
			return -1;
		}
	}
}

?>