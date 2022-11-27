<?php

namespace Sabo\Custom;

use \Twig\Extension\AbstractExtension;

use \Twig\TwigFunction;

use \Exception;

class RouteCustomExtensions extends AbstractExtension
{
	private array $routes_list;

	private bool $debug_mode;

	public function __construct(array $routes_list,bool $debug_mode)
	{
		$this->routes_list = $routes_list;
		$this->debug_mode = $debug_mode;
	}

	public function getFunctions():array
	{
		return [
			new TwigFunction("route",[$this,"get_route_from"])
		];
	}

	public function get_route_from(string $route_name,array $replaces = []):string
	{
		$route = !empty($this->routes_list[$route_name]) ? $this->routes_list[$route_name] : "";

		foreach($replaces as $to_replace => $replace)
			$route = str_replace("{{$to_replace}}",$replace,$route);

		if(empty($route) && $this->debug_mode)
			throw new Exception("Route $route_name not exist");

		if(!empty($route) && $route[0] != "/")
			$route = "/{$route}";

		return $route;
	}
}