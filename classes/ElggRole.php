<?php

/**
 * Class to implement Role objects
 * 
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2012
 * @link http://www.arckinteractive.com/
 *
 * @property string   $name        Role name
 * @property string   $title       Human readable role title
 */
class ElggRole extends ElggObject {

	/**
	 * Protected permissions metadata
	 * @var string
	 */
	protected $permissions;

	/**
	 * Protected extends metdata
	 * @var string[]
	 */
	protected $extends;

	/**
	 * {@inheritdoc}
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "role";
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDisplayName() {
		return elgg_echo($this->title);
	}

	/**
	 * Sets role permissions
	 * @return void
	 */
	public function setPermissions($permissions = array()) {
		$this->setMetadata('permissions', serialize($permissions));
	}

	/**
	 * Returns an array of permissions for this role
	 * @return array
	 */
	public function getPermissions() {
		$permissions = unserialize($this->getMetadata('permissions'));
		if (!is_array($permissions)) {
			return array();
		}
		foreach ($permissions as $type => $rules) {
			if (!is_array($rules)) {
				continue;
			}
			foreach ($rules as $name => $opts) {
				if (is_string($opts)) {
					$permissions[$type][$name] = array('rule' => $opts);
				}
			}
		}
		return $permissions;
	}

	/**
	 * Set extends
	 * @param string[] $extends
	 * @return void
	 */
	public function setExtends($extends = array()) {
		$this->setMetadata('extends', $extends);
	}

	/**
	 * Get extends
	 * @return string[]
	 */
	public function getExtends() {
		return (array) $this->getMetadata('extends');
	}

	/**
	 * Gets all reserved role names
	 * @return array The list of reserved role names
	 * @deprecated 2.0
	 */
	public static function getReservedRoleNames() {
		return roles()->getReservedRoleNames();
	}

	/**
	 * 
	 * Checks if a role name is reserved in the system
	 * 
	 * @param string $role_name The name of the role to check
	 * @return boolean True if the passed $role_name is a reserved role name
	 * @deprecated 2.0
	 */
	public static function isReservedRoleName($role_name) {
		return roles()->isReservedRoleName($role_name);
	}

	/**
	 * 
	 * Checks if this role is a reserved role
	 * @return boolean True if the current role is a reserved role
	 */
	public function isReservedRole() {
		return roles()->isReservedRoleName($this->name);
	}

	/**
	 * Obtain the list of users for the current role object
	 *
	 * @param array $options An array of $key => $value pairs accepted by {@link elgg_get_entities()}
	 * @return ElggUser[]|false The array of users having this role, false if no user found
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
