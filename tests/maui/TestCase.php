<?php

namespace maui;

abstract class TestCase extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$Maui = \Maui::instance(\Maui::D, 'testing');
		$f = json_decode(file_get_contents(MAUI_ROOT . '/tests/maui/_fixtures/default.json'), true);
		array_walk_recursive($f, function(&$val, $key) {
			if ($key === '_id') {
				$val = new \MongoId($val);
			}
		});
		$DB = $Maui->dbDb();
		$DB->drop();
		foreach ($f as $eachCollection=>$eachData) {
			$Collection = $DB->$eachCollection;
			//$Collection->batchInsert($eachData);
			foreach ($eachData as $eachModelData) {
				$Collection->insert($eachModelData);
			}
		}
	}

}
