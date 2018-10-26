<?php
/**
 * @file src/Module/Hashtag.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Text;
use Friendica\Core\System;
use Friendica\Database\DBA;

require_once 'include/dba.php';
require_once 'include/text.php';

/**
 * Hashtag module.
 */
class Hashtag extends BaseModule
{

	public static function content()
	{
		$result = [];

		$t = Text::escapeTags($_REQUEST['t']);
		if (empty($t)) {
			System::jsonExit($result);
		}

		$taglist = DBA::p("SELECT DISTINCT(`term`) FROM `term` WHERE `term` LIKE ? AND `type` = ? ORDER BY `term`",
			$t . '%',
			intval(TERM_HASHTAG)
		);
		while ($tag = DBA::fetch($taglist)) {
			$result[] = ['text' => $tag['term']];
		}
		DBA::close($taglist);

		System::jsonExit($result);
	}
}
