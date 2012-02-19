<?php

/**
 *
 * Roles library functions
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2012
 * @link http://www.arckinteractive.com/
 */

/**
 * Obtains the role of a given user
 *
 * @param ElggUser $user

 * @return ElggRole The role the user belongs to
 */
function roles_get_role($user = null) {

	$user = $user ? $user : elgg_get_logged_in_user_entity();

	if (elgg_instanceof($user, 'user')) {
		$options = array(
			'type' => 'object',
			'subtype' => 'role',
			'relationship' => 'has_role',
			'relationship_guid' => $user->guid,
		);
		$roles = elgg_get_entities_from_relationship($options);

		if (is_array($roles) && !empty($roles)) {
			return $roles[0];
		}
	}

	// Couldn't find role for the current user, or there is no logged in user
	return roles_get_role_by_name(roles_filter_role_name(NO_ROLE));
}

/**
 * Checks if the user has a specific role
 *
 * @param ElggUser $user

 * @return bool True if the user belongs to the passed role, false otherwise
 */
function roles_has_role($user = null, $role_name = DEFAULT_ROLE) {

	$user = $user ? $user : elgg_get_logged_in_user_entity();
	if (!elgg_instanceof($user, 'user')) {
		return false;
	}

	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'metadata_name_value_pairs' => array('name' => 'name', 'value' => $role_name, 'operand' => '='),
		'relationship' => 'has_role',
		'relationship_guid' => $user->guid,
	);
	$roles = elgg_get_entities_from_relationship($options);


	if (is_array($roles) && !empty($roles)) {
		return true;
	}
	return false;
}

/**
 * Assigns a role to a particular user
 *
 * @param ElggRole $role The role to be assigned
 * @param ElggUser $user The user the role needs to be assigned to

 * @return mixed True if the role change was successful, false if could not update user role, and null if there was no change in user role
 */

function roles_set_role($role, $user = null) {
	if (!elgg_instanceof($role, 'object', 'role')) {
		return false;	// Couldn't set new role
	}

	$user = $user ? $user : elgg_get_logged_in_user_entity();
	if (!elgg_instanceof($user, 'user')) {
		return false;	// Couldn't set new role
	}

	$current_role = roles_get_role($user);
	if ($role != $current_role) {
		remove_entity_relationships($user->guid, 'has_role');
		if (($role->name != DEFAULT_ROLE) && ($role->name != ADMIN_ROLE)) {
			if (!add_entity_relationship($user->guid, 'has_role', $role->guid)) {
				return false;	// Couldn't set new role
			}
		}
		return true; // Role has been changed
	}

	return null; // There was no change necessary, old and new role are the same
}

/**
 * Gets all role objects
 *
 * @return mixed An array of ElggRole objects defined in the system, or false if none found
 */

function roles_get_all_roles() {

	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => 0

	);
	return elgg_get_entities($options);

}

/**
 *
 * Gets all non-default role objects
 * This is used by the role selector view. Default roles (VISITOR_ROLE, ADMIN_ROLE, DEFAULT_ROLE) need to be omitted from
 * the list of selectable roles - as default roles are automatically assigned to users based on their Elgg membership type
 *
 * @return mixed An array of non-default ElggRole objects defined in the system, or false if none found
 */
function roles_get_all_selectable_roles() {

	$dbprefix = elgg_get_config('dbprefix');
	$reserved_role_names = "('" . implode("','", ElggRole::getReservedRoleNames()) ."')";
	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => 0,
		'joins' => array(
			"INNER JOIN {$dbprefix}metadata m ON (m.entity_guid = e.guid)",
			"INNER JOIN {$dbprefix}metastrings s1 ON (s1.id = m.name_id AND s1.string = 'name')",
			"INNER JOIN {$dbprefix}metastrings s2 ON (s2.id = m.value_id)",
		),
		'wheres' => array("s2.string NOT IN $reserved_role_names")
	);
	return elgg_get_entities($options);
}

/**
 * Obtains a list of permissions associated with a particular role object
 *
 * @global array $PERMISSIONS_CACHE In-memory cache for role permission
 * @param ElggRole $role The role to check for permissions
 * @param string $permission_type The section from the configuration array ('actions', 'menus', 'views', etc.)
 *
 * @return array The permission rules for the given role and permission type
 */

function roles_get_role_permissions($role = null, $permission_type = null) {
	global $PERMISSIONS_CACHE;

	$role = ($role == null) ? roles_get_role() : $role;
	if (!elgg_instanceof($role, 'object', 'role')) {
		return false;
	}

	if (!isset($PERMISSIONS_CACHE[$role->name])) {
		roles_cache_permissions($role);
	}

	if ($permission_type) {
		return $PERMISSIONS_CACHE[$role->name][$permission_type];
	} else {
		return $PERMISSIONS_CACHE[$role->name];
	}

}

