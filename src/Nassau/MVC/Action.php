<?php namespace Nassau\MVC;

abstract class Action extends Component {

	const RESULT_RENDER		= 0x00;
	const RESULT_REDIRECT	= 0x01;
	
	public abstract function execute();
}
