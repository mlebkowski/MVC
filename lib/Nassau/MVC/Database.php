<?php namespace Nassau\MVC;

class Database extends \PDO {
	public function __construct($dsn, $user, $pass, $options = null) {
		parent::__construct($dsn, $user, $pass, $options);
		$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}
	public function select($sql, $params = array ()) {
		$stmt = $this->prepare($sql);
		$stmt->execute($params);
		return $stmt;
	}
}
