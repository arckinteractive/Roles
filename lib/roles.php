<?php
/**
 * @package roles
 * @note The intention of this module is that plugin authors should be able to develop most functionality without the explicit need of knowing a user’s certain role. Most of role based functionality – i.e. what the user can see and interact with – can be moved to the configuration array; hence no need for handling role based conditionals in the code.
 */

/**
 * Obtain a role of a given user
 * 
 * @param ElggUser $user
 * @return ElggRole 
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
 * Check if the user has a specific role
 *
 * @param ElggUser $user
 * @return bool
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
 * Assign a role to a particular user
 *
 * @param ElggRole $role
 * @param ElggUser $user
 * @return bool
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
 * Get all role objects
 *
 * @return mixed
 */

function roles_get_all_roles() {
	
	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => 0

	);
	return elgg_get_entities($options);

}

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
 * Obtain a list of permissions associated with a particular role object
 *
 * @global array $PERMISSIONS_CACHE
 * @param ElggRole $role
 * @param string $permission_type
 * @return mixed
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
 * Cache permissions associated with a role object
 *
 * @global array $PERMISSIONS_CACHE
 * @param ElggRole $role
 *
 * @return void
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
 * Get a role object based on it's name
 *
 * @param string $role_name
 * @return mixed
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
 * Process the configuration file and generate ElggRole objects
 *
 * @return void
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
			$current_role->title = elgg_echo($rdetails['name']);
			$current_role->extends = $rdetails['extends'];
			$current_role->permissions = serialize($rdetails['permissions']);
			$current_role->save();
		} else {
			// Create new role object
			$new_role = new ElggRole();
			$new_role->title = elgg_echo($rdetails['name']);
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
 * Check if the configuration array has been updated and updates roles accordingly if needed
 *
 * @return void
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


/******************* Helper functions for menu operations ***********************/

function roles_replace_menu($menu_name, $item_name, $menu_obj) {
	global $CONFIG;

	if (false !== $index = roles_find_menu_index($menu_name, $item_name)) {
		array_splice($CONFIG->menus[$menu_name], $index, 1, array($menu_obj));
	}
}

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


function roles_prepare_menu_vars($vars) {
	
	$prepared_vars = $vars;
	if (isset($prepared_vars['href'])) {
		$prepared_vars['href'] = roles_replace_dynamic_paths($prepared_vars['href']);
	}
	
	return $prepared_vars;
}

function roles_debug_menu($menu_name) {
	global $CONFIG;
	$item_names = array();
	
	if (is_array($CONFIG->menus[$menu_name])) {
		foreach($CONFIG->menus[$menu_name] as $menu_item) {
			$item_names[] = $menu_item->getName();
		}
	}
	error_log("Menu $menu_name has elements: " . implode(',', $item_names));
}

function roles_debug_menu_vars($menu_vars) {
	$item_names = array();
	
	if (is_array($menu_vars)) {
		foreach($menu_vars as $section => $menu_items) {
			foreach($menu_items as $menu_item) {
				$item_names[] = $menu_item->getName();
			}
		}
	}
	error_log("Menu vars has elements: " . implode(',', $item_names));
}

function roles_get_menu($menu_name) {
	global $CONFIG;
	return $CONFIG->menus[$menu_name];	
}



function roles_replace_dynamic_paths($str) {
	$user = elgg_get_logged_in_user_entity();
	if (elgg_instanceof($user, 'user')) {
		$self_username = $user->username;
		$self_guid = $user->guid;
		$role = roles_get_role($user);
	
		$res = str_replace('{$self_username}', $self_username, $str); 
		$res = str_replace('{$self_guid}', $self_guid, $res);
		if (elgg_instanceof($role, 'object', 'role')) {
			$res = str_replace('{$self_rolename}', $role->name, $str);
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

function roles_check_context($permission_details, $strict = false) {
	global $CONFIG;
	$result = true;
	if (is_array($permission_details['context'])) { 
		if ($strict) {
			$result = in_array(elgg_get_contex(), $permission_details['context']);
		} else {
			$result = count(array_intersect($permission_details['context'], $CONFIG->context)) > 0;
		}
	}
	return $result;
}




