<?php

namespace Model\Cond;

use \Model\Interface\CondInterface;

use \Attribute;

#[Attribute]
class ColumnCond
{
	private array $conds_list;

	private ?CondInterface $first_error;

	public function __construct(CondInterface $cond_interface,CondInterface... $more_cond_interfaces)
	{
		array_push($more_cond_interfaces,$cond_interface);

		$this->conds_list = $more_cond_interfaces;
		$this->first_error = NULL;
	}

	public function is_valid(mixed $data):bool
	{
		foreach($this->conds_list as $cond_interface)
		{
			if(!$cond_interface->is_valid($data) )
			{
				$this->first_error = $cond_interface;

				return false;
			}
		}

		return true;
	}

	public function get_error_message():?string
	{
		return $this->first_error != NULL ? $this->first_error->get_not_valid_message() : NULL; 
	}
}