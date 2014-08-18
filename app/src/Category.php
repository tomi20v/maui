<?php

class Category extends \Model {

	protected static $_schema = array(
		'title' => array(
			'type' => 'string',
			'minLength' => 3,
			'maxLength' => 30,
		),
		'description',
		'timestamp',
		'createdby',
	);

}
