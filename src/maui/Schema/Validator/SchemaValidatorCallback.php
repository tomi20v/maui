<?php

namespace Maui;

class SchemaValidatorCallback extends \SchemaValidator {

	protected $_callback;

	public function __construct($value, $callback) {
		parent::__construct($value);
		$this->_callback = $callback;
	}

	public static function _apply($val,  $validatorValue) {

	}

}
