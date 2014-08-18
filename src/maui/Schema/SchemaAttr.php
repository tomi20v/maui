<?php

namespace Maui;

class SchemaAttr {

	use \Maui\TraitHasLabel;

	/**
	 * @var string[] valid data types
	 */
	protected static $_validTypes = array(
		'int',
		'float',
		'string',
		'array',
	);

	/**
	 * @var string attr name in schema
	 */
	protected $_key;

	/**
	 * @var string data types will be cast to this type, see valid ones in $__validTypes
	 */
	protected $_type = 'string';

	protected $_validators = array();

//	protected $_callbacks = array();

	public static function isSchemaAttr($schemaAttr) {
		if ($schemaAttr instanceof \SchemaAttr);
		elseif (is_array($schemaAttr));
		else return false;
		return true;
	}

	public static function from($attrSchema, $key=null) {
		if ($attrSchema instanceof \SchemaAttr) {
			return $attrSchema;
		}
		elseif (is_string($attrSchema)) {
			$SchemaAttr = new static();
			$SchemaAttr->_key = $key;
			return $SchemaAttr;
		}
		elseif (is_array($attrSchema)) {
			$SchemaAttr = new static();
			foreach ($attrSchema as $eachKey=>$eachVal) {
				switch (true) {
					// 'int'
					case $eachKey == 'type':
						$SchemaAttr->_type = $eachVal;
						break;
					// 'type' => 'int'
					case (is_numeric($eachKey) && in_array($eachVal, static::$_validTypes)):
						$SchemaAttr->_type = $eachKey;
						break;
					// 'label' => 'asd',
					case $eachKey == 'label':
						$SchemaAttr->_label = $eachVal;
						break;
					// 'CallbackClass::method' => 5
					case is_callable($eachKey):
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachKey, $eachVal);
						break;
					case is_array($eachVal) && (count($eachVal) == 2) && is_callable($eachVal[0]):
						die('callbacks not yet implemented');
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

}
