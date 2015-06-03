<?php

define("__CLASSNAME__", "\\MailReceiver");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class MailReceiver extends com\busit\Connector implements com\busit\Producer, com\busit\Consumer
{
	private $inbox;
	private $bodyPlain = '';
	private $bodyHtml = '';
	private $attachments = array();

	public function produce($out)
	{
		$messagelist = com\busit\Factory::messageList();	
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');	
		
		$hostname = '{'.$this->config('hostname').':993/imap/ssl}INBOX';
		try
		{
			$this->inbox = imap_open($hostname, $this->config('username'), $this->config('password'));

			if( !$this->inbox )
				throw new Exception("Connection failed");
		}
		catch( Exception $e )
		{
			$this->notifyUser("The IMAP credentials are not correct, login failed or server unavailable");
			return null;
		}
		
		$date = date('d M Y', strToTime('-5 days'));
		$emails = imap_search($this->inbox, "SINCE \"{$date}\"");

		// first time
		$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
		$row = $mysql->selectOne($sql);
		
		if( !$row['buffer_identifier'] )
		{
			$sql = "INSERT INTO buffer (buffer_identifier, buffer_time) VALUES ('" . $this->id() . "-{$out->name}', '".time()."')";
			$mysql->insert($sql);
		}
		// end

		if( $emails )
		{
			rsort($emails);
			foreach( $emails as $e )
			{
				$overview = imap_fetch_overview($this->inbox, $e, 0);
				$structure = imap_fetchstructure($this->inbox, $e);
				$data = $this->getContent($structure, $e);
				
				$sql = "SELECT * FROM buffer WHERE buffer_identifier = '" . $this->id() . "-{$out->name}' AND buffer_time >= '".strtotime($overview[0]->date)."'";
				$row = $mysql->selectOne($sql);
			
				if( !$row['buffer_identifier'] )
				{
					$message = com\busit\Factory::message();
					$content = com\busit\Factory::content(2);

					$content['subject'] = $overview[0]->subject;
					$content['from'] = $overview[0]->from;
					if( !mb_check_encoding($content['body'], 'UTF-8') )
						$content['bodyHtml'] = utf8_encode($data['body']);
					else
						$content['bodyHtml'] = $data['body'];
					$content['timestamp'] = strtotime($overview[0]->date);
					$content['date'] =  date('Y-m-d H:i:s', strtotime($overview[0]->date));
					
					if( is_array($data['attachments']) )
					{
						foreach( $data['attachments'] as $a )
							$message->file($a['filename'], $a['binary']);
					}
					
					$message->content($content);
					$messagelist[] = $message;
					
					$sql = "UPDATE buffer SET buffer_time = '".strtotime($overview[0]->date)."' WHERE buffer_identifier = '" . $this->id() . "-{$out->name}'";
					$mysql->update($sql);
				}
			}
		}
		
		imap_close($this->inbox);
		
		return $messagelist;
	}
	
	public function consume($message, $in)
	{
		return;
	}

	public function sample($out)
	{
		return null;
	}
	
	public function test()
	{
		return true;
	}
	
	function getContent($content, $mid, $part = null, $skip_parts = false)
	{
		if( is_null($part) )
		{
			$this->bodyHtml = '';
			$this->bodyPlain = '';
			$this->attachments = array();
		}
		else
		{
			if( substr($part, 0, 1) == '.' )
				$part = substr($part, 1);
		}
	 
		$actualpart = $part;
		$split = explode('.', $actualpart);
	 
		if( is_array($skip_parts) )
		{
			foreach ($skip_parts as $p)
				array_splice($split, $p, 1);
		
			$actualpart = implode('.', $split);
		}

		$data = imap_fetchbody($this->inbox, $mid, empty($actualpart) ? 1 : $actualpart);
		switch( $content->encoding )
		{
			case '3':
				$data = base64_decode($data);
			break;
			case '4':
				$data = quoted_printable_decode($data);
			break;
		}
			
		if(strtolower($content->subtype) == 'rfc822')
		{
			if( !is_array($skip_parts) )
				$skip_parts = array();
			
			array_push($skip_parts, count($split));
		}
	 
		if( $content->type == 0 && $data )
		{
			if (strtolower($content->subtype) == 'plain' )
				$this->bodyPlain .= trim($data) ."\n\n";
			else
				$this->bodyHtml .= $data ."<br><br>";
		}
	
		if( isset($content->ifdparameters) && $content->ifdparameters == 1 && isset($content->dparameters) && is_array($content->dparameters) && $data )
		{
			foreach( $content->dparameters as $object )
			{
				if( isset($object->attribute) && preg_match('~filename~i', $object->attribute) )
				{
					$this->attachments[] = array(
						'type'          => (isset($content->subtype)) ? $content->subtype : '',
						'encoding'      => $content->encoding,
						'part'          => empty($actualpart) ? 1 : $actualpart,
						'filename'      => $object->value,
						'binary'		=> $data
					);
				}
			}
		}

		else if( isset($content->ifparameters) && $content->ifparameters == 1 && isset($content->parameters) && is_array($content->parameters) && $data )
		{
			foreach( $content->parameters as $object )
			{
				if( isset($object->attribute) && preg_match('~name~i', $object->attribute) )
				{
					$this->attachments[] = array(
						'type'          => (isset($content->subtype)) ? $content->subtype : '',
						'encoding'      => $content->encoding,
						'part'          => empty($actualpart) ? 1 : $actualpart,
						'filename'      => $object->value,
						'binary'		=> $data
					);
				}
			}
		}
	
		if( isset($content->parts) && count($content->parts) > 0 )
		{
			foreach ($content->parts as $key => $parts)
				$this->getContent($parts, $mid, ($part.'.'.($key + 1)), $skip_parts);
		}
		
		if( strlen($this->bodyPlain) > 0 )
			$body = $this->bodyPlain;
		else
			$body = $this->bodyHtml;
			
		return array('body'=>$body, 'attachments'=>$this->attachments);
	}
}

?>