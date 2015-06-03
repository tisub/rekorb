<?php

define("__CLASSNAME__", "\\Weather");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Weather extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = "http://api.openweathermap.org/data/2.5/weather?lang=" . $this->config('language') . "&q=" . $this->config('country') . "," . $this->config('city');
		$data = file_get_contents("http://cache.busit.com/?url=".urlencode($url));
		$data = json_decode($data, true);
		
		$lang = array();
		switch( $this->config('language') )
		{
			case 'FR': 
				$lang['intro'] = "Météo actuelle à " . $this->config('city') . ", " . $this->config('country');
				$lang['description'] = 'Conditions :';
				$lang['temperature'] = 'Température :';
				$lang['humidity'] = 'Humidité :';
				$lang['windspeed'] = 'Vitesse du vent :';
				$lang['winddirection'] = 'Direction du vent :';
				$lang['pressure'] = 'Pression :';
			break;
			case 'EN': 
				$lang['intro'] = "Current weather in " . $this->config('city') . ", " . $this->config('country');
				$lang['description'] = 'Weather:';
				$lang['temperature'] = 'Temperature:';
				$lang['humidity'] = 'Humidity:';
				$lang['windspeed'] = 'Wind speed:';
				$lang['winddirection'] = 'Wind direction:';
				$lang['pressure'] = 'Pressure:';
			break;
		}
		
		$number = null;
		switch( $out->key )
		{
			case 'conditions':
				if( strlen($data['weather'][0]['description']) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['description']} {{conditions}}\n{$lang['temperature']} {{temperature_celsius}}°C\n{$lang['pressure']} {{pressure}}hPa\n{$lang['windspeed']} {{windspeed}}mps\n{$lang['winddirection']} {{winddirection}}°";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['description']} <strong>{{conditions}}</strong><br />{$lang['temperature']} <strong>{{temperature_celsius}}°C</strong><br />{$lang['pressure']} <strong>{{pressure}}hPa</strong><br />{$lang['windspeed']} <strong>{{windspeed}}mps</strong><br />{$lang['winddirection']} <strong>{{winddirection}}°</strong>";
			break;
			case 'temperaturec':
				$number = round($data['main']['temp']-273.15, 2);
				if( strlen($number) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['temperature']} {{temperature_celsius}}°C";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['temperature']} <strong>{{temperature_celsius}}°C</strong>";
			break;
			case 'humidity':
				$number = round($data['main']['humidity'], 2);
				if( strlen($number) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['humidity']} {{humidity}}%";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['humidity']} <strong>{{humidity}}%</strong>";
			break;
			case 'windspeed':
				$number = $data['wind']['speed'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['windspeed']} {{windspeed}}mps";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['windspeed']} <strong>{{windspeed}}mps</strong>";
			break;
			case 'winddirection':
				$number = $data['wind']['deg'];
				if( strlen($number) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['winddirection']} {{winddirection}}°";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['winddirection']} <strong>{{winddirection}}°</strong>";
			break;
			case 'pressure':
				$number = round($data['main']['pressure'], 2);
				if( strlen($number) == 0 )
					return null;
				$textFormat = "{$lang['intro']}\n\n{$lang['pressure']} {{pressure}} hPa";
				$htmlFormat = "<strong>{$lang['intro']}</strong><br /><br />{$lang['pressure']} <strong>{{pressure}}hPa</strong>";
			break;
		}
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(6);
		
		$content->textFormat($textFormat);
		$content->htmlFormat($htmlFormat);

		if( $number != null )
			$content['number'] = $number;

		$content['subject'] = $lang['intro'];
		$content['conditions'] = $data['weather'][0]['description'];
		$content['temperature_celsius'] = $data['main']['temp']-273.15;
		$content['temperature_fahrenheit'] = $data['main']['temp'];
		$content['temperature'] = $data['main']['temp']-273.15;
		$content['pressure'] = $data['main']['pressure'];
		$content['humidity'] = $data['main']['humidity'];
		$content['windspeed'] = $data['wind']['speed'];
		$content['winddirection'] = $data['wind']['deg'];
		$content['timestamp'] = $data['dt'];
		$content['date'] =  date('Y-m-d H:i:s', $data['dt']);
		
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