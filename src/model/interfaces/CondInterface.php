<?php

namespace Model\Interface;

interface CondInterface
{
	public function is_valid(mixed $data):bool;
	public function get_not_valid_message():string;
}