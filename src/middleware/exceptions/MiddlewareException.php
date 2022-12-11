<?php

namespace Middleware\Exception;

use \Exception;

use \Model\Exception\ModelException;

class MiddlewareException extends Exception
{
	public const TECHNICAL_ERROR = 1;
	public const BAD_DATA = 2;
	public const NOT_FOUND = 3;
	public const ALREADY_EXIST = 4;
	public const MISSED_DATA = 5;
	public const MODEL_EXCEPTION = 6;
	public const NOT_ALLOWED = 7;

	private int $error_code;

	private bool $is_displayable;

	public function __construct(int $error_code,?ModelException $model_exception = NULL,array $message_replaces = [],bool $is_displayable = true)
	{
		$default_messages = [
			self::TECHNICAL_ERROR => "Une erreur technique s'est produite, veuillez retenter l'opération",
			self::BAD_DATA => "Veuillez vérifier le format des données saisies",
			self::NOT_FOUND => "Non trouvé",
			self::ALREADY_EXIST => "Déjà existant",
			self::MISSED_DATA => "Il manque des données",
			self::NOT_ALLOWED => "Vous n'êtes pas autorisé à faire cette action"
		];

		foreach($message_replaces as $key => $replace)
			$default_messages[$key] = $replace;

		if($model_exception == NULL)
		{
			$this->is_displayable = $is_displayable;

			if(!empty($default_messages[$error_code]) )
			{
				$this->error_code = $error_code;

				parent::__construct($default_messages[$error_code]);
			}
			else
			{
				$this->error_code = self::TECHNICAL_ERROR;

				parent::__construct($default_messages[self::TECHNICAL_ERROR]);
			}
		}
		else
		{
			$this->is_displayable = $model_exception->is_displayable();
			$this->error_code = self::MODEL_EXCEPTION;

			parent::__construct($model_exception->getMessage() );
		}
	}

	public function get_error_code():int
	{
		return $this->error_code;
	}

	public function is_displayable():bool
	{
		return $this->is_displayable;
	}
} 