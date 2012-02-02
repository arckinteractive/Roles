<?php

/**
 * Roles plugin
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2011
 * @link http://www.arckinteractive.com/
 */



elgg_register_event_handler('init', 'system', 'roles_init');

function roles_init() {

	elgg_register_library('roles', elgg_get_plugins_path() . 'roles/lib/roles.php');
	elgg_register_library('roles_config', elgg_get_plugins_path() . 'roles/lib/config.php');

	elgg_load_library('roles');
	elgg_load_library('roles_config');

	elgg_register_plugin_hook_handler('view', 'all', 'roles_views_permissions');
	elgg_register_plugin_hook_handler('action', 'all', 'roles_actions_permissions');
	elgg_register_event_handler('pagesetup', 'system', 'roles_menus_permissions');
	elgg_register_event_handler('pagesetup', 'system', 'roles_pages_permissions');


	if (elgg_is_admin_logged_in()) {
		elgg_register_plugin_hook_handler('usersettings:save', 'user', 'roles_users_settings_save');
		elgg_extend_view('forms/account/settings', 'roles/settings/account/role', 150);
	}
	
	
}

function roles_views_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_actions_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_menus_permissions($event, $type, $object) {
	$role = roles_get_role();
	
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'menus');
		error_log(print_r($role_perms, 1));
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $menu => $permission) {
				switch ($permission) {
					case 'deny':
						list($menu_name, $item_name) = explode(':', $menu);
						elgg_unregister_menu_item($menu_name, $item_name);
						break;
					default:
						break;
				}
			}
		}
	}
	
	return true;
}

function roles_pages_permissions($event, $type, $object) {
}

function roles_users_settings_save($hook_name, $entity_type, $return_value, $params) {
	$role_name = get_input('role');
	$user_id = get_input('guid');
	$current_role_name = '_default';
	$error = false;
	
	if (!$user_id) {
		$user = elgg_get_logged_in_user_entity();
	} else {
		$user = get_entity($user_id);
	}
	
	if (elgg_instanceof($user, 'user')) {
		// Remove earlier role, if present
		$current_role = roles_get_role($user);
		if (elgg_instanceof($current_role, 'object', 'role')) {
			$current_role_name = $current_role->name;
			if (strcmp($role_name, $current_role_name) != 0) {
				remove_entity_relationships($user->guid, 'has_role');
			}
		}
		
		// Add new role relationship, if necessary
		if (($role_name != '_default') && (strcmp($role_name, $current_role_name) != 0)) {
			$new_role = roles_get_role_by_name($role_name);
			if (elgg_instanceof($new_role, 'object', 'role')) {
				if (!add_entity_relationship($user->guid, 'has_role', $new_role->guid)) {
					$error = true;
				}
			} else {
				$error = true;
			}
		}
	} else {
		$error = true;
	}
	
	if ($error) {
		register_error(elgg_echo('user:role:fail'));
		return false;
	}
	
	// If the user role changed
	if ($role_name != $current_role_name) {
		system_message(elgg_echo('user:role:success'));
		return true;				
	}

	return null;
}

?>