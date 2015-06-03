<?php

define("__CLASSNAME__", "\\Graph");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Graph extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		$content = $message->content();
		
		if( $this->config('number') != null && strlen($this->config('number')) > 0 )
			$number = $this->config('number');
		else if( isset($content['number']) && strlen($content['number']) > 0 )
			$number = $content['number'];
		else
			$number = strlen($content->toText());
		
		$sql = "INSERT INTO graph (graph_identifier, graph_instance, graph_interface, graph_date, graph_value) VALUES ('". $this->id() . "', '" . $this->config('__instance') . "', '{$in->name}',  UNIX_TIMESTAMP(), '{$number}')";
		$mysql->insert($sql);
	}
	
	public function test()
	{
		return true;
	}
}

?>