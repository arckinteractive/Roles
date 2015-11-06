<?php

namespace Elgg\Roles;

use PHPUnit_Framework_TestCase;

class ApiTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Api
	 */
	protected $api;

	public function setUp() {
		$this->api = new Api(new DbMock());
	}

	public function testGetReservedRoleName() {
		$expected = array(Api::DEFAULT_ROLE, Api::ADMIN_ROLE, Api::VISITOR_ROLE);
		$this->assertEquals($expected, $this->api->getReservedRoleNames());
	}

	public function testIsReservedName() {
		$this->assertTrue($this->api->isReservedRoleName(Api::DEFAULT_ROLE));
		$this->assertFalse($this->api->isReservedRoleName('foobar'));
	}

	public function testGetPermissions() {
		$expected = [
			'bar/foo' => ['rule' => 'allow'],
			'baz' => array('rule' => 'deny', 'redirect' => 'bar/foo'),
		];
		$this->assertEquals($expected, $this->api->getPermissions($this->api->getRoleByName('default'), 'actions'));
	}

	public function testGetPermissionsWithExtensions() {
		$expected = [
			'baz' => array('rule' => 'deny', 'redirect' => 'bar/foo'),
			'foo/bar' => ['rule' => 'allow'],
			'foo/bar/baz' => ['rule' => 'deny'],
			'bar/foo' => ['rule' => 'deny']
		];
		$this->assertEquals($expected, $this->api->getPermissions($this->api->getRoleByName('tester1'), 'actions'));
	}

	public function testGetRoleByName() {

		$role =  $this->api->getRoleByName('default');
		$this->assertInstanceOf('\ElggRole', $role);
		$this->assertEquals('default', $role->name);

		$this->assertFalse($this->api->getRoleByName('foo'));
	}

	public function testGetSelectable() {

		$selectable = $this->api->getSelectable();
		$this->assertContains($this->api->getRoleByName('tester1'), $selectable);
		$this->assertContains($this->api->getRoleByName('tester2'), $selectable);
		$this->assertNotContains($this->api->getRoleByName('default'), $selectable);
	}
}
