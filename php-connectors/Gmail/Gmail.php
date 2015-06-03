<?php

define("__CLASSNAME__", "\\Gmail");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class Gmail implements cb\IConnector
{
	private $config;
	private $inputs;
	private $outputs;
	private $uid;
	private $messagelist;
	private $message;
	private $status = false;
	private $inbox;
	private $bodyPlain = '';
	private $bodyHtml = '';
	private $attachments = array();
	private $url;

	public function init($config, $inputs, $outputs)
	{
		$this->uid = $config['uid'];
		$this->config = $config;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function cron($message, $interfaceId)
	{
		$this->message = $message;
		$this->messagelist = new cb\MessageList();
		
		$this->url = 'https://apps.busit.com/google/mail/pull?type=mails';
			
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	
		
		$result = file_get_contents($this->url . '&config=' . urlencode(json_encode($this->config)));
		$emails = json_decode($result, true);

		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}-{$interfaceId}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('{$this->uid}-{$interfaceId}', '".time()."')";
			$mysql->insert($sql);
		}
		// end

		$emails = array_reverse($emails);
		if( count($emails) > 0 )
		{
			foreach( $emails as $e )
			{
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '{$this->uid}-{$interfaceId}' AND buffer_time >= '".strtotime($e['Date'])."'";
				$row = $mysql->selectOne($sql);
			
				if( !$row['buffer_identifier'] )
				{
					$type = new cb\KnownType();
					$type->wkid(13);
					$type->name('Email');
					$type->format('[@subject] from [@from] - [@content]');
					$type->compatibility(array(13));
					$type['subject'] = $e['Subject'];
					$type['from'] = $e['From'];
					$type['to'] = $e['To'];
					$type['message_id'] = $e['Message-ID'];

					if( !mb_check_encoding($e['body'], 'UTF-8') )
						$type['content'] = utf8_encode($e['body']);
					else
						$type['content'] = $e['body'];

					$type['timestamp'] = strtotime($e['Date']);
					$type['date'] =  date('Y-m-d H:i:s', strtotime($e['Date']));

					if( is_array($e['attachments']) )
					{
						foreach( $e['attachments'] as $a )
							$this->message->addAttachment($a['filename'], $this->urlsafe_b64decode($a['binary']));
					}
					
					$this->message->setKnownType($type);
					$this->messagelist[] = $this->message->duplicate();
					$this->status = true;
					
					$sql = "UPDATE buffer SET buffer_time = '".strtotime($e['Date'])."' WHERE buffer_identifier = '{$this->uid}-{$interfaceId}'";
					$mysql->update($sql);
				}
			}
		}
	}
	
	public function setInput($message, $interfaceId)
	{
		throw new Exception("Unsupported operation");
	}
	
	public function getOutput($interfaceId)
	{
		if( $this->status == true )
			return $this->messagelist;
		else
			return null;
	}
	
	public function urlsafe_b64decode($string)
	{
		$data = str_replace(array('-','_'), array('+','/'), $string);
		$mod4 = strlen($data) % 4;
		
		if( $mod4 )
			$data .= substr('====', $mod4);
		
		return base64_decode($data);
	}
}

?>