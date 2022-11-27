<?php

namespace Sabo\Sabo;

use \Exception;
use \ReflectionMethod;

use \Sabo\Interface\ShowableInterface;
use \Sabo\Interface\MaintenanceManagerInterface;

use \Sabo\Helper\FileHelper;
use \Sabo\Helper\WordHelper;

use \Sabo\Custom\RouteCustomExtensions;

use \Model\Model\AbstractModel;

class Router
{
	public const CLASSIC_ENV = 0;
	public const JSON_ENV = 1;

	// the router will look to config/routes.php and config/env_file
	public function __construct(
		?ShowableInterface $internal_error_page_manager = NULL,
		?ShowableInterface $page_not_found_manager = NULL,
		?MaintenanceManagerInterface $maintenance_manager = NULL,
		bool $debug_mode = false
	)
	{
		// try to read the config file

		$config_file_content = NULL;

		switch(CONFIG_FILE_TYPE)
		{
			case self::CLASSIC_ENV:
				$config_file_content = FileHelper::convert_env_file_to_array(ROOT . "config/.env");
			; break;

			case self::JSON_ENV:
				$file_content = @file_get_contents(ROOT . "config/env.json");

				if($file_content != false)
					$config_file_content = @json_decode($file_content,true);
			; break;
		}

		if(empty($config_file_content) || !isset($config_file_content['maintenance']) || !WordHelper::check_bool($config_file_content['maintenance']) )
			$this->internal_error($debug_mode,"Failed to read config file or missed maintenance state",$internal_error_page_manager);

		$_ENV = $config_file_content;

		// if website is in maintenance
		if($config_file_content['maintenance'])
		{
			if($maintenance_manager != NULL)
			{
				if(!$maintenance_manager->can_continue_in_website() )
				{
					$maintenance_manager->show_page();

					die();
				}
			}
			else $this->show_default_page("Site en maintenance","Le site est actuellement en maintenance, il sera bientôt disponible !");
		}

		// try to init database connexion
		if(!AbstractModel::init_con($debug_mode) )
			$this->internal_error($debug_mode,"Failed to init database connexion",$internal_error_page_manager);

		$routes = @include(ROOT . "config/routes.php");

		if(!$routes)
			$this->internal_error($debug_mode,"Failed to load routes",$internal_error_page_manager);

		$this->start_site($routes,$page_not_found_manager,$debug_mode);
	}

	private function start_site(array $routes,?ShowableInterface $page_not_found_manager,bool $debug_mode):void
	{
		$request_method = strtolower($_SERVER["REQUEST_METHOD"]);

		if(array_key_exists($request_method,$routes["routes"]) )
		{
			// find the matched routes

			foreach($routes["routes"][$request_method] as $route_data)
			{
				list(
					"route" => $route,
					"route_regex" => $route_regex,
					"controller_class" => $controller_class,
					"method_name" => $method_name
				) = $route_data;

				if(preg_match("#^{$route_regex}$#i",$_SERVER['REQUEST_URI'],$uri_matches) )
				{
					$args = [];
					$method_params = [];

					// get args for the method to call

					if(preg_match_all("#{[a-zA-Z\_]+\}#",$route,$route_matches) )
					{
						$uri_matches = array_slice($uri_matches,1);

						foreach($route_matches[0] as $key => $match)
							$method_params[substr($match,1,-1)] = $uri_matches[$key];
					}

					$reflection_method = new ReflectionMethod($controller_class,$method_name);

					// put the methods args in order

					foreach($reflection_method->getParameters() as $reflection_parameter)
					{
						$arg_name = $reflection_parameter->getName();

						if(isset($method_params[$arg_name]) )
							array_push($args,urldecode($method_params[$arg_name]) );
					}

					// call the controller and the method with the args from url
					call_user_func_array(
						[new $controller_class($routes["routes_names"],$debug_mode),$method_name],
						$args
					);

					die();
				}
			}
		}

		// if requested url is not found
		
		if($page_not_found_manager != NULL)
		{
			$page_not_found_manager->show_page();

			die();
		}
		else $this->show_default_page("Page non trouvé","La page que vous cherché n'a pas été trouvé.");
	}

	private function internal_error(int $debug_mode,string $message,?ShowableInterface $internal_error_page_manager):void
	{
		if($debug_mode)
			throw new Exception($message);

		if($internal_error_page_manager != NULL)
		{
			$internal_error_page_manager->show_page();

			die();
		}

		$this->show_default_page("Erreur interne","Une erreur s'est produite, veuillez rafraîchir la page.");
	}

	private function show_default_page(string $title,string $message):void
	{
		echo <<<HTML
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="utf8">
				<meta name="robots" content="noindex">
				<title>{$title}</title>
			</head>
			<body>
				<h1 style="font-family: arial;font-size: 16px;text-align: center;">{$message}</h1>
			</body>
			</html>
		HTML;

		die();
	}
}