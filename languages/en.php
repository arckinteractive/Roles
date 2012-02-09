<?php
/**
 * Elgg roles plugin language pack
 *
 * @package ElggRoles
 */

$english = array(
	
	'item:object:role' => 'Roles',

	'user:set:role' => 'Role settings',
	
	'roles:nd:DEFAULT_ROLE' => 'Member',
	'roles:nd:affiliate' => 'Affiliate',
	'roles:nd:adherent' => 'Adherent',

	'user:role:label' => 'Select role',

	'user:role:success' => 'User role has been successfully updated',
	'user:role:fail' => 'Could not update user role. Please try again later.',
	
	'roles:page:denied' => 'Sorry, but you do not have the necessary privileges to view that page.',

	/* REGISTRATION / LOGIN */
	'nd:registertobe' => 'Register To Be:',
	'nd:becomeaffiliate' => 'Become an <span>affiliate</span>',
	'nd:becomeadherent' => 'Become an <span>adherent</span>',
	'nd:affiliate:desc' => 'Full access with option to be a candidate, ID and elector card number obligatory. Choice to participate with real ID or with nick name.',
	'nd:adherent:desc' => 'Able to use a nick name, simple registration with email. Can comment and participate.',
	
	
);

add_translation("en", $english);
