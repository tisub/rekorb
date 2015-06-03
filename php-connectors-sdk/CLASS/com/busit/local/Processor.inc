<?php
namespace com\busit\local;

use com\anotherservice\util as cau;
use com\anotherservice\io as cai;
use com\busit as cb;

class Processor
{
	public function __construct()
	{
		cb\Factory::instance(new FactoryImpl());
		cai\RestApi::initialize("http://api.busit.com/busit/", "", true);
	}
	
	private $connector = null;
	
	public function _1_construct($source)
	{
		cau\Logger::info("Processing phase 1 : parsing connector code");
		if( $source == null || strlen($source) == 0 )
			throw new \Exception("Invalid source code");

		$source = preg_replace("/^\\s*<\\?php\\s*(.*)\\s*\\?>\\s*$/is", '$1', $source);
		eval($source);
		
		if( !defined('__CLASSNAME__') || __CLASSNAME__ == null || strlen(__CLASSNAME__) == 0 )
			throw new \Exception("Invalid or missing '__CLASSNAME__' in connector configuration");
		
		$className = __CLASSNAME__;
		$this->connector = new $className();
		if( !($this->connector instanceof cb\IConnector) )
			throw new \Exception("Invalid connector instance");
		
		cau\Logger::info("Processing phase 1 : testing connector");
		if( !$this->connector->test() )
			throw new \Exception("Connector test() failed");
		
		cau\Logger::info("Processing phase 1 complete");
	}
	
	public function _2_initialize($init)
	{
		cau\Logger::info("Processing phase 2 : initializing configuration and interfaces");
		$this->connector->init($init["config"], $init["inputs"], $init["outputs"]);
		cau\Logger::info("Processing phase 2 complete");
	}
	
	private $sample = null;
	public function _3_push($push)
	{
		if( isset($push["cron"]) )
		{
			cau\Logger::info("Processing phase 3 : sending cron signal on input interface " . $push["interfaceId"]);
			cb\Factory::instance()->template($push["cron"]);
			$this->connector->cron($push["cron"], $push["interfaceId"]);
			cau\Logger::info("Processing phase 3 complete for " . $push["interfaceId"]);
		}
		else if( isset($push["input"]) )
		{
			cau\Logger::info("Processing phase 3 : pushing data on input interface " . $push["interfaceId"]);
			cb\Factory::instance()->template($push["input"]);
			$this->connector->setInput($push["input"], $push["interfaceId"]);
			cau\Logger::info("Processing phase 3 complete for " . $push["interfaceId"]);
		}
		else if( isset($push["sample"]) )
		{
			cau\Logger::info("Processing phase 3 skipped");
			$this->sample = $push["sample"];
			cb\Factory::instance()->template($this->sample);
		}
		else
			throw new \Exception("Unexpected push mode in phase 3");
	}

	public function _4_pull($pull)
	{
		if( $this->sample != null )
		{
			cau\Logger::info("Processing phase 4 : requesting sample data from output interface " . $pull["interfaceId"]);
			$m = $this->connector->getSampleData($this->sample, $pull["interfaceId"]);
			cau\Logger::info("Processing phase 4 complete for " . $pull["interfaceId"]);
			return $m;
		}
		else
		{
			cau\Logger::info("Processing phase 4 : pulling data from output interface " . $pull["interfaceId"]);
			$m = $this->connector->getOutput($pull["interfaceId"]);
			cau\Logger::info("Processing phase 4 complete for " . $pull["interfaceId"]);
			return $m;
		}
	}
}

?>