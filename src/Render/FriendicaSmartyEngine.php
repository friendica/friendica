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

namespace Friendica\Render;

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Util\Strings;

/**
 * Smarty implementation of the Friendica template abstraction
 */
final class FriendicaSmartyEngine extends TemplateEngine
{
	static $name = 'smarty3';

	const FILE_PREFIX = 'file:';
	const STRING_PREFIX = 'string:';

	/** @var FriendicaSmarty */
	private $smarty;

	/**
	 * @inheritDoc
	 */
	public function __construct(string $theme, array $theme_info)
	{
		$this->theme      = $theme;
		$this->theme_info = $theme_info;

		$work_dir     = DI::config()->get('smarty3', 'config_dir');
		$use_sub_dirs = DI::config()->get('smarty3', 'use_sub_dirs');

		$this->smarty = new FriendicaSmarty($this->theme, $this->theme_info, $work_dir, $use_sub_dirs);

		if (!is_writable($work_dir)) {
			$admin_message = DI::l10n()->t('The folder %s must be writable by webserver.', $work_dir);
			DI::logger()->critical($admin_message);
			$message = DI::app()->isSiteAdmin() ?
				$admin_message :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new ServiceUnavailableException($message);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function testInstall(array &$errors = null)
	{
		$this->smarty->testInstall($errors);
	}

	/**
	 * @inheritDoc
	 */
	public function replaceMacros(string $template, array $vars): string
	{
		if (!Strings::startsWith($template, self::FILE_PREFIX)) {
			$template = self::STRING_PREFIX . $template;
		}

		// "middleware": inject variables into templates
		$arr = [
			'template' => basename($this->smarty->filename ?? ''),
			'vars' => $vars
		];
		Hook::callAll('template_vars', $arr);
		$vars = $arr['vars'];

		$this->smarty->clearAllAssign();

		foreach ($vars as $key => $value) {
			if ($key[0] === '$') {
				$key = substr($key, 1);
			}

			$this->smarty->assign($key, $value);
		}

		return $this->smarty->fetch($template);
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateFile(string $file, string $subDir = '')
	{
		// Make sure $root ends with a slash /
		if ($subDir !== '' && substr($subDir, -1, 1) !== '/') {
			$subDir = $subDir . '/';
		}

		$root = DI::basePath() . '/' . $subDir;

		$filename = $this->smarty::SMARTY3_TEMPLATE_FOLDER . '/' . $file;

		if (file_exists("{$root}view/theme/$this->theme/$filename")) {
			$template_file = "{$root}view/theme/$this->theme/$filename";
		} elseif (!empty($this->theme_info['extends']) && file_exists(sprintf('%sview/theme/%s}/%s', $root, $this->theme_info['extends'], $filename))) {
			$template_file = sprintf('%sview/theme/%s}/%s', $root, $this->theme_info['extends'], $filename);
		} elseif (file_exists("{$root}/$filename")) {
			$template_file = "{$root}/$filename";
		} else {
			$template_file = "{$root}view/$filename";
		}

		$this->smarty->filename = $template_file;

		return self::FILE_PREFIX . $template_file;
	}
}
