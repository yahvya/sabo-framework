<?php

namespace Model\Attribute;

use \Attribute;

#[Attribute]
class TableName
{
	private string $table_name;

	public function __construct(string $table_name)
	{
		$this->table_name = $table_name;
	}

	public function get_table_name():string
	{
		return $this->table_name;
	}
}