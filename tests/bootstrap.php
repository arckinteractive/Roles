<?php

date_default_timezone_set('UTC');
error_reporting(E_ALL | E_STRICT);
global $CONFIG;
$CONFIG = (object) array(
			'dbprefix' => 'elgg_',
			'boot_complete' => false,
			'wwwroot' => 'http://localhost/',
);
$engine = dirname(dirname(dirname(dirname(__FILE__)))) . '/engine';

require_once "$engine/lib/elgglib.php";
require_once "$engine/lib/sessions.php";

function elgg_get_config($name) {
	global $CONFIG;
	return $CONFIG->$name;
}

require_once dirname(__DIR__) . "/vendor/autoload.php";
