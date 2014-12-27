<?php

namespace maui;

/**
 * Class ModelData - abstracted data fucntionality. Note that actual $_data and $_originalData remained in Model itself
 *
 * @package maui
 */
class ModelData extends Model {

	/**
	 * @var \Model
	 */
	protected $_Model;

	public static function __init() {}

	public function __construct($Model) {
		$this->_Model = $Model;
	}

	/**
	 * in case you'd call Data() on me...
	 * @return $this
	 */
	public function Data() {
		return $this;
	}

	////////////////////////////////////////////////////////////////////////////////
	// field related
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I return if an attribute exists in my schema
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasField($key) {
		return $this->_Model->_getSchema()->hasField($key);
	}

	/**
	 * I return a field. I wrap _getAttr() and _getRelative
	 * @param $key
	 * @param int $whichData
	 * @param bool $asIs
	 * @return null
	 */
	public function getField($key, $whichData=\ModelManager::DATA_ALL, $asIs=false) {
		if ($this->hasAttr($key)) {
			return $this->_getAttr($key, $whichData, $asIs);
		}
		elseif ($this->_Model->Data()->hasRelative($key)) {
			return $this->_getRelative($key, $whichData, $asIs);
		}
	}

	/**
	 * I set a fields value. I wrap _setAttr() and _setRelative()
	 * @param $key
	 * @param $val
	 * @param int $whichData
	 * @return $this
	 */
	public function setField($key, $val, $whichData=\ModelManager::DATA_CHANGED) {
		if ($this->hasAttr($key)) {
			return $this->_setAttr($key, $val, $whichData);
		}
		elseif ($this->hasRelative($key)) {
			return $this->_setRelative($key, $val, $whichData);
		}
	}

