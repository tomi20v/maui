<?php

namespace Maui;

/**
 * abstract class for type casting validators REMEMBER: you must override validate() method as well
 * @see SchemaValidator::validate()
 */
abstract class SchemaValidatorTo extends \SchemaValidator {

	/**
	 * I just wrap apply() as for a 'To' validator the apply does the filtering idd
	 * @param $val
	 * @return mixed|null
	 */
	public function filter($val) {
		return $this->apply($val);
	}

}
