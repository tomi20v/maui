<?php

namespace Maui;

class Maui {

	protected static $_instance;

	/**
	 * @var \MongoClient
	 */
	protected static $_db;

	protected static $_dbHost = 'mongodb://localhost:27017';

	protected static $_dbDb = 'Maui';

	protected static $_dbOptions = array("connect" => TRUE);

	const ENV_DEV = 'envDev';
	const ENV_TEST = 'envTest';
	const ENV_PROD = 'envProd';

	public static $_env = \Maui\Maui::ENV_PROD;

	public static function instance() {
		if (!isset(static::$_instance)) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}

	public static function setDbHost($dbHost) {
		static::$_dbHost = $dbHost;
	}

	public static function db($key=null, $val=null) {
		if (is_null(static::$_db)) {
			static::$_db = new \MongoClient(static::$_dbHost, static::$_dbOptions);
		}
		return static::$_db;
	}

	public static function dbDb($dbDb = null) {
		if (!is_null($dbDb)) {
			static::$_dbDb = static::$_db->$dbDb;
		}
		elseif (is_string(static::$_dbDb)) {
			$dbDb = static::$_dbDb;
			static::$_dbDb = static::$_db->$dbDb;
		}
		return static::$_dbDb;
	}

	protected function __construct() {

	}

}
