<?php

define("__CLASSNAME__", "\\ParrotFlowerPower");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class ParrotFlowerPower extends com\busit\Connector implements com\busit\Producer
{
	private $location = null;
	private $sensor = null;
	
	public function produce($out)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://apiflowerpower.parrot.com/sensor_data/v3/garden_locations_status');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer '. $this->config('token'),
		));
		$result = curl_exec($ch);
		$result = json_decode($result, true);
		
		// Get the proper location
		if( count($result['locations']) > 0 ) 
		{
			foreach( $result['locations'] as $l )
			{
				if( $l['location_identifier'] == $this->config('location') )
				{
					$this->location = $l;
					break;
				}
			}
		}
		else
			return null;
			
		// Get the proper sensor
		if( count($result['sensors']) > 0 ) 
		{
			foreach( $result['sensors'] as $s )
			{
				if( $s['sensor_serial'] == $this->config('sensor') )
				{
					$this->sensor = $s;
					break;
				}
			}
		}
		else
			return null;
		
		// Generate the message
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(19);
		$content['subject'] = 'Parrot FlowerPower Conditions';
		$content['battery'] = $this->sensor['battery_level']['level_percent'];
		$content['temperature'] = $this->location['air_temperature']['gauge_values']['current_value'];
		$content['moisture'] = $this->location['soil_moisture']['gauge_values']['current_value'];
		$content['sunlight'] = $this->location['light']['gauge_values']['current_value'];
		$content['fertilizer'] = $this->location['fertilizer']['gauge_values']['current_value'];
		
		// Select format and number
		switch( $out->key )
		{
			case 'all':
				if( $this->sensor == null || !is_numeric($this->sensor['battery_level']['level_percent']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nBattery level: {{battery}}%\nTemperature: {{temperature}}°C\nMoisture: {{moisture}}%\nSunlight: {{sunlight}}%\nFertilizer: {{fertilizer}}');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Battery level: <strong>{{battery}}%</strong><br />Temperature: <strong>{{temperature}}°C</strong><br />Moisture: <strong>{{moisture}}%</strong><br />Sunlight: <strong>{{sunlight}}%</strong><br />Fertilizer: <strong>{{fertilizer}}</strong>');
			break;
			case 'battery':
				if( $this->sensor == null || !is_numeric($this->sensor['battery_level']['level_percent']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nBattery: {{battery}}%');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Battery: <strong>{{battery}}%</strong>');
				$content['number'] = $this->sensor['battery_level']['level_percent'];
			break;
			case 'temperature':
				if( $this->location == null || !is_numeric($this->location['air_temperature']['gauge_values']['current_value']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nTemperature: {{temperature}}°C');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Temperature: <strong>{{temperature}}°C</strong>');
				$content['number'] = $this->location['air_temperature']['gauge_values']['current_value'];
			break;
			case 'moisture':
				if( $this->location == null || !is_numeric($this->location['soil_moisture']['gauge_values']['current_value']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nMoisture: {{moisture}}%');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Moisture: <strong>{{moisture}}%</strong>');
				$content['number'] = $this->location['soil_moisture']['gauge_values']['current_value'];
			break;
			case 'sunlight':
				if( $this->location == null || !is_numeric($this->location['light']['gauge_values']['current_value']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nSunlight: {{sunlight}}%');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Sunlight: <strong>{{sunlight}}%</strong>');
				$content['number'] = $this->location['light']['gauge_values']['current_value'];
			break;
			case 'fertilizer':
				if( $this->location == null || !is_numeric($this->location['fertilizer']['gauge_values']['current_value']) ) return null;
				$content->textFormat('Parrot FlowerPower Information\n\nFertilizer: {{fertilizer}}');
				$content->htmlFormat('<strong>Parrot FlowerPower Information</strong><br /><br />Fertilizer: <strong>{{fertilizer}}</strong>');
				$content['number'] = $this->location['fertilizer']['gauge_values']['current_value'];
			break;
		}
		
		$message->content($content);
		
		return $message;
	}
	
	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
}

?>