<?php

/**
 *
 * Roles plugin
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2012
 * @link http://www.arckinteractive.com/
 */

/**
 *
 * Default role constants definitions
 */
define('DEFAULT_ROLE', 'default');
define('ADMIN_ROLE', 'admin');
define('VISITOR_ROLE', 'visitor');
define('NO_ROLE', '_no_role_');

/**
 *
 * Register Roles plugin's init function
 */
elgg_register_event_handler('init', 'system', 'roles_init');

/**
 *
 * Initializes the Roles plugin
 */
function roles_init($event, $type, $object) {
	elgg_extend_view('forms/useradd', 'roles/useradd');

	elgg_register_library('roles', elgg_get_plugins_path() . 'roles/lib/roles.php');
	elgg_register_library('roles_config', elgg_get_plugins_path() . 'roles/lib/config.php');

	elgg_load_library('roles');
	elgg_load_library('roles_config');

	// Provides default roles by own handler. This should be extended by site specific handlers
	elgg_register_plugin_hook_handler('roles:config', 'role', 'roles_get_roles_config');

	// Catch all actions and page route requests
	elgg_register_plugin_hook_handler('action', 'all', 'roles_actions_permissions');
	elgg_register_plugin_hook_handler('route', 'all', 'roles_pages_permissions');

	// Due to dynamically created (or extended) menus, we need to catch all 'register' hooks _after_ other modules added/removed their menu items
	elgg_register_plugin_hook_handler('register', 'all', 'roles_menus_permissions', 9999);
	
	// Set up roles based hooks and event listener, after all plugin is initialized
	elgg_register_event_handler('ready', 'system', 'roles_hooks_permissions');
	elgg_register_event_handler('ready', 'system', 'roles_events_permissions');
	elgg_register_event_handler('create', 'user', 'roles_create_user');

	// Check for role configuration updates
	if (elgg_is_admin_logged_in()) {	// @TODO think through if this should rather be a role-based permission
		run_function_once('roles_update_100_to_101');
		elgg_register_event_handler('ready', 'system', 'roles_check_update');
	}

	// Set up roles based view management
	elgg_register_event_handler('ready', 'system', 'roles_register_views');

}

