<?php

namespace maui;

/**
 * Class ModelBubbler - I get/set inherited properties traversing tree structure up by 'Parent' field
 *
 * @package maui
 */
class ModelBubbler {

	protected $_Model;

	public function __construct($Model) {

		$this->_Model = $Model;

	}

	/**
	 * shorthand for $Model->getBubbler()->field syntax
	 * @param $key
	 * @return $this|Model|null
	 */
	public function __get($key) {

		return $this->bubbleGet($key);

	}

	/**
	 * I return the first value found on field $key
	 * @param $key
	 * @return $this|Model|null
	 */
	public function bubbleGet($key) {

		return $this->_bubbleGet($this->_Model, $key);

	}

	/**
	 * @param \Model $Model
	 * @param mixed $key
	 */
	protected function _bubbleGet($Model, $key) {

		if ($Model->Data()->fieldIsSet($key)) {
			return $Model->$key;
		}

		if ($Model->Data()->fieldIsSet('Parent')) {
			return $this->_bubbleGet($Model->Parent, $key);
		}

		return null;

	}

	/**
	 * I set a value into the model in which it is currently set
	 * @param string $key
	 * @param $val
	 * @return $this|Model|null
	 */
	public function bubbleSet($key, $val) {

		return $this->_bubbleSet($this->_Model, $key, $val);

	}

	/**
	 * @param \Model $Model
	 * @param string $key
	 * @param mixed $val
	 * @return $this|Model|null
	 */
	protected function _bubbleSet($Model, $key, $val) {

		if ($Model->Data()->fieldIsSet($key)) {
			return $Model->Data()->setField($key, $val);
		}
		elseif ($Model->Data()->fieldIsSet('Parent')) {
			return $this->_bubbleSet($Model->Parent, $key, $val);
		}

		return null;

	}

	/**
	 * I return an array of all values in me and my parents of field $key
	 * @param string $key
	 * @param bool $includeEmpty if false, empty values will be omitted
	 * @return string[]
	 */
	public function bubbleGetAll($key, $includeEmpty=false) {
		return $this->_bubbleGetAll($this->_Model, $key, $includeEmpty);
	}

	/**
	 * @param \Model $Model
	 * @param string $key
	 * @return string[]
	 */
	protected function _bubbleGetAll($Model, $key, $includeEmpty) {
		$vals = [];
		if (!$Model->Data()->fieldIsEmpty('Parent') && ($Model->Parent !== $Model)) {
			$vals = $this->_bubbleGetAll($Model->Parent, $key, $includeEmpty);
		}
		if ($Model->Data()->fieldIsSet($key)) {
			if ($includeEmpty || !$Model->Data()->fieldIsEmpty($key)) {
				$vals[] = $Model->Data()->getField($key);
			}
		}
		return $vals;
	}

}
