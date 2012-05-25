<?php namespace Nassau\MVC;

use Nassau\MVC\Exception\MethodNotAllowed
	, Nassau\MVC\Exception\Unauthorized
	, Nassau\MVC\Exception\Forbidden
	;

class Controller extends Component {
	public function processRoute($route, $params = array ()) {
		if ($route['method']) {
			$check = $this->verifyMethod($route['method']);
			if (false == $check) {
				throw new MethodNotAllowed('Requested method not allowed with this resource', 405);
			}
		}
		if ($route['role']) {
			$check = $this->verifyRole($route['role']);
			if (false == $check) {
				if ('user' === $route['role']) {
					throw new Unauthorized('Authentication is possible but has failed', 401);
				} else {
					throw new Forbidden('Server refuses to respond to request', 403);
				}
			}
		}
		
		// load stuff like stylesheets here
	}
	
	public function verifyMethod($method) {
		return strtolower($method) == strtolower($this->fc->request->getMethod());
	}
	
	public function verifyRole($role) {
		return $this->fc->session->user && $this->fc->session->user->is($role);
	}
}
