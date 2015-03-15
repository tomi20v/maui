<?php

namespace maui;

class SchemaValidatorCallback extends \SchemaValidator {

	function __construct($val, $parent=null, $isMulti=null) {
		if (!is_callable($val)) {
			throw new \Exception('TBI - make nice error exception here');
		}
		parent::__construct($val, $parent, $isMulti);
	}

	public function validate($val, $Model=null) {
		$isValid = call_user_func_array($this->_value, [$val, $Model, $this]);
		return $isValid;
	}

	public function apply(&$val, $Model=null) {
		throw new \Exception('TBI');
	}

}
