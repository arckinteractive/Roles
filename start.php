<?php

/**
 * Roles plugin
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2011
 * @link http://www.arckinteractive.com/
 */

define('DEFAULT_ROLE', 'default');
define('ADMIN_ROLE', 'admin');
define('VISITOR_ROLE', 'visitor');
define('NO_ROLE', '_no_role_');

elgg_register_event_handler('init', 'system', 'roles_init');

function roles_init() {

	elgg_register_library('roles', elgg_get_plugins_path() . 'roles/lib/roles.php');
	elgg_register_library('roles_config', elgg_get_plugins_path() . 'roles/lib/config.php');

	elgg_load_library('roles');
	elgg_load_library('roles_config');

	// Provides default roles by own handler. This should be extended by site specific handlers
	elgg_register_plugin_hook_handler('roles:config', 'role', 'roles_get_roles_config');

	// Catch all actions and page route requests
	elgg_register_plugin_hook_handler('action', 'all', 'roles_actions_permissions');
	elgg_register_plugin_hook_handler('route', 'all', 'roles_pages_permissions');

	elgg_register_event_handler('pagesetup', 'system', 'roles_menus_permissions');

	// 
	elgg_register_event_handler('ready', 'system', 'roles_hooks_permissions');

	// Check for role configuration updates 
	elgg_register_event_handler('ready', 'system', 'roles_update_checker');

}

function roles_register_views() {
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
						$params = roles_replace_dynamic_paths($perm_details['view_extension']);
						$view_extension = $params['view'];
						$priority = isset($params['priority']) ? $params['priority'] : 501;
						$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
						elgg_extend_view($view, $view_extension, $priority, $viewtype);
						break;
					case 'replace':
						$params = roles_replace_dynamic_paths($perm_details['view_replacement']);
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
					return ''; // Supress view output
				}
			}
		}
	}
}

function roles_actions_permissions($hook, $type, $return_value, $params) {
	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'actions');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $action => $perm_details) {
				if ($action == $type) {
					switch ($perm_details['rule']) {
						case 'deny':
							register_error(elgg_echo('roles:action:denied'));
							return false;
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

function roles_hooks_permissions($event, $type, $object) {

	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'hooks');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $hook => $perm_details) {
				list($hook_name, $type) = explode('::', $hook);
				if (!$type) {
					$type = 'all';
				}
				switch ($perm_details['rule']) {
					case 'deny':
						$params = $perm_details['hook'];
						if (is_array($params)) {
							$handler = $params['handler'];
							elgg_unregister_plugin_hook_handler($hook_name, $type, $handler);
						} else {
							global $CONFIG;
							unset($CONFIG->hooks[$hook_name][$type]);
						}
						break;
					case 'extend':
						$params = $perm_details['hook'];
						$handler = $params['handler'];
						$priority = isset($params['priority']) ? $params['priority'] : 500;
						elgg_register_plugin_hook_handler($hook_name, $type, $handler, $priority);
						break;
					case 'replace':
						$params = $perm_details['hook'];
						$old_handler = $params['old_handler'];
						$new_handler = $params['new_handler'];
						$priority = isset($params['priority']) ? $params['priority'] : 500;
						elgg_unregister_plugin_hook_handler($hook_name, $type, $old_handler);
						elgg_register_plugin_hook_handler($hook_name, $type, $new_handler, $priority);
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


function roles_update_checker($event, $type, $object) {
	roles_check_update();
	roles_register_views();
}


function roles_user_settings_save($hook_name, $entity_type, $return_value, $params) {
	$role_name = get_input('role');
	$user_id = get_input('guid');

	$role_name = roles_filter_role_name($role_name);
	$role = roles_get_role_by_name($role_name);
	$user = get_entity($user_id);

	$res = roles_set_role($role, $user);

	if ($res === false) {
		register_error(elgg_echo('user:role:fail'));
		return false;
	} else if ($res === true) {
		system_message(elgg_echo('user:role:success'));
		return true;
	}

	return null;
}

?>