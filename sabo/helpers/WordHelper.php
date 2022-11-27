<?php

namespace Sabo\Helper;

abstract class WordHelper
{
	public static function check_bool(mixed &$to_match):bool
	{
		$false_matches = ["false","False","FALSE",false];
		$true_matches = ["true","True","TRUE",true];

		if(in_array($to_match,$false_matches) )
		{
			$to_match = false;

			return true;
		}
		elseif(in_array($to_match,$true_matches) )
		{
			$to_match = true;

			return true;
		}
		else return false;
	}
}