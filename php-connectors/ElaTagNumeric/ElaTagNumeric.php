<?php

define("__CLASSNAME__", "\\ElaTagNumeric");

use com\anotherservice\util as cau;

class ElaTagNumeric extends com\busit\Connector implements com\busit\Producer,com\busit\Consumer
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
		
		$m = com\busit\Factory::message();
		switch( $out->key )
		{
			case 'warning': 
				if( $warning )
				{
					$c = com\busit\Factory::content(0);
					$c['data'] = "Low battery";
					$c['alert'] = "Low battery";
					$c['subject'] = "ELA Tag Numeric - Alert";
					$c->textFormat('ELA RFID Tag Numeric - Alert\n\nTag ID: {{tag_id}}\nAlert: {{data}}');
					$c->htmlFormat('ELA RFID Tag Numeric - Alert<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Alert: <strong>{{data}}</strong>');
				}
				else
					return null;
			break;
			case 'level':
				if( strlen($level) == 0 )
					return null;
				$c = com\busit\Factory::content(25);
				$c['number'] = $level;
				$c['subject'] = "ELA Tag Numeric - Signal Strength";
				$c->textFormat('ELA RFID Tag Numeric - Signal Strength\n\nTag ID: {{tag_id}}\nSignal strength: {{strength}}dBm');
				$c->htmlFormat('ELA RFID Tag Numeric - Signal Strength<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong>');
			break;
			case 'reader':
				if( strlen($box) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $box;
				$c['number'] = $box;
				$c['subject'] = "ELA Tag Numeric - Reader Information";
				$c->textFormat('ELA RFID Tag Numeric - Reader Information\n\nTag ID: {{tag_id}}\nReader ID: {{data}}');
				$c->htmlFormat('ELA RFID Tag Numeric - Reader Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{data}}</strong>');				
			break;
			case 'context':
				if( strlen($context) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $context . ' of reader ' . $box;
				$c['subject'] = "ELA Tag Numeric - Context";
				$c->textFormat('ELA RFID Tag Numeric - Context\n\nTag ID: {{tag_id}}\nCurrent context: {{data}}');
				$c->htmlFormat('ELA RFID Tag Numeric - Context<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Current context: <strong>{{data}}</strong>');	
			break;
			case 'value':
				if( strlen($value) == 0 )
					return null;
				$c = com\busit\Factory::content(29);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Numeric - Value";
				$c->textFormat('ELA RFID Tag Numeric - Value\n\nTag ID: {{tag_id}}\nNumeric input value: {{numeric_value}}');
				$c->htmlFormat('ELA RFID Tag Numeric - Value<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Numeric input value: <strong>{{numeric_value}}</strong>');	
			break;
			case 'all':
				if( strlen($value) == 0 )
					return null;
				$c = com\busit\Factory::content(29);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Numeric - Information";
				$c->textFormat('ELA RFID Tag Numeric - Information\n\nTag ID: {{tag_id}}\nReader ID: {{reader}}\nContext: {{context}}\nSignal strength: {{strength}}dBm\nNumeric input value: {{numeric_value}}\nAlert: {{alert}}');
				$c->htmlFormat('ELA RFID Tag Numeric - Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{reader}}</strong><br />Context: <strong>{{context}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong><br />Numeric input value: <strong>{{numeric_value}}</strong><br />Alert: <strong>{{alert}}</strong>');
			break;
			default: return null;
		}
		
		$c['tag_id'] = $id;
		$c['reader'] = $box;
		$c['context'] = $context;
		$c['strength'] = $level;
		$c['alert'] = ($warning? 'Low battery' : 'None');
		$c['numeric_value'] = $value;
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
		$this->data = "[9C87D17F01]";
		return $this->produce($out);
	}
	
	public function test()
	{
		return true;
	}
}

?>