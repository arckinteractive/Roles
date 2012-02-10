<?php

$user = elgg_get_page_owner_entity();
$role = roles_get_role($user);
$metafields = roles_get_role_metafields($role, 'profile');

if (!$role) {
	return true;
}

$even_odd = null;
if (is_array($profile_fields) && sizeof($profile_fields) > 0) {
	elgg_view_title(elgg_echo("roles:profile:$role->name"), array('class' => 'roles-profile-title'));
	foreach ($profile_fields as $shortname => $options) {

		$valtype = $options['input_type'];

		if ($options['admin_only'] && !elgg_is_admin_logged_in()) {
			continue;
		}

		$value = $user->$shortname;
		if (!empty($value)) {
			//This function controls the alternating class
			$even_odd = ( 'odd' != $even_odd ) ? 'odd' : 'even';
			?>
			<div class="<?php echo $even_odd; ?>">
				<b><?php echo elgg_echo("roles:profile:{$shortname}"); ?>: </b>
				<?php
					echo elgg_view("output/{$valtype}", array('value' => $user->$shortname));
				?>
			</div>
			<?php
		}
	}
}
