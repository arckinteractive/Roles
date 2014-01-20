<?php

if (elgg_get_plugin_setting('automatic_reset', 'roles')) {
	// Delete all role objects upon plugin deactivate if the plugin setting says so
	$roles = roles_get_all_roles();
	foreach ($roles as $role) {
		$role->delete();
	}

	elgg_unset_plugin_setting('automatic_reset', 'roles');
	elgg_unset_plugin_setting('roles_hash', 'roles');
}