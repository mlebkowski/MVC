<?php namespace Nassau\MVC;

use Nassau\Config\Config
	, Nassau\Routing\Matcher
	, Symfony\Component\HttpFoundation\Request
	, Nassau\MVC\Exception\NotFound
	;

class FrontController {

	public $params = array ();
	
	protected $app;
	protected $request;
	protected $controller;
	protected $config;	
	protected $database;
	protected $router;
	protected $view;
	
	protected $services = array ();
	protected $managers = array ();
	protected $blocks = array ();

	public $route;
	
	public $types = array('service', 'manager', 'block');

	function __construct(Config $config, Application $app) {
		
		$this->config = $config;
		$this->app = $app;
	}
	/*
		Dodam event dispatchera (DI), ktory bedzie odpalal eventa
		po kazdym skonfigurowanym komponencie!
		
		->initRouter
			->dispatchEvent('init-routing');
		->initView
			->addEventListener('dispatch', setView);
		->initController
			->addEventListener('dispatch', processRoute);
		->run(Request);
	*/
	public function init() {
		$this->initRouter();
		$this->initView();
		$this->initController();
		
	}
	
	
	protected $defaultLayout, $defaultController;
	protected function initRouter() {
		$routesPath = $this->config->read('Paths/Routes', 'etc/routes.yaml');

		$routes = new Config($routesPath);
		
			$this->defaultLayout = $routes->read('Default/layout', 'index');
			$this->defaultController = $routes->read('Default/Controller', 'Web');
		
		$this->router = new Matcher($routes);
	}

	public function run(Request $request) {
		$this->request = $request;
		
		try {
			list ($REQ) = explode("?", $request->getRequestUri());
			$this->route = $this->router->match($REQ);
			
			$this->dispatch();
			// dispatch!
		} catch (\Exception $e) {
			throw new NotFound('Requested resoruce could not be found', 404);
		}
		
		$action = parse_url($this->route['route']['action']);
		
		$static_params = array();
		parse_str($action['query'], $static_params);

		// all params, get, post, route
		$this->params = array_merge(
			$request->query->all(),
			$request->request->all(),
			$static_params,
			$this->route['params']
		);
		
		$action = $this->buildAction($action['path']);
		$this->executeAction($action);
	}	
	
	public function getAppNamespace()
	{
		$ns = func_get_args();
		$ns = array_map("ucfirst", $ns);
		
		array_unshift($ns, $this->app->ns);
		array_unshift($ns, null);
		
		return implode("\\", $ns);
	}
	
	public function initView() {
		$classname = $this->getAppNamespace('View');
		$this->view = new $classname($this);
	}
	
	public function dispatch() {
		$this->dispatchView();
		$this->dispatchController();
	}
	
	public function dispatchView() {
		$this->view->setFormat($this->route['format']);
		$this->view->setTitle($this->route['route']['title']);
		$this->view->params = $this->route['params'];
		$layout = $this->route['route']['layout'];
		if (is_null($layout)) {
			$layout = $this->defaultLayout;
		}
		$this->view->setLayout($layout);
		$this->view->setPage($this->route['route']['page']);
	}


	public function dispatchController() {
		$name = $this->route['route']['controller'];
		if (is_null($name)) {
			$name = $this->defaultController;
		}
		$this->controller = $this->buildController($name);

	// this is weird:
		$this->controller->processRoute($this->route['route']);
	}

	public function executeAction(Action $action) {
		$result = $action -> execute();
		
		switch ($result):
		case Action::RESULT_RENDER:
		default:
			echo $this->view->execute();
		endswitch;
		
	}
	
	public function __get($key) {
		if (property_exists($this, $key)) {
			return $this->$key;
		}
	}
	
	public function getService($name) {
		if (false === isset($this->services[$name])) {
			$classname = $this->getAppNamespace("Service", $name);
			$this->services[$name] = new $classname($this);
		}
		return $this->services[$name];
	}
	
	public function getManager($name) {
		if (false === isset($this->managers[$name])) {
			$this->managers[$name] = $this->buildManager($name);
		}
		return $this->managers[$name];
	}
	
	public function getBlock($name) {
		return null;
	}
	
	public function isPost () {
		return strtoupper($this->request->getMethod()) == 'POST';	
	}
	
	public function setParam($name, $value) {
		$this->params[$name] = $value;	
	}
	
	public function getParam($name, $default = null) {
		if (array_key_exists($name, $this->params)) {
			$default = $this->params[$name];
		}	
		return $default;
	}
	public function __call($name, $arguments) {
		if ('build' === substr($name, 0, strlen('build'))) {
			$name = substr($name, strlen('build'));
			$name = strtolower($name);
			return $this->build($name, $arguments[0]);
		}
	}
	
	public function build($type, $name = null) {
		$type = strtolower($type);
		if (false === in_array($type, Component::$types)) {
			throw new \InvalidArgumentException('Invalid component type: ' . $type);	
		}
		
		$name = $name ?: $type;

		$classname = $this->getAppNamespace($type, $name);
		
		$component = new $classname($this, $this->getDatabase());

	// dependency injection	
		$component->setConfig($this->config);
		$component->setView($this->view);
	// session
	// 

		$component->init();
		return $component;
	}
	
	public function getDatabase() {
		if (null === $this->database) {
			$DB = 'Database/' . ucfirst(strtolower($this->app->env));
			$dbName = $this->config->read($DB . '/Name');
			if ('' == $dbName) return null;
			
			$this->database = new Database(
				'mysql:host=localhost;dbname=' . $dbName,
				$this->config->read($DB . '/User'), $this->config->read($DB . '/Password')
			);
		}
		
		return $this->database;
	}
	
}
