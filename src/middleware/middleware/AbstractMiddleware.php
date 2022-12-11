<?php

namespace Middleware\Middleware;

use \Middleware\Exception\MiddlewareException;

use \Model\Exception\ModelException;

abstract class AbstractMiddleware
{
	private $messages_replaces = [];

	public function __construct(array $messages_replaces = [])
	{
		$this->messages_replaces = $messages_replaces;
	}

	public function set_message_replaces(array $message_replaces):void
	{
		$this->messages_replaces = $message_replaces;
	}

	public function get_message_replaces():array
	{
		return $this->messages_replaces;
	}

	protected function throw_exception(int $error_code,?ModelException $model_exception = NULL,bool $is_displayable = true):void
	{
		throw new MiddlewareException($error_code,$model_exception,$this->messages_replaces,$is_displayable);
	}
}