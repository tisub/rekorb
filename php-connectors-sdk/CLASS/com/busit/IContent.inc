<?php
namespace com\busit;

interface IContent extends \IteratorAggregate, \ArrayAccess, \Countable
{
	public function id($id);
	public function name($name);
	public function compatible($id, $value);
	public function compatibility($compat);
	public function textFormat($format);
	public function toText($format);
	public function htmlFormat($format);
	public function toHtml($format);
	public function toJson();
	public function merge($content);
}

?>