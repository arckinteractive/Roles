<?php 

if (get_subtype_id('object', 'role')) {
	update_subtype('object', 'role', 'ElggRole');
} else {
	add_subtype('object', 'role', 'ElggRole');
}


?>