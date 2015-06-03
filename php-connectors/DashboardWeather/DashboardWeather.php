<?php

define("__CLASSNAME__", "\\DashboardWeather");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class DashboardWeather extends com\busit\Connector implements com\busit\Consumer
{	
	public function consume($message, $in)
	{
		$mysql = new cad\mysql('sql', 'mysql-s0TviBv7', 'shos001', 'mysql-s0TviBv7-master');
		$content = $message->content();
		
		// group temperature
		$insert = null;
		if( $content['temperature_inside'] !== null )
			$insert = "'temperature', '{$content['temperature_inside']}'";
		else if( $content['temperature'] !== null )
			$insert = "'temperature', '{$content['temperature']}'";
		else if( $content['temperature_celsius'] !== null )
			$insert = "'temperature', '{$content['temperature_celsius']}'";
		else if( $content['temp'] !== null )
			$insert = "'temperature', '{$content['temp']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_conditions (conditions_instance, conditions_interface, conditions_metric, conditions_value, conditions_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
					
		// group humidity
		$insert = null;
		if( $content['humidity_inside'] !== null )
			$insert = "'humidity', '{$content['humidity_inside']}'";
		else if( $content['humidity'] !== null )
			$insert = "'humidity', '{$content['humidity']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_conditions (conditions_instance, conditions_interface, conditions_metric, conditions_value, conditions_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
			
		// group pressure
		$insert = null;
		if( $content['pressure_inside'] !== null )
			$insert = "'pressure', '{$content['pressure_inside']}'";
		else if( $content['pressure'] !== null )
			$insert = "'pressure', '{$content['pressure']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_conditions (conditions_instance, conditions_interface, conditions_metric, conditions_value, conditions_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group co2
		$insert = null;
		if( $content['co2_inside'] !== null )
			$insert = "'co2', '{$content['co2_inside']}'";
		else if( $content['co2'] !== null )
			$insert = "'co2', '{$content['co2']}'";
	
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_conditions (conditions_instance, conditions_interface, conditions_metric, conditions_value, conditions_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group sound
		$insert = null;
		if( $content['sound_inside'] !== null )
			$insert = "'sound', '{$content['sound_inside']}'";
		else if( $content['sound'] !== null )
			$insert = "'sound', '{$content['sound']}'";	

		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_conditions (conditions_instance, conditions_interface, conditions_metric, conditions_value, conditions_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
	}
	
	public function test()
	{
		return true;
	}
}

?>