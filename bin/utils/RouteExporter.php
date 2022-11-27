<?php

namespace Sabo;

abstract class RouteExporter extends AbstractCommand
{
	public static function exec_command(int $argc, array $argv, string $project_root_path): void
	{
		$complete_path = $argc >= 1 ? $argv[0] : "{$project_root_path}export_routes.json";

		require_once("{$project_root_path}/vendor/autoload.php");

		$routes = require_once("{$project_root_path}config/routes.php");

		if(@file_put_contents($complete_path,json_encode($routes,JSON_PRETTY_PRINT) ) )
			self::print_tool_message("les routes ont bien été exportés vers le chemin ({$complete_path})");
		else
			self::print_tool_message("une erreur s'est produite lors de l'export des routes");
	}

	public static function print_args(): void
	{
		self::print_tool_message("Liste des arguments");
		self::print_tool_message("chemin complet de destination (optionnel)","",true,false);
	}
}