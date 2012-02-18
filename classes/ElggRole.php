<?php

/**
 * 
 * Class to implement Role objects
 * 
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2012
 * @link http://www.arckinteractive.com/
 *
 */
class ElggRole extends ElggObject {

	/**
	 * Initializes the object's subtype to "role"
	 * 
	 * @see ElggObject::initializeAttributes()
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "role";
	}
	
	/**
	 * 
	 * Create or update the role object
	 * 
	 * @param int $guid The GUID of the role object, if updating an existing object
	 */
	public function __construct($guid = null) {
		parent::__construct($guid);
	}
	

	/**
	 * 
	 * Gets all reserved role names
	 * 
	 * @return array The list of reserved role names
	 */	
	public static function getReservedRoleNames() {
		return array(DEFAULT_ROLE, ADMIN_ROLE, VISITOR_ROLE);
	}
	
	/**
	 * 
	 * Checks if a role name is reserved in the system
	 * 
	 * @param string $role_name The name of the role to check
	 * 
	 * @return boolean True if the passed $role_name is a reserved role name
	 */
	public static function isReservedRoleName($role_name) {
		return in_array($role_name, ElggRole::getReservedRoleNames());
	}
	


	/**
	 * 
	 * Checks if this role is a reserved role
	 * 
	 * @return boolean True if the current role is a reserved role
	 */
	public function isReservedRole() {
		return ElggRole::isReservedRoleName($this->name);
	}
	

	/**
	 * Obtain the list of users for the current role object
	 *
	 * @param array $options An array of $key => $value pairs accepted by {@link elgg_get_entities()}

	 * @return mixed The array of users having this role, false if no user found
	 */
	public function getUsers($options) {

		switch ($this->name) {
			case DEFAULT_ROLE :
				$dbprefix = elgg_get_config('dbprefix');
				$defaults = array(
					'type' => 'user',
					'joins' => array(
						"INNER JOIN {$dbprefix}users_entity u ON (u.guid = e.guid)",
						"LEFT JOIN {$dbprefix}entity_relationships r ON (r.guid_one = e.guid AND r.relationship = 'has_role')",
					),
					'wheres' => array(
						'r.guid_two IS NULL',
						'u.admin = "no"'
					)
				);
				$options = array_merge($defaults, $options);
				$users = elgg_get_entities($options);
				break;
			case ADMIN_ROLE :
				$dbprefix = elgg_get_config('dbprefix');
				$defaults = array(
					'type' => 'user',
					'joins' => array(
						"INNER JOIN {$dbprefix}users_entity u ON (u.guid = e.guid)",
						"LEFT JOIN {$dbprefix}entity_relationships r ON (r.guid_one = e.guid AND r.relationship = 'has_role')",
					),
					'wheres' => array(
						'r.guid_two IS NULL',
						'u.admin = "yes"'
					)
				);
				$options = array_merge($defaults, $options);
				$users = elgg_get_entities($options);
				break;
			default :
				$defaults = array(
					'type' => 'user',
					'relationship' => 'has_role',
					'relationship_guid' => $this->get('guid'),
					'inverse_relationship' => true
				);
				$options = array_merge($defaults, $options);
				$users = elgg_get_entities_from_relationship($options);
				break;
		}

		return $users;
	}

}