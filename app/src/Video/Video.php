<?php

class Video extends \Model {

	protected static $_schema = array(
		'user' => 'User',
		'category' => 'Category',
		'title' => array(
			'type' => 'string',
//			'valCustomFilter' => array(array('\SchemaValidatorMinLength::apply'), 5),
//			array('\SchemaValidatorMinLength::apply', 5),
//			'SchemaValidatorMinLength::_apply' => 5,
			'minLength' => 5,
			'maxLength' => 30,
		),
		'description',
		'length' => array(
			'int',
			'min' => 1,
			'max' => 600,
			'label' => 'Play time',
		),
		'staff' => array(
			'reference' => 'Staff',
			'label' => 'Cast',
			'schema' => array(
				'name',
			)
		)
	);

}
