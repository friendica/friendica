<?php
/**
 * @file src/Worker/GProbe.php
 */

namespace Friendica\Worker;

use Friendica\Content\Text;
use Friendica\Core\Cache;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\GContact;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;

class GProbe {
	public static function execute($url = '')
	{
		if (empty($url)) {
			return;
		}

		$r = q(
			"SELECT `id`, `url`, `network` FROM `gcontact` WHERE `nurl` = '%s' ORDER BY `id` LIMIT 1",
			DBA::escape(normalise_link($url))
		);

		Text::logger("gprobe start for ".normalise_link($url), LOGGER_DEBUG);

		if (!DBA::isResult($r)) {
			// Is it a DDoS attempt?
			$urlparts = parse_url($url);

			$result = Cache::get("gprobe:".$urlparts["host"]);
			if (!is_null($result)) {
				if (in_array($result["network"], [Protocol::FEED, Protocol::PHANTOM])) {
					Text::logger("DDoS attempt detected for ".$urlparts["host"]." by ".$_SERVER["REMOTE_ADDR"].". server data: ".print_r($_SERVER, true), LOGGER_DEBUG);
					return;
				}
			}

			$arr = Probe::uri($url);

			if (is_null($result)) {
				Cache::set("gprobe:".$urlparts["host"], $arr);
			}

			if (!in_array($arr["network"], [Protocol::FEED, Protocol::PHANTOM])) {
				GContact::update($arr);
			}

			$r = q(
				"SELECT `id`, `url`, `network` FROM `gcontact` WHERE `nurl` = '%s' ORDER BY `id` LIMIT 1",
				DBA::escape(normalise_link($url))
			);
		}
		if (DBA::isResult($r)) {
			// Check for accessibility and do a poco discovery
			if (PortableContact::lastUpdated($r[0]['url'], true) && ($r[0]["network"] == Protocol::DFRN)) {
				PortableContact::loadWorker(0, 0, $r[0]['id'], str_replace('/profile/', '/poco/', $r[0]['url']));
			}
		}

		Text::logger("gprobe end for ".normalise_link($url), LOGGER_DEBUG);
		return;
	}
}
