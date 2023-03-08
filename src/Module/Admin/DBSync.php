<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Admin;

use Friendica\Core\Renderer;
use Friendica\Core\Update;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class DBSync extends BaseAdmin
{
	protected function content(array $request = []): string
	{
		parent::content();

		$a = DI::app();

		$action = $this->parameters['action'] ?? '';
		$update = $this->parameters['update'] ?? 0;

		switch ($action) {
			case 'mark':
				if ($update) {
					DI::keyValue()->set('database_update_' . $update, 'success');
					$curr = DI::config()->get('system', 'build');
					if (intval($curr) == $update) {
						DI::config()->set('system', 'build', intval($curr) + 1);
					}

					DI::sysmsg()->addInfo(DI::l10n()->t('Update has been marked successful'));
				}

				break;
			case 'check':
				// @TODO Seems like a similar logic like Update::check()
				$retval = DBStructure::performUpdate();
				if ($retval === '') {
					$o = DI::l10n()->t("Database structure update %s was successfully applied.", DB_UPDATE_VERSION) . "<br />";
				} else {
					$o = DI::l10n()->t("Executing of database structure update %s failed with error: %s", DB_UPDATE_VERSION, $retval) . "<br />";
				}

				return $o;
			case 'update':
				require_once 'update.php';

				// @TODO: Replace with parameter from router
				if ($update) {
					$func = 'update_' . $update;

					if (function_exists($func)) {
						$retval = $func();

						if ($retval === Update::FAILED) {
							$o = DI::l10n()->t("Executing %s failed with error: %s", $func, $retval);
						} elseif ($retval === Update::SUCCESS) {
							$o = DI::l10n()->t('Update %s was successfully applied.', $func);
							DI::keyValue()->set(sprintf('database_%s', $func), 'success');
						} else {
							$o = DI::l10n()->t('Update %s did not return a status. Unknown if it succeeded.', $func);
						}
					} else {
						$o = DI::l10n()->t('There was no additional update function %s that needed to be called.', $func) . "<br />";
						DI::keyValue()->set(sprintf('database_%s', $func), 'success');
					}

					return $o;
				}

				break;
			default:
				$failed = [];
				$configStmt = DBA::select('config', ['k', 'v'], ['cat' => 'database']);
				while ($config = DBA::fetch($configStmt)) {
					$upd = intval(substr($config['k'], 7));
					if ($upd >= 1139 && $config['v'] != 'success') {
						$failed[] = $upd;
					}
				}
				DBA::close($configStmt);

				if (!count($failed)) {
					$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/dbsync/structure_check.tpl'), [
						'$banner' => DI::l10n()->t('No failed updates.'),
						'$check' => DI::l10n()->t('Check database structure'),
					]);
				} else {
					$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('admin/dbsync/failed_updates.tpl'), [
						'$banner' => DI::l10n()->t('Failed Updates'),
						'$desc' => DI::l10n()->t('This does not include updates prior to 1139, which did not return a status.'),
						'$mark' => DI::l10n()->t("Mark success \x28if update was manually applied\x29"),
						'$apply' => DI::l10n()->t('Attempt to execute this update step automatically'),
						'$failed' => $failed
					]);
				}

				return $o;
		}

		DI::baseUrl()->redirect('admin/dbsync');
		return '';
	}
}
