<?php
namespace com\busit;

class Factory
{
	private static $_instance = null;
	public static function instance($i=null)
	{
		if( $i == null )
		{
			if( self::$_instance == null )
				throw new \Exception("Instance is not yet defined");
			return self::$_instance;
		}
		else
		{
			if( self::$_instance != null )
				throw new \Exception("Instance is already defined and cannot be overridden");
			self::$_instance = $i;
		}
	}
	
	public static function content($id) { return self::instance()->content($id); }
	public static function message() { return self::instance()->message(); }
	public static function messageList() { return self::instance()->messageList(); }
}

?>