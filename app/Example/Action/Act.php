<?php namespace Example\Action;

class Act extends \Nassau\MVC\Action {
	public function execute() {
		var_dump($this->testParam);
	}
}
