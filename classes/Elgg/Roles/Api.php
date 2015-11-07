<?php

namespace Elgg\Roles;

use ElggMenuItem;
use ElggRole;
use ElggUser;

class Api {

	const DEFAULT_ROLE = 'default';
	const ADMIN_ROLE = 'admin';
	const VISITOR_ROLE = 'visitor';
	const NO_ROLE = '_no_role_';
	const DENY = 'deny';
	const ALLOW = 'allow';
	const REPLACE = 'replace';
	const EXTEND = 'extend';
	const REDIRECT = 'redirect';
	const FORWARD = 'forward';

	/**
	 * @var DbInterface
	 */
	private $db;

	/**
	 * Permissions cache
	 * @var array
	 */
	private $cache;

	/**
	 * Roles cache
	 * @var ElggRole[]
	 */
	private $roles;

	/**
	 * Constructor
	 */
	public function __construct(DbInterface $db) {
		$this->cache = array();
		$this->db = $db;
	}

	/**
	 * Obtains the role of a given user
	 *
	 * @param ElggUser $user User entity
	 * @return ElggRole The role the user belongs to
	 */
	public function getRole(ElggUser $user) {
		$role = $this->db->getUserRole($user);
		return $role ? : $this->getRoleByName($this->filterName(self::NO_ROLE, $user));
	}

	/**
	 * Checks if the user has a specific role
	 *
	 * @param ElggUser $user User entity
	 * @return bool True if the user belongs to the passed role, false otherwise
	 */
	public function hasRole(ElggUser $user, $role_name = self::DEFAULT_ROLE) {
		return $this->getRole($user)->name == $role_name;
	}

	/**
	 * Assigns a role to a particular user
	 *
	 * @param ElggUser $user The user the role needs to be assigned to
	 * @param ElggRole $role The role to be assigned
	 * @return bool|void True if the role change was successful, false if could not update user role, and null if there was no change in user role
	 */
	public function setRole(ElggUser $user, ElggRole $role) {

		$current_role = $this->getRole($user);
		if ($role->name == $current_role->name) {
			// There was no change necessary, old and new role are the same
			return;
		}

		if (!$this->unsetRole($user)) {
			return false;
		}

		if ($role->isReservedRole()) {
			// Changed to reserved role which is resolved without relationships
			return true;
		}

		return $this->db->setUserRole($user, $role);
	}

	/**
	 * Clear user roles
	 * 
	 * @param ElggUser $user User entity
	 * @return bool
	 */
	public function unsetRole(ElggUser $user) {
		return $this->db->unsetUserRole($user);
	}

	/**
	 * Gets all role objects
	 * @return ElggRole[]|false An array of ElggRole objects defined in the system, or false if none found
	 */
	public function getAll() {
		if (!isset($this->roles)) {
			$this->roles = array();
			$roles = $this->db->getAllRoles();
			foreach ($roles as $role) {
				/* @var $role ElggRole */
				$this->roles[$role->name] = $role;
			}
		}
		return $this->roles;
	}

	/**
	 * Gets all non-default role objects
	 *
	 * This is used by the role selector view. Default roles (VISITOR_ROLE, ADMIN_ROLE, DEFAULT_ROLE) need to be omitted from
	 * the list of selectable roles - as default roles are automatically assigned to users based on their Elgg membership type
	 *
	 * @return ElggRole[]|false An array of non-default ElggRole objects defined in the system, or false if none found
	 */
	public function getSelectable() {
		$roles = $this->getAll();
		return array_filter($roles, function(ElggRole $role) {
			return !$role->isReservedRole();
		});
	}

	/**
	 * Obtains a list of permissions associated with a particular role object
	 *
	 * @param ElggRole $role            The role to check for permissions
	 * @param string   $permission_type The section from the configuration array ('actions', 'menus', 'views', etc.)
	 * @return array The permission rules for the given role and permission type
	 */
	public function getPermissions(ElggRole $role, $permission_type = null) {
		if (!isset($this->cache[$role->name])) {
			$this->cachePermissions($role);
		}

		if ($permission_type) {
			return (isset($this->cache[$role->name][$permission_type])) ? (array) $this->cache[$role->name][$permission_type] : array();
		} else {
			return (array) $this->cache[$role->name];
		}
	}

