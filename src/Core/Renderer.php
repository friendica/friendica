<?php
/**
 * @file src/Core/Renderer.php
 */

namespace Friendica\Core;

use Exception;
use Friendica\DI;
use Friendica\Render\FriendicaSmarty;
use Friendica\Render\ITemplateEngine;

/**
 * @brief This class handles Renderer related functions.
 */
class Renderer
{
	/**
	 * @brief An array of registered template engines ('name'=>'class name')
	 */
	public static $template_engines = [];

	/**
	 * @brief An array of instanced template engines ('name'=>'instance')
	 */
	public static $template_engine_instance = [];

	/**
	 * @brief An array for all theme-controllable parameters
	 *
	 * Mostly unimplemented yet. Only options 'template_engine' and
	 * beyond are used.
	 */
	public static $theme = [
		'sourcename' => '',
		'videowidth' => 425,
		'videoheight' => 350,
		'force_max_items' => 0,
		'stylesheet' => '',
		'template_engine' => 'smarty3',
	];

	private static $ldelim = [
		'internal' => '',
		'smarty3' => '{{'
	];
	private static $rdelim = [
		'internal' => '',
		'smarty3' => '}}'
	];

	/**
	 * @brief This is our template processor
	 *
	 * @param string|FriendicaSmarty $s    The string requiring macro substitution or an instance of FriendicaSmarty
	 * @param array                  $vars Key value pairs (search => replace)
	 *
	 * @return string substituted string
	 * @throws Exception
	 */
	public static function replaceMacros($s, array $vars = [])
	{
		$stamp1 = microtime(true);
		$a = DI::app();

		// pass $baseurl to all templates if it isn't set
		$vars = array_merge(['$baseurl' => $a->getBaseURL()], $vars);

		$t = self::getTemplateEngine();

		try {
			$output = $t->replaceMacros($s, $vars);
		} catch (Exception $e) {
			echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
			exit();
		}

		$a->getProfiler()->saveTimestamp($stamp1, "rendering", System::callstack());

		return $output;
	}

	/**
	 * @brief Load a given template $s
	 *
	 * @param string $s    Template to load.
	 * @param string $root Optional.
	 *
	 * @return string template.
	 * @throws Exception
	 */
	public static function getMarkupTemplate($s, $root = '')
	{
		$stamp1 = microtime(true);
		$a = DI::app();
		$t = self::getTemplateEngine();

		try {
			$template = $t->getTemplateFile($s, $root);
		} catch (Exception $e) {
			echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
			exit();
		}

		$a->getProfiler()->saveTimestamp($stamp1, "file", System::callstack());

		return $template;
	}

	/**
	 * @brief Register template engine class
	 *
	 * @param string $class
	 */
	public static function registerTemplateEngine($class)
	{
		$v = get_class_vars($class);

		if (!empty($v['name']))
		{
			$name = $v['name'];
			self::$template_engines[$name] = $class;
		} else {
			echo "template engine <tt>$class</tt> cannot be registered without a name.\n";
			die();
		}
	}

	/**
	 * @brief Return template engine instance.
	 *
	 * If $name is not defined, return engine defined by theme,
	 * or default
	 *
	 * @return ITemplateEngine Template Engine instance
	 */
	public static function getTemplateEngine()
	{
		$template_engine = (self::$theme['template_engine'] ?? '') ?: 'smarty3';

		if (isset(self::$template_engines[$template_engine])) {
			if (isset(self::$template_engine_instance[$template_engine])) {
				return self::$template_engine_instance[$template_engine];
			} else {
				$class = self::$template_engines[$template_engine];
				$obj = new $class;
				self::$template_engine_instance[$template_engine] = $obj;
				return $obj;
			}
		}

		echo "template engine <tt>$template_engine</tt> is not registered!\n";
		exit();
	}

	/**
	 * @brief Returns the active template engine.
	 *
	 * @return string the active template engine
	 */
	public static function getActiveTemplateEngine()
	{
		return self::$theme['template_engine'];
	}

	/**
	 * sets the active template engine
	 *
	 * @param string $engine the template engine (default is Smarty3)
	 */
	public static function setActiveTemplateEngine($engine = 'smarty3')
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
	public static function getTemplateLeftDelimiter($engine = 'smarty3')
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
	public static function getTemplateRightDelimiter($engine = 'smarty3')
	{
		return self::$rdelim[$engine];
	}
}
