<?php

use Maui\SchemaManager;

class Staff extends \User {

	protected static $_schema = array(
		'_id',
		'name',
		'role',
	);

	protected static $_referred = SchemaManager::REF_INLINE;

}
