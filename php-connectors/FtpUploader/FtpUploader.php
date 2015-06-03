<?php

define("__CLASSNAME__", "\\FtpUploader");

use com\busit as cb;
use com\anotherservice\util as cau;

class FtpUploader extends com\busit\Connector implements com\busit\Consumer
{
	public function consume($message, $in)
	{
		$res = @ftp_connect($this->config('server')); 
		if( $res === false )
		{
			$this->notifyUser("Unable to connect to server (". $this->config('server') .")");
			return;
		}
		
		if( @ftp_login($res, $this->config('username'), $this->config('password')) === false )
		{
			@ftp_close($res);
			$this->notifyUser("Invalid username (". $this->config('username') .") or password (". $this->config('password') .")");
			return;
		}

		if( @ftp_pasv($res) === false )
		{
			@ftp_close($res);
			throw new Exception("Failed to use passive mode");
		}
		
		if( @ftp_chdir($res, $in->value) === false )
		{
			@ftp_close($res);
			throw new Exception("Failed to change directory");
		}
		
		// add message content as attachment
		$content = $message->content();
		$text = $message->content->toText();
		
		if( strlen($text) > 0 )
			$message->file('content_' . date('YmdHis') . uniqid('_') . '.txt', $text);
		
		foreach( $message->files() as $name => $binary )
		{
			$filename = basename(str_replace("\\", "/", $name));
			
			$tmp = tmpfile();
			if( $tmp === false )
			{
				@ftp_close($res);
				throw new Exception("Impossible to create local temporary file to upload");
			}
			
			if( fwrite($tmp, $binary) === false )
			{
				@ftp_close($res);
				@fclose($tmp);
				throw new Exception("Impossible to populate temporary file to upload");
			}
			
			if( rewind($tmp) === false )
			{
				@ftp_close($res);
				@fclose($tmp);
				throw new Exception("Impossible to reset temporary file to upload");
			}
			
			if( ftp_fput($res, $filename, $tmp, FTP_BINARY) === false )
			{
				@ftp_close($res);
				@fclose($tmp);
				throw new Exception("Failed to upload temporary file");
			}
			
			@fclose($tmp);
		}
		
		@ftp_close($res);
	}
	
	public function test()
	{
		return true;
	}
}

?>