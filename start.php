<?php

/**
 * Roles plugin
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2011
 * @link http://www.arckinteractive.com/
 */
define('DEFAULT_ROLE', '_default_');

elgg_register_event_handler('init', 'system', 'roles_init');

function roles_init() {

	elgg_register_library('roles', elgg_get_plugins_path() . 'roles/lib/roles.php');
	elgg_register_library('roles_config', elgg_get_plugins_path() . 'roles/lib/config.php');

	elgg_load_library('roles');
	elgg_load_library('roles_config');

	roles_check_update();

	elgg_register_plugin_hook_handler('action', 'all', 'roles_actions_permissions');
	elgg_register_plugin_hook_handler('route', 'all', 'roles_pages_permissions');

	elgg_register_event_handler('pagesetup', 'system', 'roles_menus_permissions');
	elgg_register_event_handler('ready', 'system', 'roles_hooks_permissions');

	roles_register_views_hook_handler();

	// Registration and profile
	elgg_extend_view('register/extend', 'roles/registration/fields');
	elgg_register_plugin_hook_handler('register', 'user', 'roles_hooks_registration');

	elgg_extend_view('profile/status', 'roles/profile/details');
	elgg_extend_view('forms/profile/edit', 'roles/profile/edit', 1);
	elgg_trigger_event('profileupdate', 'user', 'roles_update_profile_fields');
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

function roles_user_settings_save($hook_name, $entity_type, $return_value, $params) {
	$role_name = get_input('role');
	$user_id = get_input('guid');

	$error = false;

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

function roles_hooks_registration($hook, $type, $return, $params) {

	if (!$role_name = get_input('role', false)) {
		return true;
	}

	$owner = elgg_extract('user', $params, false);

	if (!$owner) {
		return true;
	}

	$role = roles_get_role_by_name($role_name);
	$metafields = roles_get_role_metafields($role, 'registration');

	roles_set_role($role, $owner);

	foreach ($metafields as $shortname => $options) {
		$valuetype = $options['input_type'];

		$value = get_input($shortname);

		if (is_array($value)) {
			array_walk_recursive($value, 'roles_metafields_array_decoder');
		} else {
			$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');
		}

//		if ($options['required'] && empty($value)) {
//			$error = elgg_echo('roles:registration:required', array(elgg_echo("roles:registration:{$shortname}")));
//			register_error($error);
//			return false;
//		}

		// limit to reasonable sizes
		if (!is_array($value) && $valuetype != 'longtext' && elgg_strlen($value) > 250) {
			$error = elgg_echo('roles:registration:field_too_long', array(elgg_echo("roles:registration:{$shortname}")));
			register_error($error);
			return false;
		}

		if ($valuetype == 'tags') {
			$value = string_to_tag_array($value);
		}

		$input[$shortname] = $value;
	}

	if (sizeof($input) > 0) {
		foreach ($input as $shortname => $value) {
			$options = array(
				'guid' => $owner->guid,
				'metadata_name' => $shortname
			);

			$access_id = ACCESS_DEFAULT;

			if (is_array($value)) {
				$i = 0;
				foreach ($value as $interval) {
					$i++;
					$multiple = ($i > 1) ? TRUE : FALSE;
					create_metadata($owner->guid, $shortname, $interval, 'text', $owner->guid, $access_id, $multiple);
				}
			} else {
				create_metadata($owner->getGUID(), $shortname, $value, 'text', $owner->getGUID(), $access_id);
			}
		}
	}

	return true;
}

function roles_update_profile_fields($event, $type, $entity) {

	$owner = $entity;
	$role = roles_get_role($owner);
	$metafields = roles_get_role_metafields($role, 'profile');

	foreach ($metafields as $shortname => $options) {

		$accesslevel = get_input('accesslevel');

		if (!is_array($accesslevel)) {
			$accesslevel = array();
		}

		$valuetype = $options['input_type'];

		$value = get_input($shortname);

		if (is_array($value)) {
			array_walk_recursive($value, 'roles_metafields_array_decoder');
		} else {
			$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');
		}

		if ($options['required'] && empty($value)) {
			$error = elgg_echo('roles:registration:required', array(elgg_echo("roles:registration:{$shortname}")));
			register_error($error);
			return false;
		}

		// limit to reasonable sizes
		if (!is_array($value) && $valuetype != 'longtext' && elgg_strlen($value) > 250) {
			$error = elgg_echo('roles:registration:field_too_long', array(elgg_echo("roles:registration:{$shortname}")));
			register_error($error);
			return false;
		}

		if ($valuetype == 'tags') {
			$value = string_to_tag_array($value);
		}

		$input[$shortname] = $value;
	}

	if (sizeof($input) > 0) {
		foreach ($input as $shortname => $value) {
			$options = array(
				'guid' => $owner->guid,
				'metadata_name' => $shortname
			);
			elgg_delete_metadata($options);
			if (isset($accesslevel[$shortname])) {
				$access_id = (int) $accesslevel[$shortname];
			} else {
				$access_id = ACCESS_DEFAULT;
			}

			if (is_array($value)) {
				$i = 0;
				foreach ($value as $interval) {
					$i++;
					$multiple = ($i > 1) ? TRUE : FALSE;
					create_metadata($owner->guid, $shortname, $interval, 'text', $owner->guid, $access_id, $multiple);
				}
			} else {
				create_metadata($owner->getGUID(), $shortname, $value, 'text', $owner->getGUID(), $access_id);
			}
		}
	}

	return true;
}

function roles_metafields_array_decoder(&$v) {
	$v = html_entity_decode($v, ENT_COMPAT, 'UTF-8');
}

?>