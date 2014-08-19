<?php

use Maui\SchemaManager;

class Staff extends \User {

	protected static $_referred = SchemaManager::REF_INLINE;

}
