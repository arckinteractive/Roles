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

	elgg_register_plugin_hook_handler('view', 'all', 'roles_view_permissions');
	elgg_register_plugin_hook_handler('action', 'all', 'roles_action_permissions');
	elgg_register_event_handler('pagesetup', 'system', 'roles_menu_permissions');
	elgg_register_event_handler('pagesetup', 'system', 'roles_pages_permissions');


	if (elgg_is_admin_logged_in()) {
		elgg_register_plugin_hook_handler('usersettings:save', 'user', 'roles_users_settings_save');
		elgg_extend_view('forms/account/settings', 'roles/settings/account/role', 150);
	}
	
	
}

function roles_view_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_action_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_menu_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_pages_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_users_settings_save($hook_name, $entity_type, $return_value, $params) {
	$role_name = get_input('role');
	$user_id = get_input('guid');
	
	if (!$user_id) {
		$user = elgg_get_logged_in_user_entity();
	} else {
		$user = get_entity($user_id);
	}
	
	if ((elgg_instanceof($user, 'user')) && (elgg_instanceof($role, 'object', 'role'))) {
		$current_role = roles_get_role($user);
		if (strcmp($role_name, $current_role->name) != 0) {
			$options = array(
				'type' => 'object',
				'subtype' => 'role',
				'metadata_name_value_pairs' => array('name' => 'name', 'value' => $role_name, 'operand' => '=')
			);
			$role_array = elgg_get_entities_from_metadata($options);
			if (is_array($role_array) && !empty($role_array)) {
				$new_role = $role_array[0];
				remove_entity_relationships($user->guid, 'has_role');
				if (add_entity_relationship($user->guid, 'has_role', $new_role->guid)) {
					system_message(elgg_echo('user:role:success'));
					return true;
				} else {
					register_error(elgg_echo('user:role:fail'));
				}
			} else {
				register_error(elgg_echo('user:role:fail'));
			}
		} else {
			// no change
			return null;
		}
	} else {
		register_error(elgg_echo('user:role:fail'));
	}
	return false;	
}

?>