/**
 *
 * Processes view permissions from the role configuration array. This is called after the 'ready', 'system' event.
 *
 * For view extension and replacements the function simply calls the corresponding {@link elgg_extend_view()} and
 * {@link elgg_set_view_location()} functions, to post-register views after all plugins have been initalized.
 *
 * For suppressing views (by using the "deny" rule), it registers a specific handler for the given view,
 * to return an empty string instead of the view's original output. This is to conserve resources -
 * there are hundreds of views contributing to any elgg page. Listening for all "views", "all" hooks would
 * be quite a waste.
 *
 * @param string $event Equals 'ready'
 * @param string $event_type Equals 'system'
 * @param mixed $object Not in use for this specific listener
*/
function roles_register_views($event, $type, $object) {
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
						$view_extension = roles_replace_dynamic_paths($params['view']);
						$priority = isset($params['priority']) ? $params['priority'] : 501;
						$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
						elgg_extend_view($view, $view_extension, $priority, $viewtype);
						break;
					case 'replace':
						$params = $perm_details['view_replacement'];
						$location = elgg_get_root_path() . roles_replace_dynamic_paths($params['location']);
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

/**
 *
 * A hook handler registered by {@link roles_register_views()} to suppress the outputs of certain views defined by
 * the role configuration array.
 *
 * @param string $hook_name Equals "view"
 * @param string $type The view name
 * @param mixed $return_value The original view output
 * @param mixed $params An associative array of parameters provided by the hook trigger
 *
 * @return string	An empty string to suppress the output of the original view
 */
function roles_views_permissions($hook_name, $type, $return_value, $params) {

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


/**
 *
 * Processes action permissions from the role configuration array. This is called upon each action execution.
 *
 * @param string $hook_name Equals "action"
 * @param string $type The registered action name
 * @param boolean $return_value
 * @param mixed $params An associative array of parameters provided by the hook trigger

 * @return boolean	True if the action should be executed, false if it should be stopped
 */
function roles_actions_permissions($hook, $type, $return_value, $params) {
	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'actions');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $action => $perm_details) {
				if (roles_path_match(roles_replace_dynamic_paths($action), $type)) {
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


/**
 *
 * Processes menu permissions from the role configuration array. This is called upon each "register" triggered hook.
 *
 * @param string $hook_name Equals "register"
 * @param string $type The triggered "register" hook's type
 * @param boolean $return_value
 * @param mixed $params An associative array of parameters provided by the hook trigger
 */

function roles_menus_permissions($hook, $type, $return_value, $params) {

	$updated_menu = $return_value;

	// Ignore all triggered hooks except for 'menu:menu_name' type
	list($hook_type, $prepared_menu_name) = explode(':', $type);

	if (($hook_type == 'menu') && !empty($prepared_menu_name)) {
		$role = roles_get_role();
		if (elgg_instanceof($role, 'object', 'role')) {
			$role_perms = roles_get_role_permissions($role, 'menus');
			
			if (is_array($role_perms) && !empty($role_perms)) {

				foreach ($role_perms as $menu => $perm_details) {

					$menu_parts = explode('::', $menu);
					$menu_name = isset($menu_parts[0]) ? $menu_parts[0] : "";

					// Check if this rule relates to the currently triggered menu and if we're in the right context for the current rule
					if (roles_check_context($perm_details)) {
						// Try to act on this permission rule
						switch ($perm_details['rule']) {
							case 'deny':
								$updated_menu = roles_unregister_menu_item_recursive($updated_menu, $menu, $prepared_menu_name);
								break;
							case 'extend':
								if ($menu_name === $prepared_menu_name) {
									$menu_item = roles_prepare_menu_vars($perm_details['menu_item']);
									$menu_obj = ElggMenuItem::factory($menu_item);
									elgg_register_menu_item($menu_name, $menu_obj);
									$updated_menu = roles_get_menu($menu_name);
								}
								break;
							case 'replace':
								$menu_item = roles_prepare_menu_vars($perm_details['menu_item']);
								$menu_obj = ElggMenuItem::factory($menu_item);
								$updated_menu = roles_replace_menu_item_recursive($updated_menu, $menu, $prepared_menu_name, $menu_obj);
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
	// Return the updated menu to the hook triggering function (elgg_view_menu)
	return $updated_menu;
}

/**
 *
 * Processes page permissions from the role configuration array. This is called upon each "route" triggered hook.
 *
 * @param string $hook_name Equals "route"
 * @param string $type The triggered "register" hook's type
 * @param mixed $return_value
 * @param mixed $params An associative array of parameters provided by the hook trigger
 */
function roles_pages_permissions($hook_name, $type, $return_value, $params) {
	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'pages');
		$page_path = $return_value['handler'] . '/' . implode('/', $return_value['segments']);
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $page => $perm_details) {
				if (roles_path_match(roles_replace_dynamic_paths($page), $page_path)) {
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

/**
 *
 * Processes hook permissions from the role configuration array. Triggered by the 'ready', 'system' event.
 * This is to make sure that all plugins' init functions have been executed, and all hook handlers have already been initialized
 *
 * @param string $event Equals 'ready'
 * @param string $event_type Equals 'system'
 * @param mixed $object Not in use for this specific listener
 * @return boolean
 */
function roles_hooks_permissions($event, $event_type, $object) {

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


/**
 *
 * Processes event permissions from the role configuration array. Triggered by the 'ready', 'system' event.
 * This is to make sure that all plugins' init functions have been executed, and all event handlers have already been initialized
 *
 * @param string $event Equals 'ready'
 * @param string $event_type Equals 'system'
 * @param mixed $object Not in use for this specific listener
 * @return boolean
 */
function roles_events_permissions($event, $type, $object) {

	$role = roles_get_role();
	if (elgg_instanceof($role, 'object', 'role')) {
		$role_perms = roles_get_role_permissions($role, 'events');
		if (is_array($role_perms) && !empty($role_perms)) {
			foreach ($role_perms as $event => $perm_details) {
				list($event_name, $type) = explode('::', $event);
				if (!$type) {
					$type = 'all';
				}
				switch ($perm_details['rule']) {
					case 'deny':
						$params = $perm_details['event'];
						if (is_array($params)) {
							$handler = $params['handler'];
							elgg_unregister_event_handler($event_name, $type, $handler);
						} else {
							global $CONFIG;
							unset($CONFIG->events[$event_name][$type]);
						}
						break;
					case 'extend':
						$params = $perm_details['event'];
						$handler = $params['handler'];
						$priority = isset($params['priority']) ? $params['priority'] : 500;
						elgg_register_event_handler($event_name, $type, $handler, $priority);
						break;
					case 'replace':
						$params = $perm_details['hook'];
						$old_handler = $params['old_handler'];
						$new_handler = $params['new_handler'];
						$priority = isset($params['priority']) ? $params['priority'] : 500;
						elgg_unregister_event_handler($event_name, $type, $old_handler);
						elgg_register_event_handler($event_name, $type, $new_handler, $priority);
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

/**
 *
 * Saves user role upon changing role on the user settings page
 *
 * @param string $hook_name Equals "usersettings:save"
 * @param string $entity_type Equals "user"
 * @param mixed $return_value
 * @param mixed $params An associative array of parameters provided by the hook trigger
 */
function roles_user_settings_save($hook_name, $entity_type, $return_value, $params) {
	$role_name = get_input('role');
	$user_id = get_input('guid');

	$role_name = roles_filter_role_name($role_name, $user_id);
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


function roles_create_user($event, $type, $user) {
	$rolename = get_input('role', false);
	if (elgg_is_admin_logged_in() && $rolename) {
		// admin is adding a user, give them the role they asked for
		$role = roles_get_role_by_name($rolename);
		
		if ($role) {
			roles_set_role($role, $user);
		}
	}
}
