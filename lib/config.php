<?php

function roles_get_roles_config($hook_name, $entity_type, $return_value, $params) {

	$roles = array(



		DEFAULT_ROLE => array(
			'name' => 'roles:role:DEFAULT_ROLE',
			'extends' => array(),
			'permissions' => array(
				
				'menus' => array(
					'site::members' => array('rule' => 'deny'),
					'filter::mine' => array(
						'rule' => 'deny',
						'context' => array('blog', 'bookmark'),
					),
				),

			)
		),
		
		ADMIN_ROLE => array(
			'name' => 'roles:role:ADMIN_ROLE',
			'extends' => array(),
			'permissions' => array(
				
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
			)
		)
	
	);

	if (!is_array($return_value)) {
		return $roles;
	} else {
		return array_merge($return_value, $roles);
	}
}


