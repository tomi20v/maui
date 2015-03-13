<?php

namespace maui;

class SchemaValidatorDomainName extends \SchemaValidator {

	const DOMAIN_PREG = '/([0-9a-z-]{2,}\.[0-9a-z-]{2,3}\.[0-9a-z-]{2,5}|[0-9a-z-]{2,}\.[0-9a-z-]{2,5})$/i';

	public function validate($val, $Model=null) {
		if (is_string($val)) {
			return preg_match(self::DOMAIN_PREG, $val) ? true : false;
		}
		return null;
	}

	public function getError($val=null, $Model=null) {
		return 'is not domain name: ' . $val . $this->_getSawValueString(func_num_args(), $val, $Model);
	}

	public function apply(&$val, $Model=null) {
		return is_string($val) ? $val : null;
	}

}
