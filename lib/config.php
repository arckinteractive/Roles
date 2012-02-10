<?php

function roles_get_roles_config() {

	$roles = array(

		'affiliate' => array(
			'name' => 'roles:nd:affiliate',
			'extends' => array('adherent'),
			'permissions' => array(

				'actions' => array(
					'usersettings/save' => array('rule' => 'allow')
				),

				'views' => array(
					/* 'input/password' => array('rule' => 'deny'), */

					/* 'roles/settings/account/role' => array(
					 'rule' => 'replace',
					 'view_replacement' => array(
					 'location' => 'mod/roles/views',
					 )
					 ), */

				),

				'pages' => array(
					'groups/add/{$self_guid}' => array('rule' => 'deny')
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
			),

			'metafields' => array(

				'registration' => array(),

				'profile' => array(

					'elector_card_number' => array(
						'input_type' => 'text',
						'required' => true,
						'admin_only' => true
					),

					'municipality' => array(
						'input_type' => 'dropdown',
						'required' => true,
						'options_values' => array(
							'municipality1' => elgg_echo('roles:municipality:municipality1'),
							'municipality2' => elgg_echo('roles:municipality:municipality2'),
							'municipality3' => elgg_echo('roles:municipality:municipality2')
						)
					),

					'terms' => array(
						'input_type' => 'checkbox',
						'required' => true,
						'admin_only' => true
					),

					'extras' => array(
						'input_type' => 'text'
					)

				)
			)
		),

		'adherent' => array(
			'name' => 'roles:nd:adherent',
			'extends' => array(DEFAULT_ROLE),
			'permissions' => array(

				'actions' => array(
					'usersettings/save' => array('rule' => 'allow')
				),

				'pages' => array(
					'group/new/{$username}' => array('rule' => 'deny')
				),

				'menus' => array(
					'site::blog' => array('rule' => 'deny'),
					'site::members' => array('rule' => 'allow')
				),

				'hooks' => array(
				),
	
			)
		),

		DEFAULT_ROLE => array(
			'name' => 'roles:nd:DEFAULT_ROLE',
			'extends' => array(),
			'permissions' => array(

				/*'actions' => array(
					'usersettings/save' => array('rule' => 'deny')
				),*/
				
				'menus' => array(
					'site::members' => array('rule' => 'deny')
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
					/*
					'usersettings:save::user' => array(
						'rule' => 'deny',
						'hook' => array(
							'handler' => 'users_settings_save',
						)
					),
					*/
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

	return $roles;
}
