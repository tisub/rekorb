<?php
namespace com\busit;

interface Consumer
{
	public function consume($message, $in);
	public function test();
}
?>