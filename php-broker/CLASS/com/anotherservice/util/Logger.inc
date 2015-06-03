<?php
namespace com\anotherservice\util;

class Log
{
	public $logDate = true;
	public $logClass = true;
	public $logMethod = false;
	public $logLevel = E_NOTICE;
	public $logName = "com.anotherservice";
	public $logStream = null;
	
	private function format($level, $message)
	{
		if( $this->logClass )
		{
			$s = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS);
			while( count($s) > 0 )
			{
				if( isset($s[0]['class']) && strpos($s[0]['class'], "com\\anotherservice\\util\\") !== false )
					array_shift($s);
				else if( count($s) >= 2 && isset($s[0]['function']) && ($s[0]['function'] == 'notifyUser' || $s[0]['function'] == 'notifyOwner') )
				{
					$s[0]['class'] = $s[1]['class'];
					break;
				}
				else
					break;
			}
			
			if( is_array($s) && count($s) > 0 )
			{
				$s = $s[0];
				if( !isset($s['class']) ) $s['class'] = '';
				if( !isset($s['type']) ) $s['type'] = '';
				if( !isset($s['function']) ) $s['function'] = '';
				$message = "[" . $s['class'] . ($this->logMethod ? $s['type'] . $s['function'] . "()" : "") . "] " . $message;
			}
		}
		
		if( $this->logDate )
			$message = date("Y/m/d H:i:s") . " " . $message;
			
		switch($level)
		{
			case E_ERROR: $message = 'ERROR ' . $message; break;
			case E_WARNING: $message = 'WARNING ' . $message; break;
			case E_NOTICE: $message = 'NOTICE ' . $message; break;
			default: $message = 'UNKNOWN(' . $level . ') ' . $message; break;
		}

		return $message;
	}
	
	public function severe($message) { $this->realLog(E_ERROR, $message); }
	public function warning($message) { $this->realLog(E_WARNING, $message); }
	public function info($message) { $this->realLog(E_NOTICE, $message); }
	public function config($message) { $this->realLog(E_NOTICE, $message); }
	public function fine($message) { $this->realLog(E_NOTICE, $message); }
	public function finer($message) { $this->realLog(E_NOTICE, $message); }
	public function finest($message) { $this->realLog(E_NOTICE, $message); }
	public function log($level, $message) { $this->realLog($level, $message); }
	
	private function realLog($level, $message)
	{
		if( $level == null )
			$level = E_NOTICE;
		if( $message == null )
			$message = "";

		if( ($level & $this->logLevel) > 0 )
		{
			if( $this->logStream == null )
				error_log($this->format($level, $message));
			else
			{
				fwrite($this->logStream, $this->format($level, $message) . "\n");
				fflush($this->logStream);
			}
		}
	}
}

class Logger
{
	const _DEFAULT = "com.anotherservice";
	
	private function __construct() { }
	private static $instances = array();
	public static function instance($name = null, $log = null)
	{
		if( $name == null || strlen($name) == 0 ) $name = self::_DEFAULT;
		if( $log == null )
		{
			if( !isset(self::$instances[$name]) )
				self::$instances[$name] = new Log($name);
			return self::$instances[$name];
		}
		else
			self::$instances[$name] = $log;
	}
	
	public static function severe($message) 		{ self::instance()->severe($message); }
	public static function warning($message) 		{ self::instance()->warning($message); }
	public static function info($message) 			{ self::instance()->info($message); }
	public static function config($message) 		{ self::instance()->config($message); }
	public static function fine($message) 			{ self::instance()->fine($message); }
	public static function finer($message) 			{ self::instance()->finer($message); }
	public static function finest($message) 		{ self::instance()->finest($message); }
	public static function log($level, $message) 	{ self::instance()->log($level, $message); }
}

?>