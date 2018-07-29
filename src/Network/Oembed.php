<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content;

/**
 * Oembed module
 *
 * Displays stored embed content based on a base64 hash of a remote URL
 *
 * Example: /oembed/aHR0cHM6Ly9...
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Oembed extends BaseModule
{
	public static function content()
	{
		$a = self::getApp();

		// Unused form: /oembed/b2h?url=...
		if ($a->argv[1] == 'b2h') {
			$url = ["", trim(hex2bin($_REQUEST['url']))];
			echo Content\OEmbed::replaceCallback($url);
			killme();
		}

		// Unused form: /oembed/h2b?text=...
		if ($a->argv[1] == 'h2b') {
			$text = trim(hex2bin($_REQUEST['text']));
			echo Content\OEmbed::HTML2BBCode($text);
			killme();
		}

		if ($a->argc == 2) {
			echo '<html><body>';
			$url = base64url_decode($a->argv[1]);
			$j = Content\OEmbed::fetchURL($url);

			// workaround for media.ccc.de (and any other endpoint that return size 0)
			if (substr($j->html, 0, 7) == "<iframe" && strstr($j->html, 'width="0"')) {
				$j->html = '<style>html,body{margin:0;padding:0;} iframe{width:100%;height:100%;}</style>' . $j->html;
				$j->html = str_replace('width="0"', '', $j->html);
				$j->html = str_replace('height="0"', '', $j->html);
			}
			echo $j->html;
			echo '</body></html>';
		}
		killme();
	}
}
