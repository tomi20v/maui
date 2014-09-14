<?php

define('MAUI_ROOT', dirname(__FILE__));

function Maui_autoload($classname) {
	if (!strrpos($classname, '\\') &&
		class_exists($originalClassname = 'maui\\' . trim($classname, '\\'))) {
		class_alias($originalClassname, $classname);
	}
}
spl_autoload_register('Maui_autoload');

date_default_timezone_set('Europe/Berlin');

