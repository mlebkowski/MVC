<?php namespace Nassau\MVC;

class View extends \Twig_Environment {
	protected $repository;
	protected $fc;
	
	protected $title;
	
	public function __construct(FrontController $fc) {
		$this->fc = $fc;
		$this->repository = 'app/' . str_replace("\\", "/", $fc->app->ns) . '/templates';
		
		parent::__construct(
			new \Twig_Loader_Filesystem($this->repository),
			array (
				'cache' => 'cache/templates',
				'debug' => 'dev' === $this->fc->app->env,
			)
		);
	}
	
	public function setFormat($format) {
	
	}
	
	public function setTitle($title) {
		$this->title = $title;
	}
	
	public function setLayout($layout) {
		$this->layout = $layout;
		if (false === file_exists($this->repository . '/layouts/' . $layout . '.tpl')) {
			throw new \Exception('Layout template not found: ' . $layout);
		}
	}
	public function setPage($page) {
		$this->page = $page;
		if (false === file_exists($this->repository . '/pages/' . $page . '.tpl')) {
			throw new \Exception('Page template not found: ' . $page);
		}
	}
	
	public function execute() {
		// execute page
		// execute layout
	}
}
