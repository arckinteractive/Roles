<?php

function roles_get_roles_config($hook_name, $entity_type, $return_value, $params) {

	$roles = array(
		VISITOR_ROLE => array(
			'name' => 'roles:role:VISITOR_ROLE',
			'extends' => array(),
			'permissions' => array(
			),
		),
		DEFAULT_ROLE => array(
			'name' => 'roles:role:DEFAULT_ROLE',
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
			'name' => 'roles:role:ADMIN_ROLE',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
				),
				'menus' => array(
					'filter:mine' => array(
						'rule' => 'deny',
						'context' => array('bookmarks')
					)
				),
				'views' => array(
				),
				'hooks' => array(
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

