<?php

namespace maui;

/**
 * Class TraitFromJson - include it in models to create them from json string
 *
 * @package maui
 */
trait TraitFromJson {

	/**
	 * I return myself constructed from data in json string
	 * @param string $json
	 * @return static
	 */
	public static function fromJson($json) {
		$json = json_decode($json, true);
		$ModReg = new static($json, true);
		return $ModReg;
	}

}
