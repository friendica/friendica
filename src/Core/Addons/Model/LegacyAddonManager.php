<?php

namespace Friendica\Core\Addons\Model;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class LegacyAddonManager
{
	/** @var LoggerInterface */
	protected $logger;
	/** @var IManageConfigValues */
	protected $config;
	/** @var App */
	protected $app;

	/**
	 * installs an addon.
	 *
	 * @param string $addon name of the addon
	 *
	 * @return bool
	 */
	public function install(string $addon): bool
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$addon_file_path = 'addon/' . $addon . '/' . $addon . '.php';

		// silently fail if addon was removed of if $addon is funky
		if (!file_exists($addon_file_path)) {
			return false;
		}

		$this->logger->debug("Addon {addon}: {action}", ['action' => 'install', 'addon' => $addon]);
		$t = @filemtime($addon_file_path);
		@include_once($addon_file_path);
		if (function_exists($addon . '_install')) {
			$func = $addon . '_install';
			$func($this->app	);
		}

		$this->config->set('addons', $addon, [
			'last_update' => $t,
			'admin' => function_exists($addon . '_addon_admin'),
		]);

		return true;
	}

	/**
	 * uninstalls an addon.
	 *
	 * @param string $addon name of the addon
	 * @return void
	 * @throws \Exception
	 */
	public function uninstall(string $addon)
	{
		$addon = Strings::sanitizeFilePathItem($addon);

		$this->logger->debug("Addon {addon}: {action}", ['action' => 'uninstall', 'addon' => $addon]);
		$this->config->delete('addons', $addon);

		@include_once('addon/' . $addon . '/' . $addon . '.php');
		if (function_exists($addon . '_uninstall')) {
			$func = $addon . '_uninstall';
			$func();
		}

		Hook::delete(['file' => 'addon/' . $addon . '/' . $addon . '.php']);
	}
}
