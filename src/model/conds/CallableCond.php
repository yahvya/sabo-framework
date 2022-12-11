<?php

namespace Model\Cond;

use \Model\Interface\CondInterface;

class CallableCond implements CondInterface
{
    private array $callable;
    
    private string $invalid_message; 

    public function __construct(array $callable,string $invalid_message)
    {
        $this->callable = $callable;
        $this->invalid_message = $invalid_message;
    }

    public function is_valid(mixed $data):bool
    {
        return call_user_func_array($this->callable,[$data]);
    }

    public function get_not_valid_message():string
    {
        return $this->invalid_message;
    }
}