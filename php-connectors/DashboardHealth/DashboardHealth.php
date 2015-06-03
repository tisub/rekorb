<?php

define("__CLASSNAME__", "\\DashboardHealth");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class DashboardHealth extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{
		$mysql = new cad\mysql('sql', 'mysql-s0TviBv7', 'shos001', 'mysql-s0TviBv7-master');
		$content = $message->content();
		
		// group weight
		$insert = null;
		if( $content['weight'] !== null )
			$insert = "'weight', '{$content['weight']}'";
		else if( $content['poids'] !== null )
			$insert = "'weight', '{$content['poids']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}

		// group steps
		$insert = null;
		if( $content['steps'] !== null )
			$insert = "'steps', '{$content['steps']}'";
		else if( $type['pas'] !== null )
			$insert = "'steps', '{$content['poids']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group heart
		$insert = null;
		if( $content['heart'] !== null )
			$insert = "'heart', '{$content['heart']}'";
		else if( $content['heartrate'] !== null )
			$insert = "'steps', '{$content['heartrate']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group calories
		$insert = null;
		if( $content['calories'] !== null )
			$insert = "'calories', '{$content['calories']}'";
		else if( $content['cal'] !== null )
			$insert = "'calories', '{$content['cal']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group calories
		$insert = null;
		if( $content['calories'] !== null )
			$insert = "'calories', '{$content['calories']}'";
		else if( $content['cal'] !== null )
			$insert = "'calories', '{$content['cal']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group systolic
		$insert = null;
		if( $content['systolic'] !== null )
			$insert = "'systolic', '{$content['systolic']}'";
		else if( $content['blood_systolic'] !== null )
			$insert = "'systolic', '{$content['blood_systolic']}'";
		else if( $content['blood_pressure'] !== null )
			$insert = "'systolic', '{$content['blood_pressure']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group diastolic
		$insert = null;
		if( $content['diastolic'] !== null )
			$insert = "'diastolic', '{$content['diastolic']}'";
		else if( $content['blood_diastolic'] !== null )
			$insert = "'diastolic', '{$content['blood_diastolic']}'";
		else if( $content['blood_pressure'] !== null )
			$insert = "'diastolic', '{$content['blood_diastolic']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group spo2
		$insert = null;
		if( $content['spo2'] !== null )
			$insert = "'spo2', '{$content['spo2']}'";
		else if( $content['o2'] !== null )
			$insert = "'spo2', '{$content['o2']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
		
		// group distance
		$insert = null;
		if( $content['distance'] !== null )
			$insert = "'distance', '{$content['distance']}'";
		else if( $content['way'] !== null )
			$insert = "'distance', '{$content['way']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}

		// group elevation
		$insert = null;
		if( $content['elevation'] !== null )
			$insert = "'elevation', '{$content['elevation']}'";
		else if( $content['elev'] !== null )
			$insert = "'elevation', '{$content['elev']}'";
		
		if( $insert != null )
		{
			$sql = "INSERT INTO dashboard_health (health_instance, health_interface, health_metric, health_value, health_timestamp) VALUES ('". $this->config('__instance') ."', '{$in->value}', {$insert}, UNIX_TIMESTAMP())";
			$mysql->insert($sql);
		}
	}
	
	public function test()
	{
		return true;
	}
}

?>