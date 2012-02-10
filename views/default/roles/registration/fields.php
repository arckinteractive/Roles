<?php

$role_name = elgg_extract('role', $vars, get_input('role'));

if (!$role_name) {
	return true;
}

$role = roles_get_role_by_name($role_name);
$metafields = roles_get_role_metafields($role, 'registration');

echo elgg_view('input/hidden', array(
	'role' => $role_name
));

if (is_array($metafields) && sizeof($metafields) > 0) {
	foreach ($metafields as $shortname => $options) {
		$valtype = $options['input_type'];
		unset($options['input_type']);

		$hint = $options['hint'];
		unset($options['hint']);

		echo "<label class=\"{$options['required']}\">" . elgg_echo("roles:registration:$shortname") . '</label>';
		echo "<span class=\"hint\">$hint</span>";
		echo elgg_view("input/$valtype", $options);
	}
}