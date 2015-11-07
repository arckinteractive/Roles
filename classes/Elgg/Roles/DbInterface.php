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
	
	/**
	 * Returns user role stored in the DB
	 * 
	 * @param \ElggUser $user User entity
	 * @return \ElggRole|false
	 */
	public function getUserRole(\ElggUser $user);

	/**
	 * Stores user role in the DB
	 *
	 * @param \ElggUser $user User entity
	 * @param \ElggRole $role Role entity
	 * @return bool
	 */
	public function setUserRole(\ElggUser $user, \ElggRole $role);

	/**
	 * Clears user roles
	 *
	 * @param \ElggUser $user User entity
	 * @return bool
	 */
	public function unsetUserRole(\ElggUser $user);
}
