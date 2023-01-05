<?php

namespace Sabo\Custom;

use \Twig\Extension\AbstractExtension;

use \Twig\TwigFunction;

use \Exception;

class JsRouteCustomExtensions extends AbstractExtension
{
    private RouteCustomExtensions $route_custom_extension;

    private bool $debug_mode;

    public function __construct(array $routes_names,bool $debug_mode,?RouteCustomExtensions $route_custom_extension = NULL)
    {
        $this->route_custom_extension = $route_custom_extension == NULL ? new RouteCustomExtensions($routes_names,$debug_mode) : $route_custom_extension;
        $this->debug_mode = $debug_mode;
    }

    public function getFunctions():array
	{
		return [
			new TwigFunction("jroutes",[$this,"get_routes"],["is_safe" => ["html"] ])
		];
	}

    public function get_routes(string $var_name,string|array $base_route_data,string|array ...$other_routes):string
    {
        $routes = [];

        array_push($other_routes,$base_route_data);

        foreach($other_routes as $route_data)
        {
            list($route_name,$replaces) = gettype($route_data) == "array" ? $route_data : [$route_data,[]];

            $routes[$route_name] = $this->route_custom_extension->get_route_from($route_name,$replaces,"The given js route << $route_name >> doesn't exist");
        }

        $json_routes = json_encode($routes);

        if($json_routes == false)
        {
            if(!$this->debug_mode)
                $json_routes = "{}";
            else
                throw new Exception("Failed to json encode routes");
        }
        else
            $json_routes = addslashes($json_routes);

        return <<<HTML
            <script>
                var {$var_name} = JSON.parse("{$json_routes}");
            </script>
        HTML;
    }
}