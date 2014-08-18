<?php

namespace Maui;

/**
 * Class TraitLabelable - use it for anything that can have a label
 *
 * @package Maui
 */
trait TraitHasLabel {

	/**
	 * @var string
	 */
	protected $_label = null;

	/**
	 * @param null $label call without param (or null) to get label,
	 * 					  pass true to get some label (even if not set)
	 * 					  pass false to get label only if set
	 * 					  pass string to set new label
	 * @return string|$this returns label, or null if no label found, or $this if it was a set
	 */
	public function label($labelOrFallback=true) {
		if ((func_num_args() == 1) && !is_bool($labelOrFallback)) {
			$this->_label = $labelOrFallback;
			return $this;
		}
		if (!empty($this->_label)) {
			return $this->_label;
		}
		elseif ($labelOrFallback) {
			if (property_exists($this, '_key')) {
				return $this->_key;
			}
			else {
				return get_called_class();
			}
		}
		return null;
	}

}
