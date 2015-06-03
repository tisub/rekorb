<?php

define("__CLASSNAME__", "\\StockQuote");

use com\busit as cb;
use com\anotherservice\util as cau;
use com\anotherservice\db as cad;

class StockQuote extends com\busit\Connector implements com\busit\Producer
{
	public function produce($out)
	{
		$mysql = new cad\mysql('sql', 'mysql-DUXJyTvD', 'gdx1n6hc', 'mysql-DUXJyTvD');
		
		$url = 'http://query.yahooapis.com/v1/public/yql?q='.urlencode('select * from yahoo.finance.quotes where symbol = "' . $out->value . '"').'&format=json&diagnostics=true&env=http://datatables.org/alltables.env&callback=';
		$result = json_decode(file_get_contents($url), true);
		
		switch( $out->key )
		{
			case 'price':
				$number = $result['query']['results']['quote']['LastTradePriceOnly'];
				if( $number == 0 || strlen($number) == 0 )
					return null;
				$textFormat = "{{quote_name}} ({{quote_code}})\n\nLast trade price: {{price}}{{currency}}";
				$htmlFormat = "<strong>{{quote_name}} ({{quote_code}})</strong><br /><br />Last trade price: {{price}}{{currency}}";
			break;
			case 'changerealtime':
				$number = $result['query']['results']['quote']['ChangeRealtime'];
				if( $number == 0 || strlen($number) == 0 )
					return null;
				$textFormat = "{{quote_name}} ({{quote_code}})\n\nLast realtime change: {{change}}%";
				$htmlFormat = "<strong>{{quote_name}} ({{quote_code}})</strong><br /><br />Last realtime change: {{change}}%";
			break;
			case 'volume': 
				$number = $result['query']['results']['quote']['Volume'];
				if( $number == 0 || strlen($number) == 0 )
					return null;
				$textFormat = "{{quote_name}} ({{quote_code}})\n\nExchanged volume: {{volume}}";
				$htmlFormat = "<strong>{{quote_name}} ({{quote_code}})</strong><br /><br />Exchanged volume: {{volume}}";
			break;
			case 'changepercent':
				$number = $result['query']['results']['quote']['ChangeinPercent'];
				if( $number == 0 || strlen($number) == 0 )
					return null;
				$textFormat = "{{quote_name}} ({{quote_code}})\n\nVariation in percent: {{variation}}%";
				$htmlFormat = "<strong>{{quote_name}} ({{quote_code}})</strong><br /><br />Variation in percent: {{variation}}%";
			break;
		}
		
		$message = com\busit\Factory::message();
		$content = com\busit\Factory::content(15);
		
		if( $out->key != 'all' )
		{
			$content->textFormat($textFormat);
			$content->htmlFormat($htmlFormat);
			$content['number'] = $number;
		}
		
		$content['quote_name'] = $result['query']['results']['quote']['Name'];
		$content['quote_code'] = $result['query']['results']['quote']['symbol'];
		$content['currency'] = $result['query']['results']['quote']['Currency'];
		$content['price'] = $result['query']['results']['quote']['LastTradePriceOnly'];
		$content['change'] = $result['query']['results']['quote']['ChangeRealtime'];
		$content['volume'] = $result['query']['results']['quote']['Volume'];
		$content['variation'] = $result['query']['results']['quote']['ChangeinPercent'];
		$content['timestamp'] = time();
		$content['date'] = date('Y-m-d H:i:s', $content['timestamp']);
		
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