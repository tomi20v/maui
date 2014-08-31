<?php

class Category extends \Model {

	protected static $_schema = array(
		'label' => array(
			'toString',
			'minLength' => 3,
			'maxLength' => 30,
		),
		'description',
//		'timestamp',
//		'createdby',
	);

}
