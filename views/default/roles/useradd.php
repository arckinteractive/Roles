<?php
/**
 * Provide a way of setting your language prefs
 *
 * @package Elgg
 * @subpackage Core
 */

$roles_options = array(NO_ROLE => elgg_echo('roles:role:NO_ROLE'));

$all_roles = roles_get_all_selectable_roles();
if (is_array($all_roles) && !empty($all_roles)) {
	foreach ($all_roles as $role) {
		$roles_options[$role->name] = $role->title;
	}
}

?>
<div class="elgg-module elgg-module-info elgg-module-info-roles">
	<div class="elgg-head">
		<h3><?php echo elgg_echo('user:set:role'); ?></h3>
	</div>
	<div class="elgg-body">
		<p>
			<?php echo elgg_echo('user:role:label'); ?>:
			<?php
			echo elgg_view("input/dropdown", array(
				'name' => 'role',
				'value' => '',
				'options_values' => $roles_options
			));
			?>
		</p>
	</div>
</div>

<script>
	$(document).ready(function() {
		$('.elgg-module-info-roles').insertBefore($('.elgg-form-useradd input[type=submit]'));
	});
</script>