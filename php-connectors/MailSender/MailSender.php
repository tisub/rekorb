<?php

define("__CLASSNAME__", "\\MailSender");

require_once('Mail.php');
require_once('Mail/mime.php');

use com\busit as cb;
use com\anotherservice\util as cau;

class MailSender extends com\busit\Connector implements com\busit\Consumer
{
	private $template = "
		<html>
			<head>
				<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
				<style type=\"text/css\"> 
					a { color: #005596; text-decoration: none; }
					a:hover { color: #0482e4; }
				</style>
			</head>
			<body style=\"background-color: #f5f5f5; font-family: Arial; font-size: 14px;\">
				<div style=\"padding: 20px;\">
					<div style=\"width: 90%; height: 50px; margin: 0 auto;\">
						<img src=\"{LOGO}\" alt=\"Logo Busit\" style=\"width: 100px;\" />
					</div>
				<div style=\"width: 90%; margin: 0 auto; padding: 10px; background-color: #ffffff; border: 1px solid #e5e5e5; margin-bottom: 10px;\">
					{CONTENT}
				</div>
				<div style=\"width: 90%; height: 50px; margin: 0 auto; color: #9f9f9f; font-size: 12px;\">
					Copyright &copy; 2015 Busit - <a href=\"https://twitter.com/Bus_IT\" class=\"normal\">Twitter</a> - <a href=\"https://facebook.com/busit.net\" class=\"normal\">Facebook</a>
				</div>
			</body>
		</html>";
	private $logo = "https://images.busit.com/logos/logo_busit.png";
	
	public function consume($message, $in)
	{
		$content = $message->content();
		if( $content->compatible(2) )
		{
			$subject = $content['subject'];
			if( $content['bodyHtml'] != null )
				$body = $content['bodyHtml'];
			else
				$body = $content['bodyText'];
		}
		else
		{
			if( isset($content['subject']) && strlen($content['subject']) > 0 )
				$subject = $content['subject'];
			else
				$subject = $this->config('subject');
			$body = $content->toHtml();
		}
		
		$mail = new Mail_mime();
		
		$body = $this->makeLinks($body);
		$body = str_replace(array('{LOGO}', '{CONTENT}'), array($this->logo, $body), $this->template);
		
		$mail->setHTMLBody($body);
		$mimeparams['text_encoding']="7bit"; 
		$mimeparams['text_charset']="UTF-8"; 
		$mimeparams['html_charset']="UTF-8"; 
		$mimeparams['head_charset']="UTF-8"; 
		
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;
			
			$handle = fopen('/tmp/' . $file['name'], 'w');
			fwrite($handle, $file['binary']);
			fclose($handle);
			
			$mail->addAttachment('/tmp/' . $file['name']);
		}
		
		$email = $mail->get($mimeparams);
		$extraheaders = array("From"=>"Busit Notification <notification@busit.com>", "Subject"=>$subject, "Content-Type"=>"text/html; charset=UTF-8");
		$headers = $mail->headers($extraheaders);
		$factory = Mail::factory("mail");
 
		$factory->send($in->value, $headers, $email);		
		
		foreach( $message->files() as $name => $binary )
			unlink('/tmp/' . $file['name']);
	}
	
	public function test()
	{
		return true;
	}
	
	private function makeLinks($str)
	{
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		$urls = array();
		$urlsToReplace = array();
		if( preg_match_all($reg_exUrl, $str, $urls) )
		{
			$numOfMatches = count($urls[0]);
			$numOfUrlsToReplace = 0;
			for( $i=0; $i<$numOfMatches; $i++ )
			{
				$alreadyAdded = false;
				$numOfUrlsToReplace = count($urlsToReplace);
				for( $j=0; $j<$numOfUrlsToReplace; $j++ )
				{
					if($urlsToReplace[$j] == $urls[0][$i])
						$alreadyAdded = true;
				}
				if( !$alreadyAdded )
					array_push($urlsToReplace, $urls[0][$i]);
			}
			
			$numOfUrlsToReplace = count($urlsToReplace);
			
			for( $i=0; $i<$numOfUrlsToReplace; $i++ )
				$str = str_replace($urlsToReplace[$i], "<a href=\"".$urlsToReplace[$i]."\">".$urlsToReplace[$i]."</a> ", $str);
			
			return $str;
		}
		else
			return $str;
	}
}

?>