<?php namespace Nassau\MVC;

class Component {
	protected $fc;
	protected $config;
	protected $db;
	protected $session;
	protected $view;
	
	public static $types = array ('action', 'block', 'manager', 'controller');

	public function __get($key) {
		foreach (array ('Service', 'Model', 'Manager', 'Action', 'Block', 'Form') as $name) {
			if (substr($key, -strlen($name)) === $name) {
				return $this->fc->{"get$name"}(ucfirst(substr($key, 0, -strlen($name))));
			}
		} 
		if ('Param' === substr($key, - strlen('Param'))) {
			return $this->fc->getParam(substr($key, 0, - strlen('Param')));
		}
	}
	public function __construct(FrontController $fc, Database $db) {
		$this->setFrontController($fc);
		$this->setDatabase($db);
	}
	public function setDatabase(Database $db) {
		$this->db = $db;
	}
	public function setConfig(\Nassau\Config\Config $config) {
		$this->config = $config;
	}
	public function setFrontController(FrontController $fc) {
		$this->fc = $fc;
	}
	public function setSession(Session $session) {
		$this->session = $session;
	}
	public function setView(View $view) {
		$this->view = $view;
	}
	
	public function init() {
		
	}
	
}