/**
 * Caches permissions associated with a role object. Also resolves all role extensions.
 *
 * @global array $PERMISSIONS_CACHE In-memory cache for role permission
 * @param ElggRole $role The role to cache permissions for
 */

function roles_cache_permissions($role) {
	global $PERMISSIONS_CACHE;
	if (!is_array($PERMISSIONS_CACHE[$role->name])) {
		$PERMISSIONS_CACHE[$role->name] = array();
	}

	// Let' start by processing role extensions
	$extends = $role->extends;
	if (!empty($role->extends) && !is_array($extends)) {
		$extends = array($extends);
	}
	if (is_array($extends) &&  !empty($extends)) {
		foreach ($extends as $extended_role_name) {

			$extended_role = roles_get_role_by_name($extended_role_name);
			if (!isset($PERMISSIONS_CACHE[$extended_role->name])) {
				roles_cache_permissions($extended_role);
			}

			foreach ($PERMISSIONS_CACHE[$extended_role->name] as $type => $permission_rules) {
				if (is_array($PERMISSIONS_CACHE[$role->name][$type])) {
					$PERMISSIONS_CACHE[$role->name][$type] = array_merge($PERMISSIONS_CACHE[$role->name][$type], $permission_rules);
				} else {
					$PERMISSIONS_CACHE[$role->name][$type] = $permission_rules;
				}
			}
		}
	}

	$permissions = unserialize($role->permissions);
	foreach ($permissions as $type => $permission_rules) {
		if (is_array($PERMISSIONS_CACHE[$role->name][$type])) {
			$PERMISSIONS_CACHE[$role->name][$type] = array_merge($PERMISSIONS_CACHE[$role->name][$type], $permission_rules);
		} else {
			$PERMISSIONS_CACHE[$role->name][$type] = $permission_rules;
		}
	}

}

/**
 * Gets a role object based on it's name
 *
 * @param string $role_name The name of the role

 * @return mixed An ElggRole object if it could be found based on the name, false otherwise
 */

function roles_get_role_by_name($role_name) {
	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'metadata_name_value_pairs' => array('name' => 'name', 'value' => $role_name, 'operand' => '=')
	);
	$role_array = elgg_get_entities_from_metadata($options);

	if (is_array($role_array) && !empty($role_array)) {
		return $role_array[0];
	} else {
		return false;
	}

}

/**
 * Resolves the default role for the currently logged in user

 * @param string $role_name The name uf the user's role
 */
function roles_filter_role_name($role_name) {
	if ($role_name !== NO_ROLE) {
		return $role_name;
	}

	if (!elgg_is_logged_in()) {
		return VISITOR_ROLE;
	} else if (elgg_is_admin_logged_in()) {
		return ADMIN_ROLE;
	} else {
		return DEFAULT_ROLE;
	}
}

/**
 * Processes the configuration files and generates the appropriate ElggRole objects.
 *
 * If, for any role definition, there is an already existing role with the same name,
 * the role permissions will be updated for the given role object.
 * If there is no previously existing, corresponding role object, it will be created now.
 *
 * @param array $roles_array The roles configuration array
 */

function roles_create_from_config($roles_array) {

	$options = array(
			'type' => 'object',
			'subtype' => 'role',
	);
	$roles = elgg_get_entities($options);

	$existing_roles = array();
	foreach ($roles as $role) {
		$existing_roles[$role->name] = $role;
	}

	foreach($roles_array as $rname => $rdetails) {
		$current_role = $existing_roles[$rname];
		if (elgg_instanceof($current_role, 'object', 'role')) {
			// Update existing role obejct
			$current_role->title = elgg_echo($rdetails['title']);
			$current_role->extends = $rdetails['extends'];
			$current_role->permissions = serialize($rdetails['permissions']);
			$current_role->save();
		} else {
			// Create new role object
			$new_role = new ElggRole();
			$new_role->title = elgg_echo($rdetails['title']);
			$new_role->owner_guid = elgg_get_logged_in_user_guid();
			$new_role->container_guid = $new_role->owner_guid;
			$new_role->access_id = ACCESS_PUBLIC;
			if (!($new_role->save())) {
				error_log('Could not create new role $rname');
			} else {
				// Add metadata
				$new_role->name = $rname;
				$new_role->extends = $rdetails['extends'];
				$new_role->permissions = serialize($rdetails['permissions']);
			}
		}
	}

}

/**
 * Checks if the configuration array has been updated and updates role objects accordingly if needed
 *
 */
function roles_check_update() {
	$hash = elgg_get_plugin_setting('roles_hash');
	$roles_array = elgg_trigger_plugin_hook('roles:config', 'role', array(), null);

	$current_hash = sha1(serialize($roles_array));

	if ($hash != $current_hash) {
		roles_create_from_config($roles_array);
		elgg_set_plugin_setting('roles_hash', $current_hash);
	}
}


