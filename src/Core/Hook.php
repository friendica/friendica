<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core;

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

/**
 * Some functions to handle hooks
 */
class Hook
{
	/**
	 * Array of registered hooks
	 *
	 * Format:
	 * [
	 *		["<hook name>"] => [
	 *			0 => "<hook file>",
	 *			1 => "<hook function name>"
	 *		],
	 *		...
	 * ]
	 *
	 * @var array
	 */
	private static $hooks = [];

	/**
	 * Load hooks
	 *
	 * @return void
	 */
	public static function loadHooks()
	{
		self::$hooks = [];
		$stmt = DBA::select('hook', ['hook', 'file', 'function'], [], ['order' => ['priority' => 'desc', 'file']]);

		while ($hook = DBA::fetch($stmt)) {
			self::add($hook['hook'], $hook['file'], $hook['function']);
		}
		DBA::close($stmt);
	}

	/**
	 * Adds a new hook to the hooks array.
	 *
	 * This function is meant to be called by modules on each page load as it works after loadHooks has been called.
	 *
	 * @param string $hook
	 * @param string $file
	 * @param string $function
	 * @return void
	 */
	public static function add(string $hook, string $file, string $function)
	{
		if (!array_key_exists($hook, self::$hooks)) {
			self::$hooks[$hook] = [];
		}
		self::$hooks[$hook][] = [$file, $function];
	}

	/**
	 * Registers a hook.
	 *
	 * This function is meant to be called once when an addon is enabled for example as it doesn't add to the current hooks.
	 *
	 * @param string $hook     the name of the hook
	 * @param string $file     the name of the file that hooks into
	 * @param string $function the name of the function that the hook will call
	 * @param int    $priority A priority (defaults to 0)
	 * @return mixed|bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function register(string $hook, string $file, string $function, int $priority = 0)
	{
		$file = str_replace(DI::app()->getBasePath() . DIRECTORY_SEPARATOR, '', $file);

		$condition = ['hook' => $hook, 'file' => $file, 'function' => $function];
		if (DBA::exists('hook', $condition)) {
			return true;
		}

		return self::insert(['hook' => $hook, 'file' => $file, 'function' => $function, 'priority' => $priority]);
	}

	/**
	 * Unregisters a hook.
	 *
	 * @param string $hook     the name of the hook
	 * @param string $file     the name of the file that hooks into
	 * @param string $function the name of the function that the hook called
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function unregister(string $hook, string $file, string $function): bool
	{
		$relative_file = str_replace(DI::app()->getBasePath() . DIRECTORY_SEPARATOR, '', $file);

		// This here is only needed for fixing a problem that existed on the develop branch
		$condition = ['hook' => $hook, 'file' => $file, 'function' => $function];
		self::delete($condition);

		$condition = ['hook' => $hook, 'file' => $relative_file, 'function' => $function];

		return self::delete($condition);
	}

	/**
	 * Returns the list of callbacks for a single hook
	 *
	 * @param  string $name Name of the hook
	 * @return array
	 */
	public static function getByName(string $name): array
	{
		$return = [];

		if (isset(self::$hooks[$name])) {
			$return = self::$hooks[$name];
		}

		return $return;
	}

	/**
	 * Forks a hook.
	 *
	 * Use this function when you want to fork a hook via the worker.
	 *
	 * @param integer $priority of the hook
	 * @param string  $name     of the hook to call
	 * @param mixed   $data     to transmit to the callback handler
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fork(int $priority, string $name, $data = null)
	{
		if (array_key_exists($name, self::$hooks)) {
			foreach (self::$hooks[$name] as $hook) {
				// Call a hook to check if this hook call needs to be forked
				if (array_key_exists('hook_fork', self::$hooks)) {
					$hookdata = ['name' => $name, 'data' => $data, 'execute' => true];

					foreach (self::$hooks['hook_fork'] as $fork_hook) {
						if ($hook[0] != $fork_hook[0]) {
							continue;
						}
						self::callSingle('hook_fork', $fork_hook, $hookdata);
					}

					if (!$hookdata['execute']) {
						continue;
					}
				}

				Worker::add($priority, 'ForkHook', $name, $hook, $data);
			}
		}
	}

	/**
	 * Calls a hook.
	 *
	 * Use this function when you want to be able to allow a hook to manipulate
	 * the provided data.
	 *
	 * @param string        $name of the hook to call
	 * @param string|array &$data to transmit to the callback handler
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function callAll(string $name, &$data = null)
	{
		if (array_key_exists($name, self::$hooks)) {
			foreach (self::$hooks[$name] as $hook) {
				self::callSingle($name, $hook, $data);
			}
		}
	}

	/**
	 * Calls a single hook.
	 *
	 * @param string          $name of the hook to call
	 * @param array           $hook Hook data
	 * @param string|array   &$data to transmit to the callback handler
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function callSingle(string $name, array $hook, &$data = null)
	{
		// Don't run a theme's hook if the user isn't using the theme
		if (strpos($hook[0], 'view/theme/') !== false && strpos($hook[0], 'view/theme/' . DI::app()->getCurrentTheme()) === false) {
			return;
		}

		@include_once($hook[0]);
		if (function_exists($hook[1])) {
			$func = $hook[1];
			$func($data);
		} else {
			// remove orphan hooks
			$condition = ['hook' => $name, 'file' => $hook[0], 'function' => $hook[1]];
			self::delete($condition);
		}
	}

	/**
	 * Checks if an app_menu hook exist for the provided addon name.
	 * Return true if the addon is an app
	 *
	 * @param string $name Name of the addon
	 * @return boolean
	 */
	public static function isAddonApp(string $name): bool
	{
		$name = Strings::sanitizeFilePathItem($name);

		if (array_key_exists('app_menu', self::$hooks)) {
			foreach (self::$hooks['app_menu'] as $hook) {
				if ($hook[0] == 'addon/' . $name . '/' . $name . '.php') {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Deletes one or more hook records
	 *
	 * We have to clear the cached routerDispatchData because addons can provide routes
	 *
	 * @param array $condition
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete(array $condition): bool
	{
		$result = DBA::delete('hook', $condition);

		if ($result) {
			DI::cache()->delete('routerDispatchData');
		}

		return $result;
	}

	/**
	 * Inserts a hook record
	 *
	 * We have to clear the cached routerDispatchData because addons can provide routes
	 *
	 * @param array $condition
	 * @return bool
	 * @throws \Exception
	 */
	private static function insert(array $condition): bool
	{
		$result = DBA::insert('hook', $condition);

		if ($result) {
			DI::cache()->delete('routerDispatchData');
		}

		return $result;
	}
}
