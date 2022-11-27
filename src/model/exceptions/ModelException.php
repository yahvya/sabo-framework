<?php

namespace Model\Exception;

use \Exception;

class ModelException extends Exception
{
	private bool $is_displayable;
	
	public function __construct(string $message,bool $is_displayable = true)
	{
		parent::__construct($message);

		$this->is_displayable = $is_displayable;
	}

	public function is_displayable():bool
	{
		return $this->is_displayable;
	}	
} 