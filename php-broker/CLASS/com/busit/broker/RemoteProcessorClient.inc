<?php
namespace com\busit\broker;

use com\anotherservice\util as cau;
use com\anotherservice\io as cai;
use com\anotherservice\rest\security as cars;
use com\busit\security as cbs;
use com\busit as cb;
use com\busit\routing as cbr;

class StdIOLog
{
	private $err;
	public function __construct() 			{ $this->err = new cai\StdIOIPC(null, STDERR); }
	public function severe($message) 		{ $this->err->send($message); }
	public function warning($message) 		{ $this->err->send($message); }
	public function info($message) 			{ $this->err->send($message); }
	public function config($message) 		{ $this->err->send($message); }
	public function fine($message) 			{ $this->err->send($message); }
	public function finer($message) 		{ $this->err->send($message); }
	public function finest($message) 		{ $this->err->send($message); }
	public function log($level, $message) 	{ $this->err->send($message); }
}

class RemoteProcessorClient
{
	public function __construct()
	{
		try
		{
			cau\Logger::instance(cau\Logger::_DEFAULT, new StdIOLog());
			
			cb\Factory::instance(new FactoryImpl());
			cai\RestApi::initialize("http://api.broker.busit.com/busit/", "", true);
				
			ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE);
			$this->run();
			$buffer = ob_get_contents();
			if( strlen($buffer) > 0 )
				cau\Logger::info(json_encode(array("notifyOwner"=>$buffer)));
			ob_clean();
		}
		catch(\Exception $e)
		{
			Logger.warning(json_encode(array("notifyBusit" => $e.'')));
		}
	}
	
	public function run()
	{
		$io = new cai\StdIOIPC(STDIN, STDOUT);
		try
		{
			// 1) tell the server the startup phase is complete
			$io->sendAny(array("status"=>"started"));

			// 2) get the connector code
			$code = $io->receiveAny();
			$this->construct($code["code"]);

			// 3) tell the server the creation phase is complete
			$io->sendAny(array("status"=>"created"));
			
			// 4) get the init data
			$init = $io->receiveAny();
			$this->connector->init($init["config"], $init["inputs"], $init["outputs"]);

			// 5) tell the server the init phase is complete
			$io->sendAny(array("status"=>"initialized"));
			
			// 6) get the message to push
			$sample = null;
			$push = $io->receiveAny();
			if( isset($push["cron"]) )
			{
				$m_cron = new UncheckedMessage($push["cron"]);
				cb\Factory::instance()->template($m_cron);
				$this->connector->cron($m_cron, $push["interfaceId"]);
			}
			else if( isset($push["input"]) )
			{
				$m_input = new UncheckedMessage($push["input"]);
				cb\Factory::instance()->template($m_input);
				$this->connector->setInput($m_input, $push["interfaceId"]);
			}
			else if( isset($push["sample"]) )
			{
				$sample = new UncheckedMessage($push["sample"]);
				cb\Factory::instance()->template($sample);
			}
			else
				throw new \Exception("Unexpected push mode");

			// 7) tell the server the push phase is complete
			$io->sendAny(array("status"=>"pushed"));
			
			// start loop for pulls until the server tell us to stop
			$count = 0;
			do
			{
				// 8) get the server orders
				$pull = $io->receiveAny();
				if( isset($pull["stop"]) )
				{
					// 8.a) tell the server the pull phase is complete
					$io->sendAny(array("status"=>"pulled"));
					break;
				}
				else
				{
					$m = null;
					if( $sample != null )
						$m = $this->connector->getSampleData($sample, $pull["interfaceId"]);
					else
						$m = $this->connector->getOutput($pull["interfaceId"]);
					
					// 8.b) give the pull result to the server
					if( $m instanceof cbs\MessageList )
					{
						$a = array();
						for( $i = 0; $i < count($m); $i++ )
							$a[] = $m[$i]->toString();
						$io->sendAny(array("message"=>$a));
					}
					else if( $m == null )
						$io->sendAny(array("message"=>$m));
					else
						$io->sendAny(array("message"=>$m->toString()));
					$count++;
				}
			}
			while(true);
		}
		catch(\Exception $e)
		{
			cau\Logger::severe(json_encode(array("notifyOwner" => $e.'')));
			// 9) tell the server about the error
			try { $io->sendAny(array("abort" => true)); } catch(\Exception $ex) {}
		}
		
		// 10) cleanup everything
		$io->close();
	}
	
	private $connector = null;
	
	public function construct($code)
	{
		$source = base64_decode($code);
		if( $source == false )
			throw new \Exception("Invalid encoding");

		$source = preg_replace("/^\\s*<\\?php\\s*(.*)\\s*\\?>\\s*$/is", '$1', $source);
		eval($source);
		
		if( !defined('__CLASSNAME__') || __CLASSNAME__ == null || strlen(__CLASSNAME__) == 0 )
			throw new \Exception("Invalid or missing '__CLASSNAME__' in connector configuration");
		
		$className = __CLASSNAME__;
		$this->connector = new $className();
		if( !($this->connector instanceof cb\IConnector) )
			throw new \Exception("Invalid connector instance");
	}
	
}

?>