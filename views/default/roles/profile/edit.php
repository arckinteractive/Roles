<?php

$user = elgg_extract('entity', $vars);
$role = roles_get_role($user);

$metafields = roles_get_role_metafields($role, 'profile');

if (is_array($metafields) && sizeof($metafields) > 0) {
	elgg_view_title(elgg_echo("roles:profile:edit:$role_name"), array('class' => 'roles-profile-edit-title'));
	foreach ($metafields as $shortname => $options) {

		$metadata = elgg_get_metadata(array(
			'guid' => $vars['entity']->guid,
			'metadata_name' => $shortname
				));
		if ($metadata) {
			if (is_array($metadata)) {
				$value = '';
				foreach ($metadata as $md) {
					if (!empty($value)) {
						$value .= ', ';
					}
					$value .= $md->value;
					$access_id = $md->access_id;
				}
			} else {
				$value = $metadata->value;
				$access_id = $metadata->access_id;
			}
		} else {
			$value = '';
			$access_id = ACCESS_DEFAULT;
		}

		$options['value'] = $value;

		$valtype = $options['input_type'];
		unset($options['input_type']);

		$hint = $options['hint'];
		unset($options['hint']);

		echo "<label class=\"{$options['required']}\">" . elgg_echo("roles:profile:$shortname") . '</label>';
		echo "<span class=\"hint\">$hint</span>";
		echo elgg_view("input/$valtype", $options);
		$params = array(
			'name' => "accesslevel[$shortname]",
			'value' => $access_id,
		);
		echo elgg_view('input/access', $params);
	}
}