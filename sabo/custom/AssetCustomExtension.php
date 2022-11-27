<?php

namespace Sabo\Custom;

use \Twig\Extension\AbstractExtension;

use \Twig\TwigFunction;

use \Exception;

class AssetCustomExtension extends AbstractExtension
{
	private string $dirpath;

	private bool $debug_mode;

	private CONST AUTHORIZED_TYPES = ["css","js"];

	public function __construct(string $dirpath,bool $debug_mode)
	{
		$this->dirpath = str_ends_with($dirpath,"/") ? $dirpath : $dirpath ; "/";
		$this->debug_mode = $debug_mode;
	}

	public function getFunctions():array
	{
		return [
			new TwigFunction("asset",[$this,"get_asset"],["is_safe" => ["html"] ])
		];
	}

	public function get_asset(string $type,string $filename,bool $is_private = false):string
	{
		$type = strtolower($type);

		if(!in_array($type,self::AUTHORIZED_TYPES) )
		{
			if($this->debug_mode)
				throw new Exception("unknow asset type given << $type >>");
			else
				$type = self::AUTHORIZED_TYPES[0];
		}

		switch($type)
		{
			case "css" : 
				if(file_exists(ROOT . "{$this->dirpath}css/{$filename}.css") )
				{
					return <<<HTML
						<link rel="stylesheet" href="/{$this->dirpath}css/{$filename}.css">
					HTML;
				}
				elseif(file_exists(ROOT . "public/css/{$filename}.css") )
				{
					return <<<HTML
						<link rel="stylesheet" href="/public/css/{$filename}.css">
					HTML;
				}
				elseif($this->debug_mode) throw new Exception("css file << $filename >> not found");
			break;

			case "js" :

				$file_path = NULL; 
				
				if(file_exists(ROOT . "{$this->dirpath}js/{$filename}.js") )
					$file_path = "{$this->dirpath}js/{$filename}.js";	
				elseif(file_exists(ROOT . "public/js/{$filename}.js") )
					$file_path = "public/js/{$filename}.js";
				else if($this->debug_mode)
					throw new Exception("js file $filename not found");

				if($file_path != NULL)
				{
					if(!$is_private)
					{
						return <<<HTML
							<script src="/{$file_path}" defer></script>
						HTML;
					}
					else
					{
						$js_file_content = @file_get_contents(ROOT . $file_path);

						if($js_file_content != false)
						{
							return <<<HTML
								<script>{$js_file_content}</script>
							HTML;
						}
						elseif($this->debug_mode) throw new Exception("failed to load js file");
					}
				}

			; break;
		}

		return "";
	}
}