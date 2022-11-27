<?php

namespace Sabo;

abstract class ModelMaker extends AbstractCommand
{
	public static function exec_command(int $argc, array $argv, string $project_root_path):void
	{
		if($argc < 1)
		{
			self::print_args();

			return;
		}


		$model_name = strtolower($argv[0]);

		$basename = $model_name;

		// change name to correct format NameModel

		$model_name = implode("",array_map("ucfirst",explode("_",$model_name)) );

		if(str_ends_with($model_name,"model") )
			$model_name = str_replace("model","Model",$model_name);
		elseif(!str_ends_with($model_name,"Model") )
			$model_name .= "Model";

		$path = "{$project_root_path}src/model/model/{$model_name}.php";

		if(file_exists($path) )
		{
			self::print_tool_message("le model existe déja sur le chemin ({$path})",":(");

			return;
		}

		$models_model = @file_get_contents(__DIR__ . "/../resources/model_model.txt");

		$searches = ["{model_name}","{table_name}"];
		$replaces = [$model_name,$basename];

		if($models_model != false && @file_put_contents($path,str_replace($searches,$replaces,$models_model) ) )
			self::print_tool_message("le model a bien été crée sur le chemin ({$path})");
		else
			self::print_tool_message("une erreur s'est produite lors de la création",":(");
	}

	public static function print_args():void
	{
		self::print_tool_message("Liste des arguments");
		self::print_tool_message("nom du model","",true,false);
	}
}