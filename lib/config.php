<?php

/**
 * 
 * Provides the default roles configuration array. Triggered by the 'roles:config' hook.
 * 
 * When implementing specific roles, authors should register for the same hook their handler.
 * The handler should merge new role values into the existing configuration array
 * Examples of this can be seen in the roles_moderators and roles_group_amins plugins
 * 
 * @param string $hook_name    Equals "roles:config"
 * @param string $entity_type  Equals "role"
 * @param mixed  $return_value The array of already initialized role configuration values
 * @param mixed  $params       Hook params (not in use)
 * @return array The role configuration
 */
function roles_get_roles_config($hook_name, $entity_type, $return_value, $params) {

	$roles = array(

		VISITOR_ROLE => array(
			'title' => 'roles:role:VISITOR_ROLE',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
				),
				'menus' => array(
				),
				'views' => array(
				),
				'hooks' => array(
				),
			),
		),

		DEFAULT_ROLE => array(
			'title' => 'roles:role:DEFAULT_ROLE',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
				),
				'menus' => array(
				),
				'views' => array(
				),
				'hooks' => array(
				),
			),
		),

		ADMIN_ROLE => array(
			'title' => 'roles:role:ADMIN_ROLE',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
				),
				'menus' => array(
				),
				'views' => array(
					'forms/account/settings' => array(
						'rule' => 'extend',
						'view_extension' => array(
							'view' => 'roles/settings/account/role',
							'priority' => 150
						)
					),
				),
				'hooks' => array(
					'usersettings:save::user' => array(
						'rule' => 'extend',
						'hook' => array(
							'handler' => 'roles_user_settings_save',
							'priority' => 500,
						)
					),
				),
			),
		),
	);

	if (!is_array($return_value)) {
		return $roles;
	} else {
		return array_merge($return_value, $roles);
	}
}
