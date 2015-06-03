<?php

define("__CLASSNAME__", "\\Curiosity");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Curiosity extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = "http://marsweather.ingenology.com/v1/latest/?format=json";
		$data = file_get_contents("http://cache.busit.com/?url=".urlencode($url));
		$data = json_decode($data, true);
		$data = $data['report'];
		
		switch( $out->key )
		{
			case 'weather':
				if( strlen($data['atmo_opacity']) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast conditions: {{conditions}}.\nLast maximum temperature: {{temperature_max}}°C.\nLast minimum temperature: {{temperature_min}}°C.\nLast humidity: {{humidity}}%.\nLast pressure {{pressure}}hPa.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last conditions: {{conditions}}.<br />Last maximum temperature: {{temperature_max}}°C.<br />Last minimum temperature: {{temperature_min}}°C.<br />Last humidity: {{humidity}}%.<br />Last pressure {{pressure}}hPa.";
			break;
			case 'maxtemperature':
				$number = $data['max_temp'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast maximum temperature: {{temperature_max}}°C.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last maximum temperature: {{temperature_max}}°C.";
			break;
			case 'mintemperature':
				$number = $data['min_temp'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast minimum temperature: {{temperature_min}}°C.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last minimum temperature: {{temperature_min}}°C.";
			break;
			case 'humidity':
				$number = $data['abs_humidity'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast humidity: {{humidity}}%.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last humidity: {{humidity}}%.";
			break;
			case 'windspeed':
				$number = $data['wind_speed'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast wind speed: {{windspeed}}.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last wind speed: {{windspeed}}.";
			break;
			case 'pressure':
				$number = $data['pressure'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Mars Weather\n\nLast pressure {{pressure}}hPa.";
				$htmlFormat = "<strong>Mars Weather</strong><br /><br />Last pressure {{pressure}}hPa.";
			break;
		}
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(6);
		
		$content->textFormat($textFormat);
		$content->htmlFormat($htmlFormat);

		if( $number != null )
			$content['number'] = $number;
		
		$content['subject'] = 'Mars Weather Information';
		$content['conditions'] = $data['atmo_opacity'];
		$content['temperature_max'] = $data['max_temp'];
		$content['temperature_min'] = $data['min_temp'];
		$content['temperature'] = $data['max_temp'];
		$content['humidity'] = $data['abs_humidity'];
		$content['windspeed'] = $data['wind_speed'];
		$content['pressure'] = $data['pressure'];
		$content['timestamp'] = strtotime(time());
		$content['date'] =  date('Y-m-d H:i:s', strtotime(time()));

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