<?php

use Maui\SchemaManager;

class Staff extends \User {

	protected static $_schema = array(
		'@@extends' => 'User',
		'role',
	);

	protected static $_referred = SchemaManager::REF_INLINE;

	protected function _getCollectionName() {
		return 'UserCollection';
	}

}
