<?php

class User extends \Model {

	protected static $_referred = \Schema::REF_ALWAYS;

	protected static $_schema = array(
		'_id',
		'name',
		'registered' => 'Date',
		'Timestamp',
	);

}
