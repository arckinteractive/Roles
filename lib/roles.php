<?php 

function roles_get_role($user = null) {
	if (!$user) {
		$user = elgg_get_logged_in_user_entity();
	}
	if (!elgg_instanceof($user, 'user')) {
		return null;
	}

	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'relationship' => 'has_role',
		'relationship_guid' => $user->guid,
	);
	$roles = elgg_get_entities_from_relationship($options);


	if (is_array($roles) && !empty($roles)) {
		return $roles[0];
	} else {
		return false;
	}
}

function roles_get_all_roles() {
	
	$options = array(
		'type' => 'object',
		'subtype' => 'role',
		'limit' => 0

	);
	return elgg_get_entities($options);

}

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
			$extended_permissions = unserialize($extended_role->permissions);
			foreach ($extended_permissions as $type => $permission_rules) {
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


function roles_create_from_config() {
	$roles_array = roles_get_roles_config();
	
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

function roles_check_update() {
	$hash = elgg_get_plugin_setting('roles_hash');
	$roles_array = roles_get_roles_config();
	
	$current_hash = sha1(serialize($roles_array));

	if ($hash != $current_hash) {
		roles_create_from_config();
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

function roles_replace_dynamic_paths($str) {
	$user = elgg_get_logged_in_user_entity();
	$self_username = $user->username;
	$self_guid = $user->guid;
	
	$res = str_replace('{$self_username}', $self_username, $str); 
	$res = str_replace('{$self_guid}', $self_guid, $res);
	
	return $res;
}