	/**
	 * Caches permissions associated with a role object. Also resolves all role extensions.
	 *
	 * @param ElggRole $role The role to cache permissions for
	 * @return void
	 */
	public function cachePermissions(ElggRole $role) {
		if (empty($this->cache[$role->name])) {
			$this->cache[$role->name] = array();
		}

		// Let' start by processing role extensions
		$extends = $role->getExtends();
		if (!empty($extends)) {
			foreach ($extends as $extended_role_name) {
				$extended_role = $this->getRoleByName($extended_role_name);
				if (!isset($this->cache[$extended_role->name])) {
					$this->cachePermissions($extended_role);
				}

				foreach ($this->cache[$extended_role->name] as $type => $permission_rules) {
					if (empty($this->cache[$role->name][$type])) {
						$this->cache[$role->name][$type] = array();
					}
					if (is_array($this->cache[$role->name][$type])) {
						$this->cache[$role->name][$type] = array_merge($this->cache[$role->name][$type], $permission_rules);
					} else {
						$this->cache[$role->name][$type] = $permission_rules;
					}
				}
			}
		}

		$permissions = $role->getPermissions();
		foreach ($permissions as $type => $permission_rules) {
			if (isset($this->cache[$role->name][$type]) && is_array($this->cache[$role->name][$type])) {
				$this->cache[$role->name][$type] = array_merge($this->cache[$role->name][$type], $permission_rules);
			} else {
				$this->cache[$role->name][$type] = $permission_rules;
			}
		}
	}

	/**
	 * Gets a role object based on it's name
	 *
	 * @param string $role_name The name of the role
	 * @return ElggRole|false An ElggRole object if it could be found based on the name, false otherwise
	 */
	public function getRoleByName($role_name = '') {
		$roles = $this->getAll();
		return isset($roles[$role_name]) ? $roles[$role_name] : false;
	}

