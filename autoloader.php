<?php

$plugin_root = __DIR__;
if (file_exists("{$plugin_root}/vendor/autoload.php")) {
	// check if composer dependencies are distributed with the plugin
	require_once "{$plugin_root}/vendor/autoload.php";
}

/**
 * Roles service
 *
 * @staticvar \Elgg\Roles\Api $api
 * @return \Elgg\Roles\Api
 * @access private
 */
function roles() {
	static $api;
	if (!isset($api)) {
		$api = new \Elgg\Roles\Api(new \Elgg\Roles\Db());
	}
	return $api;
}
