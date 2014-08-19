<?php

namespace Maui;

class SchemaAttr {

	use \Maui\TraitHasLabel;

	/**
	 * @var string attr name in schema
	 */
	protected $_key;

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
					// 'CallbackClass::method' => 5
					case 0&&is_callable($eachKey):
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachKey, $eachVal, $SchemaAttr);
						break;
					// "int"
					case is_numeric($eachKey) && !strncmp($eachVal, 'to', 2) && class_exists('\\SchemaValidator' . ucfirst($eachVal)):
						$SchemaAttr->_validators[] = \SchemaValidatorTo::from($eachVal, $eachVal, $SchemaAttr);
						break;
					case is_string($eachKey) && class_exists('\\SchemaValidator' . ucfirst($eachKey)):
						$SchemaAttr->_validators[] = \SchemaValidator::from($eachKey, $eachVal, $SchemaAttr);
						break;
					case is_array($eachVal) && (count($eachVal) == 2) && is_callable($eachVal[0]):
						die('callbacks not yet implemented');
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

}
