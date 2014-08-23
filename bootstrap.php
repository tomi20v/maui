<?php

require('vendor/autoload.php');

require('vendor/tomi20v/echop/src/echop.php');
require('vendor/tomi20v/echop/src/echox.php');
require('vendor/tomi20v/echop/src/echon.php');

function Maui_autoload($classname) {
	if (!strrpos($classname, '\\') &&
		class_exists($originalClassname = '\\Maui\\' . trim($classname, '\\'))) {
		class_alias($originalClassname, $classname);
	}
}
spl_autoload_register('Maui_autoload');
