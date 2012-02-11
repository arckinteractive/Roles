<?php

/**
As of this version, there is no fancy admin interface to set up role permission; this has to be done via an associative configuration array. The array resides in the mod/roles/lib/config.php file, and has roughly the following structure:

$roles = array(
	'role1' => array(
		'name' => 'Role one',
		'extends' => array('role2', DEFAULT_ROLE),
		'permissions' => array( ... )
	),
	'role2' => array(
		'name' => 'Role two',
		'extends' => array(),
		'permissions' => array( ... )
	),
	DEFAULT_ROLE => array(
		'name' => 'Role three',
		'extends' => array(),
		'permissions' => array( ... )
	)
);


The first level keys (role1, role2, role3) are the role names and must be unique, as they identify the roles themselves. The DEFAULT_ROLE name is reserved, and it will be associated with all members not having a specific (other than default) role. The configuration array always has to contain an entry for DEFAULT_ROLE, with at least the name and the permission keys defined, name being a string and permission being (at least an empty) array.

The “permissions” section holds individual permission rules that determine what the user can see and interact with on the site. Permissions can contain sections of rules relating to menu items, views, pages, actions and plugin hooks. For most of these permission sections, the basic permission rules are as follows:

deny: Deny access to a specific item. In some cases this will generate an error message and result in a redirect (for actions and pages), other cases the given item will simply not be rendered (for views and menus). Also, in case of hooks the specified hook will not be triggered.
allow: Allow access to a specific item. This rule is most useful when extending rules – the default role can deny creating new groups, while an extension of the default rule can specifically re-allow group creation.
extend: Add a new item on the fly to the current page – this works for hooks, menus and views. I.e. you can add role specific menu items, extend existing views and create new plugin hooks, just by using the right configuration values.
replace: Replaces an existing item. Works for views, menus and hooks.
redirect: Redirects to another page. Works for pages.
 */

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
			),
			
		)
	);

	return $roles;
}
