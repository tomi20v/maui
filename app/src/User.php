<?php

class User extends \Model {

	protected static $_referred = \SchemaManager::REF_REFERENCE;

	protected static $_schema = array(
		'_id',
		'name',
//		'registered' => 'Date',
//		'Timestamp',
	);

}
