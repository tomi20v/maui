<?php
/**
 * classmap file - extend \Maui\* classes into root namespace so editors won't have problem with it
 * NEVER include this file, at realtime Maui aliases its classes into root namespace if they are not defined
 */
//die();

class Collection extends \maui\Collection{}
class Maui extends \maui\Maui {}
abstract class Model extends \maui\Model {}
class ModelFinder extends \maui\ModelFinder {}
class ModelManager extends \maui\ModelManager{}
class Schema extends \maui\Schema{}
class SchemaField extends \maui\SchemaField{}
abstract class SchemaFieldAbstract extends \maui\SchemaFieldAbstract {}
class SchemaFieldAttr extends \maui\SchemaFieldAttr{}
class SchemaFieldRelative extends \maui\SchemaFieldRelative{}
class SchemaManager extends \maui\SchemaManager{}
class SchemaValidator extends \maui\SchemaValidator{}
class SchemaValidatorKeys extends \maui\SchemaValidatorKeys{}
class SchemaValidatorKeysValues extends \maui\SchemaValidatorKeysValues{}
class SchemaValidatorTo extends \maui\SchemaValidatorTo{}
