<?php
namespace com\busit\local;
use com\busit as cb;

class Content extends \ArrayObject implements cb\IContent
{
	private $_id = -1;
	public function id($id=null) { if( $id == null ) return $this->_id; else $this->_id = $id; }
	
	private $_name = "";
	public function name($name=null) { if( $name == null ) return $this->_name; else $this->_name = $name; }
	
	private $_compat = array();
	public function compatible($id, $value=null)
	{
		if( $value == null )
			return $this->_id == $id || in_array($id, $this->_compat);
		else
		{
			if( !$value )
			{
				if(($key = array_search($value, $this->_compat)) !== false)
					unset($this->_compat[$key]);
			}
			else if( !$this->compatible($id) )
				$this->_compat[] = $id;
		}
	}
	public function compatibility($compat=null) { if( $compat ) $this->_compat = $compat; else return $this->_compat; }

	private $_textFormat = "";
	public function textFormat($format=null) { if( $format == null ) return $this->_textFormat; else $this->_textFormat = $format; }
	
	private $_htmlFormat = "";
	public function htmlFormat($format=null) { if( $format == null ) return $this->_htmlFormat; else $this->_htmlFormat = $format; }
	
	public function toText($format=null)
	{
		if( $format == null ) $format = $this->_textFormat;
		if( $format == null || strlen($format) == 0 )
			return $this->toJson();

		$self = $this;
		$text = preg_replace_callback("/\\{\\{(.+?)\\}\\}/s", function($matches) use ($self)
		{
			if( $matches[1] == null || strlen($matches[1]) == 0 )
				return "";
			return $this->getReplacementValue($matches[1], $this);
		}, $format);
		
		return $this->noscriptHtml($text);
	}
	
	public function toHtml($format=null)
	{
		if( $format == null ) $format = $this->_htmlFormat;
		if( $format == null || strlen($format) == 0 )
			return '<pre>'.preg_replace(array("/&/", "/</"), array("&amp;", "&lt;"), $this->toText()).'</pre>';
	
		$self = $this;
		$text = preg_replace_callback("/\\{\\{(.+?)\\}\\}/s", function($matches) use ($self)
		{
			if( $matches[1] == null || strlen($matches[1]) == 0 )
				return "";
			return preg_replace(array("/&/", "/</"), array("&amp;", "&lt;"), $this->getReplacementValue($matches[1], $this));
		}, $format);
		
		return $this->noscriptHtml($text);
	}
	
	public function toJson()
	{
		$json = array();
		$json["id"] = $this->id();
		$json["name"] = $this->name();
		$json["compatibility"] = $this->compatibility();
		$json["textFormat"] = $this->textFormat();
		$json["htmlFormat"] = $this->htmlFormat();
		$json["data"] = array();
		
		foreach( $this as $k=>$v )
			$json["data"][$k] = $v;

		$j = json_encode($json, JSON_UNESCAPED_UNICODE);
		if( $j === false )
			cau\Logger::fine(json_last_error_msg());
		
		return $j;
	}
	
	public function __construct($t=false)
	{
		parent::__construct();

		if( $t === null )
		{
			$this->_id = 0;
			$this->_name = "Data";
			$this["data"] = null;
			$this->_textFormat = "{{data}}";
			$this->_htmlFormat = "<pre>{{data}}</pre>";
		}
		else if( $t instanceof cb\IContent )
		{
			$this->id($t->id());
			$this->name($t->name());
			$this->textFormat($t->textFormat());
			$this->htmlFormat($t->htmlFormat());
			$this->compatibility($t->compatibility());
			
			foreach( $t as $key=>$value )
				$this[$key] = $value;
		}
		else if( is_string($t) )
		{
			$json = json_decode($t, true);
			if( $json == null )
			{
				$this->_id = 0;
				$this->_name = "Data";
				$this["data"] = $t;
				$this->_textFormat = "{{data}}";
				$this->_htmlFormat = "<pre>{{data}}</pre>";
			}
			else
				$this->importFrom($json);
		}
	}
	
	public function importFrom($json)
	{
		if( $json == null )
			throw new \Exception("input parameter cannot be null");
		
		if( !isset($json["data"]) || !is_array($json["data"]) )
			throw new \Exception("Minimum requirement for Content is {\"data\": {}}");
		
		if( isset($json["id"]) && $json["id"] !== null )
			$this->id($json["id"]);
		if( isset($json["name"]) && $json["name"] !== null )
			$this->name($json["name"]);
		if( isset($json["textFormat"]) && $json["textFormat"] !== null )
			$this->textFormat($json["textFormat"]);
		if( isset($json["htmlFormat"]) && $json["htmlFormat"] !== null )
			$this->htmlFormat($json["htmlFormat"]);
		
		$compat = array();
		if( isset($json["compatibility"]) && is_array($json["compatibility"]) )
			foreach( $json["compatibility"] as $id )
				$compat[] = $id;
		$this->compatibility($compat);
		
		foreach( $json["data"] as $key=>$value )
			$this[$key] = $value;
	}
	
	public function merge($c)
	{
		foreach( $c->compatibility() as $id )
			$this->compatible($id, true);
		$this->compatible($c->id(), true);
		foreach( $c as $key=>$value )
			$this[$key] = $value;
	}
	
	private function getReplacementValue($key, $root)
	{
		try
		{
			if( $key == null || strlen($key) == 0 )
				return ($root == null ? "" : "" . $root);
			
			if( !is_array($root) && !($root instanceof \ArrayAccess))
				return "";
			
			if( isset($root[$key]) )
				return ($root[$key] != null ? $root[$key] : "");
			
			$i = strpos($key, '.');
			if( $i < 0 || $i === false ) return "";
			if( isset($root[substr($key, 0, i)]) )
				return getReplacementValue(substr($key, i+1), $root[substr($key, 0, i)]);
			return "";
		}
		catch(\Exception $e)
		{
			return "";
		}
	}
	
	private function noscriptHtml($html)
	{
		if( $html == null ) return "";
		
		return preg_replace(array(
			"/<\\s*script(:?.*?<\\s*\\/\\s*script\\s*>|[^>]*?\\/\\s*>)/is",
			"/<\\s*object(:?.*?<\\s*\\/\\s*object\\s*>|[^>]*?\\/\\s*>)/is",
			"/<\\s*embed(:?.*?<\\s*\\/\\s*embed\\s*>|[^>]*?\\/\\s*>)/is"), "", $html);
	}
}

?>