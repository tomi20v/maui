<?php

namespace Maui;

class SchemaManager extends \Schema {

	/**
	 * always store objects separately and just refer them
	 */
	const REF_REFERENCE = 'reference';
	/**
	 * store inline, move to reference if reused
	 */
	const REF_AUTO = 'auto';
	/**
	 * never refer, have the data inline
	 */
	const REF_INLINE = 'inline';

	/**
	 * relation constants
	 * @todo think it over, maybe the canhavesome and canhaveone shall be changed to
	 *		hassome and hasone including the possibility of 0 and using with min and max
	 */
	/**
	 * object has no relation like this. pretty useless
	 */
	const REL_NONE = 'none';
	/**
	 * has exactly one
	 */
	const REL_HASONE = 'hasOne';
	/**
	 * has 0 or 1
	 */
	const REL_CANHAVEONE = 'canHaveOne';
	/**
	 * has 0, 1, or more
	 */
	const REL_CANHAVESOME = 'canHaveSome';
	/**
	 * has 1 or more
	 */
	const REL_HASSOME = 'hasSome';


	/**
	 * @var \Schema[string] I hold instances of Schema objects keyed by classname
	 */
	protected static $_pool = array();

	protected static $_instance;

	public static function instance() {
		if (is_null(static::$_instance)) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}

	/**
	 * I return the schema for $classname
	 * @param string $context
	 * @return \Schema
	 * @throws \Exception
	 */
	public static function getSchema($context, $attr=null) {
		$context = '\\' . trim($context, '\\');
		if (!array_key_exists($context, self::$_pool)) {
			throw new \Exception('schema not found');
		}
		return static::$_pool[$context];
	}

	/**
	 * I register a schema for an object
	 * @param string $context where the schema comes from, can be a plain classname
	 * @param $schema
	 * @return Schema
	 */
	public static function registerSchema($context, $schema) {
		$context = '\\' . trim($context, '\\');
		if (isset(self::$_pool[$context])) {
			throw new \Exception('schema for ' . $context . ' already registered');
		}
		$schema = static::from($schema, $context);
		self::$_pool[$context] = $schema;
		echo 'registered: ' . $context."\n";
		return $schema;
	}

	public static function from($schema, $context) {
		if ($schema instanceof \Schema) {
			return $schema;
		}
		elseif (is_array($schema)) {
			return static::_fromArray($schema, $context);
		}
		throw new \Exception('cannot create schema from unknown format');
	}

	protected static function _fromArray($schema, $context) {
		$ret = new \Schema();
		foreach ($schema as $eachKey=>$eachVal) {
			// 'field' => 'Classname' reference
			if (is_string($eachKey) && \SchemaObject::isSchemaObject($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaObject::from($eachVal, $context, $eachKey);
			}
			// 'field' (just value, without key)
			elseif (is_numeric($eachKey) && is_string($eachVal)) {
				$ret->_schema[$eachVal] = \SchemaAttr::from($eachVal, $eachVal);
			}
			elseif (is_string($eachKey) && \SchemaAttr::isSchemaAttr($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaAttr::from($eachVal, $eachKey);
			}
			else {
				print_r($eachKey);
				print_r($eachVal);
				die ('FU');
//				throw new \Exception();
			}
		}
		return $ret;
	}

}
