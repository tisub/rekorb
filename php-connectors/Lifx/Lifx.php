<?php

define("__CLASSNAME__", "\\Lifx");

use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Lifx extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	public function produce($out)
	{
		$result = com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb'), array(), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'GET');
		$result = json_decode($result, true);
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(31);
		
		$content['light_name'] = $result['label'];
		$content['light_location'] = $result['location']['name'];
		$content['light_status'] = $result['power'];
		$content['light_brightness'] = round($result['brightness']*100);
		$content['light_hue'] = round($result['color']['hue']);
		$content['light_saturation'] = round($result['color']['saturation']*100);
		$content['light_kelvin'] = $result['color']['kelvin'];
		
		$rgb = $this->fGetRGB($content['light_hue'], $content['light_saturation'], $content['light_brightness']);
		$content['light_color'] = "rgb({$rgb})";
		
		switch( $out->key )
		{
			case 'all':
				if( strlen($content['light_status']) == 0 ) return null;
				$content['subject'] = 'Lifx Light Information';
				$content->textFormat('Lifx Light Information\n\nName: {{light_name}}\nLocation: {{light_location}}\nStatus: {{light_status}}\nColor: {{light_color}}\nBrightness: {{light_brightness}}%\nHue: {{light_hue}}\nSaturation: {{light_saturation}}%');
				$content->htmlFormat('<strong>Lifx Light Information</strong><br /><br />Name: <strong>{{light_name}}</strong><br />Location: <strong>{{light_location}}</strong><br />Status: <strong>{{light_status}}</strong><br />Color: <strong>{{light_color}}</strong><br />Brightness: <strong>{{light_brightness}}%</strong><br />Hue: <strong>{{light_hue}}</strong><br />Saturation: <strong>{{light_saturation}}%</strong>');
			break;
			case 'currentstatus':
				if( strlen($content['light_status']) == 0 ) return null;
				$content['number'] = ($content['light_status'] == 'on'? "1":"0");
				$content['subject'] = 'Lifx Light Information';
				$content->textFormat('Lifx Light Information\n\nName: {{light_name}}\nLocation: {{light_location}}\nStatus: {{light_status}}');
				$content->htmlFormat('<strong>Lifx Light Information</strong><br /><br />Name: <strong>{{light_name}}</strong><br />Location: <strong>{{light_location}}</strong><br />Status: <strong>{{light_status}}</strong>');
			break;
			case 'currentcolor':
				if( strlen($content['light_color']) == 0 ) return null;
				$content['subject'] = 'Lifx Light Information';
				$content['number'] = $content['light_hue'];
				$content->textFormat('Lifx Light Information\n\nName: {{light_name}}\nLocation: {{light_location}}\nColor: {{light_color}}\nBrightness: {{light_brightness}}%\nHue: {{light_hue}}\nSaturation: {{light_saturation}}%\nKelvin: {{light_kelvin}}');
				$content->htmlFormat('<strong>Lifx Light Information</strong><br /><br />Name: <strong>{{light_name}}</strong><br />Location: <strong>{{light_location}}</strong><br />Color: <strong>{{light_color}}</strong><br />Brightness: <strong>{{light_brightness}}%</strong><br />Hue: <strong>{{light_hue}}</strong><br />Saturation: <strong>{{light_saturation}}%</strong><br />Kelvin: <strong>{{light_kelvin}}</strong>');
			break;
		}
		
		$message->content($content);
		
		return $message;
	}
	
	public function consume($message, $in)
	{
		switch( $in->key )
		{
			case 'red':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/color', array('color' => 'red'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'blue':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/color', array('color' => 'blue'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'purple':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/color', array('color' => 'purple'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'green':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/color', array('color' => 'green'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'white':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/color', array('color' => 'white'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'on':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'on'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;
			case 'off':
				com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'off'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
			break;		
			case 'blink':
				$result = com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb'), array(), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'GET');
				if( $result['power'] == 'on' )
				{
					com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'off'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
					sleep(1);
					com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'on'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
				}
				else
				{
					com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'on'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');
					sleep(1);
					com\busit\HTTP::send('https://api.lifx.com/v1beta1/lights/' . $this->config('bulb') . '/power', array('state' => 'off'), null, array('Authorization'=>'Bearer ' . $this->config('token')), 'PUT');					
				}
			break;
			case 'breathe':
				
			break;	
		}
	}
	
	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
	
	function fGetRGB($iH, $iS, $iV)
	{
        if($iH < 0)   $iH = 0; 
        if($iH > 360) $iH = 360;
        if($iS < 0)   $iS = 0;
        if($iS > 100) $iS = 100;
        if($iV < 0)   $iV = 0;
        if($iV > 100) $iV = 100;

        $dS = $iS/100.0;
        $dV = $iV/100.0;
        $dC = $dV*$dS;
        $dH = $iH/60.0;
        $dT = $dH;

        while( $dT >= 2.0 ) $dT -= 2.0;
        $dX = $dC*(1-abs($dT-1));

        switch( $dH )
		{
            case($dH >= 0.0 && $dH < 1.0):
                $dR = $dC; $dG = $dX; $dB = 0.0;
			break;
            case($dH >= 1.0 && $dH < 2.0):
                $dR = $dX; $dG = $dC; $dB = 0.0;
			break;
            case($dH >= 2.0 && $dH < 3.0):
                $dR = 0.0; $dG = $dC; $dB = $dX;
			break;
            case($dH >= 3.0 && $dH < 4.0):
                $dR = 0.0; $dG = $dX; $dB = $dC;
			break;
            case($dH >= 4.0 && $dH < 5.0):
                $dR = $dX; $dG = 0.0; $dB = $dC;
			break;
            case($dH >= 5.0 && $dH < 6.0):
                $dR = $dC; $dG = 0.0; $dB = $dX;
			break;
            default:
                $dR = 0.0; $dG = 0.0; $dB = 0.0;
			break;
        }

        $dM  = $dV - $dC;
        $dR += $dM; $dG += $dM; $dB += $dM;
        $dR *= 255; $dG *= 255; $dB *= 255;

        return round($dR).",".round($dG).",".round($dB);
    }
}

?>