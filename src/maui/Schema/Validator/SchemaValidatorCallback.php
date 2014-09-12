<?php

namespace maui;

class SchemaValidatorCallback extends \SchemaValidator {

	protected $_callback;

	public function __construct($val, $callback) {
		parent::__construct($val);
		$this->_callback = $callback;
	}

	public function validate($val, $Model=null) {
		throw new \Exception('TBI');
	}

	public function apply($val, $Model=null) {
		throw new \Exception('TBI');
	}

}
