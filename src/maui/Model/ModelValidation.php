<?php

namespace maui;

/**
 * Class ModelValidation - abstracted validation behaviour
 *
 * @package maui
 */
class ModelValidation extends \Model {

	/**
	 * @var \Model $Model referred model which is to be validated
	 */
	protected $_Model;

	/**
	 * @var array[] collection of errors per field
	 */
	protected $_validationErrors = array();

	/**
	 * @var boolean[] there will be a true for all fields validated after its value was set. [1] is true if all the
	 * 		objects' fields are validated, [0] is true if all set props are validated
	 */
	protected $_isValidated = array();

	final public function __construct($Model) {
		$this->_Model = $Model;
	}

	/**
	 * @todo make it cached by $this->_isValid again!?
	 * @param bool $key
	 * @return bool
	 */
	public function isValid($key=true) {
		$errors = $this->getErrors($key);
		return empty($errors);
	}

	/**
	 * @param bool|string|string[] $key
	 * 	true   - validate all values in model context
	 *  false  - validate all values formally
	 *  string - validate one field formally
	 *  string[] - validate some fields in model context
	 * @return bool
	 * @throws \Exception
	 */
	public function validate($key=true) {
		switch(true) {
		case $key === true:
		case $key === false:
			$this->_isValidated = array(true=>false, false=>false);
			$this->_validationErrors = array();
//			$Schema = static::_getSchema();
			$Schema = $this->_Model->_getSchema();
			foreach ($Schema as $eachKey=>$EachField) {
//				if (!$key && !array_key_exists($eachKey, $this->_data)) {
				if (!$key && !array_key_exists($eachKey, $this->_Model->_data)) {
					continue;
				}
				$eachVal = $this->_Model->Data()->getField($eachKey, \ModelManager::DATA_ALL, true);
				$errors = $EachField->getErrors($eachVal, $key ? $this->_Model : null);
				if (!empty($errors)) {
					$this->_validationErrors[$eachKey] = $errors;
				}
				$this->_isValidated[$eachKey] = $key;
			}
			$this->_isValidated[true] = $key;
			$this->_isValidated[false] = true;
			return empty($this->_validationErrors);
			break;
		case is_array($key):
			foreach ($key as $eachKey) {
				unset($this->_validationErrors[$eachKey]);
				unset($this->_isValidated[$eachKey]);
				$eachVal = $this->_Model->Data()->getField($eachKey);
				$errors = $this->_Model->_getSchema()->field($eachKey)->getErrors($eachVal);
				if (!empty($errors)) {
					$this->_validationErrors[$eachKey] = $errors;
				}
				$this->_isValidated[$eachKey] = true;
			}
			$errors = array_intersect_key($this->_validationErrors, array_flip($key));
			$ret = empty($errors);
			// this actually shouldn't make sense as [true] and [false] shall be unset upon prop setting
			if ($ret) {
				unset($this->_isValidated[true]);
				unset($this->_isValidated[false]);
			}
			return count(array_intersect_key($this->_validationErrors, array_flip($key))) ? false : true;
			break;
		case $this->_Model->Data()->hasField($key):
			return $this->validate(array($key));
		default:
			throw new \Exception('cannot validate non existing field def: ' . echon($key));
		}
	}

	/**
	 * I return all or some errors
	 * @param bool $key
	 * @return array|\array[]
	 * @throws \Exception
	 */
	public function getErrors($key=true) {
		switch(true) {
		case $key === true:
		case $key === false:
			if (!isset($this->_isValidated[$key]) || !$this->_isValidated[$key]) {
				$this->validate($key);
			}
			return $this->_validationErrors;
		case is_array($key):
			if (isset($this->_isValidated[true]) && $this->_isValidated[true]);
			else {
				foreach ($key as $eachKey) {
					if (!isset($this->_isValidated[$eachKey]) || !$this->_isValidated[$eachKey]) {
						$this->validate($key);
						break;
					}
				}
			}
			return array_intersect_key($this->_validationErrors, array_flip($key));
		case $this->_Model->Data()->hasField($key):
			return $this->getErrors(array($key));
		default:
			throw new \Exception('cannot get error for non existing field def: ' . echon($key));;
		}
	}

}
