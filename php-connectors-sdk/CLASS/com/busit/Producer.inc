<?php
namespace com\busit;

interface Producer
{
	public function produce($out);
	public function sample($out);
	public function test();
}
?>