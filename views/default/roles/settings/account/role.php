<?php
/**
 * Provide a way of setting your language prefs
 *
 * @package Elgg
 * @subpackage Core
 */

$user = elgg_get_page_owner_entity();
$current_role = roles_get_role($user);
if ($current_role->isReservedRole()) {
	$current_role_name = NO_ROLE;
} else {
	$current_role_name = $current_role->name;
}

$roles_options = array(NO_ROLE => elgg_echo('roles:role:NO_ROLE'));

$all_roles = roles_get_all_selectable_roles();
if (is_array($all_roles) && !empty($all_roles)) {
	foreach ($all_roles as $role) {
		$roles_options[$role->name] = $role->title;
	}
}

if (elgg_instanceof($user, 'user')) {
?>
<div class="elgg-module elgg-module-info">
	<div class="elgg-head">
		<h3><?php echo elgg_echo('user:set:role'); ?></h3>
	</div>
	<div class="elgg-body">
		<p>
			<?php echo elgg_echo('user:role:label'); ?>:
			<?php
			echo elgg_view("input/dropdown", array(
				'name' => 'role',
				'value' => $current_role_name,
				'options_values' => $roles_options
			));
			?>
		</p>
	</div>
</div>
<?php
}