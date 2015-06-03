<?php
namespace com\busit;

interface IMessage
{
	public function file($name, $data);
	public function files();
	public function content($data);
	public function clear();
	public function copy();
	public function from();
	public function to();
}

?>