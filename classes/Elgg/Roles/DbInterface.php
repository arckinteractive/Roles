<?php

namespace Elgg\Roles;

interface DbInterface {

	/**
	 * Returns all roles as a batch
	 * @return \ElggBatch
	 */
	public function getAllRoles();

	/**
	 * Returns a role object by its name
	 *
	 * @param string $role_name Role name
	 * @return \ElggRole|false
	 */
	public function getRoleByName($role_name = '');
	
}
