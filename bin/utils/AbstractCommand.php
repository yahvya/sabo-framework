<?php

namespace Sabo;

abstract class AbstractCommand
{
    public const TOOL_NAME = "sabo";

    private static $commands_map = [
        "--help" => [
            "class" => self::class,
            "method" => "print_help",
            "description" => "Affiche la liste des commandes"
        ]
    ];

    // public static functions

    /**
        @format command_class must be a subclass of abstract command 
    */
    public static function add_command(string $command,string $command_class,string $description):bool
    {
        if(!is_subclass_of($command_class,self::class) )
            return false;

        self::$commands_map[str_replace(" ",":",$command)] = [
            "class" => $command_class,
            "description" => $description,
            "method" => "exec_command"
        ];

        return true;
    }

    public static function print_tool_message(string $message,string $smiley = " ;)",bool $add_tab = false,bool $with_name = true)
    {
        echo $add_tab ? "\n\t<< " : "\n<< ";

        if($with_name)
            echo self::TOOL_NAME;

        echo "$smiley $message >>\n";
    }

    public static function print_help(int $argc):void
    {
        self::print_tool_message("voici la liste des commandes de sabo (entrez --args pour afficher les arguments d'une commande)");

        foreach(self::$commands_map as $command => $command_data)
            self::print_tool_message("$command ({$command_data["description"]})","|",true);
    }

    public static function find_and_exec_command(int $argc,array $argv,string $project_root_path):void
    {
        if($argc < 2)
        {
            self::print_tool_message("veuillez saisir une commande (entrez --help pour afficher la liste des commandes)");

            return;
        }

        array_shift($argv);

        $command_name = strtolower(array_shift($argv) );

        $argc -= 2;

        if(!empty(self::$commands_map[$command_name]) )
        {
            list("class" => $command_class,"method" => $method_to_call,) = self::$commands_map[$command_name];

            if(!str_ends_with($project_root_path,"/") )
                $project_root_path .= "/";

            call_user_func_array([$command_class,$argc >= 1 && $argv[0] == "--args" ? "print_args" : $method_to_call],[$argc,$argv,$project_root_path]);
        }
        else self::print_tool_message("commande non trouvé (entrez --help pour afficher la liste des commandes)",":(");
    }

    public static function exec_command(int $argc,array $argv,string $project_root_path):void
    {
        self::print_tool_message("la commande fournis ne peut pas s'exécuter",":(");
    }

    public static function print_args():void
    {
        self::print_tool_message("cette commande ne prend pas d'arguments");
    }
}