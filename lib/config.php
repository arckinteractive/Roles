<?php



function roles_get_roles_config() {
	$roles = array(
		'affiliate' => array(
			'name' => 'roles:nd:affiliate',
			'extends' => array('adherent'),
			'permissions' => array(
				'actions' => array(
					'group/save' => 'deny',
					'group/delete' => 'deny'
				),
				'views' => array(
				),
				'pages' => array(
					'group/new/$guid' => 'deny'
				),
				'menus' => array(
					'site:blog' => 'allow'
				),
			)
		),
		'adherent' => array(
			'name' => 'roles:nd:adherent',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
					'group/save' => 'deny'
				),
				'views' => array(
				),
				'pages' => array(
					'group/new/{$username}' => 'deny'
				),
				'menus' => array(
					'site:blog' => 'deny'
				),
			)
		),
	);
	
	return $roles;
}
