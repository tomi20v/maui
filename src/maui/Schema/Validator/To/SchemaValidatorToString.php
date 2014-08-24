<?php

namespace Maui;

class SchemaValidatorToString extends \SchemaValidatorTo {

	/**
	 * I accept scalars and objects with __toString() method
	 * @param $val
	 * @return bool
	 */
	public function validate($val) {
		return is_scalar($val) || (is_object($val) && method_exists($val, '__toString'));
	}

	/**
	 * @param $val
	 * @return null|string
	 */
	public function apply($val) {
		if (!$this->validate($val)) {
			return null;
		}
		$val = (string) $val;
		return $val;
	}

}
