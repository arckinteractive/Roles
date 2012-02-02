<?php

class ElggRole extends ElggObject {

	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "role";
	}
	
	public function __construct($guid = null) {
		parent::__construct($guid);
	}
	
	

}