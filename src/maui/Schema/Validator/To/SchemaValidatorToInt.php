<?php

namespace Maui;

class SchemaValidatorToInt extends \SchemaValidatorTo {

	/**
	 * @param $val
	 * @return bool
	 */
	public function validate($val) {
		return is_scalar($val);
	}

	/**
	 * @param $val
	 * @param $validatorValue
	 * @return int|null
	 */
	public function apply($val) {
		if (!$this::validate($val)) {
			return null;
		}
		$val = (int) $val;
		return $val;
	}

}
