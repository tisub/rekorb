<?php

define("__CLASSNAME__", "\\WithingsActivity");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class WithingsActivity extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = 'https://apps.busit.com/withings/pull?type=activity&config=' . json_encode($this->config());
		$result = file_get_contents("http://cache.busit.com/?ttl=1000&url=".urlencode($url));
		$result = json_decode($result, true);
	
		switch( $out->key )
		{
			case 'distance':
				$number = $result['distance'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Activity Data\n\nDistance: {{distance}}m";
				$htmlFormat = "<strong>Withings Activity Data</strong><br /><br />Distance: {{distance}}m";
				$subject = "Withings Distance";
			break;
			case 'elevation':
				$number = $result['elevation'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Activity Data\n\nElevation: {{elevation}}m";
				$htmlFormat = "<strong>Withings Activity Data</strong><br /><br />Elevation: {{elevation}}m";
				$subject = "Withings Elevation";
			break;
			case 'calories':
				$number = $result['calories'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Activity Data\n\nCalories: {{calories}}Kcal";
				$htmlFormat = "<strong>Withings Activity Data</strong><br /><br />Calories: {{calories}}Kcal";
				$subject = "Withings Calories";
			break;
			case 'steps':
				$number = $result['steps'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Activity Data\n\nSteps: {{steps}}";
				$htmlFormat = "<strong>Withings Activity Data</strong><br /><br />Steps: {{steps}}";
				$subject = "Withings Steps";
			break;
			case 'all':
				$textFormat = "Withings Activity Data\n\nDistance: {{distance}}km\nElevation: {{elevation}}m\nSteps: {{steps}}\nCalories: {{calories}}Kcal";
				$htmlFormat = "<strong>Withings Activity Data</strong><br /><br />Distance: {{distance}}km<br />Elevation: {{elevation}}m<br />Steps: {{steps}}<br />Calories: {{calories}}Kcal";
				$subject = "Withings Activity Information";
			break;
		}
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(16);
		
		$content->textFormat($textFormat);
		$content->htmlFormat($htmlFormat);
		
		if( isset($number) )
			$content['number'] = $number;
		
		$content['subject'] = $subject;
		$content['distance'] = $result['distance'];
		$content['elevation'] = $result['elevation'];
		$content['calories'] = $result['calories'];
		$content['steps'] = $result['steps'];
		$content['timestamp'] = $result['date'];
		$content['date'] =  date('Y-m-d H:i:s', $result['date']);
		
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