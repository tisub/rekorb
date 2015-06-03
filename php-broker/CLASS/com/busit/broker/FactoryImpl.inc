<?php
namespace com\busit\broker;
use com\busit as cb;
use com\busit\security as cbs;
use com\anotherservice\io as io;

class FactoryImpl implements cb\IFactory
{
	public function content($id)
	{
		if( $id < 0 )
			return new cbs\Content();
		if( $id == 0 )
			return new cbs\Content(null);
		
		try
		{
			$kt = io\RestApi::request("type/select", array("id"=>$id), true);
			return new cbs\Content($kt[0]["knowntype_data"]);
		}
		catch(\Exception $e)
		{
			return new cbs\Content();
		}
	}
	
	public function message()
	{
		if( self::$_template == null )
			throw new \Exception("template is not yet defined");
		return new UncheckedMessage(self::$_template->from(), self::$_template->to());
	}
	
	public function messageList()
	{
		return new cbs\MessageList();
	}
	
	private static $_template = null;
	public function template($message)
	{
		if( self::$_template != null )
			throw new \Exception("template is already defined");
		self::$_template = $message;
	}
}

?>