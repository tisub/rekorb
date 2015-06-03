<?php

define("__CLASSNAME__", "\\PdfCreate");

use com\anotherservice\util as cau;

class PdfCreate extends com\busit\Connector implements com\busit\Transformer
{
	private $upload_url = 'https://apps.busit.com/upload';
	
	public function transform($message, $in, $out)
	{
		$link = null;

		$content = $message->content();
		if( isset($content['link']) && strlen($content['link']) > 0 )
			$link = $content['link'];
		else if( isset($content['url']) && strlen($content['url']) > 0 )
			$link = $content['url'];
		
		$data = $content->toHtml();
		
		// transform all attachments
		foreach( $message->files() as $name => $binary )
		{
			$file['name'] = basename(str_replace("\\", "/", $name));
			$file['mime'] = 'application/octet-stream';
			$file['binary'] = $binary;		
			$url = com\busit\HTTP::send($this->upload_url, array(), $file);	
			shell_exec("wkhtmltopdf --print-media-type {$url} /tmp/{$file['name']}.pdf");
			$filecontent = file_get_contents("/tmp/{$file['name']}.pdf");
			$message->file("{$file['name']}.pdf", $filecontent);
			unlink("/tmp/{$file['name']}.pdf");
		}
		
		// transform links
		if( $link != null )
		{
			$urlsanitized = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $link);
			shell_exec("wkhtmltopdf --print-media-type {$link} /tmp/{$urlsanitized}.pdf");
			$filecontent = file_get_contents("/tmp/{$urlsanitized}.pdf");
			$message->file("{$urlsanitized}.pdf", $filecontent);
			unlink("/tmp/{$urlsanitized}.pdf");
		}
		else
		{
			// transform content
			$name = 'content_' . date('YmdHis') . uniqid('_');
			$file['name'] = $name . '.html';
			$file['mime'] = 'text/plain';
			$file['binary'] = $data;
			$url = com\busit\HTTP::send($this->upload_url, array(), $file);
			shell_exec("wkhtmltopdf --print-media-type {$url} /tmp/{$name}.pdf");
			$filecontent = file_get_contents("/tmp/{$name}.pdf");
			$message->file("{$name}.pdf", $filecontent);
			unlink("/tmp/{$name}.pdf");
		}

		return $message;		
	}
	
	public function test()
	{
		return true;
	}	
}

?>