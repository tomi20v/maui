<?php

require('vendor/autoload.php');

function Maui_autoload($classname) {
	if (!strrpos($classname, '\\') &&
		class_exists($originalClassname = '\\Maui\\' . trim($classname, '\\'))) {
		class_alias($originalClassname, $classname);
	}
}
spl_autoload_register('Maui_autoload');
