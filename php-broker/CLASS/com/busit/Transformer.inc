<?php
namespace com\busit;

interface Transformer
{
	public function transform($message, $in, $out);
	public function test();
}
?>