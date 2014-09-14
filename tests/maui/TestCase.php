<?php

namespace maui;

abstract class TestCase extends PHPUnit_Framework_TestCase {

	public function setup() {
		$f = json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/default.json'));
//		print_r($f); die('OK');
	}

}
