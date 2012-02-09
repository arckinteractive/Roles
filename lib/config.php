<?php

function roles_get_roles_config() {

	$roles = array(

		DEFAULT_ROLE => array(
			'name' => 'roles:nd:DEFAULT_ROLE',
			'extends' => array(),
			'permissions' => array(
			
				'menus' => array(),

				'views' => array(),
	
				'pages' => array(),
	
				'actions' => array(),
	
				'hooks' => array(),
			)
		)
	);

	return $roles;
}
