<?php

namespace Sabo\Sabo;

use \Exception;

abstract class Route
{
	public const AUTHORIZED_METHODS = ["get","post","put","delete"];

	public static function get(string $route,string $controller_class,string $method_name,string $route_name):array
	{
		return self::get_route_from("get",$route,$controller_class,$method_name,$route_name);
	}

	public static function post(string $route,string $controller_class,string $method_name,string $route_name):array
	{
		return self::get_route_from("post",$route,$controller_class,$method_name,$route_name);
	}

	public static function put(string $route,string $controller_class,string $method_name,string $route_name):array
	{
		return self::get_route_from("put",$route,$controller_class,$method_name,$route_name);
	}
	
	public static function delete(string $route,string $controller_class,string $method_name,string $route_name):array
	{
		return self::get_route_from("delete",$route,$controller_class,$method_name,$route_name);
	}

	public static function multiple(string $methods,string $route,string $controller_class,string $method_name,array $route_names):array
	{

		$results = [];

		$methods = explode(",",$methods);

		if(count($methods) != count($route_names) )	
			throw new Exception("Each methods must have a linked name");

		foreach($methods as $key => $method)
		{
			$method = strtolower($method);

			if(!in_array($method,self::AUTHORIZED_METHODS) )
				throw new Exception("unknown method given");

			array_push($results,self::get_route_from($method,$route,$controller_class,$method_name,$route_names[$key]) );
		}

		return $results;
	}

	public static function group(string $group_link_prefix,array $routes_list):array
	{	
		if(substr($group_link_prefix,-1) != "/")
			$group_link_prefix .= "/";

		$group = [];

		foreach($routes_list as $route_data)
		{
			if(!empty($route_data["method"]) )
			{
				array_push($group,self::get_route_from(
					$route_data["method"],
					empty($route_data["route"]) ? substr($group_link_prefix,0,-1) : $group_link_prefix . $route_data["route"],
					$route_data["controller_class"],
					$route_data["method_name"],
					$route_data["route_name"]
				) );
			}
			else $group = array_merge($group,self::group($group_link_prefix,$route_data) );
		}

		return $group;
	}

	public static function generate_from(array $routes_data_list):array
	{
		$results = [
			"routes_names" => [],
			"routes" => [
				"post" => [],
				"get" => [],
				"put" => [],
				"delete" => []
			] 
		];

		foreach($routes_data_list as $route_data)
		{
			if(!isset($route_data["route_name"]) )
			{
				$result = self::generate_from($route_data);

				$results["routes_names"] = array_merge($results["routes_names"],$result["routes_names"]);
				$results["routes"]["post"] = array_merge($results["routes"]["post"],$result["routes"]["post"]);
				$results["routes"]["get"] = array_merge($results["routes"]["get"],$result["routes"]["get"]);
				$results["routes"]["put"] = array_merge($results["routes"]["put"],$result["routes"]["put"]);
				$results["routes"]["delete"] = array_merge($results["routes"]["delete"],$result["routes"]["delete"]);
			}
			else
			{
				if(!empty($results["routes_names"][$route_data["route_name"] ]) )
					throw new Exception("Two routes can't have the same name << {$route_data["route_name"]} >>");

				$results["routes_names"][$route_data["route_name"] ] = $route_data["route"];

				$method = $route_data["method"];

				unset($route_data["method"]);
				unset($route_data["route_name"]);

				array_push($results["routes"][$method],$route_data);
			}
		}

		return $results;
	}

	private static function get_route_from(string $method,string $route,string $controller_class,string $method_name,string $route_name):array
	{	
		$route = preg_replace("#\/\/#","/",$route);

		if(!str_starts_with($route,"/") )
			$route = "/{$route}";

		while(str_ends_with($route,"/") )
			$route = substr($route,0,- 1);

		$route_data["route"] = $route;

		return [
			"method" => $method,
			"route_name" => $route_name,
			"route" => $route,
			"route_regex" => str_replace("?","\?",preg_replace("#\{[a-zA-Z\_]+\}#","(.+)",$route) ) . "\/?",
			"controller_class" => $controller_class,
			"method_name" => $method_name
		];
	}
}
