<?php

class ElggRole extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "role";
	}
	
	public function __construct($guid = null) {
		parent::__construct($guid);
	}
	
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