	/**
	 * Resolves the default role for specified or currently logged in user
	 *
	 * @param string    $role_name The name of the user's role
	 * @param ElggUser $user      User whose default role needs to be resolved
	 * @return string
	 */
	public function filterName($role_name, ElggUser $user = null) {
		if ($role_name !== self::NO_ROLE) {
			return $role_name;
		}

		if ($user instanceof ElggUser) {
			return $user->isAdmin() ? self::ADMIN_ROLE : self::DEFAULT_ROLE;
		}

		return self::VISITOR_ROLE;
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
	public function createFromConfig($roles_array) {

		elgg_log('Creating roles from config', 'DEBUG');

		$existing_roles = $this->getAll();

		foreach ($roles_array as $rname => $rdetails) {
			$current_role = $existing_roles[$rname];
			if ($current_role instanceof ElggRole) {
				elgg_log("Role '$rname' already exists; updating permissions", 'DEBUG');
				// Update existing role obejct
				$current_role->title = $rdetails['title'];
				$current_role->setExtends($rdetails['extends']);
				$current_role->setPermissions($rdetails['permissions']);
				if ($current_role->save()) {
					elgg_log("Permissions for role '$rname' have been updated: " . print_r($rdetails['permissions'], true), 'DEBUG');
				}
			} else {
				elgg_log("Creating a new role '$rname'", 'DEBUG');
				// Create new role object
				$new_role = new ElggRole();
				$new_role->title = $rdetails['title'];
				$new_role->owner_guid = elgg_get_logged_in_user_guid();
				$new_role->container_guid = $new_role->owner_guid;
				$new_role->access_id = ACCESS_PUBLIC;
				if (!($new_role->save())) {
					elgg_log("Could not create new role '$rname'", 'DEBUG');
				} else {
					// Add metadata
					$new_role->name = $rname;
					$new_role->setExtends($rdetails['extends']);
					$new_role->setPermissions($rdetails['permissions']);
					if ($new_role->save()) {
						elgg_log("Role object with guid $new_role->guid has been created", 'DEBUG');
						elgg_log("Permissions for '$rname' have been set: " . print_r($rdetails['permissions'], true), 'DEBUG');
					}
				}
			}
		}

		// remove old roles
		$config_roles = array_keys($roles_array);
		foreach ($existing_roles as $name => $role) {
			if (!in_array($name, $config_roles)) {
				elgg_log("Deleting role '$rname'");
				$role->delete();
			}
		}
	}

	/**
	 * Checks if the configuration array has been updated and updates role objects accordingly if needed
	 * @return void
	 */
	public function checkUpdate() {
		$hash = elgg_get_plugin_setting('roles_hash', 'roles');
		$roles_array = elgg_trigger_plugin_hook('roles:config', 'role', array(), null);

		$current_hash = sha1(serialize($roles_array));

		if ($hash != $current_hash) {
			roles_create_from_config($roles_array);
			elgg_set_plugin_setting('roles_hash', $current_hash, 'roles');
		}
	}

	/**
	 * Substitutes dynamic parts of a menu's target URL
	 *
	 * @param array $vars An associative array holding the menu permissions
	 * @return The substituted menu permission array
	 */
	public function prepareMenuVars($vars) {

		$prepared_vars = $vars;
		if (isset($prepared_vars['href'])) {
			$prepared_vars['href'] = $this->replaceDynamicPaths($prepared_vars['href']);
		}

		return $prepared_vars;
	}

	/**
	 * Replaces certain parts of path and URL type definitions with dynamic values
	 *
	 * @param string $str The string to operate on
	 * @return string The updated, substituted string
	 */
	public function replaceDynamicPaths($str) {
		$res = $str;
		$user = elgg_get_logged_in_user_entity();
		if ($user instanceof ElggUser) {
			$self_username = $user->username;
			$self_guid = $user->guid;
			$role = roles_get_role($user);

			$res = str_replace('{$self_username}', $self_username, $str);
			$res = str_replace('{$self_guid}', $self_guid, $res);
			if ($role instanceof ElggRole) {
				$res = str_replace('{$self_rolename}', $role->name, $res);
			}
		}

		// Safe way to get hold of the page owner before system, ready event
		$pageowner_guid = elgg_trigger_plugin_hook('page_owner', 'system', NULL, 0);
		$pageowner = get_entity($pageowner_guid);

		if ($pageowner instanceof ElggUser) {
			$pageowner_username = $pageowner->username;
			$pageowner_role = roles_get_role($pageowner);

			$res = str_replace('{$pageowner_name}', $pageowner_username, $res);
			$res = str_replace('{$pageowner_guid}', $pageowner_guid, $res);
			$res = str_replace('{$pageowner_rolename}', $pageowner_role->name, $res);
		}

		return $res;
	}

	/**
	 * Checks if a path or URL type rule matches a given path. Also processes regular expressions
	 *
	 * @param string $rule The permission rule to check
	 * @param string $path The path to match against
	 * @return bool True if the rule matches the path, false otherwise
	 */
	public function matchPath($rule, $path) {
		if (preg_match('/^regexp\((.+)\)$/', $rule) > 0) {
			// The rule contains regular expression; use regexp matching for the current path
			$pattern = preg_replace('/^regexp\(/', '', $rule);
			$pattern = preg_replace('/\)$/', '', $pattern);
		} else {
			$rule = trim($rule, '/');
			$wwwroot = elgg_get_config('wwwroot') ? : elgg_get_site_url();
			$pattern = "`^{$wwwroot}$rule/*$`i";
			$path = elgg_normalize_url($path);
		}
		return (bool) preg_match($pattern, $path);
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
	public function checkContext($permission_details, $strict = false) {
		$context = elgg_extract('context', $permission_details);
		if (!isset($context)) {
			return true;
		}
		if (!is_array($context)) {
			$context = array($context);
		}
		if ($strict) {
			return in_array(elgg_get_context(), $context);
		}
		$stack = (array) elgg_get_context_stack();
		return count(array_intersect($context, $stack)) > 0;
	}

	/**
	 * Gets all reserved role names
	 * @return array The list of reserved role names
	 */
	public function getReservedRoleNames() {
		return array(self::DEFAULT_ROLE, self::ADMIN_ROLE, self::VISITOR_ROLE);
	}

	/**
	 *
	 * Checks if a role name is reserved in the system
	 *
	 * @param string $role_name The name of the role to check
	 * @return boolean True if the passed $role_name is a reserved role name
	 */
	public function isReservedRoleName($role_name) {
		return in_array($role_name, $this->getReservedRoleNames());
	}

	/**
	 * Setup views for a given role
	 * 
	 * @param ElggRole $role Role
	 * @return void
	 */
	public function setupViews(\ElggRole $role) {

		$role_perms = $this->getPermissions($role, 'views');
		foreach ($role_perms as $view => $perm_details) {
			switch ($perm_details['rule']) {

				case self::DENY:
					elgg_register_plugin_hook_handler('view', $view, array($this, 'supressView'));
					break;

				case self::EXTEND:
					$params = $perm_details['view_extension'];
					$view_extension = $this->replaceDynamicPaths($params['view']);
					$priority = isset($params['priority']) ? $params['priority'] : 501;
					$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
					elgg_extend_view($view, $view_extension, $priority, $viewtype);
					break;

				case self::REPLACE:
					$params = $perm_details['view_replacement'];
					$location = elgg_get_root_path() . $this->replaceDynamicPaths($params['location']);
					$viewtype = isset($params['viewtype']) ? $params['viewtype'] : '';
					elgg_set_view_location($view, $location, $viewtype);
					break;

				case self::ALLOW:
					elgg_unregister_plugin_hook_handler('view', $view, array($this, 'supressView'));
					break;
			}
		}
	}

	/**
	 * Supresses view output
	 * @return string
	 */
	public function supressView() {
		return '';
	}

	/**
	 * Setup hooks for a given role
	 * 
	 * @param ElggRole $role Role object
	 * @return void
	 */
	public function setupHooks(\ElggRole $role) {
		$role_perms = $this->getPermissions($role, 'hooks');
		foreach ($role_perms as $hook => $perm_details) {
			list($hook_name, $type) = explode('::', $hook);
			if (!$type) {
				$type = 'all';
			}
			switch ($perm_details['rule']) {
				case self::DENY:
					$params = elgg_extract('hook', $perm_details);
					if (isset($params['handler'])) {
						$handler = $params['handler'];
						elgg_unregister_plugin_hook_handler($hook_name, $type, $handler);
					} else {
						if (is_callable('elgg_clear_plugin_hook_handlers')) {
							elgg_clear_plugin_hook_handlers($hook_name, $type);
						} else {
							$handlers = _elgg_services()->hooks->getOrderedHandlers($hook_name, $type);
							foreach ($handlers as $handler) {
								elgg_unregister_plugin_hook_handler($hook_name, $type, $handler);
							}
						}
					}
					break;

				case self::EXTEND:
					$params = $perm_details['hook'];
					$handler = $params['handler'];
					$priority = isset($params['priority']) ? $params['priority'] : 500;
					elgg_register_plugin_hook_handler($hook_name, $type, $handler, $priority);
					break;

				case self::REPLACE:
					$params = $perm_details['hook'];
					$old_handler = $params['old_handler'];
					$new_handler = $params['new_handler'];
					$priority = isset($params['priority']) ? $params['priority'] : 500;
					elgg_unregister_plugin_hook_handler($hook_name, $type, $old_handler);
					elgg_register_plugin_hook_handler($hook_name, $type, $new_handler, $priority);
					break;

				case self::ALLOW:
				default:
					break;
			}
		}
	}

	/**
	 * Setup events for a given role
	 * 
	 * @param ElggRole $role Role object
	 * @return void
	 */
	function setupEvents(\ElggRole $role) {

		$role_perms = $this->getPermissions($role, 'events');
		foreach ($role_perms as $event => $perm_details) {

			list($event_name, $type) = explode('::', $event);
			if (!$type) {
				$type = 'all';
			}

			switch ($perm_details['rule']) {

				case self::DENY:
					$params = elgg_extract('event', $perm_details);
					if (isset($params['handler'])) {
						$handler = $params['handler'];
						elgg_unregister_event_handler($event_name, $type, $handler);
					} else {
						// @TODO: Update when https://github.com/Elgg/Elgg/issues/9113 is fixed
						$handlers = _elgg_services()->events->getOrderedHandlers($event_name, $type);
						foreach ($handlers as $handler) {
							elgg_unregister_event_handler($event_name, $type, $handler);
						}
					}
					break;

				case self::EXTEND:
					$params = elgg_extract('event', $perm_details);
					$handler = $params['handler'];
					$priority = isset($params['priority']) ? $params['priority'] : 500;
					elgg_register_event_handler($event_name, $type, $handler, $priority);
					break;

				case self::REPLACE:
					$params = elgg_extract('event', $perm_details);
					$old_handler = $params['old_handler'];
					$new_handler = $params['new_handler'];
					$priority = isset($params['priority']) ? $params['priority'] : 500;
					elgg_unregister_event_handler($event_name, $type, $old_handler);
					elgg_register_event_handler($event_name, $type, $new_handler, $priority);
					break;

				case self::ALLOW:
				default:
					break;
			}
		}
	}

	/**
	 * Check action access permissions for a given role
	 * 
	 * @param ElggRole $role   Role object
	 * @param string   $action Registered action name
	 * @return boolean|void
	 */
	function actionGatekeeper(\ElggRole $role, $action = '') {
		$role_perms = $this->getPermissions($role, 'actions');
		foreach ($role_perms as $rule_name => $perm_details) {
			if (!$this->matchPath($this->replaceDynamicPaths($rule_name), $action)) {
				continue;
			}
			switch ($perm_details['rule']) {
				case self::DENY:
					return false;
			}
		}
	}

	/**
	 * Check page access permissions for a given role
	 * 
	 * @param ElggRole $role Role object
	 * @param string   $path URL path
	 * @return array
	 */
	function pageGatekeeper(\ElggRole $role, $path = '') {
		$forward = false;
		$error = false;

		$role_perms = $this->getPermissions($role, 'pages');
		foreach ($role_perms as $page => $perm_details) {

			if (!$this->matchPath($this->replaceDynamicPaths($page), $path)) {
				continue;
			}

			switch ($perm_details['rule']) {

				case self::DENY:
					$error = true;

				case self::REDIRECT:
				case self::REPLACE:
				case self::FORWARD:
					$forward = elgg_extract('forward', $perm_details, REFERRER);
					break;
			}
		}

		return array(
			'forward' => is_string($forward) ? $this->replaceDynamicPaths($forward) : $forward,
			'error' => $error,
		);
	}

	/**
	 * Setup menu
	 *
	 * @param ElggRole       $role      Role object
	 * @param string         $menu_name Menu name
	 * @param ElggMenuItem[] $menu      Menu items
	 * @return ElggMenuItem[]
	 */
	public function setupMenu(\ElggRole $role, $menu_name = '', $menu = array()) {

		$role_perms = $this->getPermissions($role, 'menus');

		foreach ($role_perms as $menu_rule => $perm_details) {
			if (empty($menu_rule)) {
				continue;
			}

			$menu_rule_parts = explode('::', $menu_rule);
			if (elgg_extract(0, $menu_rule_parts) != $menu_name) {
				continue;
			}
			$menu_item_name = elgg_extract(1, $menu_rule_parts);

			// Check if this rule relates to the currently triggered menu and if we're in the right context for the current rule
			if (!$this->checkContext($perm_details)) {
				continue;
			}

			// Try to act on this permission rule
			switch ($perm_details['rule']) {
				case self::DENY:
					if (!$menu_item_name) {
						$menu = array();
					} else {
						foreach ($menu as $key => $item) {
							if (!$item instanceof ElggMenuItem) {
								continue;
							}
							if ($item->getName() == $menu_item_name || $item->getParentName() == $menu_item_name) {
								unset($menu[$key]);
							}
						}
					}
					break;

				case self::EXTEND:
					$menu_item = elgg_extract('menu_item', $perm_details);
					if ($menu_item) {
						$menu[] = ElggMenuItem::factory($this->prepareMenuVars($menu_item));
					}
					break;

				case self::REPLACE:
					$menu_item = elgg_extract('menu_item', $perm_details);
					if (!$menu_item_name || !$menu_item) {
						break;
					}

					$new_item = ElggMenuItem::factory($this->prepareMenuVars($menu_item));
					foreach ($menu as $key => $item) {
						if (!$item instanceof ElggMenuItem) {
							continue;
						}
						if ($item->getName() == $menu_item_name) {
							$menu[$key] = $new_item;
						} else if ($item->getParentName() == $menu_item_name) {
							$item->setParentName($new_item->getName());
							$menu[$key] = $item;
						}
					}
					break;

				case self::ALLOW:
				default:
					break;
			}
		}

		// Return the updated menu to the hook triggering function (elgg_view_menu)
		return $menu;
	}

	/**
	 * Remove items that link to denied pages and actions
	 * 
	 * @param ElggRole       $role Role object
	 * @param ElggMenuItem[] $menu Menu
	 * @return ElggMenuItem[]
	 */
	public function cleanMenu(\ElggRole $role, $menu = array()) {

		$pages = $this->getPermissions($role, 'pages');
		$actions = $this->getPermissions($role, 'actions');

		$paths = array();
		foreach ($pages as $rule => $opts) {
			if ($opts['rule'] == self::DENY) {
				$paths[] = $this->replaceDynamicPaths($rule);
			}
		}
		foreach ($actions as $rule => $opts) {
			if ($opts['rule'] == self::DENY) {
				$paths[] = $this->replaceDynamicPaths("action/$rule");
			}
		}

		$remove = array();
		// Unregister menu items that link to pages and actions that have been denied
		foreach ($menu as $key => $item) {
			if (!$item instanceof ElggMenuItem) {
				continue;
			}

			$href = $item->getHref();
			foreach ($paths as $path) {
				if ($this->matchPath($path, $href)) {
					$remove[] = $item->getName();
				}
			}
		}

		foreach ($menu as $key => $item) {
			if (!$item instanceof ElggMenuItem) {
				continue;
			}
			if (in_array($item->getName(), $remove) || in_array($item->getParentName(), $remove)) {
				unset($menu[$key]);
			}
		}

		return $menu;
	}

}
