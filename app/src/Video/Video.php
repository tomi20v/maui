<?php

class Video extends \Model {

	protected static $_schema = array(
		// this indeed is added automatically
//		'_id',
		'user' => array(
			'class' => 'User',
			'referredField' => 'name',
			'reference' => \SchemaManager::REF_REFERENCE,
			'hasMin' => 1,
			'hasMax' => 1,
		),
		'category' => 'Category',
		'rating' => array(
			'toString',
			'in' => array('G','PG','PG-13','R','NC-17'),
		),
		'title' => array(
			'toString',
//			array('\SchemaValidatorMinLength::apply', 5),
//			'SchemaValidatorMinLength::_apply_' => 5,
			'minLength' => 5,
			'maxLength' => 30,
		),
		'subtitle' => array(
			'required',
			'maxLength' => 50,
		),
		'description',
		'length' => array(
			'label' => 'Play time',
			'toInt',
			'min' => 1,
			'max' => 600,
		),
		'director' => array(
			'class' => 'Staff',
			'reference' => \SchemaManager::REF_REFERENCE,
		),
		'staff' => array(
			'label' => 'Cast',
			'class' => 'Staff',
			'reference' => \SchemaManager::REF_INLINE,
//			'relation' => \Schema::REL_CANHAVESOME,
			'hasMin' => 1,
			'hasMax' => 5,
			'schema' => array(
				'name',
			),
		)
	);

}
