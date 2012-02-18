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

// Delete all role objects upon plugin activation. Not sure if it's a good decision to automatically delete existing roles
$roles = roles_get_all_roles();
foreach($roles as $role) {
	$role->delete();
}

// Create the role objects from the configuration arrays
$roles_array = elgg_trigger_plugin_hook('roles:config', 'role', array(), null);
roles_create_from_config($roles_array);


?>