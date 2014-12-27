<?php

namespace maui;

/**
 * I represent a validation rule. I can filter, etc
 */
class SchemaValidator {

	protected $_value = null;

	protected $_parent = null;

	/**
	 * @var bool shows if current fields has multiple values or just one
	 */
	protected $_isMulti = false;

	/**
	 * I consturct and return a SchemaValidator*** object based on definition
	 * @param string|array|callable $validator validator definition or direct callback
	 * @param mixed $validatorValue valud for validator
	 * @param \SchemaFieldAttr|null $parent some validators might need accessing parent object
	 * @return \SchemaValidator
	 * @throws \Exception
	 */
	public static function from($validator, $validatorValue, &$parent=null) {
		if (is_string($validator) && class_exists('\\SchemaValidator' . $validator)) {
			$validatorClassname = '\\SchemaValidator' . $validator;
			$Validator = new $validatorClassname($validatorValue, $parent);
		}
//		elseif (is_callable($validator)) {
//			$Validator = new \SchemaValidatorCallback($validatorValue, $parent);
//		}
		else {
			throw new \Exception('TBI - SchemaValidator::from');
		}
		return $Validator;
	}

	/**
	 * @param mixed $val parameter value for validator
	 * @param \Model $parent some validators (eg. which compare to or default to another field value) need this, some not
	 */
	function __construct($val, $parent=null, $isMulti=null) {
		$this->_value = $val;
		$this->_parent = $parent;
		if (!is_null($isMulti)) {
			$this->_isMulti = $isMulti ? true : false;
		}
	}

	/**
	 * I return or set if current field has multiple values or not
	 * @param null $isMulti
	 * @return $this|bool
	 */
	public function isMulti($isMulti=null) {
		if (func_num_args() == 0) {
			return $this->_isMulti;
		}
		$this->_isMulti = $isMulti ? true : false;
		return $this;
	}

	/**
	 * I process $this->_value so if it refers a model's other field, it will be resolved
	 * eg. 'fileReadable' => '=path' will resolve $value to $Model->path (if possible, if $Model is not null and has
	 * an attribute of that name)
	 * @param \Model $Model
	 */
	protected function _getValue($Model) {

		$value = $this->_value;

		// resolve values if Model is not null
		if (is_string($value) && !is_null($Model)) {
			if ($value[0] === '=') {
				$key = substr($value, 1);
				// I resolve only attributes
				if ($Model->Data()->hasAttr($key)) {
					// I get value $asIs=true
					$value = $Model->Data()->getField($key, \ModelManager::DATA_ALL, true);
				}
			}
			elseif ($value[1] === '~') {
				$key = substr($value, 1);
				if ($Model->Data()->hasAttr($key)) {
					$value = $Model->Data()->getField($key, \ModelManager::DATA_ALL, true);
				}
				$value = is_array($value) ? count($value) : strlen($value);
			}
		}

		return $value;

	}

	/**
	 * I return true if value is valid for me. Eg. a ToInt validator will return true if $val can be cast to (int)
	 * @param $val
	 * @return bool
	 * @extendMe
	 */
	public function validate($val, $Model=null) {
		return true;
	}

	/**
	 * return error message. Field key shall be prepended later
	 * @param $val
	 * @return string
	 * @extendMe
	 */
	public function getError($val, $Model=null) {
		return 'failed ' . get_called_class() . '(' . $this->_value . ')';
	}

	/**
	 * I return a value applied to this field. I basicly do typecasting. Note that value can still be invalid.
	 * 	if I cannot interpret $val for validation (eg. an array is passed to 'min' validator), I shall return null
	 * @param $val
	 * @return mixed|null apply() should return null if $val is not applicable
	 * @extendMe
	 */
	public function apply(&$val, $Model=null) {
		return true;
	}

	/**
	 * I return filtered value suitable for this field. Normally this just requires checking if that value validates
	 * @param mixed $val
	 * @param \Model|null $Model
	 * @return null
	 */
	public function filter($val, $Model=null) {
		return $this->validate($val, $Model) ? $val : null;
	}

	/**
	 * I am to be called before save, order is beforeSave, validate, writeout
	 * I shall modify my field (or other fields) directly if necessary
	 * @param $key
	 * @param $Model
	 * @return bool
	 */
	public function beforeSave($key, $Model) {
		return true;
	}

}
