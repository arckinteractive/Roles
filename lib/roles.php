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
		'inverse_relationship' => true
		
	);
	$roles = elgg_get_entities_from_relationship($options);


	$returned_roles = false;
	if (is_array($roles) && !empty($roles)) {
		$returned_roles = array();
		foreach($roles as $role) {
			$returned_roles = roles_merge_recursive($returned_roles, $role);
		}
	}
	return $returned_roles;
}

function roles_create_from_config() {
	$roles_array = roles_get_roles();
	
	$options = array(
			'type' => 'object',
			'subtype' => 'role',
	);
	$roles = elgg_get_entities($options);
	
	foreach($roles_array as $rname => $rdetails) {
		$current_roles = false;
		foreach ($roles as $roles) {
			if ($roles->name == $rname) {
				$current_role = $role;
			}
		}
		if (elgg_instanceof($current_role, 'object', 'role')) {
			// Update existing role obejct
			$current_role->title = elgg_echo($rdetails['name']);
			$current_role->extends = $rdetails->extends;
			$current_role->privileges = serialize($rdetails->privileges);
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
				$new_role->extends = $rdetails->extends;
				$new_role->privileges = serialize($rdetails->privileges);
			}
		}
	}
	
}