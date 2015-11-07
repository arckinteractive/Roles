<?php

use Elgg\Roles\Api;

/**
 * Roles library functions
 *
 * @package Roles
 * @author Andras Szepeshazi
 * @copyright Arck Interactive, LLC 2012
 * @link http://www.arckinteractive.com/
 */

/**
 * Obtains the role of a given user
 *
 * @param ElggUser $user User entity
 * @return ElggRole The role the user belongs to
 */
function roles_get_role($user = null) {
	$user = isset($user) ? $user : elgg_get_logged_in_user_entity();
	if ($user instanceof ElggUser) {
		return roles()->getRole($user);
	}
	return roles()->getRoleByName(roles()->filterName(Api::NO_ROLE));
}

/**
 * Checks if the user has a specific role
 *
 * @param ElggUser $user
 * @return bool True if the user belongs to the passed role, false otherwise
 */
function roles_has_role($user = null, $role_name = DEFAULT_ROLE) {
	return roles()->hasRole($user, $role_name);
}

/**
 * Assigns a role to a particular user
 *
 * @param ElggRole $role The role to be assigned
 * @param ElggUser $user The user the role needs to be assigned to
 * @return bool|void True if the role change was successful, false if could not update user role, and null if there was no change in user role
 */
function roles_set_role($role, $user = null) {
	$user = isset($user) ? $user : elgg_get_logged_in_user_entity();
	if ($user instanceof ElggUser && $role instanceof ElggRole) {
		return roles()->setRole($user, $role);
	}
	return false;
}

/**
 * Gets all role objects
 * @return ElggRole[]|false An array of ElggRole objects defined in the system, or false if none found
 */
function roles_get_all_roles() {
	return roles()->getAll();
}

/**
 * Gets all non-default role objects
 *
 * This is used by the role selector view. Default roles (VISITOR_ROLE, ADMIN_ROLE, DEFAULT_ROLE) need to be omitted from
 * the list of selectable roles - as default roles are automatically assigned to users based on their Elgg membership type
 * 
 * @return ElggRole[]|false An array of non-default ElggRole objects defined in the system, or false if none found
 */
function roles_get_all_selectable_roles() {
	return roles()->getSelectable();
}

/**
 * Obtains a list of permissions associated with a particular role object
 *
 * @param ElggRole $role            The role to check for permissions
 * @param string   $permission_type The section from the configuration array ('actions', 'menus', 'views', etc.)
 * @return array The permission rules for the given role and permission type
 */
function roles_get_role_permissions($role = null, $permission_type = null) {
	$role = isset($role) ? $role : roles_get_role();
	return roles()->getPermissions($role, $permission_type);
}

/**
 * Caches permissions associated with a role object. Also resolves all role extensions.
 *
 * @param ElggRole $role The role to cache permissions for
 * @return void
 */
function roles_cache_permissions($role) {
	return roles()->cachePermissions($role);
}

/**
 * Gets a role object based on it's name
 *
 * @param string $role_name The name of the role
 * @return ElggRole|false An ElggRole object if it could be found based on the name, false otherwise
 */
function roles_get_role_by_name($role_name) {
	return roles()->getRoleByName($role_name);
}

/**
 * Resolves the default role for specified or currently logged in user
 *
 * @param string $role_name The name of the user's role
 * @param int    $user_guid GUID of the user whose default role needs to be resolved
 * @return string
 */
function roles_filter_role_name($role_name, $user_guid = null) {
	if (!isset($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	return roles()->filterName($role_name, get_entity($user_guid) ? : null);
}

/**
 * Processes the configuration files and generates the appropriate ElggRole objects.
 *
 * If, for any role definition, there is an already existing role with the same name,
 * the role permissions will be updated for the given role object.
 * If there is no previously existing, corresponding role object, it will be created now.
 *
 * @param array $roles_array The roles configuration array
 * @return void
 */
function roles_create_from_config($roles_array) {
	return roles()->createFromConfig($roles_array);
}

/**
 * Checks if the configuration array has been updated and updates role objects accordingly if needed
 * @return void
 */
function roles_check_update() {
	return roles()->checkUpdate();
}

/**
 * @deprecated 2.0
 */
function roles_unregister_menu_item() {
	
}

/**
 * @deprecated 2.0
 */
function roles_replace_menu_item() {

}

/**
 * @deprecated 2.0
 */
function roles_unregister_menu_item_recursive() {
	
}

/**
 * @deprecated 2.0
 */
function roles_replace_menu_item_recursive() {
	
}

/**
 * @deprecated 2.0
 */
function roles_find_menu_index() {
	
}

/**
 * Substitutes dynamic parts of a menu's target URL
 *
 * @param array $vars An associative array holding the menu permissions
 * @return The substituted menu permission array
 */
function roles_prepare_menu_vars($vars) {
	return roles()->prepareMenuVars($vars);
}

/**
 * @deprecated 2.0
 */
function roles_get_menu($menu_name) {
	
}

/**
 * Replaces certain parts of path and URL type definitions with dynamic values
 *
 * @param string $str The string to operate on
 * @return string The updated, substituted string
 */
function roles_replace_dynamic_paths($str) {
	return roles()->replaceDynamicPaths($str);
}

/**
 * Checks if a path or URL type rule matches a given path. Also processes regular expressions
 *
 * @param string $rule The permission rule to check
 * @param string $path The path to match against
 * @return boole True if the rule matches the path, false otherwise
 */
function roles_path_match($rule, $path) {
	return roles()->matchPath($rule, $path);
}

/**
 * Checks if a permission rule should be executed for the current context
 *
 * @param string  $permission_details The permission rule configuration
 * @param boolean $strict             If strict context matching should be used.
 * 							          If true, only the last context will be checked for the rule matching.
 * 							          If false, any context value in the context stack will be considered.
 * @return bool True if the rule should be executed, false otherwise
 */
function roles_check_context($permission_details, $strict = false) {
	return roles()->checkContext($permission_details, $strict);
}

/**
 * Updates roles objects to 1.0.1 version
 * @return bool
 */
function roles_update_100_to_101() {

	// Remove all 'roles_hash' values from plugin settings
	// This will force new storage of the configuration array hash
	$dbprefix = elgg_get_config('dbprefix');
	$statement = "DELETE from {$dbprefix}private_settings WHERE name = 'roles_hash'";
	return delete_data($statement);
}
