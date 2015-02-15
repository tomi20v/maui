<?php

namespace maui;

/**
 * Class SchemaManager
 * I extend Schema so I can directly manipulate its data
 *
 * @package Maui
 */
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
	 * ID field currently have a constant key
	 */
	const KEY_ID = '_id';

	const KEY_TYPE = '_type';

	/**
	 * @var \Schema[string] I hold instances of Schema objects keyed by classname
	 */
	protected static $_pool = [];

	protected static $_instance;

	public static function instance() {
		if (is_null(static::$_instance)) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}

	protected static function _toContext($context) {
		$context = trim($context, '\\');
		if ($pos = strrpos($context, '\\')) {
			$context = substr($context, $pos+1);
		}
		return $context;
	}

	/**
	 * I return the schema for $classname
	 * @param string|mixed $context
	 * @return \Schema
	 * @throws \Exception
	 */
	public static function getSchema($context) {
		if (is_object($context)) {
			$context = get_class($context);
		}
		$context = static::_toContext($context);
		if (!array_key_exists($context, self::$_pool)) {
			throw new \Exception('schema ' . $context . ' not found');
		}
		return static::$_pool[$context];
	}

	/**
	 * I return names of fields in a schema
	 * @param string|mixed$context
	 * @return string[]
	 * @throws \Exception
	 */
	public static function getFieldnames($context) {
		if (is_object($context)) {
			$context = get_class($context);
		}
		$context = static::_toContext($context);
		if (!array_key_exists($context, self::$_pool)) {
			throw new \Exception('schema ' . $context . ' not found');
		}
		return static::$_pool[$context]->fieldnames();
	}

	/**
	 * I register a schema for an object
	 * @param string $context where the schema comes from, can be a plain classname
	 * @param $schema
	 * @return Schema
	 */
	public static function registerSchema($context, $schema) {
		$context = static::_toContext($context);
		if (isset(self::$_pool[$context])) {
			throw new \Exception('schema for ' . $context . ' already registered');
		}
		$schema = static::from($schema, $context);
		self::$_pool[$context] = $schema;
		return $schema;
	}

	/**
	 * I tell if a given schema is already registered
	 * @param $context
	 * @return bool
	 */
	public static function isRegistered($context) {
		$context = static::_toContext($context);
		return isset(self::$_pool[$context]);
	}

	/**
	 * I construct a $Schema object
	 * @param array $schema data to use
	 * @param string $context the "name" of the class or pseudo-class for which schema stands
	 * @return \Schema
	 * @throws \Exception
	 */
	public static function from($schema, $context) {
		if ($schema instanceof \Schema) {
			return $schema;
		}
		elseif (is_array($schema)) {
			return static::_fromArray($schema, $context);
		}
		throw new \Exception('cannot create schema from unknown format');
	}

	/**
	 * construct Schema object from array definition
	 * @param $schema
	 * @param $context
	 * @return \Schema
	 * @throws \Exception
	 */
	protected static function _fromArray($schema, $context) {

		$ret = new \Schema();

		if (isset($schema['@@extends'])) {
			$extends = $schema['@@extends'];
			if (is_string($extends)) {
				$extends = array($extends);
			}
			if (!is_array($extends)) {
				throw new \Exception('extends definition invalid: ' . echon($extends));
			}
			foreach ($extends as $eachExtends) {
				if (!\SchemaManager::isRegistered($eachExtends)) {
					$eachExtends::__init();
				}
				$extendSchema = \SchemaManager::getSchema($eachExtends);
				foreach ($extendSchema as $eachKey=>$eachField) {
					$ret->_schema[$eachKey] = $eachField;
				}
			}
			unset($schema['@@extends']);
		}

		foreach ($schema as $eachKey=>$eachVal) {
			// 'field' => 'Classname' reference
			if (is_string($eachKey) && \SchemaFieldRelative::isSchemaObject($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaFieldRelative::from($eachVal, $context, $eachKey);
			}
			// 'field' (just value, without key)
			elseif (is_numeric($eachKey) && is_string($eachVal)) {
				$ret->_schema[$eachVal] = \SchemaFieldAttr::from($eachVal, $context, $eachVal);
			}
			elseif (is_string($eachKey) && \SchemaFieldAttr::isSchemaAttr($eachVal)) {
				$ret->_schema[$eachKey] = \SchemaFieldAttr::from($eachVal, $context, $eachKey);
			}
			else {
				throw new \Exception('invalid schema entry: ' . echon($eachKey) . ' / ' . echon($eachVal));
			}
		}
		return $ret;
	}

	/**
	 * I add _id field definition to $Schema if it doesn't have it
	 * @param $schema
	 */
	public static function ensureHasId($schema) {
		$additionalFields = array();
		if (!array_key_exists(static::KEY_ID, $schema)) {
			$additionalFields[\SchemaManager::KEY_ID] = static::_getIdDef();
		};
		if (!array_key_exists(static::KEY_TYPE, $schema)) {
			$additionalFields[\SchemaManager::KEY_TYPE] = static::_getTypeDef();
		}
		if (!empty($additionalFields)) {
			$schema = $additionalFields + $schema;
		}
		// if there's an '_id' defined only by its name, unset it as it would overwrite our new id field...
		if (($key = array_search(\SchemaManager::KEY_ID, $schema)) !== false) {
			unset($schema[$key]);
		}
		// if there's a '_type' defined only by its name, unset it as it would overwrite our new id field...
		if (($key = array_search(\SchemaManager::KEY_TYPE, $schema)) !== false) {
			unset($schema[$key]);
		}

		return $schema;
	}

	/**
	 * I return ID field def. you can override it
	 * @return array
	 */
	protected static function _getIdDef() {
		return array(
			'label' => 'ID',
			'toId',
		);
	}

	/**
	 * I return type field def. you can override it
	 * @return array
	 */
	protected static function _getTypeDef() {
		return array(
			'label' => 'type',
			'toType',
		);
	}

	public static function filterBySchema($dataOrModel, $schemaNameOrSchema) {
		if ($dataOrModel instanceof \Model) {
			$dataOrModel = $dataOrModel->Data()->getData(true);
		}
		if (!is_array($dataOrModel)) {
			throw new \Exception('cannot use for data: ' . echon($dataOrModel));
		}
		if (is_string($schemaNameOrSchema)) {
			$schemaNameOrSchema = static::getSchema($schemaNameOrSchema);
		}
		if (!$schemaNameOrSchema instanceof \Schema) {
			throw new \Exception('not a schema: ' . echon($schemaNameOrSchema));
		}
		return static::_filterBySchema($dataOrModel, $schemaNameOrSchema);
	}

	/**
	 * @param mixed[] $data
	 * @param \Schema $Schema
	 */
	protected static function _filterBySchema($data, $Schema) {
		/**
		 * @var SchemaFieldAttr|SchemaFieldRelative $eachVal
		 */
		foreach ($data as $eachKey => $eachVal) {
			if ($Schema->hasAttr($eachKey));
			elseif ($Schema->hasRelative($eachKey)) {
				if (($eachVal instanceof \Collection) ||
					($eachVal instanceof \Model)) {
					$eachVal = $eachVal->getData(true);
				}
				if (is_array($eachVal)) {
					$data[$eachKey] = static::_filterBySchema($eachVal, $Schema->field($eachKey));
				}
			}
			else {
				unset($data[$eachKey]);
			}
		}
		return $data;
	}

}
