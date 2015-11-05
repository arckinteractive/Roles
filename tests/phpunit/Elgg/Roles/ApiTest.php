<?php

namespace Elgg\Roles;

use PHPUnit_Framework_TestCase;

class ApiTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Api
	 */
	protected $api;

	public function setUp() {
		$this->api = new Api();
	}

	public function testGetReservedRoleName() {
		$expected = array(Api::DEFAULT_ROLE, Api::ADMIN_ROLE, Api::VISITOR_ROLE);
		$this->assertEquals($expected, $this->api->getReservedRoleNames());
	}

	public function testIsReservedName() {
		$this->assertTrue($this->api->isReservedRoleName(Api::DEFAULT_ROLE));
		$this->assertFalse($this->api->isReservedRoleName('foobar'));
	}

}
