<?php

function roles_get_roles_config() {

	$roles = array(
		DEFAULT_ROLE => array(
			'name' => 'roles:DEFAULT_ROLE',
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
		)
	);

	return elgg_trigger_plugin_hook('roles:config', 'system', null, $roles);
}

