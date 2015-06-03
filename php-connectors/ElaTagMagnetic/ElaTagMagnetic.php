<?php

define("__CLASSNAME__", "\\ElaTagMagnetic");

use com\anotherservice\util as cau;

class ElaTagMagnetic extends com\busit\Connector implements com\busit\Producer,com\busit\Consumer
{
	private $data = '';
	private $previous = '';
	
	public function produce($out)
	{
		$context = 	($this->data[0] == '[' ? 'In range' : 'Out of range');
		$level = 	hexdec(substr($this->data, 1, 2));
		$box = 		substr($this->data, -3, 2);
		$id = 		substr($this->data, 3, -6);
		$value = 	hexdec(substr($this->data, -6, 3));
		$warning = 	($value == 0x7FF);
		$status =   ($value==1? 'Opened' : 'Closed');
		
		$m = com\busit\Factory::message();
		switch( $out->key )
		{
			case 'warning': 
				if( $warning )
				{
					$c = com\busit\Factory::content(0);
					$c['data'] = "Low battery";
					$c['alert'] = "Low battery";
					$c['subject'] = "ELA Tag Magnetic Contact - Alert";
					$c->textFormat('ELA RFID Tag Magnetic Contact - Alert\n\nTag ID: {{tag_id}}\nAlert: {{data}}');
					$c->htmlFormat('ELA RFID Tag Magnetic Contact - Alert<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Alert: <strong>{{data}}</strong>');
				}
				else
					return null;
			break;
			case 'level':
				if( strlen($level) == 0 )
					return null;
				$c = com\busit\Factory::content(25);
				$c['number'] = $level;
				$c['subject'] = "ELA Tag Magnetic Contact - Signal Strength";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Signal Strength\n\nTag ID: {{tag_id}}\nSignal strength: {{strength}}dBm');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Signal Strength<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong>');
			break;
			case 'reader':
				if( strlen($box) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $box;
				$c['number'] = $box;
				$c['subject'] = "ELA Tag Magnetic Contact - Reader Information";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Reader Information\n\nTag ID: {{tag_id}}\nReader ID: {{data}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Reader Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{data}}</strong>');				
			break;
			case 'context':
				if( strlen($context) == 0 )
					return null;
				$c = com\busit\Factory::content(0);
				$c['data'] = $context . ' of reader ' . $box;
				$c['subject'] = "ELA Tag Magnetic Contact - Context";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Context\n\nTag ID: {{tag_id}}\nCurrent context: {{data}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Context<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Current context: <strong>{{data}}</strong>');	
			break;
			case 'value':
				$c = com\busit\Factory::content(21);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Magnetic Contact - Status";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Status\n\nTag ID: {{tag_id}}\nCurrent status: {{switch_status}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Status<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Current status: <strong>{{switch_status}}</strong>');
			break;
			case 'all':
				$c = com\busit\Factory::content(21);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Magnetic Contact - Information";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Information\n\nTag ID: {{tag_id}}\nReader ID: {{reader}}\nContext: {{context}}\nSignal strength: {{strength}}dBm\nCurrent status: {{switch_status}}\nAlert: {{alert}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Information<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Reader ID: <strong>{{reader}}</strong><br />Context: <strong>{{context}}</strong><br />Signal strength: <strong>{{strength}}dBm</strong><br />Current status: <strong>{{switch_status}}</strong><br />Alert: <strong>{{alert}}</strong>');
			break;
			case 'open':				
				if( $this->previous == 'Opened' || $status == 'Closed' || $warning == true )
					return null;
				$c = com\busit\Factory::content(21);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Magnetic Contact - Opened";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Opened\n\nTag ID: {{tag_id}}\nStatus changed: {{switch_status}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Opened<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Status changed: <strong>{{switch_status}}</strong>');	
			break;
			case 'close':
				if( $this->previous == 'Closed' || $status == 'Opened' || $warning == true )
					return null;
				$c = com\busit\Factory::content(21);
				$c['number'] = $value;
				$c['subject'] = "ELA Tag Magnetic Contact - Closed";
				$c->textFormat('ELA RFID Tag Magnetic Contact - Closed\n\nTag ID: {{tag_id}}\nStatus changed: {{switch_status}}');
				$c->htmlFormat('ELA RFID Tag Magnetic Contact - Closed<br /><br />Tag ID: <strong>{{tag_id}}</strong><br />Status changed: <strong>{{switch_status}}</strong>');	
			break;
			default: return null;
		}
		
		$c['switch_name'] = $id;
		$c['tag_id'] = $id;
		$c['reader'] = $box;
		$c['context'] = $context;
		$c['strength'] = $level;
		$c['alert'] = ($warning? 'Low battery' : 'None');
		$c['switch_status'] = $status;
		$c['timestamp'] = time();
		$c['date'] = date('Y-m-d H:i:s', $c['timestamp']);
		
		$m->content($c);
		return $m;
	}
	
	public function consume($message, $in)
	{
		$c = $message->content();
		$this->data = $c['data'];
		
		$value = hexdec(substr($this->data, -6, 3));
		$status = ($value==1? 'Opened' : 'Closed');
		
		$m = new Memcache();
		$m->connect('bi-001.vlan-101', 11211);
		$this->previous = $m->get($this->id());
		$m->set($this->id(), $status, 0, 360000); // 100 hours validity
		$m->close();
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