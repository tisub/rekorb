<?php

define("__CLASSNAME__", "\\WithingsBody");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class WithingsBody extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$url = 'https://apps.busit.com/withings/pull?type=body&config=' . json_encode($this->config());
		$result = file_get_contents("http://cache.busit.com/?ttl=1000&url=".urlencode($url));
		$result = json_decode($result, true);
		
		switch( $out->key )
		{
			case 'weight':
				$number = $result['weight'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nWeight: {{weight}}kg";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Weight: {{weight}}kg";
				$subject = "Withings Weight";
			break;
			case 'height':
				$number = $result['height'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nHeight: {{height}}m";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Height: {{height}}m";
				$subject = "Withings Height";
			break;
			case 'fatfreemass':
				$number = $result['fatfreemass'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nFat Free Mass: {{fatfreemass}}%";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Fat Free Mass: {{fatfreemass}}%";
				$subject = "Withings Fat Free Mass";
			break;
			case 'fatratio':
				$number = $result['fatratio'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nFat ratio: {{fatratio}}%";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Fat ratio: {{fatratio}}%";
				$subject = "Withings Fat Ratio";
			break;
			case 'fatweight':
				$number = $result['fatweight'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nFat weight: {{fatweight}}%";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Fat weight: {{fatweight}}%";
				$subject = "Withings Fat Weight";
			break;
			case 'systolic':
				$number = $result['systolic'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nSystolic Blood Pressure: {{systolic}}mmhg";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Systolic Blood Pressure: {{systolic}}mmhg";
				$subject = "Withings Systolic Blood Pressure";
			break;
			case 'diastolic':
				$number = $result['diastolic'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nDiastolic pressure: {{diastolic}}mmhg";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Diastolic pressure: {{diastolic}}mmhg";
				$subject = "Withings Diastolic Blood Pressure";
			break;
			case 'heart':
				$number = $result['heart'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\n\nHeart pulse: {{heart}}bpm";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Heart pulse: {{heart}}bpm";
				$subject = "Withings Heart Pulse";
			break;
			case 'spo2':
				$number = $result['spo2'];
				if( strlen($number) == 0 || $number == 0 )
					return null;
				$textFormat = "Withings Health Data\nSPO2: {{spo2}}%";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />SPO2: {{spo2}}%";
				$subject = "Withings SPO2";
			break;
			case 'all':
				$textFormat = "Withings Health Data\n\nWeight: {{weight}}kg\nHeight: {{height}}cm\nHeart: {{heart}}bpm\nSPO2: {{spo2}}%";
				$htmlFormat = "<strong>Withings Health Data</strong><br /><br />Weight: {{weight}}kg<br />Height: {{height}}cm<br />Heart: {{heart}}bpm<br />SPO2: {{spo2}}%";
				$subject = "Withings Health Information";
			break;
		}

		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(14);
		
		$content->textFormat($textFormat);
		$content->htmlFormat($htmlFormat);
		
		if( isset($number) )
			$content['number'] = $number;

		$content['subject'] = $subject;
		$content['weight'] = $result['weight'];
		$content['height'] = $result['height'];
		$content['fatfreemass'] = $result['fatfreemass'];
		$content['fatratio'] = $result['fatratio'];
		$content['fatweight'] = $result['fatweight'];
		$content['systolic'] = $result['systolic'];
		$content['diastolic'] = $result['diastolic'];
		$content['heart'] = $result['heart'];	
		$content['spo2'] = $result['spo2'];
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