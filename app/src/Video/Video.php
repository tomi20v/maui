<?php

class Video extends \Model {

	protected static $_schema = array(
		'User' => array(
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
			'required',
			'toString',
			'minLength' => 5,
			'maxLength' => 30,
		),
		'subtitle' => array(
			'required',
			'maxLength' => 50,
		),
		'description',
		'length' => array(
			'required',
			'label' => 'Play time',
			'toInt',
			'minValue' => 1,
			'maxValue' => 600,
		),
		'Director' => array(
			'class' => 'Staff',
			'reference' => \SchemaManager::REF_REFERENCE,
		),
		'Staff' => array(
			'label' => 'Cast',
			'class' => 'Staff',
			'reference' => \SchemaManager::REF_INLINE,
			'hasMin' => 0,
			'hasMax' => 5,
			'schema' => array(
				'name',
			),
		),
		'awards' => array(
			'toArray',
			'hasMax' => 10,
			'keys' => array('award', 'category', 'year'),
			'keysRequired' => array('award'),
		),
	);

}
