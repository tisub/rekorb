<?php

define("__CLASSNAME__", "\\NetatmoWeather");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class NetatmoWeather extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = 'https://apps.busit.com/netatmo/pull?type=current&config=' . json_encode($this->config());
		$result = file_get_contents("http://cache.busit.com/?ttl=1000&url=".urlencode($url));
		$result = json_decode($result, true);
		
		switch( $out->key )
		{
			case 'externaltemperature':
				$number = $result['temp_ext'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nOutside temperature: {{temperature_outside}}°C";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Outside temperature: {{temperature_outside}}°C";
				$subject = "Netatmo Outside Temperature";
			break;
			case 'internaltemperature':
				$number = $result['temp_int'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nInside temperature: {{temperature_inside}}°C";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Inside temperature: {{temperature_inside}}°C";
				$subject = "Netatmo Inside Temperature";
			break;
			case 'externalhumidity':
				$number = $result['humidity_ext'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nOutside humidity: {{humidity_outside}}%";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Outside humidity: {{humidity_outside}}%";
				$subject = "Netatmo Outside Humidity";
			break;
			case 'internalhumidity':
				$number = $result['humidity_int'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nInside humidity: {{humidity_inside}}%";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Inside humidity: {{humidity_inside}}%";
				$subject = "Netatmo Inside Humidity";
			break;
			case 'externalpressure':
				$number = $result['pressure_ext'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nOutside pressure: {{pressure_outside}}mb";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Outside pressure: {{pressure_outside}}mb";
				$subject = "Netatmo Outside Pressure";
			break;
			case 'internalpressure':
				$number = $result['pressure_int'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nInside pressure: {{pressure_inside}}mb";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Inside pressure: {{pressure_inside}}mb";
				$subject = "Netatmo Inside Pressure";
			break;
			case 'internalco2':
				$number = $result['co2_int'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nInside CO2 level: {{co2_inside}}ppm";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Inside CO2 level: {{co2_inside}}ppm";
				$subject = "Netatmo Inside CO2 level";
			break;
			case 'internalsound':
				$number = $result['sound_int'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station\n\nInside sound volume: {{sound_inside}}db";
				$htmlFormat = "<strong>Netatmo Weather Station</strong><br /><br />Inside sound volume: {{sound_inside}}db";
				$subject = "Netatmo Inside Sound volume";
			break;
			case 'conditions':
				if( strlen($result['temp_ext']) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station Outside\n\nTemperature: {{temperature_outside}}°C\nHumidity: {{humidity_outside}}%\nPressure: {{pressure_outside}}mb";
				$htmlFormat = "<strong>Netatmo Weather Station Outside</strong><br /><br />Temperature: {{temperature_outside}}°C<br />Humidity: {{humidity_outside}}%<br />Pressure: {{pressure_outside}}mb";
				$subject = "Netatmo All Outside Conditions";
			break;
			case 'all':
				if( strlen($result['temp_int']) == 0 )
					return null;
				$textFormat = "Netatmo Weather Station Inside\n\nTemperature: {{temperature_inside}}°C\nHumidity: {{humidity_inside}}%\nPressure: {{pressure_inside}}mb\nCO2 Level: {{co2_inside}}ppm\nSound level: {{sound_inside}}db";
				$htmlFormat = "<strong>Netatmo Weather Station Inside</strong><br /><br />Temperature: {{temperature_inside}}°C<br />Humidity: {{humidity_inside}}%<br />Pressure: {{pressure_inside}}mb<br />CO2 Level: {{co2_inside}}ppm<br />Sound level: {{sound_inside}}db";
				$subject = "Netatmo All Inside Conditions";
			break;
		}

		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(6);
		
		$content->textFormat($textFormat);
		$content->htmlFormat($htmlFormat);
		
		if( $out->key == 'all' )
		{
			$content['temperature'] = $result['temp_int'];
			$content['temperature_celsius'] = $result['temp_int'];
			$content['temperature_fahrenheit'] = $result['temp_int']*33.8;
			$content['humidity'] = $result['humidity_int'];
			$content['pressure'] = $result['pressure_int'];
			$content['co2'] = $result['co2_int'];
			$content['sound'] = $result['sound_int'];
		}
		else if( $out->key == 'conditions' )
		{
			$content['temperature'] = $result['temp_ext'];
			$content['temperature_celsius'] = $result['temp_ext'];
			$content['temperature_fahrenheit'] = $result['temp_ext']*33.8;
			$content['humidity'] = $result['humidity_ext'];
			$content['pressure'] = $result['pressure_ext'];			
		}
		else
		{
			$content['temperature'] = $result['temp_int'];
			$content['temperature_celsius'] = $result['temp_int'];
			$content['temperature_fahrenheit'] = $result['temp_int']*33.8;
			$content['humidity'] = $result['humidity_int'];
			$content['pressure'] = $result['pressure_int'];
			$content['co2'] = $result['co2_int'];
			$content['sound'] = $result['sound_int'];
			$content['number'] = $number;
		}
		
		$content['subject'] = $subject;
		$content['temperature_outside'] = $result['temp_ext'];
		$content['temperature_inside'] = $result['temp_int'];	
		$content['humidity_outside'] = $result['humidity_ext'];
		$content['humidity_inside'] = $result['humidity_int'];
		$content['pressure_outside'] = $result['pressure_ext'];
		$content['pressure_inside'] = $result['pressure_int'];
		$content['co2_inside'] = $result['co2_int'];
		$content['sound_inside'] = $result['sound_int'];
		$content['timestamp'] = $result['time'];
		$content['date'] =  date('Y-m-d H:i:s', $result['time']);
		
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