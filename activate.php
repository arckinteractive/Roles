<?php 

if (get_subtype_id('object', 'role')) {
	update_subtype('object', 'role', 'ElggRole');
} else {
	add_subtype('object', 'role', 'ElggRole');
}

elgg_register_library('roles', elgg_get_plugins_path() . 'roles/lib/roles.php');
elgg_register_library('roles_config', elgg_get_plugins_path() . 'roles/lib/config.php');

elgg_load_library('roles');
elgg_load_library('roles_config');

// Create the role objects from the configuration arrays
$roles_array = elgg_trigger_plugin_hook('roles:config', 'role', array(), null);
roles_create_from_config($roles_array);

if (is_null(elgg_get_plugin_setting('automatic_reset', 'roles'))) {
	elgg_set_plugin_setting('automatic_reset', true, 'roles');
}
?>