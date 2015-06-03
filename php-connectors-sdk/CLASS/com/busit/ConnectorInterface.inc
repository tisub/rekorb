<?php
namespace com\busit;

class ConnectorInterface
{
	public $key;
	public $value;
	public $name;
	
	public function __construct($id, $i)
	{
		$this->name = $id;
		$this->key = $i['key'];
		$this->value = $i['value'];
	}
}
?>