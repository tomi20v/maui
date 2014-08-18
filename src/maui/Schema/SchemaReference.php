<?php

namespace Maui;

class SchemaReference {

	use \Maui\TraitHasLabel;

	/**
	 * @var string
	 */
	protected $_referredClass;

	/**
	 * @var string
	 */
	protected $_key;

	/**
	 * @var \Schema
	 */
	protected $_schema = null;

	public static function isSchemaReference($referenceSchema) {
		if (is_string($referenceSchema));
		elseif ($referenceSchema instanceof \SchemaReference);
		elseif (is_array($referenceSchema)) {
			// if array, a 'reference' => 'classname' is expected
			if (!isset($referenceSchema['reference'])) {
				return false;
			}
		}
		else
			return false;
		return true;
	}

	public static function from($referenceSchema, $key=null) {
		if ($referenceSchema instanceof \SchemaReference) {
			$SchemaReference = $referenceSchema;
		}
		elseif (is_string($referenceSchema) && !is_null($key)) {
			$referredClass = $referenceSchema;
			if (is_numeric($key)) {
				$key = $referredClass;
			}
			if (!class_exists($referredClass)) {
				throw new \Exception('Class ' . $referredClass . ' does not exist');
			}
			$SchemaReference = new static();
			$SchemaReference->_referredClass = $referredClass;
		}
		elseif (is_array($referenceSchema)) {
			$referredClass = null;
			if (isset($referenceSchema['reference'])) {
				$referredClass = $referenceSchema['reference'];
				unset($referenceSchema['reference']);
			}
			elseif (is_string($key) && !is_numeric($key)) {
				$referredClass = $key;
			}
			if (is_null($referredClass) || !class_exists($referredClass)) {
				throw new \Exception('could not determine class for reference: ' . $referredClass);
			}
			$SchemaReference = new static();
			$SchemaReference->_referredClass = $referredClass;
			foreach ($referenceSchema as $eachKey=>$eachVal) {
				switch(true) {
					case $eachKey == 'schema':
						$SchemaReference->_schema = \SchemaManager::from($eachVal);
						break;
					case $eachKey == 'label':
						$SchemaReference->_label;
						break;
					default:
						throw new \Exception('unrecognized SchemaReference key ' . $eachKey);
				}
			}
		}
		else {
			print_r($referenceSchema); die;
			throw new \Exception();
		}
		if (!is_null($key) && !is_numeric($key)) {
			$SchemaReference->_key = $key;
		}
		return $SchemaReference;
	}

}
