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

	public function testDenyPage() {
		$role = $this->api->getRoleByName('deny');
		$this->assertEquals(['forward' => REFERRER, 'error' => true], $this->api->pageGatekeeper($role, 'foo/bar'));
		$this->assertEquals(['forward' => false, 'error' => false], $this->api->pageGatekeeper($role, 'foo/baz'));
	}

	public function testAllowPage() {
		$role = $this->api->getRoleByName('allow');
		$this->assertEquals(['forward' => false, 'error' => false], $this->api->pageGatekeeper($role, 'foo/bar'));
		$this->assertEquals(['forward' => false, 'error' => false], $this->api->pageGatekeeper($role, 'foo/baz'));
	}

	public function testRedirectPage() {
		$role = $this->api->getRoleByName('replace');
		$this->assertEquals(['forward' => 'foo/baz', 'error' => false], $this->api->pageGatekeeper($role, 'foo/bar'));
		$this->assertEquals(['forward' => false, 'error' => false], $this->api->pageGatekeeper($role, 'foo/baz'));
	}

	public function testDenyHook() {

		elgg_register_plugin_hook_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('deny');
		$this->api->setupHooks($role);

		$expected = false;
		$actual = elgg_trigger_plugin_hook('foo', 'bar', null, $expected);
		$this->assertEquals($expected, $actual);
	}

	public function testAllowHook() {

		elgg_register_plugin_hook_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('allow');
		$this->api->setupHooks($role);

		$expected = true;
		$actual = elgg_trigger_plugin_hook('foo', 'bar', null, null);
		$this->assertEquals($expected, $actual);
	}

	public function testExtendHook() {

		elgg_register_plugin_hook_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('extend');
		$this->api->setupHooks($role);

		$expected = false;
		$actual = elgg_trigger_plugin_hook('foo', 'bar', null, null);
		$this->assertEquals($expected, $actual);
	}

	public function testReplaceHook() {

		elgg_register_plugin_hook_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('replace');
		$this->api->setupHooks($role);

		$expected = false;
		$actual = elgg_trigger_plugin_hook('foo', 'bar', null, null);
		$this->assertEquals($expected, $actual);
	}

	public function testDenyEvent() {

		elgg_register_event_handler('foo', 'bar', '\Elgg\Values::getFalse');
		$role = $this->api->getRoleByName('deny');
		$this->api->setupEvents($role);

		$expected = true;
		$actual = elgg_trigger_event('foo', 'bar');
		$this->assertEquals($expected, $actual);
	}

	public function testAllowEvent() {

		elgg_register_event_handler('foo', 'bar', '\Elgg\Values::getFalse');
		$role = $this->api->getRoleByName('allow');
		$this->api->setupEvents($role);

		$expected = false;
		$actual = elgg_trigger_event('foo', 'bar');
		$this->assertEquals($expected, $actual);
	}

	public function testExtendEvent() {

		elgg_register_event_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('extend');
		$this->api->setupEvents($role);

		$expected = false;
		$actual = elgg_trigger_event('foo', 'bar');
		$this->assertEquals($expected, $actual);
	}

	public function testReplaceEvent() {

		elgg_register_event_handler('foo', 'bar', '\Elgg\Values::getTrue');
		$role = $this->api->getRoleByName('replace');
		$this->api->setupEvents($role);

		$expected = false;
		$actual = elgg_trigger_event('foo', 'bar');
		$this->assertEquals($expected, $actual);
	}

	public function testDenyMenu() {
		$role = $this->api->getRoleByName('deny');

		$menu = $this->getMenu();
		$this->assertEmpty($this->api->setupMenu($role, 'foo', $menu));

		$filtered_menu = $this->api->setupMenu($role, 'bar', $menu);
		$this->assertNotContains($menu['parent'], $filtered_menu);
		$this->assertNotContains($menu['child'], $filtered_menu);
	}

	public function testAllowMenu() {
		$role = $this->api->getRoleByName('allow');
		$menu = $this->getMenu();
		$this->assertEquals($menu, $this->api->setupMenu($role, 'foo', $menu));
		$this->assertEquals($menu, $this->api->setupMenu($role, 'bar', $menu));
	}

	public function testExtendMenu() {
		$role = $this->api->getRoleByName('extend');
		$menu = $this->getMenu();
		$this->assertEquals(count($menu) + 1, count($this->api->setupMenu($role, 'foo', $menu)));
	}

	public function testReplaceMenu() {
		$role = $this->api->getRoleByName('replace');

		$menu = $this->getMenu();

		$filtered_menu = $this->api->setupMenu($role, 'bar', $menu);
		$this->assertEquals('baz2', $filtered_menu['parent']->getName());
		$this->assertEquals('baz2', $filtered_menu['child']->getParentName());
	}

	public function getMenu() {

		$menu = array();
		$menu['parent'] = \ElggMenuItem::factory(array(
					'name' => 'baz',
					'href' => 'baz',
					'text' => 'baz',
		));
		$menu['bad'] = 'bad';
		$menu['child'] = \ElggMenuItem::factory(array(
					'name' => 'baz:child',
					'parent_name' => 'baz',
					'href' => 'baz/child',
					'text' => 'baz:child'
		));
		return $menu;
	}

	public function testCleanMenu() {

		$role = $this->api->getRoleByName('tester1');
		$item = \ElggMenuItem::factory(array(
			'name' => 'foo',
			'href' => 'action/bar/foo',
			'text' => 'foo',
		));

		$this->assertNotContains($item, $this->api->cleanMenu($role, [$item]));
	}

	/**
	 * @dataProvider providerMatchPath
	 */
	public function testMatchPath($expectation, $a, $b) {
		if ($expectation === true) {
			$this->assertTrue($this->api->matchPath($a, $b));
		} else if ($expectation === false) {
			$this->assertFalse($this->api->matchPath($a, $b));
		}
	}

	public function providerMatchPath() {

		return array(
			array(
				true,
				'blog/edit',
				'/blog/edit',
			),
			array(
				true,
				'blog/save',
				elgg_normalize_url('blog/save'),
			),
			array(
				false,
				'blog/view/123',
				'/groups/blog/view/123',
			),
			array(
				true,
				'/blog/add/234',
				'blog/add/234',
			),
			array(
				false,
				'/blog/new/345',
				'/blog/new/',
			),
			array(
				true,
				'regexp(/^admin\/((?!administer_utilities\/reportedcontent).)*$/)',
				'admin/plugins',
			),
			array(
				false,
				'regexp(/^admin\/((?!administer_utilities\/reportedcontent).)*$/)',
				'/admin/reportedcontent/123',
			),
			array(
				true,
				'/blog/owner/\d+/.*',
				'/blog/owner/25/some-title',
			),
			array(
				true,
				'/blog/(view|edit)/\d+/.*',
				'blog/edit/15/some-title',
			),
		);
	}
}
