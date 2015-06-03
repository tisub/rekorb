<?php
namespace com\busit;

interface IFactory
{
	public function content($id);
	public function message();
	public function messageList();
	public function template($message);
}

?>