	/**
	 * I return if a fields value is set in data array.
	 * I do not validate $key param
	 * @param string $key
	 * @param int $whichData
	 * @return bool
	 * @throws \Exception
	 */
	public function fieldIsSet($key, $whichData = \ModelManager::DATA_ALL, $noDefault=false) {

		switch($whichData) {
		case \ModelManager::DATA_CHANGED:
			$ret = array_key_exists($key, $this->_Model->_data);
			break;
		case \ModelManager::DATA_ORIGINAL:
			$ret = array_key_exists($key, $this->_Model->_originalData);
			break;
		case \ModelManager::DATA_ALL:
			$ret = array_key_exists($key, $this->_Model->_data) || array_key_exists($key, $this->_Model->_originalData);
			break;
		default:
			throw new \Exception('invalid value (' . echon($whichData) . ' for $whichData in fieldIsSet()');
		}
		if (!$ret && !$noDefault && $this->hasField($key)) {
			$Field = $this->_Model->_getSchema()->field($key);
			if ($Field->getDefault($key, $this) !== null) {
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	 * I return result of isset()
	 * @param $key
	 * @param int $whichData
	 * @return bool
	 * @throws \Exception
	 */
	public function fieldNotNull($key, $whichData = \ModelManager::DATA_ALL) {
		switch($whichData) {
		case \ModelManager::DATA_CHANGED:
			return isset($this->_Model->_data[$key]);
		case \ModelManager::DATA_ORIGINAL:
			return isset($this->_Model->_originalData[$key]);
		case \ModelManager::DATA_ALL:
			return isset($this->_Model->_data[$key]) || isset($this->_Model->_originalData[$key]);
		}
		throw new \Exception('invalid value (' . echon($whichData) . ' for $whichData in fieldIsSet()');
	}

	/**
	 * I return if a fields value is set and has non empty value
	 * @param $key
	 * @param int $whichData
	 * @return bool
	 * @throws \Exception
	 */
	public function fieldIsEmpty($key, $whichData = \ModelManager::DATA_ALL) {
		if (!$this->fieldIsSet($key, $whichData)) {
			return true;
		}
		switch($whichData) {
		case \ModelManager::DATA_CHANGED:
			return empty($this->_Model->_data[$key]);
		case \ModelManager::DATA_ORIGINAL:
			return empty($this->_Model->_originalData[$key]);
		case \ModelManager::DATA_ALL:
			return empty($this->_Model->_data[$key]) && empty($this->_Model->_originalData[$key]);
		}
		throw new \Exception('invalid value (' . echon($whichData) . ' for $whichData in fieldEmpty()');
	}

	/**
	 * I return if I have an attribute called $key
	 * @param $key
	 * @return bool
	 */
	public function hasAttr($key) {
		return $this->_Model->_getSchema()->hasAttr($key);
	}

	/**
	 * I return an attribute
	 * @param string $key
	 * @param int $whichData
	 * @param bool $asIs if false, and field is multi, cast it to array
	 * @return null
	 */
	protected function _getAttr($key, $whichData, $asIs) {
		$val = isset($this->_Model->_data[$key]) ? $this->_Model->_data[$key] : null;
		$originalVal = isset($this->_Model->_originalData[$key]) ? $this->_Model->_originalData[$key] : null;

		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
			$ret = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$ret = $originalVal;
			break;
		case \ModelManager::DATA_ALL:
			$ret = isset($val) ? $val : $originalVal;
			break;
		}

		if (!$asIs) {
			$Attr = $this->_Model->_getSchema()->getAttr($key);
			if (is_null($ret)) {
				$ret = $Attr->getDefault($key, $this);
			}
			elseif ($Attr->isMulti()) {
				$ret = (array) $ret;
			}
		}

		return $ret;

	}

	/**
	 * @param string $key
	 * @param mixed $val value to set
	 * @param int $whichData
	 */
	protected function _setAttr($key, $val, $whichData) {
		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
		case \ModelManager::DATA_ALL:
			$this->_Model->_data[$key] = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$this->_Model->_originalData[$key] = $val;
			break;
		}
		return $this;
	}

	/**
	 * I return if I have relative(s) called $key
	 * @param $key
	 * @return bool
	 */
	public function hasRelative($key) {
		return $this->_Model->_getSchema()->hasRelative($key);
	}

	/**
	 * @param string $key
	 * @param int $whichData as in ModelManager::DATA_* constants
	 * @param bool $asIs get relative value as is (eg. return empty or just mongoId object) or create (and set) proper object
	 * @return null
	 */
	protected function _getRelative($key, $whichData, $asIs) {

		$val = isset($this->_Model->_data[$key]) ? $this->_Model->_data[$key] : null;
		$originalVal = isset($this->_Model->_originalData[$key]) ? $this->_Model->_originalData[$key] : null;

		// $asIs = false - return some meaningful object, create (and set) if necessary
		// this shall be more savvy
		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
			$ret = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$ret = $originalVal;
			break;
		case \ModelManager::DATA_ALL:
			$ret = isset($val) ? $val : $originalVal;
			break;
		}

		if ($asIs);
		elseif (is_object($ret) && !($ret instanceof \MongoId));
		else {

			$Rel = $this->_Model->_getSchema()->field($key);
			$ret = $Rel->getReferredObject($ret);

			switch ($whichData) {
			case \ModelManager::DATA_CHANGED:
			case \ModelManager::DATA_ALL:
				$this->_Model->_data[$key] = $ret;
				break;
			case \ModelManager::DATA_ORIGINAL:
				$this->_Model->_originalData[$key] = $ret;
				break;
			}

		}

		return $ret;

	}

	protected function _setRelative($key, $val, $whichData) {

		/**
		 * @var \SchemaFieldRelative $Rel
		 */
		$Rel = $this->_Model->_getSchema()->field($key);

		$Rel->checkVal($val);

		// do not overwrite if it's a relative, and already inflated, and incoming data is the same but by reference
		if ($Rel->getReference() === \SchemaManager::REF_REFERENCE) {
			$currentValue = $this->_getRelative($key, $whichData, true);
			// @todo implement check for collections
			if (($currentValue instanceof \Model) &&
				is_string($val) &&
				((string)$currentValue->_id === $val)) {

				return;

			}
		}

		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
		case \ModelManager::DATA_ALL:
			$this->_Model->_data[$key] = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$this->_Model->_originalData[$key] = $val;
			break;
		}

