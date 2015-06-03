<?php
namespace com\busit;

interface ConnectorHelper
{
	public function notifyOwner($message, $data);
	public function notifyUser($message);
	public function config($key);
	public function output($key);
	public function input($key);
	public function locale();
	public function id();
}

?>