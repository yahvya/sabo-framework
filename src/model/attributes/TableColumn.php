<?php

namespace Model\Attribute;

use \Attribute;
use \Exception;

#[Attribute]
class TableColumn
{
	public const PRIMARY_KEY = 1;
	public const NULLABLE = 2;
	public const AUTO_INCREMENT = 3;
	public const TO_ERASE_IN_SERIALIZE = 4;

	private const HASHSABLE_TYPES = ["varchar","char","text"];

	private bool $is_primary;
	private bool $is_nullable;	
	private bool $can_be_hashed;
	private bool $is_auto_increment;
	private bool $is_serializable;

	private string $linked_col_name;

	public function __construct(string $linked_col_name,string $col_format,int... $options)
	{
		if(empty($col_format) )
			throw new Exception("the column $linked_col_name format can't  be empty");

		$col_data = array_map(fn(string $part):string => strtolower($part),explode(":",$col_format) );

		$this->is_primary = in_array(self::PRIMARY_KEY,$options);
		$this->is_auto_increment = in_array(self::AUTO_INCREMENT,$options);
		$this->is_nullable = !$this->is_primary && in_array(self::NULLABLE,$options);
		$this->linked_col_name = $linked_col_name;
		$this->can_be_hashed = in_array($col_data[0],self::HASHSABLE_TYPES);
		$this->is_serializable = !in_array(self::TO_ERASE_IN_SERIALIZE,$options);
	}

	public function get_is_primary():bool
	{
		return $this->is_primary;
	}
	public function get_is_nullable():bool
	{
		return $this->is_nullable;
	}

	public function get_can_be_hashed():bool
	{
		return $this->can_be_hashed;
	}

	public function get_is_auto_increment():bool
	{
		return $this->is_auto_increment;
	}

	public function get_linked_col_name():string
	{
		return $this->linked_col_name;
	}

	public function get_is_serializable():bool
	{
		return $this->is_serializable;
	}

	public function get_all():array
	{
		return [
			"is_primary" => $this->get_is_primary(),
			"is_nullable" => $this->get_is_nullable(),
			"can_be_hashed" => $this->get_can_be_hashed(),
			"is_auto_increment" => $this->get_is_auto_increment(),
			"is_serializable" => $this->get_is_serializable(),
			"linked_col_name" => $this->get_linked_col_name()
		];
	}
}