/**
 *
 * Replaces an existing menu item with a new one
 *
 * @param string $menu_name The menu name ('site', 'filter', etc.)
 * @param string $item_name The menu item's name ('blog', 'bookmarks', etc.) to be replaced
 * @param ElggMenuItem $menu_obj The replacement menu item
 */
function roles_replace_menu($menu_name, $item_name, $menu_obj) {
	global $CONFIG;

	if (false !== $index = roles_find_menu_index($menu_name, $item_name)) {
		array_splice($CONFIG->menus[$menu_name], $index, 1, array($menu_obj));
	}
}

/**
 *
 * Finds the index of a menu item in the menu array
 *
 * @param string $menu_name The menu name ('site', 'filter', etc.)
 * @param string $item_name The menu item's name ('blog', 'bookmarks', etc.) to be replaced
 *
 * @return int The index of the menu item in the menu array
 */
function roles_find_menu_index($menu_name, $item_name) {
	global $CONFIG;
	$index = -1;
	$found = false;

	if (is_array($CONFIG->menus[$menu_name])) {
		$count = count($CONFIG->menus[$menu_name]);
		while(!$found && (++$index < $count)) {
			if ($CONFIG->menus[$menu_name][$index]->getName() === $item_name) {
				$found = true;
			}
		}
	}

	return $found ? $index : false;
}


/**
 *
 * Substitutes dynamic parts of a menu's target URL
 *
 * @param array $vars An associative array holding the menu permissions
 *
 * @return The substituted menu permission array
 */
function roles_prepare_menu_vars($vars) {

	$prepared_vars = $vars;
	if (isset($prepared_vars['href'])) {
		$prepared_vars['href'] = roles_replace_dynamic_paths($prepared_vars['href']);
	}

	return $prepared_vars;
}


/**
 *
 * Gets a menu by name
 *
 * @param string $menu_name The name of the menu
 *
 * @return array The array of ElggMenuItem objects from the menu
 */
function roles_get_menu($menu_name) {
	global $CONFIG;
	return $CONFIG->menus[$menu_name];
}


/**
 *
 * Replaces certain parts of path and URL type definitions with dynamic values
 *
 * @param string $str The string to operate on
 *
 * @return string The updated, substituted string
 */
function roles_replace_dynamic_paths($str) {
	$user = elgg_get_logged_in_user_entity();
	if (elgg_instanceof($user, 'user')) {
		$self_username = $user->username;
		$self_guid = $user->guid;
		$role = roles_get_role($user);

		$res = str_replace('{$self_username}', $self_username, $str);
		$res = str_replace('{$self_guid}', $self_guid, $res);
		if (elgg_instanceof($role, 'object', 'role')) {
			$res = str_replace('{$self_rolename}', $role->name, $res);
		}
	}

	$pageowner = elgg_get_page_owner_entity();
	if (elgg_instanceof($pageowner, 'user')) {
		$pageowner_username = $pageowner->username;
		$pageowner_guid = $pageowner->guid;
		$pageowner_role = roles_get_role($pageowner);

		$res = str_replace('{$pageowner_name}', $pageowner_username, $res);
		$res = str_replace('{$pageowner_guid}', $pageowner_guid, $res);
		$res = str_replace('{$pageowner_rolename}', $pageowner_role->name, $res);
	}

	return $res;
}


/**
 *
 * Checks if a path or URL type rule matches a given path. Also processes regular expressions
 *
 * @param string $rule The permission rule to check
 * @param string $path The path to match against
 *
 * @return boolean True if the rule matches the path, false otherwise
 */
function roles_path_match($rule, $path) {
	if (preg_match('/^regexp\((.+)\)$/', $rule) > 0) {
		// The rule contains regular expression; use regexp matching for the current path
		$pattern = preg_replace('/^regexp\(/', '', $rule);
		$pattern = preg_replace('/\)$/', '', $pattern);
		return preg_match($pattern, $path);
	} else {
		// The rule contains a simple string; default string comparision will be used
		return ($rule == $path);
	}
}

/**
 *
 * Checks if a permission rule should be executed for the current context
 *
 * @param string $permission_details The permission rule configuration
 * @param boolean $strict	If strict context matching should be used.
 * 							If true, only the last context will be checked for the rule matching.
 * 							If false, any context value in the context stack will be considered.
 *
 * @return True if the rule should be executed, false otherwise
 */
function roles_check_context($permission_details, $strict = false) {
	global $CONFIG;
	$result = true;
	if (is_array($permission_details['context'])) {
		if ($strict) {
			$result = in_array(elgg_get_context(), $permission_details['context']);
		} else {
			$result = count(array_intersect($permission_details['context'], $CONFIG->context)) > 0;
		}
	}
	return $result;
}

?>
