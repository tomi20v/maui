<?php

namespace Maui;

class SchemaAttr {

	use \Maui\TraitHasLabel;

	/**
	 * @var string attr name in schema
	 */
	protected $_key;

	protected $_required = false;

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
	 * I return true if $val passes all validators
	 * @param $val
	 * @return bool
	 */
	public function validate($val) {
		foreach($this->_validators as $eachValidator) {
			if (is_null($val)) {
				die('FU');
				return !$this->_required;
			}
			elseif (!$eachValidator->validate($val)) {
				return false;
			}
		}
		return true;
	}

	public function getErrors($val) {
		$errors = array();
		if (is_null($val)) {
			if ($this->_required) {
				$errors[] = $this->_getRequiredError();
			}
		}
		else {
			foreach ($this->_validators as $eachValidator) {
				if (!$eachValidator->validate($val)) {
					$errors[] = $eachValidator->getError($val);
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
	public function apply($val) {
		$wasNull = is_null($val);
		foreach ($this->_validators as $eachValidator) {
			$val = $eachValidator->apply($val);
			if (is_null($val) && !$wasNull) {
				return $val;
			}
		}
		return $val;
	}

	public function filter($val) {
		return $val;
	}

}
