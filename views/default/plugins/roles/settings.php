<?php

echo '<div>';
echo '<label>' . elgg_echo('roles:settings:automatic_reset') . '</label>';
echo '<div class="elgg-text-help">' . elgg_echo('roles:settings:automatic_reset:help') . '</div>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[automatic_reset]',
	'value' => $vars['entity']->automatic_reset,
	'options_values' => array(
		0 => elgg_echo('option:no'),
		1 => elgg_echo('option:yes')
	)
));
echo '</div>';
?>






