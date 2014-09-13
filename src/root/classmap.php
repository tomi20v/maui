<?php
/**
 * classmap file - extend \Maui\* classes into root namespace so editors won't have problem with it
 * NEVER include this file, at realtime Maui aliases its classes into root namespace if they are not defined
 */
die();

class Collection extends \maui\Collection{}
class Maui extends \maui\Maui {}
abstract class Model extends \maui\Model {}
class ModelManager extends \maui\ModelManager{}
class Schema extends \maui\Schema{}
class SchemaAttr extends \maui\SchemaAttr{}
class SchemaManager extends \maui\SchemaManager{}
class SchemaRelative extends \maui\SchemaRelative{}
class SchemaValidator extends \maui\SchemaValidator{}
class SchemaValidatorTo extends \maui\SchemaValidatorTo{}
