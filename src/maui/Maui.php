<?php

namespace Maui;

class Maui {

	protected static $_instances = array();

	/**
	 * @var \MongoClient
	 */
	protected static $_db;

	protected static $_dbHost = 'mongodb://localhost:27017';

	protected static $_dbDb = 'Maui';

	protected static $_dbOptions = array("connect" => TRUE);

	/**
	 * I index the default instance
	 */
	const ENV_DEFAULT = 'default';
	/**
	 * development
	 */
	const ENV_DEV = 'envDev';
	/**
	 * testing
	 */
	const ENV_TEST = 'envTest';
	/**
	 * production
	 */
	const ENV_PROD = 'envProd';

	/**
	 * @var string environment of current instance. defaults to production to prevent accidentally putting online dev code...
	 */
	protected $_env = \Maui\Maui::ENV_PROD;

	/**
	 * I return an instance
	 * @param string $env instance to get. call without param to get default
	 * @return mixed
	 * @throws \Exception
	 */
	public static function instance($env=null) {
		if (is_null($env) && empty(static::$_instances)) {
			$env = \Maui\Maui::ENV_PROD;
		}
		if (is_null($env) && isset(static::$_instances[static::ENV_DEFAULT])) {
			$env = static::ENV_DEFAULT;
		}
		elseif (!isset(static::$_instances[$env])) {
			static::$_instances[$env] = new static($env);
			if (empty(static::$_instances[static::ENV_DEFAULT])) {
				static::$_instances[static::ENV_DEFAULT] = &static::$_instances[$env];
			}
		}
		else {
			throw new \Exception($env);
		}
		return static::$_instances[$env];
	}

	public function setDbHost($dbHost) {
		$this->_dbHost = $dbHost;
	}

	public function db($key=null, $val=null) {
		if (is_null($this->_db)) {
			$this->_db = new \MongoClient($this->_dbHost, $this->_dbOptions);
		}
		return $this->_db;
	}

	public function dbDb($dbDb = null) {
		if (!is_null($dbDb)) {
			$this->_dbDb = $this->_db->$dbDb;
		}
		elseif (is_string($this->_dbDb)) {
			$dbDb = $this->_dbDb;
			$this->_dbDb = $this->_db->$dbDb;
		}
		return $this->_dbDb;
	}

	protected function __construct($env) {
		$this->_env = $env;
	}

	/**
	 * I return my environment
	 * @return string
	 */
	public function env() {
		return $this->_env;
	}

}
