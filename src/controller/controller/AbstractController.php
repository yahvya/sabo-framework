<?php

namespace Controller\Controller;

use \Sabo\Custom\RouteCustomExtensions;
use \Sabo\Custom\AssetCustomExtension;

use \Twig\Loader\FilesystemLoader;

use \Twig\Environment;

abstract class AbstractController
{
	protected string $view_start_path = ROOT . "views/templates/";

	protected string $cache_path = ROOT . "views/cache/";

	private bool $debug_mode;

	private FilesystemLoader $twig_loader;

	private Environment $twig_environment;

	private RouteCustomExtensions $route_custom_extension;

	public function __construct(array $routes_names,bool $debug_mode)
	{
		$this->twig_loader = new FilesystemLoader($this->view_start_path);
		$this->twig_environment = new Environment($this->twig_loader,[
            'debug' => $debug_mode,
            'charset' => 'UTF-8',
            'autoescape' => 'html',
            'cache' => $debug_mode ? false : $this->cache_path
        ]);     
        $this->route_custom_extension = new RouteCustomExtensions($routes_names,$debug_mode);
        $this->twig_environment->addExtension($this->route_custom_extension);
        $this->debug_mode = $debug_mode;
	}	

	protected function render(string $file,array $view_data = []):void
	{
		$file_parts = explode("/",$file);

		$dirpath = str_replace(ROOT,"",$this->view_start_path) . implode("/",array_slice($file_parts,0,count($file_parts) - 1) );

		if(!str_ends_with($dirpath,"/") )
			$dirpath .= "/";

		$this->twig_environment->addExtension(new AssetCustomExtension($dirpath,$this->debug_mode) );

		die($this->twig_environment->render($file,$view_data));
	}

	protected function redirect(string $link = "/"):never
	{
		if($link[0] != "/")
			$link = "/{$link}";

		header("Location: $link");

		die();
	}

	protected function route(string $route_name,array $replaces = []):string
	{
		return $this->route_custom_extension->get_route_from($route_name,$replaces);
	}

	protected function get_twig_environment():Environment
	{
		return $this->twig_environment;
	}

	protected function get_debug_mode():bool
	{
		return $this->debug_mode;
	}

	protected function get_route_custom_extension():RouteCustomExtensions
	{
		return $this->route_custom_extension;
	}
}