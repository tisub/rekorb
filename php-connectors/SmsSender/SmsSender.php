<?php

define("__CLASSNAME__", "\\SmsSender");

use com\busit as cb;
use com\anotherservice\util as cau;

class SmsSender extends com\busit\Connector implements com\busit\Consumer
{	
	public function consume($message, $in)
	{
		$nic = 'xxx';
		$pass = 'xxx';
		$account = 'xxx';
		$from = 'xxx';

		$soap = new SoapClient('https://www.ovh.com/soapi/soapi-re-1.63.wsdl');
		$session = $soap->login($nic, $pass,'fr', false);
		
		$content = $message->content();
		if( $content->compatible(1) )
			$sms = $content['message'];
		else
			$sms = $content->toText();
		
		$sms = substr($sms, 0, 320);
		$number = $in->value;
		
		if( substr($number, 0, 1 ) === '0' && substr($number, 1, 2 ) != '0' ) {
			$number = preg_replace('/^' . preg_quote('0', '/') . '/', '+33', $number);
		}
		
		$soap->telephonySmsSend($session, $account, $from, $number, $sms, '', '1', '', '');
		$soap->logout($session);
	}
	
	public function test()
	{
		return true;
	}
}

?>