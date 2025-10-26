<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Exception;
use Friendica\DI;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Render\TemplateEngine;

/**
 * This class handles Renderer related functions.
 */
class Renderer
{
	/**
	 * An array of registered template engines ('name'=>'class name')
	 */
	public static $template_engines = [];

	/**
	 * An array of instanced template engines ('name'=>'instance')
	 */
	public static $template_engine_instance = [];

	/**
	 * An array for all theme-controllable parameters
	 *
	 * Mostly unimplemented yet. Only options 'template_engine' and
	 * beyond are used.
	 */
	public static $theme = [
		'stylesheet'      => '',
		'template_engine' => 'smarty3',
	];

	private static $ldelim = [
		'internal' => '',
		'smarty3'  => '{{'
	];
	private static $rdelim = [
		'internal' => '',
		'smarty3'  => '}}'
	];

	/**
	 * Returns the rendered template output from the template string and variables
	 *
	 * @param string $template
	 * @param array  $vars
	 * @return string
	 * @throws ServiceUnavailableException
	 */
	public static function replaceMacros(string $template, array $vars = []): string
	{
		DI::profiler()->startRecording('rendering');

		// Default template variables
		$vars = array_merge([
			'$baseurl' => DI::baseUrl(),
			'$VERSION' => \Friendica\App::VERSION,
		], $vars);

		$t = self::getTemplateEngine();

		try {
			$output = $t->replaceMacros($template, $vars);
		} catch (Exception $e) {
			DI::logger()->critical($e->getMessage(), ['template' => $template, 'vars' => $vars]);
			$message = DI::userSession()->isSiteAdmin() ?
				$e->getMessage() :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new ServiceUnavailableException($message);
		}

		DI::profiler()->stopRecording();

		return $output;
	}

	/**
	 * Load a given template $s
	 *
	 * @param string $file   Template to load.
	 * @param string $subDir Subdirectory (Optional)
	 *
	 * @return string Template
	 * @throws ServiceUnavailableException
	 */
	public static function getMarkupTemplate(string $file, string $subDir = ''): string
	{
		DI::profiler()->startRecording('file');
		$t = self::getTemplateEngine();

		try {
			$template = $t->getTemplateFile($file, $subDir);
		} catch (Exception $e) {
			DI::logger()->critical($e->getMessage(), ['file' => $file, 'subDir' => $subDir]);
			$message = DI::userSession()->isSiteAdmin() ?
				$e->getMessage() :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new ServiceUnavailableException($message);
		}

		DI::profiler()->stopRecording();

		return $template;
	}

	/**
	 * Register template engine class
	 *
	 * @param string $class
	 *
	 * @return void
	 * @throws ServiceUnavailableException
	 */
	public static function registerTemplateEngine(string $class)
	{
		$v = get_class_vars($class);

		if (!empty($v['name'])) {
			$name = $v['name'];

			self::$template_engines[$name] = $class;
		} else {
			$admin_message = DI::l10n()->t('template engine cannot be registered without a name.');
			DI::logger()->critical($admin_message, ['class' => $class]);
			$message = DI::userSession()->isSiteAdmin() ?
				$admin_message :
				DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
			throw new ServiceUnavailableException($message);
		}
	}

	/**
	 * Return template engine instance.
	 *
	 * If $name is not defined, return engine defined by theme,
	 * or default
	 *
	 * @return TemplateEngine Template Engine instance
	 * @throws ServiceUnavailableException
	 */
	public static function getTemplateEngine(): TemplateEngine
	{
		$template_engine = (self::$theme['template_engine'] ?? '') ?: 'smarty3';

		if (isset(self::$template_engines[$template_engine])) {
			if (isset(self::$template_engine_instance[$template_engine])) {
				return self::$template_engine_instance[$template_engine];
			} else {
				$appHelper = DI::appHelper();
				$class     = self::$template_engines[$template_engine];
				$obj       = new $class($appHelper->getCurrentTheme(), $appHelper->getThemeInfo());

				self::$template_engine_instance[$template_engine] = $obj;
				return $obj;
			}
		}

		$admin_message = DI::l10n()->t('template engine is not registered!');
		DI::logger()->critical($admin_message, ['template_engine' => $template_engine]);
		$message = DI::userSession()->isSiteAdmin() ?
			$admin_message :
			DI::l10n()->t('Friendica can\'t display this page at the moment, please contact the administrator.');
		throw new ServiceUnavailableException($message);
	}

	/**
	 * Returns the active template engine.
	 *
	 * @return string the active template engine
	 */
	public static function getActiveTemplateEngine(): string
	{
		return self::$theme['template_engine'];
	}

	/**
	 * sets the active template engine
	 *
	 * @param string $engine the template engine (default is Smarty3)
	 *
	 * @return void
	 */
	public static function setActiveTemplateEngine(string $engine = 'smarty3')
	{
		self::$theme['template_engine'] = $engine;
	}

	/**
	 * Gets the right delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '{{'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the right delimiter
	 */
	public static function getTemplateLeftDelimiter(string $engine = 'smarty3'): string
	{
		return self::$ldelim[$engine];
	}

	/**
	 * Gets the left delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '}}'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the left delimiter
	 */
	public static function getTemplateRightDelimiter(string $engine = 'smarty3'): string
	{
		return self::$rdelim[$engine];
	}
}
