<?php
/**
 * classmap file - extend \Maui\* classes into root namespace so editors won't have problem with it
 * NEVER include this file, at realtime Maui aliases its classes into root namespace if they are not defined
 */
die();

class Collection extends \Maui\Collection{}
class Maui extends \Maui\Maui {}
abstract class Model extends \Maui\Model {}
class ModelManager extends \Maui\ModelManager{}
class Schema extends \Maui\Schema{}
class SchemaAttr extends \Maui\SchemaAttr{}
class SchemaManager extends \Maui\SchemaManager{}
class SchemaRelative extends \Maui\SchemaRelative{}
class SchemaValidator extends \Maui\SchemaValidator{}
class SchemaValidatorTo extends \Maui\SchemaValidatorTo{}
