<?php

namespace Maui;

use Maui\SchemaManager;

class SchemaObject {

	use \Maui\TraitHasLabel;

	/**
	 * @var string key in schema. redundant, but handy
	 */
	protected $_key;

	/**
	 * @var string class of relative
	 */
	protected $_class;

	/**
	 * @var string storage class
	 * @see Schema::REF_REFERENCE
	 */
	protected $_reference = SchemaManager::REF_INLINE;

	/**
	 * @var string current object will refer this field, eg. 'user' => 'Admin' refers to the user who has field 'name' the same
	 */
	protected $_referredField = '_id';

	/**
	 * @var \Schema the actual schema data contained in the object. just as the object's
	 * 	own schema, but this schema can be just a subset (maybe expansion) of the original
	 */
	protected $_schema = null;

	/**
	 * @var \SchemaValidator[] relational validators
	 */
	protected $_validators = array();

	/**
	 * @var int minimum relatives
	 */
	protected $_hasMin = null;

	/**
	 * @var int maximum relatives
	 */
	protected $_hasMax = null;

	/**
	 * I tell if $objectSchema is valid definition for an object
	 * @param array|\SchemaObject $objectSchema
	 * @return bool
	 */
	public static function isSchemaObject($objectSchema) {
		if (is_string($objectSchema));
		elseif ($objectSchema instanceof \SchemaObject);
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
	 * @param array|\SchemaObject $objectSchema anything accepted by isSchemaObject()
	 * @param null|string $key key in schema definition, used as 'class' if class is not defined in the object
	 * @return \SchemaObject|static
	 * @throws \Exception
	 */
	public static function from($objectSchema, $context, $key=null) {
		if ($objectSchema instanceof \SchemaObject) {
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

}