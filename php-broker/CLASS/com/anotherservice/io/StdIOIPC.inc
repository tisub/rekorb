<?php
namespace com\anotherservice\io;

use com\anotherservice\util as cau;

class StdIOIPC
{
	const MAX_LENGTH = 1073741824; // 1GB
	const CONTROL_SEQUENCE = "#BUSIT-STDIOIPC#";
	
	private $in;
	private $out;
	
	public function __construct($in, $out)
	{
		$this->in = $in;
		$this->out = $out;
	}
	
	public function sendAny($data) { $this->send(json_encode($data)); }
	public function send($data)
	{
		$length = strlen($data);
		if( $length > self::MAX_LENGTH ) throw new \Exception("Input data too big");
		
		// first send control sequence
		if( self::CONTROL_SEQUENCE != null && strlen(self::CONTROL_SEQUENCE) > 0 )
			fwrite($this->out, self::CONTROL_SEQUENCE, strlen(self::CONTROL_SEQUENCE));

		// then send the length as 32bit int
		fwrite($this->out, chr(($length >> 24) & 0xff));
		fwrite($this->out, chr(($length >> 16) & 0xff));
		fwrite($this->out, chr(($length >> 8) & 0xff));
		fwrite($this->out, chr(($length >> 0) & 0xff));
		fflush($this->out);
		
		// then send the data
		fwrite($this->out, $data, $length);
		fflush($this->out);
	}
	
	
	public function receiveAny() { return json_decode($this->receive(), true); }
	public function receive()
	{
		if( self::CONTROL_SEQUENCE != null && strlen(self::CONTROL_SEQUENCE) > 0 &&
			self::CONTROL_SEQUENCE != self::readExactly($this->in, strlen(self::CONTROL_SEQUENCE)) )
				throw new \Exception("Invalid control sequence");
				
		$raw_length = self::readExactly($this->in, 4);
		$length = 0 
			| ((ord($raw_length[0]) & 0xff) << 24) 
			| ((ord($raw_length[1]) & 0xff) << 16)
			| ((ord($raw_length[2]) & 0xff) << 8)
			| ((ord($raw_length[3]) & 0xff) << 0);
		
		if( $length < 0 || $length > self::MAX_LENGTH )
			throw new \Exception("Input data too big");

		return self::readExactly($this->in, $length);
	}
	
	public function close()
	{
		try { fclose($this->out); } catch(\Exception $e) { }
		try { fclose($this->in); } catch(\Exception $e) { }
	}
	
	// ===================================
	//
	// STREAM READER
	//
	// ===================================
	
	public static function readExactly($in, $length)
	{
		$data = self::readAtMost($in, $length);
		
		if( strlen($data) != $length )
			throw new \Exception("Could not read exactly " . $length . " bytes");
		
		return $data;
	}
	
	public static function readAtMost($in, $length)
	{
		$data = '';

		for( $l = 0; $l < $length; $l = strlen($data) )
			$data .= fread($in, $length - $l);

		return $data;
	}
}
?>