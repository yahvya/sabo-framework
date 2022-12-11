<?php

namespace Model\Cond;

use \Attribute;

use \Model\Interface\CondInterface;

#[Attribute]
class RowExistCond implements CondInterface
{
	private string $invalid_message;
	private string $class;

	private bool $can_be_null;

	private array $conditions;

	public function __construct(string $class,string $invalid_message,array $conditions = [],bool $can_be_null = false)
	{
		$this->invalid_message = $invalid_message;
		$this->class = $class;
		$this->can_be_null = $can_be_null;
		$this->conditions = $conditions;
	}

	public function is_valid(mixed $data):bool
	{
		$this->conditions["id"] = $data;

		return ($this->can_be_null && $data === NULL) || !empty($this->class::find($this->conditions) );
	}

	public function get_not_valid_message():string
	{
		return $this->invalid_message;
	}
}