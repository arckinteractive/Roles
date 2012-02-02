<?php



function roles_get_roles() {
	$roles = array(
		'affiliate' => array(
			'name' => 'roles:nd:affiliate',
			'extends' => array(),
			'privileges' => array(
				'actions' => array(
					'group/save' => 'deny'
				),
				'views' => array(
				),
				'pages' => array(
					'group/new/$guid' => 'deny'
				),
				'menus' => array(
				),
			)
		),
		'adherent' => array(
			'name' => 'roles:nd:adherent',
			'extends' => array(),
			'privileges' => array(
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
