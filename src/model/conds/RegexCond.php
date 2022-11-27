<?php

namespace Model\Cond;

use \Model\Interface\CondInterface;

class RegexCond implements CondInterface
{
	private string $regex;
	private string $invalid_message;
	private string $regex_options;

	public function __construct(string $regex,string $invalid_message,string $regex_options = "")
	{
		$this->regex = $regex;
		$this->invalid_message = $invalid_message;
		$this->regex_options = $regex_options;
	}

	public function is_valid(mixed $data):bool
	{
		return preg_match("#{$this->regex}#{$this->regex_options}",$data);
	}

	public function get_not_valid_message():string
	{
		return $this->invalid_message;
	}
}