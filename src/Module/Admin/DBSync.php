<?php

namespace Friendica\Module\Admin;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Update;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;

class DBSync extends BaseAdminModule
{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$o = '';

		if ($a->argc > 3 && $a->argv[2] === 'mark') {
			// @TODO: Replace with parameter from router
			$update = intval($a->argv[3]);
			if ($update) {
				Config::set('database', 'update_' . $update, 'success');
				$curr = Config::get('system', 'build');
				if (intval($curr) == $update) {
					Config::set('system', 'build', intval($curr) + 1);
				}
				info(L10n::t('Update has been marked successful') . EOL);
			}
			DI::baseUrl()->redirect('admin/dbsync');
		}

		if ($a->argc > 2) {
			if ($a->argv[2] === 'check') {
				// @TODO Seems like a similar logic like Update::check()
				$retval = DBStructure::update($a->getBasePath(), false, true);
				if ($retval === '') {
					$o .= L10n::t("Database structure update %s was successfully applied.", DB_UPDATE_VERSION) . "<br />";
					Config::set('database', 'last_successful_update', DB_UPDATE_VERSION);
					Config::set('database', 'last_successful_update_time', time());
				} else {
					$o .= L10n::t("Executing of database structure update %s failed with error: %s", DB_UPDATE_VERSION, $retval) . "<br />";
				}
				if ($a->argv[2] === 'check') {
					return $o;
				}
			} elseif (intval($a->argv[2])) {
				require_once 'update.php';

				// @TODO: Replace with parameter from router
				$update = intval($a->argv[2]);

				$func = 'update_' . $update;

				if (function_exists($func)) {
					$retval = $func();

					if ($retval === Update::FAILED) {
						$o .= L10n::t("Executing %s failed with error: %s", $func, $retval);
					} elseif ($retval === Update::SUCCESS) {
						$o .= L10n::t('Update %s was successfully applied.', $func);
						Config::set('database', $func, 'success');
					} else {
						$o .= L10n::t('Update %s did not return a status. Unknown if it succeeded.', $func);
					}
				} else {
					$o .= L10n::t('There was no additional update function %s that needed to be called.', $func) . "<br />";
					Config::set('database', $func, 'success');
				}

				return $o;
			}
		}

		$failed = [];
		$configStmt = DBA::select('config', ['k', 'v'], ['cat' => 'database']);
		while ($config = DBA::fetch($configStmt)) {
			$upd = intval(substr($config['k'], 7));
			if ($upd >= 1139 && $config['v'] != 'success') {
				$failed[] = $upd;
			}
		}

		if (!count($failed)) {
			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/dbsync/structure_check.tpl'), [
				'$base' => DI::baseUrl()->get(true),
				'$banner' => L10n::t('No failed updates.'),
				'$check' => L10n::t('Check database structure'),
			]);
		} else {
			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/dbsync/failed_updates.tpl'), [
				'$base' => DI::baseUrl()->get(true),
				'$banner' => L10n::t('Failed Updates'),
				'$desc' => L10n::t('This does not include updates prior to 1139, which did not return a status.'),
				'$mark' => L10n::t("Mark success \x28if update was manually applied\x29"),
				'$apply' => L10n::t('Attempt to execute this update step automatically'),
				'$failed' => $failed
			]);
		}

		return $o;
	}
}
