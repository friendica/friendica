<?php
/**
 * @file src/Render/FriendicaSmartyEngine.php
 */
namespace Friendica\Render;

use Friendica\Core\Hook;
use Friendica\Registry\DI;

/**
 * Smarty implementation of the Friendica template engine interface
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class FriendicaSmartyEngine implements ITemplateEngine
{
	static $name = "smarty3";

	public function __construct()
	{
		if (!is_writable(__DIR__ . '/../../view/smarty3/')) {
			echo "<b>ERROR:</b> folder <tt>view/smarty3/</tt> must be writable by webserver.";
			exit();
		}
	}

	// ITemplateEngine interface
	public function replaceMacros($s, $r)
	{
		$template = '';
		if (gettype($s) === 'string') {
			$template = $s;
			$s = new FriendicaSmarty();
		}

		$r['$APP'] = DI::app();

		// "middleware": inject variables into templates
		$arr = [
			"template" => basename($s->filename),
			"vars" => $r
		];
		Hook::callAll("template_vars", $arr);
		$r = $arr['vars'];

		foreach ($r as $key => $value) {
			if ($key[0] === '$') {
				$key = substr($key, 1);
			}

			$s->assign($key, $value);
		}
		return $s->parsed($template);
	}

	public function getTemplateFile($file, $root = '')
	{
		$a = DI::app();
		$template = new FriendicaSmarty();

		// Make sure $root ends with a slash /
		if ($root !== '' && substr($root, -1, 1) !== '/') {
			$root = $root . '/';
		}

		$theme = $a->getCurrentTheme();
		$filename = $template::SMARTY3_TEMPLATE_FOLDER . '/' . $file;

		if (file_exists("{$root}view/theme/$theme/$filename")) {
			$template_file = "{$root}view/theme/$theme/$filename";
		} elseif (!empty($a->theme_info['extends']) && file_exists(sprintf('%sview/theme/%s}/%s', $root, $a->theme_info['extends'], $filename))) {
			$template_file = sprintf('%sview/theme/%s}/%s', $root, $a->theme_info['extends'], $filename);
		} elseif (file_exists("{$root}/$filename")) {
			$template_file = "{$root}/$filename";
		} else {
			$template_file = "{$root}view/$filename";
		}

		$template->filename = $template_file;

		return $template;
	}
}
