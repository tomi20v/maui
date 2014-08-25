<?php

namespace Maui;

/**
 * abstract class for type casting validators REMEMBER: you must override validate() method as well
 * note: type casting operators are always run when a value is applied to a field
 * @see SchemaValidator::validate()
 */
abstract class SchemaValidatorTo extends \SchemaValidator {}
