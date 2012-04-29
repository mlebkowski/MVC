<?php namespace App\Action;

class Example extends \Nassau\MVC\Action {
	public function execute() {
		var_dump($this->exampleParam);
	}
}
