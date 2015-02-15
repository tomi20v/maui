<?php

namespace ninja;

/**
 * Class ModelFinderConstraints - data object for finding options (to be applied to the cursor)
 *
 * @package ninja
 */
class ModelFinderConstraints {

	const SORT_ASC = 1;

	const SORT_DESC = -1;

	public $start=0;

	public $skip=0;

	public $fields=[];

	public $sortFields=[];

	public function __construct($start=null, $limit=null, $fields=null) {
		$this->start = intval($start);
		$this->limit = intval($limit);
		if (!is_null($fields)) {
			$this->fields = $fields;
		}
	}

	/**
	 * I add a sorter by fieldname and direction
	 * @param string $fieldname
	 * @param int $direction as in static::SORT_ASC and SORT_DESC
	 * @return $this
	 */
	public function sortBy($fieldname, $direction=null) {
		if (is_null($direction)) {
			$direction = static::SORT_ASC;
		}
		$this->sortFields[$fieldname] = $direction;
		return $this;
	}

}
