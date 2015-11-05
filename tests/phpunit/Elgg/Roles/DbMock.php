<?php

namespace Elgg\Roles;

use ElggRole;

class DbMock implements DbInterface {

	private $conf = array(
		'default' => array(
			'title' => 'roles:role:DEFAULT_ROLE',
			'extends' => array(),
			'permissions' => array(
				'actions' => array(
					'bar/foo' => 'allow',
					'baz' => array('rule' => 'deny', 'redirect' => 'bar/foo'),
				),
			),
		),
		'tester1' => array(
			'title' => 'roles:role:ADMIN_ROLE',
			'extends' => array('default'),
			'permissions' => array(
				'actions' => array(
					'foo/bar' => 'allow',
					'foo/bar/baz' => 'deny',
					'bar/foo' => ['rule' => 'deny']
				),
			),
		),
		'tester2' => array(
			'title' => 'roles:role:ADMIN_ROLE',
			'extends' => array('default'),
			'permissions' => array(
				'actions' => array(
					'foo/foo/bar' => 'allow',
					'bar/baz' => 'deny',
					'bar/foo' => 'deny',
				),
			),
		),
	);

	public function getRoleByName($role_name = '') {

		$conf = isset($this->conf[$role_name]) ? $this->conf[$role_name] : $this->conf['default'];
		$role = new ElggRole();
		$role->name = $role_name;
		$role->title = $conf['title'];
		$role->setExtends($conf['extends']);
		$role->setPermissions($conf['permissions']);

		return $role;
	}

}