		return $this;

	}

	////////////////////////////////////////////////////////////////////////////////
	// whole $data related
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I return array representation of current data state
	 * @param array|bool $keys field keys to return, true to return all. still, I will return only fields which are set
	 * @param int $whichData as in ModelManager
	 * @param bool $asIs if true, data is returned as is. If false, relatives will be constructed from existing data
	 * @return array|null
	 */
	public function getData($keys=true, $whichData=\ModelManager::DATA_ALL, $asIs=true) {

		if ($asIs) {
			switch ($whichData) {
			case \ModelManager::DATA_CHANGED:
				$data = $this->_Model->_data;
				break;
			case \ModelManager::DATA_ORIGINAL:
				$data = $this->_Model->_originalData;
				break;
			case \ModelManager::DATA_ALL:
				$data = $this->_Model->_data + $this->_Model->_originalData;
				break;
			default:
				throw new \Exception('invalid value for $whichData: ' . echon($whichData));
			}
			if (is_array($keys)) {
				$data = array_intersect_key($data, array_flip($keys));
			}
			return $data;
		}

		$data = array();
		$Schema = $this->_Model->_getSchema();

		foreach ($Schema as $eachKey=>$EachField) {
			$data[$eachKey] = $this->getField($eachKey, $whichData, false);
		}

		return $data;

	}

	/**
	 * @todo implement me?
	 * @param $arrData
	 * @param int $whichData
	 * @throws \Exception
	 */
	public static function flatArrData($arrData, $whichData=\ModelManager::DATA_ALL) {
		throw new \Exception('TBI');
	}

	/**
	 * I return data representation in a multi dimensional array, suitable for save. This same data can be set for the
	 * 		proper model and it shall be the same as before save.
	 * note I am marked final - at least for now
	 * @param int $whichData
	 * @return array multidimensional array of just scalar values
	 * @throws \Exception
	 */
	final public function flatData($whichData=\ModelManager::DATA_ALL) {

		$data = array();

		$Schema = $this->_Model->_getSchema();

		foreach ($Schema as $eachKey=>$EachField) {
			if (!$this->fieldIsSet($eachKey, $whichData)) {
				continue;
			}
			$eachVal = $this->getField($eachKey, $whichData, true);
			if (is_null($eachVal)) {

			}
			elseif (($eachVal instanceof \Model) && ($EachField instanceof \SchemaFieldRelative)) {
				$eachVal = $EachField->getReference() === \SchemaManager::REF_INLINE
//					? $eachVal->flatData($whichData)
					? $eachVal->Data()->flatData($whichData)
					: (is_null($eachVal->_id) ? null : '' . $eachVal->_id);
			}
			elseif ($EachField->isMulti() && is_array($eachVal)) {
				foreach ($eachVal as $eachValKey=>$eachValVal) {
					if (($eachValVal instanceof \Model) && ($EachField instanceof \SchemaFieldRelative)) {
						$eachVal[$eachValKey] = $EachField->getReference() === \SchemaManager::REF_INLINE
							? $eachValVal->Data()->flatData($whichData)
							: '' . $eachValVal->_id;
					}
				}
			}
			elseif ($eachVal instanceof \Collection) {
				//throw new \Exception('TBI');
				$tmpVal = array();
				foreach ($eachVal as $eachValKey=>$eachModel) {
//					$tmpVal[$eachValKey] = $eachModel->flatData($whichData);
					$tmpVal[$eachValKey] = $eachModel->Data()->flatData($whichData);
				}
				$eachVal = $tmpVal;
			}
			$data[$eachKey] = $eachVal;
		}

		return $data;

	}

	/**
	 * I am a fast teller if object has any field value set (so no need to call for a getData())
	 * @param string|string[] array of fieldnames to exclude from comparison
	 * @return bool
	 */
	public function isEmpty($excludeKeys) {
		$excludeKeys = (array) $excludeKeys;
		$keys = array_diff(array_keys($this->Model->_data), $excludeKeys);
		return empty($keys);
	}

	/**
	 * I set or merge data
	 * @param mixed[] $data
	 * @param bool $overWrite if true, $data is set as data erasing old, otherwise they're merged
	 * @param int $whichData as in ModelManager constants
	 * @return $this
	 * @throws \Exception
	 */
	public function apply($data, $overWrite=false, $whichData=\ModelManager::DATA_CHANGED) {

		if (!is_array($data)) {
			throw new \Exception('can apply only an array of data, got: ' . echon($data));
		}

		if ($overWrite) {

			switch ($whichData) {
			case \ModelManager::DATA_ORIGINAL:
				$dataStore = &$this->_Model->_originalData;
				break;
			case \ModelManager::DATA_CHANGED:
			case \ModelManager::DATA_ALL:
				$dataStore = &$this->_Model->_data;
				break;
			}

			$dataStore = $data;

		}
		else {
			foreach ($data as $eachKey=>$eachVal) {
				$this->setField($eachKey, $eachVal, $whichData);
			}
		}

		return $this;

	}

	/**
	 * I merge $data to current data
	 * @param null $data
	 */
	public function mergeData($data = null) {

		$this->apply($this->_Model->_data, false, \ModelManager::DATA_ORIGINAL);
		$this->_Model->_data = array();

		if (!is_null($data)) {
			$this->apply($data, false, \ModelManager::DATA_ORIGINAL);
		}

	}

}
