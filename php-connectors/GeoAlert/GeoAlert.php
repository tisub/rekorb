<?php
define("__CLASSNAME__", "\\GeoAlert");

use com\busit as cb;
use com\anotherservice\util as cau;

class GeoAlert extends com\busit\Connector implements com\busit\Transformer
{
	private $lat;
	private $long;
	
	public function transform($message, $in, $out)
	{
		$content = $message->content();
		
		if( isset($content['lat']) && strlen($content['lat']) > 0 && isset($content['long']) && strlen($content['long']) > 0 )
		{
			$this->lat = $m['lat'];
			$this->long = $m['long'];
		}
		else if( isset($content['gprmc']) && strlen($content['gprmc']) > 0 )
		{
			$this->parseGPRMC($content['gprmc']);
		}
		else
		{
			// try to parse a GPRMC
			$text = $content->toText();
			preg_match("/^|\\s(\\$GPRMC,[^\\s]+)\\s|$/is", $text, $matches);
			$this->parseGPRMC($matches[1]);
		}
		
		if( $this->lat === null ||  $this->long === null || !is_numeric($this->lat) || !is_numeric($this->long) || abs($this->lat) > 90 || abs($this->long) > 180 )
		{
			$this->notifyUser("No valid GPS coordinates in the message");
			return null;
		}
		
		if( $this->config('shape') == null || strlen($this->config('shape')) == 0 )
			throw new \Exception("Invalid configuration");
		
		$relation = $this->esriIntersect($this->lat, $this->long, $this->config('shape'));
		
		if( $out->key == 'inside' )
		{
			if( count($relation) > 0 ) return $message;
			else return null;
		}
		else if( $out->key == 'outside' )
		{
			if( count($relation) == 0 ) return $message;
			else return null;
		}
		else
		{
			$m = new Memcache();
			if( $m->connect('bi-001.vlan-101', 11211) === false ) return null;
			
			$previous = $m->get($this->id());
			$current = (count($relation) > 0 ? 'IN' : 'OUT');
			
			if( $previous != $current )
			{
				$m->set($this->id(), $current, 0, 360000); // 100 hours validity
				$m->close();
			}
			else
			{
				$m->close();
				return null;
			}

			if( $previous === false )
				return null;
			else if( $previous == 'IN' && $current == 'OUT' && ($out->key == 'leave' || $out->key == 'cross') )
				return $message;
			else if( $previous == 'OUT' && $current == 'IN' && ($out->key == 'enter' || $out->key == 'cross') )
				return $message;
		}
		
		return null;
	}
	
	public function test()
	{
		return true;
	}	
	
	private function parseGPRMC($gprmc)
	{
		if( $gprmc == null || strlen($gprmc) == 0 )
			return;
		
		//$GPRMC,092750.000,A,5321.6802,N,00630.3372,W,0.02,31.66,280511,,,A*43
		$gprmc = explode(',', $gprmc);
		if( count($gprmc) != 13 )
			return;

		$this->lat = intval(substr($gprmc[3], 0, 2)) + (intval(substr($gprmc[3], 2)) * 60);
		$this->long = intval(substr($gprmc[5], 0, 3)) + (intval(substr($gprmc[5], 3)) * 60);
	}
	
	private function esriIntersect($latitude, $longitude, $geometry)
	{
		$x = $this->long_4326_to_x_102100($longitude);
		$y = $this->lat_4326_to_y_102100($latitude);

		$g1 = "{\"geometryType\":\"esriGeometryPoint\",\"geometries\":[{\"x\":{$x},\"y\":{$y},\"spatialReference\":{\"wkid\":102100}}]}";
		$g2 = "{\"geometryType\":\"esriGeometryPolygon\",\"geometries\":[{$geometry}]}";
		$params = array('f'=>'json', 'relation'=>'esriGeometryRelationWithin', 'sr'=>102100, 'geometries1'=>$g1, 'geometries2'=>$g2);
		
		$request = array( 'http' => array( 'user_agent' => 'PHP/5.x (Busit) API/1.0', 'method' => 'POST', 'timeout' => 10.0 ));
		$request['http']['content'] = http_build_query($params);
		$request['http']['header']  = 'Content-Length: ' . strlen($request['http']['content']) . "\r\n";
		$request['http']['header'] .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";

		try
		{
			$fh = fopen('https://tasks.arcgisonline.com/ArcGIS/rest/services/Geometry/GeometryServer/relation', 'r', false, stream_context_create( $request ));
			if( $fh === false )
				throw new \Exception("Communication error");

			$response = stream_get_contents($fh);
			fclose($fh);
			$response = json_decode($response, true);
			if( isset($response['error']) )
				throw new \Exception($response['error']['message']);
			if( !isset($response['relations']) )
				throw new \Exception("Unexpected geometry service response");
			return $response['relations'];
		}
		catch(\Exception $e)
		{
			throw new \Exception("Geometry service error : " . $e->getMessage());
		}
	}
	
	private function lat_4326_to_y_102100($latitude)
	{
		if( $latitude > 90 || $latitude < -90 )
			return $latitude;

		$sin = sin($latitude * 0.0174532);
		$y = 6378137/2 * log( (1+$sin)/(1-$sin) );
		return $y;
	}
	
	private function long_4326_to_x_102100($longitude)
	{
		if( $longitude > 180 || $longitude < -180 )
			return $longitude;

		$x = $longitude * 0.017453292519943 * 6378137;
		return $x;
	}
}
?>