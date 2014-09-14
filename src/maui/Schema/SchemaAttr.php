<?php

namespace maui;

class SchemaAttr {

	use \maui\TraitHasLabel;

	/**
	 * @var string field name in schema
	 */
	protected $_key;

	protected $_required = false;

	protected $_hasMin = null;

	protected $_hasMax = null;

	/**
	 * @var \SchemaValidator[]
	 */
	protected $_validators = array();

//	protected $_callbacks = array();

	public static function isSchemaAttr($schemaAttr) {
		if ($schemaAttr instanceof \SchemaAttr);
		elseif (is_array($schemaAttr));
		else return false;
		return true;
	}

	/**
	 * I build a nice schema attribute object
	 * @param array|\SchemaAttr $attrSchema
	 * @param string $key key in parent object
	 * @return static
	 * @throws \Exception
	 */
	public static function from($attrSchema, $key=null) {
		if ($attrSchema instanceof \SchemaAttr) {
			return $attrSchema;
		}
		elseif (is_string($attrSchema) && !is_numeric($key)) {
			$SchemaAttr = new static();
			$SchemaAttr->_key = $key;
			return $SchemaAttr;
		}
		elseif (is_array($attrSchema)) {
			$SchemaAttr = new static();
			foreach ($attrSchema as $eachKey=>$eachVal) {
				switch (true) {
					// 'label' => 'asd',
					case $eachKey === 'label':
						$SchemaAttr->_label = $eachVal;
						break;
					case $eachKey === 'hasMin':
						$SchemaAttr->_hasMin = (int) $eachVal;
						break;
					case $eachKey === 'hasMax':
						$SchemaAttr->_hasMax = (int) $eachVal;
						break;
					case is_numeric($eachKey) && ($eachVal === 'required'):
						$SchemaAttr->_required = true;
						break;
					// "int"
					case is_numeric($eachKey) && !strncmp($eachVal, 'to', 2) && class_exists('\\SchemaValidator' . ucfirst($eachVal)):
						$SchemaAttr->_validators[] = \SchemaValidatorTo::from($eachVal, $eachVal, $SchemaAttr);
						break;
					case is_string($eachKey) && class_exists('\\SchemaValidator' . ucfirst($eachKey)):
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachKey, $eachVal, $SchemaAttr);
						break;
					case is_numeric($eachKey) && is_string($eachVal) && class_exists('\\SchemaValidator' . ucfirst($eachVal)):
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachVal, $eachVal, $SchemaAttr);
						break;
					// 'CallbackClass::method' => 5
					case is_callable($eachKey):
						throw new \Exception('TBI');
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachKey, $eachVal, $SchemaAttr);
						break;
					case is_array($eachVal) && (count($eachVal) == 2) && is_callable($eachVal[0]):
						throw new \Exception('TBI');
						break;
					default:
						throw new \Exception(echon($eachKey,1) . ' / ' . echon($eachVal, 1));
						break;
				}
			}
			if (!is_null($key) && !is_numeric($key)) {
				$SchemaAttr->_key = $key;
			}
			return $SchemaAttr;
		}
		else {
			throw new \Exception();
		}
	}

	/**
	 * I return true if field has multiple values (ie. is array)
	 * @return bool
	 */
	public function isMulti() {
		return (!is_null($this->_hasMin) && ($this->_hasMin > 1)) ||
			(!is_null($this->_hasMax) && ($this->_hasMax != 1));
	}

	/**
	 * I return true if $val passes all validators
	 * @param $val
	 * @param null $Model send Model object to validate in context (eg. unique)
	 * @return bool true if validation succeeded, false if could be validated but failed, null if
	 * 		could not validate - eg. the toInt validator will return null on an object type val
	 *		as it cannot cast object to int for validation
	 * NOTE validation sequence matters as validation will stop on a first null response. This
	 * 		can solve the problem of displaying failed 'min' and 'max' errors if field contains text
	 */
	public function validate($val, $Model=null) {
		if (is_null($val) && $this->_required) {
			return false;
		}
		foreach($this->_validators as $EachValidator) {
			if ($this->isMulti()) {
				$val = (array)$val;
				foreach ($val as $eachVal) {
					if (!$EachValidator->validate($eachVal, $Model)) {
						return false;
					}
				}
			}
			else {
				if (!$EachValidator->validate($val, $Model)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * I return all errors generated by validation for this field
	 * @param $val
	 * @param null $Model send Model object to validate in context (eg. unique)
	 * @return array|null
	 */
	public function getErrors($val, $Model=null) {
		$errors = array();
		if (is_null($val)) {
			if ($this->_required) {
				$errors[] = $this->_getRequiredError();
			}
		}
		else {
			foreach ($this->_validators as $EachValidator) {
				$result = null;
				if ($this->isMulti()) {
					$val = (array)$val;
					foreach ($val as $eachVal) {
						$result = $EachValidator->validate($eachVal, $Model);
						if (!$result) {
							$errors[] = $EachValidator->getError($val, $Model);
						}
					}
				}
				else {
					$errors[] = $EachValidator->getError($val, $Model);
				}
				if (is_null($result)) {
					break;
				}
			}
		}
		return empty($errors) ? null : $errors;
	}

	/**
	 * @return string
	 * @extendMe
	 */
	public function _getRequiredError() {
		return 'required';
	}

	/**
	 * I return $val applied to this attribute
	 * @param $val
	 * @return mixed|null
	 */
	public function apply($val, $Model=null) {
		$wasNull = is_null($val);
		foreach ($this->_validators as $EachValidator) {
			$val = $EachValidator->apply($val, $Model);
			if (is_null($val) && !$wasNull) {
				return $val;
			}
		}
		return $val;
	}

	public function filter($val) {
		return $val;
	}

	public function beforeSave($key, $Model) {
		foreach ($this->_validators as $EachValidator) {
			$EachValidator->beforeSave($key, $Model);
		}
		return true;
	}

}
