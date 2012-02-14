<?php

class ElggRole extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "role";
	}
	
	public function __construct($guid = null) {
		parent::__construct($guid);
	}
	

	
	public static function getReservedRoleNames() {
		return array(DEFAULT_ROLE, ADMIN_ROLE, VISITOR_ROLE);
	}
	
	public static function isReservedRoleName($role_name) {
		return in_array($role_name, ElggRole::getReservedRoleNames());
	}
	

	
	public function isReservedRole() {
		return ElggRole::isReservedRoleName($this->name);
	}
	

	/**
	 * Obtain a list of users for the current role object
	 *
	 * @param array $options An array of $key => $value pairs accepted by {@link elgg_get_entities()}
	 * @return mixed
	 */
	public function getUsers($options) {
		if ($this->name == DEFAULT_ROLE) {
			$dbprefix = elgg_get_config('dbprefix');
			$defaults = array(
				'type' => 'user',
				'joins' => "LEFT JOIN {$dbprefix}entity_relationships r ON (r.guid_one = e.guid AND r.relationship = 'has_role')",
				'wheres' => 'r.guid_two IS NULL'
			);
			$options = array_merge($defaults, $options);
			$users = elgg_get_entities($options);
		} else {
			$defaults = array(
				'type' => 'user',
				'relationship' => 'has_role',
				'relationship_guid' => $this->get('guid'),
				'inverse_relationship' => true
			);
			$options = array_merge($defaults, $options);
			$users = elgg_get_entities_from_relationship($options);
		}
		return $users;
	}

}