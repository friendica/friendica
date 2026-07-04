<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public static $name = 'smarty3';

	public const FILE_PREFIX   = 'file:';
	public const STRING_PREFIX = 'string:';

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
			$message = DI::userSession()->isSiteAdmin()
				? $admin_message
				: DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new ServiceUnavailableException($message);
		}
	}

	/**
	 * Test install
	 *
	 * @param-out array $errors Array of errors passed by reference
	 */
	public function testInstall(?array &$errors = null)
	{
		/** @phpstan-ignore paramOut.type(ignore wrong param type in Smarty::testInstall()) */
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
			'vars'     => $vars,
		];
		Hook::callAll('template_vars', $arr);
		$vars = $arr['vars'];

		$this->smarty->clearAllAssign();

		foreach ($vars as $key => $value) {
			if ($key[0] === '$') {
				$key = substr((string) $key, 1);
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
		if ($subDir !== '' && !str_ends_with($subDir, '/')) {
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
