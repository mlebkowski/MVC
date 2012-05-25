<?php namespace Nassau\MVC;

class Application {

	public $env, $ns;

	public function __construct($env, $ns = 'App')
	{
		$this->env = $env;
		$this->ns = $ns;
	}
}
