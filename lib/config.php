<?php



function roles_get_roles_config() {

	$roles = array(
		
		'affiliate' => array(
			'name' => 'roles:nd:affiliate',
			'extends' => array('adherent'),
			'permissions' => array(

				'actions' => array(
					
					'group/save' => array('rule' => 'deny'),
					
					'group/delete' => array('rule' => 'deny')
				),
				
				'views' => array(
					/* 'input/password' => array('rule' => 'deny'), */

					'forms/account/settings' => array(
						'rule' => 'extend',
						'view_extension' => array(
							'view' => 'roles/settings/account/role',
							'priority' => 150
						)
					),
					
					'roles/settings/account/role' => array(
						'rule' => 'replace',
						'view_replacement' => array(
							'location' => 'mod/roles/views',
						)
					),
	
				),
				
				'pages' => array(
					'group/new/$guid' => array('rule' => 'deny')
				),
				
				'menus' => array(
					
					'site::blog' => array('rule' => 'allow'),
					
					'site::activity' => array(
						'rule' => 'replace',
						'menu_item' => array(
							'name' => 'mygroups',
							'text' => 'Any of my Groups',
							'href' => 'groups/member/{$self_username}',
						)
					),
					
					'site' => array(
						'rule' => 'extend',
						'menu_item' => array(
							'name' => 'books',
							'text' => 'Books',
							'href' => 'books/all',
						)
					),
				),
				
				'entities' => array(
				),
			)
		),
		
		'adherent' => array(
			'name' => 'roles:nd:adherent',
			'extends' => array(),
			'permissions' => array(
				
				'actions' => array(
					'group/save' => array('rule' => 'deny')
				),
				
				'views' => array(
				),
				
				'pages' => array(
					'group/new/{$username}' => array('rule' => 'deny')
				),
				
				'menus' => array(
					'site::blog' => array('rule' => 'deny')
				),
			)
		),
	);
	
	return $roles;
}
