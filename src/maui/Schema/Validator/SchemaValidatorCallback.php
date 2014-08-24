<?php

namespace Maui;

class SchemaValidatorCallback extends \SchemaValidator {

	protected $_callback;

	public function __construct($val, $callback) {
		parent::__construct($val);
		$this->_callback = $callback;
	}

	public function validate($val) {
		throw new \Exception('TBI');
	}

	public function apply($val,  $validatorValue) {
		throw new \Exception('TBI');
	}

}
