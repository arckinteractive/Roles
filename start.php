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

	
	elgg_register_plugin_hook_handler('action', 'all', 'roles_actions_permissions');
	elgg_register_plugin_hook_handler('route', 'all', 'roles_pages_permissions');
	elgg_register_event_handler('pagesetup', 'system', 'roles_menus_permissions');
	
	roles_register_views_hook_handler();


	if (elgg_is_admin_logged_in()) {
		elgg_register_plugin_hook_handler('usersettings:save', 'user', 'roles_users_settings_save');
	}
	
	roles_check_update();
	
}

function roles_register_views_hook_handler() {
	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'views');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $view => $perm_details) {
				switch ($perm_details['rule']) {
					case 'deny':
						elgg_register_plugin_hook_handler('view', $view, 'roles_views_permissions');
						break;
					case 'extend':
						$params = $perm_details['view_extension'];
						$view_extension = $params['view'];
						$priority = isset($params['priority']) ? $params['priority'] : 501;
						$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
						elgg_extend_view($view, $view_extension, $priority, $viewtype);
						break;
					case 'replace':
						$params = $perm_details['view_replacement'];
						$location = elgg_get_root_path() . $params['location'];
						$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
						elgg_set_view_location($view, $location, $viewtype);
						break;
					case 'allow':
					default:
						break;
				}
			}
		}
	}
}

function roles_views_permissions($hook_name, $entity_type, $return_value, $params) {

	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'views');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $view => $perm_details) {
				if ($params['view'] == $view) {
					return '';	// Supress view output
				}
			}
		}
	}
}


function roles_actions_permissions($hook_name, $entity_type, $return_value, $params) {
}

function roles_menus_permissions($event, $type, $object) {

	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'menus');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $menu => $perm_details) {
 				switch ($perm_details['rule']) {
					case 'deny':
						list($menu_name, $item_name) = explode('::', $menu);
						elgg_unregister_menu_item($menu_name, $item_name);
						break;
					case 'extend':
						$menu_item = roles_prepare_menu_vars($perm_details['menu_item']);
						$menu_obj = ElggMenuItem::factory($menu_item);
						elgg_register_menu_item($menu, $menu_obj);
						break;
					case 'replace':
						list($menu_name, $item_name) = explode('::', $menu);
						$menu_item = roles_prepare_menu_vars($perm_details['menu_item']);
						$menu_obj = ElggMenuItem::factory($menu_item);
						roles_replace_menu($menu_name, $item_name, $menu_obj);
						break;
					case 'allow':
					default:
						break;
				}
			}
		}
	}
	
	return true;
}

function roles_pages_permissions($hook_name, $entity_type, $return_value, $params) {
	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'pages');
		$page_path = $return_value['handler'] . '/' . implode('/', $return_value['segments']);
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $page => $perm_details) {
				error_log("Checking $page against $page_path");
				if (roles_replace_dynamic_paths($page) == $page_path) {
					switch ($perm_details['rule']) {
						case 'deny':
							register_error(elgg_echo('roles:page:denied'));
							if (isset($perm_details['forward'])) {
								forward($perm_details['forward']);
							} else {
								forward(REFERER);
							}
							break;
						case 'redirect':
							if (isset($perm_details['forward'])) {
								forward($perm_details['forward']);
							} else {
								forward(REFERER);
							}
							break;
						case 'allow':
						default:
							break;
					}
				}
			}
		}
	}
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