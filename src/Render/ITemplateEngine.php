<?php
/**
 * @file src/Render/ITemplateEngine.php
 */

namespace Friendica\Render;

/**
 * Interface for template engines
 */
interface ITemplateEngine
{
	public function replaceMacros($s, $v);
	public function getTemplateFile($file, $root = '');
}
