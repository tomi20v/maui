<?php

namespace maui;

class SchemaFieldRelative extends \SchemaFieldAbstract {

	/**
	 * @var string class of relative
	 */
	protected $_class;

	/**
	 * @var string storage class
	 * @see Schema::REF_REFERENCE
	 */
	protected $_reference = \SchemaManager::REF_INLINE;

	/**
	 * @var string current object will refer this field, eg. 'user' => 'Admin' refers to the user who has field 'name' the same
	 */
	protected $_referredField = \SchemaManager::KEY_ID;

	/**
	 * @var \Schema the actual schema data contained in the object. just as the object's
	 * 	own schema, but this schema can be just a subset (maybe expansion) of the original
	 */
	protected $_schema = null;

	/**
	 * I tell if $objectSchema is valid definition for an object
	 *
	 * @param array|\SchemaFieldRelative $objectSchema
	 * @return bool
	 */
	public static function isSchemaObject($objectSchema) {
		if (is_string($objectSchema));
		elseif ($objectSchema instanceof \SchemaFieldRelative);
		elseif (is_array($objectSchema)) {
			// if array, a 'reference' => 'classname' is expected
			if (!isset($objectSchema['class'])) {
				return false;
			}
		}
		else
			return false;
		return true;
	}

	/**
	 * I create and return an object based from $objectSchema
	 *
	 * @param array|\SchemaFieldRelative $objectSchema anything accepted by isSchemaObject()
	 * @param null|string $key key in schema definition, used as 'class' if class is not defined in the object
	 * @return \SchemaFieldRelative|static
	 * @throws \Exception
	 */
	public static function from($objectSchema, $context, $key=null) {
		if ($objectSchema instanceof \SchemaFieldRelative) {
			$SchemaObject = $objectSchema;
		}
		elseif (is_string($objectSchema) && !is_null($key)) {
			$schemaObjectClassname = $objectSchema;
			if (is_numeric($key)) {
				$key = $schemaObjectClassname;
			}
			if (!class_exists($schemaObjectClassname)) {
				throw new \Exception('Class ' . $schemaObjectClassname . ' does not exist');
			}
			$SchemaObject = new static();
			$SchemaObject->_class = $schemaObjectClassname;
		}
		elseif (is_array($objectSchema)) {
			$schemaObjectClassname = null;
			if (isset($objectSchema['class'])) {
				$schemaObjectClassname = $objectSchema['class'];
				unset($objectSchema['class']);
			}
			elseif (is_string($key) && !is_numeric($key)) {
				$schemaObjectClassname = $key;
			}
			if (is_null($schemaObjectClassname) || !class_exists($schemaObjectClassname)) {
				throw new \Exception('could not determine class for reference: ' . $schemaObjectClassname);
			}
			$SchemaObject = new static();
			$SchemaObject->_class = $schemaObjectClassname;
			foreach ($objectSchema as $eachKey=>$eachVal) {
				switch($eachKey) {
					case 'label':
						$SchemaObject->_label;
						break;
					case 'reference':
						$SchemaObject->_reference = $eachVal;
						break;
					case 'referredField':
						$SchemaObject->_referredField = $eachVal;
						break;
					case 'relation':
						$SchemaObject->_relation = $eachVal;
						break;
					// @todo I should implement a lightweight model class with variable schemas to support these sub-schemas
					//		these would be used when a real class cannot be found
					case 'schema':
						// I shall be able to create a schema even if $key is null, eg. for ad-hoc schemas. be careful
						//		and feed $context otherwise schemas can overwrite previous ones
						$SchemaObject->_schema = is_null($key)
							? \SchemaManager::from($eachVal, $context . '.' . $schemaObjectClassname)
							: \SchemaManager::registerSchema($context . '.' . $key, $eachVal);
						break;
					case 'hasMin':
						$SchemaObject->_hasMin = (int) $eachVal;
						break;
					case 'hasMax':
						$SchemaObject->_hasMax = (int) $eachVal;
						break;
					default:
						throw new \Exception('unrecognized SchemaReference key ' . $eachKey);
				}
			}
		}
		else {
			throw new \Exception(print_r($objectSchema,1));
		}
		if (!is_null($key) && !is_numeric($key)) {
			$SchemaObject->_key = $key;
		}
		return $SchemaObject;
	}

	/**
	 * @TODO implement!
	 * @param $val
	 * @param $Model
	 * @return bool
	 */
	public function validate($val, $Model=null) {
		return true;
	}

	/**
	 * @TODO implement!
	 * @param $val
	 * @param $Model
	 * @return null
	 */
	public function getErrors($val, $Model=null) {
		return null;
	}

	/**
	 * @TODO implement!
	 * @param $val
	 * @param $Model
	 * @return mixed
	 */
	public function apply(&$val, $Model=null) {
		return true;
	}

	public function filter($val) {
		return $val;
	}

	public function beforeSave($key, $Model) {
		return true;
	}

	/**
	 * I return referred object from value
	 * @param $val
	 * @return mixed
	 */
	public function getReferredObject($val) {
		$classname = $this->_class;

		// collection
		if ($this->isMulti()) {
			$data = array();
			foreach ($val as $eachVal) {
				if ($eachVal instanceof \MongoId) {
					$eachVal = array(\SchemaManager::KEY_ID => $eachVal);
				}
				elseif ($this->_reference == \SchemaManager::REF_REFERENCE) {
					$eachVal = array($this->_referredField => $eachVal);
				}
				elseif ($this->_reference == \SchemaManager::REF_INLINE);
				else {
					throw new \Exception(echon($eachVal));
				}
				$data[] = $eachVal;
			}
			$ret = $classname::getCollection($val);
		}
		// single object
		else {
			$ret = new $classname(array($this->_referredField => $val), true);
		}
		return $ret;
	}

	/**
	 * I return object prop data based on relation reference (inline or referred)
	 * @param $Obj
	 * @return mixed
	 * @throws \Exception
	 */
	public function getObjectData($Obj) {
			switch ($this->_reference) {
			case \SchemaManager::REF_INLINE:
				return $Obj->getData();
			case \SchemaManager::REF_REFERENCE:
				return $Obj->data($this->_referredField);
			case \SchemaManager::REF_AUTO:
			default:
				throw new \Exception('TBI');
		};
	}

	/**
	 * I return referred object classname
	 * @return string
	 */
	public function getObjectClassname() {
		return $this->_class;
	}

	/**
	 * @return string $this->_reference
	 */
	public function getReference() {
		return $this->_reference;
	}

	/**
	 * I check if $val is suitable to be set.
	 * @param $val
	 * @param string $key
	 * @return bool
	 * @throws \Exception
	 */
	public function checkVal($val, $key='') {

		$classname = $this->_class;

		// a scalar should be able to be set and used as ID later. still, it might be invalid.
		if (is_scalar($val));
		elseif (is_array($val));
		elseif ($val instanceof \Model) {
			if (!$val instanceof $classname) {
				throw new \Exception('cannot set ' . echon($val) . ' for field ' . echon($key) . ' as it is not subclass of ' . echon($classname));
			}
		}
		elseif ($val instanceof \Collection) {
			if (!$this->isMulti()) {
				throw new \Exception('cannot set collection for field ' . echon($key) . ' as is not multi');
			}
			$collectionClassname = $classname::getCollectionName();
			if (!$val instanceof $collectionClassname) {
				throw new \Exception('cannot set collection ' . echon($val) . ' for field ' . echon($key) . ' as it is not subclass of ' . echon($classname));
			}
		}

		return true;

	}

}
