<?php

namespace Sabo;

abstract class ControllerMaker extends AbstractCommand
{
	public static function exec_command(int $argc, array $argv, string $project_root_path): void
	{
		if($argc < 1)
		{
			self::print_args();

			return;
		}

		$controller_name = $argv[0];

		// change name to correct format NameController

		$controller_name[0] = strtoupper($controller_name[0]);

		if(str_ends_with($controller_name,"controller") )
			$controller_name = str_replace("controller","Controller",$controller_name);
		elseif(!str_ends_with($controller_name,"Controller") )
			$controller_name .= "Controller";

		$path = "{$project_root_path}src/controller/controller/{$controller_name}.php";

		if(file_exists($path) )
		{
			self::print_tool_message("le controller existe déja sur le chemin ({$path})",":(");

			return;
		}

		$controllers_model = @file_get_contents(__DIR__ . "/../resources/controller_model.txt");

		if($controllers_model != false && @file_put_contents($path,str_replace("{controller_name}",$controller_name,$controllers_model) ) )
			self::print_tool_message("le controller a bien été crée sur le chemin ({$path})");
		else
			self::print_tool_message("une erreur s'est produite lors de la création",":(");
	}

	public static function print_args():void
	{
		self::print_tool_message("Liste des arguments");
		self::print_tool_message("nom du controller","",true,false);
	}
}