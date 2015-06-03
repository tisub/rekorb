<?php

define("__CLASSNAME__", "\\ElaTagTemperature");

use com\anotherservice\util as cau;

class ElaTagTemperature extends com\busit\Connector implements com\busit\Producer,com\busit\Consumer
{
	private $data = '';
	
	public function produce($out)
	{
		$context = 	($this->data[0] == '[' ? 'In range' : 'Out of range');
		$level = 	hexdec(substr($this->data, 1, 2));
		$box = 		substr($this->data, -3, 2);
		$id = 		substr($this->data, 3, -6);
		$value = 	hexdec(substr($this->data, -6, 3));
		$warning = 	($value == 0x7FF);

		if( ($value & 0x800) > 0 )
		{
			$value &= 0x7FF;
			$value *= -1;
		}
		
		$value *= 0.0625;
		$value = round($value, 3);
		
		$m = com\busit\Factory::message();
		
		switch( $out->key )
		{
			case 'warning': 
				if( $warning )
				{
					$c = com\busit\Factory::content(0);
					$c['data'] = "Low battery";
					$c['alert'] = "Low battery";
					$c['subject'] = "ELA Tag Temperature - Alert";
					$c->textFormat('ELA RFID Tag Temperature - Alert\n\nTag ID: {{tag_id}}\nAlert: {{data}}');
					$c->htmlFormat('ELA RFID Tag Temperature - Alert<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Alert: <strong>{{data}}</strong>');
				}
				else
					return null;
			break;
			case 'level':
				if( strlen($level) == 0 )
					return null;
				$c = com\busit\Factory::content(25);
				$c['number'] = $level;
				$c['subject'] = "ELA Tag Temperature - Signal Strength";
				$c->textFormat('ELA RFID Tag Temperature - Signal Strength\n\nTag ID: {{tag_id}}\nSignal strength: {{strength}}dBm');
				$c->htmlFormat('ELA RFID Tag Temperature - Signal Strength<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong>');
			break;
			case 'reader':
				if( strlen($box) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $box;
				$c['number'] = $box;
				$c['subject'] = "ELA Tag Temperature - Reader Information";
				$c->textFormat('ELA RFID Tag Temperature - Reader Information\n\nTag ID: {{tag_id}}\nReader ID: {{data}}');
				$c->htmlFormat('ELA RFID Tag Temperature - Reader Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{data}}</strong>');				
			break;
			case 'context':
				if( strlen($context) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $context . ' of reader ' . $box;
				$c['subject'] = "ELA Tag Temperature - Context";
				$c->textFormat('ELA RFID Tag Temperature - Context\n\nTag ID: {{tag_id}}\nCurrent context: {{data}}');
				$c->htmlFormat('ELA RFID Tag Temperature - Context<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Current context: <strong>{{data}}</strong>');	
			break;
			case 'value':
				if( strlen($value) == 0 )
					return null;
				$c = com\busit\Factory::content(24);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Temperature - Value";
				$c->textFormat('ELA RFID Tag Temperature - Value\n\nTag ID: {{tag_id}}\nTemperature: {{temperature}}°C');
				$c->htmlFormat('ELA RFID Tag Temperature - Value<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Temperature: <strong>{{temperature}}°C</strong>');	
			break;
			case 'all':
				if( strlen($value) == 0 )
					return null;
				$c = com\busit\Factory::content(24);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Temperature - Information";
				$c->textFormat('ELA RFID Tag Temperature - Information\n\nTag ID: {{tag_id}}\nReader ID: {{reader}}\nContext: {{context}}\nSignal strength: {{strength}}dBm\nTemperature: {{temperature}}°C\nAlert: {{alert}}');
				$c->htmlFormat('ELA RFID Tag Temperature - Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{reader}}</strong><br />Context: <strong>{{context}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong><br />Temperature: <strong>{{temperature}}°C</strong><br />Alert: <strong>{{alert}}</strong>');
			break;
			default: return null;
		}
		
		$c['tag_id'] = $id;
		$c['reader'] = $box;
		$c['context'] = $context;
		$c['strength'] = $level;
		$c['alert'] = ($warning? 'Low battery' : 'None');
		$c['temperature'] = $value;
		$c['temperature_celsius'] = $value;
		$c['temperature_fahrenheit'] = $value*33.8;
		$c['timestamp'] = time();
		$c['date'] = date('Y-m-d H:i:s', $c['timestamp']);
		
		$m->content($c);
		return $m;
	}
	
	public function consume($message, $in)
	{
		$c = $message->content();
		$this->data = $c['data'];
	}
	
	public function sample($out)
	{
		$this->data = "[83061F9C01]";
		return $this->produce($out);
	}
	
	public function test()
	{
		return true;
	}
}

?>