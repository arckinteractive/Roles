<?php

namespace Elgg\Roles;

use ElggUser;
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

		$role = $this->api->getRoleByName('default');
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

	public function testFilterName() {

		$this->assertEquals(Api::VISITOR_ROLE, $this->api->filterName(Api::NO_ROLE));
		$this->assertEquals(Api::DEFAULT_ROLE, $this->api->filterName(Api::DEFAULT_ROLE));
		$this->assertEquals('tester1', $this->api->filterName('tester1'));

		$user = new ElggUser();
		$this->assertEquals(Api::DEFAULT_ROLE, $this->api->filterName(Api::NO_ROLE, $user));

		$admin = new ElggUser();
		$admin->admin = true;
		$this->assertEquals(Api::ADMIN_ROLE, $this->api->filterName(Api::NO_ROLE, $admin));
	}

	public function testSetGetRole() {

		$user = new ElggUser();

		$role = $this->api->getRoleByName('tester1');
		$this->api->setRole($user, $role);
		$this->assertEquals($role, $this->api->getRole($user));

		$role = $this->api->getRoleByName(Api::DEFAULT_ROLE);
		$this->api->setRole($user, $role);
		$this->assertEquals($role, $this->api->getRole($user));
	}

	public function testContext() {

		elgg_push_context('foo');
		elgg_push_context('bar');

		$this->assertFalse($this->api->checkContext(['context' => 'baz']));
		$this->assertTrue($this->api->checkContext(['context' => 'foo']));
		$this->assertTrue($this->api->checkContext(['context' => 'bar']));
		$this->assertTrue($this->api->checkContext(['context' => ['foo', 'bar']]));
		$this->assertTrue($this->api->checkContext(['context' => ['bar']]));

		elgg_pop_context();
		elgg_pop_context();
	}

	public function testContextStrict() {

		elgg_push_context('foo');
		elgg_push_context('bar');

		$this->assertTrue($this->api->checkContext(['context' => 'bar'], true));
		$this->assertFalse($this->api->checkContext(['context' => 'baz'], true));
		$this->assertFalse($this->api->checkContext(['context' => 'foo'], true));
		$this->assertTrue($this->api->checkContext(['context' => ['foo', 'bar']], true));
		$this->assertTrue($this->api->checkContext(['context' => ['bar']], true));

		elgg_pop_context();
		elgg_pop_context();
	}

	public function testDenyView() {
		$role = $this->api->getRoleByName('deny');
		$this->api->setupViews($role);
		$this->assertEquals('', elgg_view('foo/bar'));
	}

	public function testAllowView() {
		$role = $this->api->getRoleByName('allow');
		$this->api->setupViews($role);
		$this->assertEquals('bar', elgg_view('foo/bar'));
	}

	public function testExtendView() {
		$role = $this->api->getRoleByName('extend');
		$this->api->setupViews($role);
		$this->assertEquals('bazbar', elgg_view('foo/bar'));
	}

	public function testReplaceView() {
		$role = $this->api->getRoleByName('replace');
		$this->api->setupViews($role);
		$this->assertEquals('baz2', elgg_view('foo/baz'));
	}

	public function testDenyAction() {
		$role = $this->api->getRoleByName('deny');
		$this->assertFalse($this->api->actionGatekeeper($role, 'foo/bar'));
		$this->assertNull($this->api->actionGatekeeper($role, 'foo/baz'));
	}

	public function testAllowAction() {
		$role = $this->api->getRoleByName('allow');
		$this->assertNull($this->api->actionGatekeeper($role, 'foo/bar'));
		$this->assertNull($this->api->actionGatekeeper($role, 'foo/baz'));
	}
}
