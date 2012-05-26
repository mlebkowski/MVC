<?php
//
//  Hello!. Copy me to project root. 
//  

	require 'vendor/autoload.php';

// 
//  Configure other autoloaders
//

	use Nassau\Config\Config
		, Nassau\MVC\FrontController
		, Nassau\MVC\Application
		, Nassau\MVC\Exception\NotFound
		, Nassau\MVC\Exception\Forbidden
		, Nassau\MVC\Exception\ServerError
		, Symfony\Component\HttpFoundation\Request
	;

	$env = null;
	if (isset($_SERVER['Env'])) {
		$env = $_SERVER['Env'];
	} elseif (file_exists('etc/env')) {
		$env = trim(file_get_contents('etc/env'));
	} else {
		throw new Exception('Put your environment type into `etc/env` file');
	}
	
	// TODO: SettingsManager
	$config = new Config('etc/application.yaml');
  
	if ('dev' === $env) {
		ini_set('log_errors', false);
		ini_set('display_errors', true);
		ini_set('html_errors', false);
		error_reporting(E_ALL | E_NOTICE);
	} else {
		ini_set('log_errors', true);
		ini_set('display_errors', false);
		error_reporting(E_ALL ^ E_NOTICE);
	}

	switch(PHP_SAPI):
	case 'cli':
		// TODO: php://input is $_POST
		// host can be set by absolute uri
		$request = Request::create($_SERVER['argv'][1]);
		break;
	default:
		$request = Request::createFromGlobals();
		break;
	endswitch;
	
	try {
	
		$fc = new FrontController($config, new Application($env, 'Example'));
		
		$fc->ed->addListener(FrontController::EVENT_INIT_ROUTER, function ($event) {
			$defaults = array (
				'layout' => 'index',
				'controller' => 'Web',
			);
			
			$router = $event->getArgument('fc')->router;
			$routes = $router->getRoutes();
			$array = $routes->toArray();
			foreach ($array as &$route) {
				foreach ($defaults as $key => $value) {
					if (false === array_key_exists($key, $route)) {
						$route[$key] = $routes->read('Default/' . $key, $value);
					}
				}
			}
			$router->setRoutes(new Config($array));
		});
		
		$fc->init();
		$fc->run($request);
		
	} catch (NotFound $e) {
  		throw $e;
	} catch (Forbidden $e) {
		throw $e;
	} catch (Exception $e) {
		throw $e;
	}	
