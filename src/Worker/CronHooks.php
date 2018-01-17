<?php
/**
 * @file src/Worker/CronHooks.php
 */
namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;

Class CronHooks {
	public static function execute($hook = '') {
		global $a;

		require_once 'include/datetime.php';

		if (($hook != '') && is_array($a->hooks) && array_key_exists("cron", $a->hooks)) {
			foreach ($a->hooks["cron"] as $single_hook) {
				if ($single_hook[1] == $hook) {
					logger("Calling cron hook '" . $hook . "'", LOGGER_DEBUG);
					Addon::callSingleHook($a, $hook, $single_hook);
				}
			}
			return;
		}

		$last = Config::get('system', 'last_cronhook');

		$poll_interval = intval(Config::get('system', 'cronhook_interval'));
		if (!$poll_interval) {
			$poll_interval = 9;
		}

		if ($last) {
			$next = $last + ($poll_interval * 60);
			if ($next > time()) {
				logger('cronhook intervall not reached');
				return;
			}
		}

		$a->set_baseurl(Config::get('system', 'url'));

		logger('cronhooks: start');

		$d = datetime_convert();

		if (is_array($a->hooks) && array_key_exists("cron", $a->hooks)) {
			foreach ($a->hooks["cron"] as $hook) {
				logger("Calling cronhooks for '" . $hook[1] . "'", LOGGER_DEBUG);
				Worker::add(PRIORITY_MEDIUM, "CronHooks", $hook[1]);
			}
		}

		logger('cronhooks: end');

		Config::set('system', 'last_cronhook', time());

		return;
	}
}
