<?php namespace Nassau\MVC;

use Nassau\Config\Config
	, Nassau\Routing\Matcher
	, Symfony\Component\HttpFoundation\Request
	, Nassau\MVC\Exception\NotFound
	;

class FrontController {

	public $params = array ();
	
	protected $env;

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

	function __construct(Request $request, Config $config, $env = 'dev') {
		
		$this->request = $request;
		$this->config = $config;
		$this->env = $env;
		
		$routesPath = $this->config->read('Paths/Routes', 'etc/routes.yaml');

		$routes = new Config($routesPath);
		$this->router = new Matcher($routes);

		try {
			list ($REQ) = explode("?", $request->getRequestUri());
			$this->route = $this->router->match($REQ);
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
		
		
// refactor		
// view
		$this->view = new \App\View($this);
		$this->view->setFormat($this->route['format']);
		$this->view->setTitle($this->route['route']['title']);
		$this->view->params = $this->route['params'];
		$layout = $this->route['route']['layout'];
		if (is_null($layout)) {
			$layout = $routes->read('Default/layout', 'index');
		}
		$this->view->setLayout($layout);
		$this->view->setPage($this->route['route']['page']);


// controller		
		$name = $this->route['route']['controller'];
		if (is_null($name)) {
			$name = $routes->read('Default/Controller', 'Web');
		}
		$this->controller = $this->buildController($name);

	// this is weird:
		$this->controller->processRoute($this->route['route']);


// action		
		$action = $this->buildAction($action['path']);
		
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
			$this->services[$name] = new ${"\\App\\Service\\$name"}($this);
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
		
		$type = ucfirst($type);
		$name = is_null($name) ? $type : ucfirst($name);

		$classname = sprintf('\\App\\%s\\%s', ucfirst($type), $name);
		
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
			$DB = 'Database/' . ucfirst(strtolower($this->env));
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
