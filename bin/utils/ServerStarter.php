<?php

namespace Sabo;

class ServerStarter extends AbstractCommand
{
	public static function exec_command(int $argc, array $argv, string $project_root_path):void
	{
		$port = $argc >= 1 ? $argv[0] : "8000";
			
		exec("php -S 127.0.0.1:{$port}");
	}

	public static function print_args(): void
	{
		self::print_tool_message("Liste des arguments");
		self::print_tool_message("numéro du port (option)","",true,false);
	}
}