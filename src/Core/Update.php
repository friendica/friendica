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

namespace Friendica\Core;

use Friendica\App;
use Friendica\App\Mode;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class Update
{
	const SUCCESS = 0;
	const FAILED  = 1;

	const NEW_TABLE_STRUCTURE_VERSION = 1288;

	/**
	 * Function to check if the Database structure needs an update.
	 *
	 * @param string   $basePath   The base path of this application
	 * @param boolean  $via_worker Is the check run via the worker?
	 * @param App\Mode $mode       The current app mode
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function check(string $basePath, bool $via_worker)
	{
		if (!DBA::connected()) {
			return;
		}

		// Don't check the status if the last update was failed
		if (DI::config()->get('system', 'update', Update::SUCCESS) == Update::FAILED) {
			return;
		}

		$build = DI::config()->get('system', 'build');

		if (empty($build)) {
			// legacy option - check if there's something in the Config table
			if (DBStructure::existsTable('config')) {
				$dbConfig = DBA::selectFirst('config', ['v'], ['cat' => 'system', 'k' => 'build']);
				if (!empty($dbConfig)) {
					$build = $dbConfig['v'];
				}
			}

			if (empty($build)) {
				DI::config()->set('system', 'build', DB_UPDATE_VERSION - 1);
				$build = DB_UPDATE_VERSION - 1;
			}
		}

		// We don't support upgrading from very old versions anymore
		if ($build < self::NEW_TABLE_STRUCTURE_VERSION) {
			$error = DI::l10n()->t('Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.', $build);
			if (DI::mode()->getExecutor() == Mode::INDEX) {
				die($error);
			} else {
				throw new InternalServerErrorException($error);
			}
		}

		// The postupdate has to completed version 1288 for the new post views to take over
		$postupdate = DI::keyValue()->get('post_update_version') ?? self::NEW_TABLE_STRUCTURE_VERSION;
		if ($postupdate < self::NEW_TABLE_STRUCTURE_VERSION) {
			$error = DI::l10n()->t('Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.', $postupdate);
			if (DI::mode()->getExecutor() == Mode::INDEX) {
				die($error);
			} else {
				throw new InternalServerErrorException($error);
			}
		}

		if ($build < DB_UPDATE_VERSION) {
			if ($via_worker) {
				/*
				 * Calling the database update directly via the worker enables us to perform database changes to the workerqueue table itself.
				 * This is a fallback, since normally the database update will be performed by a worker job.
				 * This worker job doesn't work for changes to the "workerqueue" table itself.
				 */
				self::run($basePath);
			} else {
				Worker::add(Worker::PRIORITY_CRITICAL, 'DBUpdate');
			}
		}
	}

	/**
	 * Automatic database updates
	 *
	 * @param string $basePath The base path of this application
	 * @param bool   $force    Force the Update-Check even if the database version doesn't match
	 * @param bool   $override Overrides any running/stuck updates
	 * @param bool   $verbose  Run the Update-Check verbose
	 * @param bool   $sendMail Sends a Mail to the administrator in case of success/failure
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function run(string $basePath, bool $force = false, bool $override = false, bool $verbose = false, bool $sendMail = true): string
	{
		// In force mode, we release the dbupdate lock first
		// Necessary in case of an stuck update
		if ($override) {
			DI::lock()->release('dbupdate', true);
		}

		if (!DBStructure::existsTable('config')) {
			DBA::e(<<<EOF
CREATE TABLE IF NOT EXISTS `config` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`cat` varbinary(50) NOT NULL DEFAULT '' COMMENT 'The category of the entry',
	`k` varbinary(50) NOT NULL DEFAULT '' COMMENT 'The key of the entry',
	`v` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `cat_k` (`cat`,`k`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='main configuration storage';
EOF
);
		}

		$build = DI::config()->get('system', 'build');

		if (empty($build)) {
			$dbConfig = DBA::selectFirst('config', ['v'], ['cat' => 'system', 'k' => 'build']);
			if (!empty($dbConfig)) {
				$build = $dbConfig['v'];
			}

			if (empty($build) || ($build > DB_UPDATE_VERSION)) {
				DI::config()->set('system', 'build', DB_UPDATE_VERSION - 1);
				$build = DB_UPDATE_VERSION - 1;
			}
		}

		if ($build != DB_UPDATE_VERSION || $force) {
			require_once 'update.php';

			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if ($stored < $current || $force) {
				DI::config()->reload();

				// Compare the current structure with the defined structure
				// If the Lock is acquired, never release it automatically to avoid double updates
				if (DI::lock()->acquire('dbupdate', 0, Cache\Enum\Duration::INFINITE)) {

					Logger::notice('Update starting.', ['from' => $stored, 'to' => $current]);

					// Checks if the build changed during Lock acquiring (so no double update occurs)
					$retryBuild = DI::config()->get('system', 'build');
					if ($retryBuild != $build) {
						// legacy option - check if there's something in the Config table
						if (DBStructure::existsTable('config')) {
							$dbConfig = DBA::selectFirst('config', ['v'], ['cat' => 'system', 'k' => 'build']);
							if (!empty($dbConfig)) {
								$retryBuild = intval($dbConfig['v']);
							}
						}

						if ($retryBuild != $build) {
							Logger::notice('Update already done.', ['from' => $build, 'retry' => $retryBuild, 'to' => $current]);
							DI::lock()->release('dbupdate');
							return '';
						}
					}

					DI::config()->set('system', 'maintenance', 1);

					// run the pre_update_nnnn functions in update.php
					for ($version = $stored + 1; $version <= $current; $version++) {
						Logger::notice('Execute pre update.', ['version' => $version]);
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: executing pre update %d',
							DateTimeFormat::utcNow() . ' ' . date('e'), $version));
						$r = self::runUpdateFunction($version, 'pre_update', $sendMail);
						if (!$r) {
							Logger::warning('Pre update failed', ['version' => $version]);
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							DI::config()->beginTransaction()
										->set('system', 'maintenance', false)
										->delete('system', 'maintenance_reason')
										->commit();
							return $r;
						} else {
							Logger::notice('Pre update executed.', ['version' => $version]);
						}
					}

					// update the structure in one call
					Logger::notice('Execute structure update');
					$retval = DBStructure::performUpdate(false, $verbose);
					if (!empty($retval)) {
						if ($sendMail) {
							self::updateFailed(
								DB_UPDATE_VERSION,
								$retval
							);
						}
						Logger::error('Update ERROR.', ['from' => $stored, 'to' => $current, 'retval' => $retval]);
						DI::config()->set('system', 'update', Update::FAILED);
						DI::lock()->release('dbupdate');
						DI::config()->beginTransaction()
									->set('system', 'maintenance', false)
									->delete('system', 'maintenance_reason')
									->commit();
						return $retval;
					} else {
						Logger::notice('Database structure update finished.', ['from' => $stored, 'to' => $current]);
					}

					// run the update_nnnn functions in update.php
					for ($version = $stored + 1; $version <= $current; $version++) {
						Logger::notice('Execute post update.', ['version' => $version]);
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: executing post update %d',
							DateTimeFormat::utcNow() . ' ' . date('e'), $version));
						$r = self::runUpdateFunction($version, 'update', $sendMail);
						if (!$r) {
							Logger::warning('Post update failed', ['version' => $version]);
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							DI::config()->beginTransaction()
										->set('system', 'maintenance', false)
										->delete('system', 'maintenance_reason')
										->commit();
							return $r;
						} else {
							DI::config()->set('system', 'build', $version);
							Logger::notice('Post update executed.', ['version' => $version]);
						}
					}

					DI::config()->set('system', 'build', $current);
					DI::config()->set('system', 'update', Update::SUCCESS);
					DI::lock()->release('dbupdate');
					DI::config()->beginTransaction()
								->set('system', 'maintenance', false)
								->delete('system', 'maintenance_reason')
								->commit();

					Logger::notice('Update success.', ['from' => $stored, 'to' => $current]);
					if ($sendMail) {
						self::updateSuccessful($stored, $current);
					}
				} else {
					Logger::warning('Update lock could not be acquired');
				}
			}
		}

		return '';
	}

	/**
	 * Executes a specific update function
	 *
	 * @param int    $version  the DB version number of the function
	 * @param string $prefix   the prefix of the function (update, pre_update)
	 * @param bool   $sendMail whether to send emails on success/failure
	 * @return bool true, if the update function worked
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function runUpdateFunction(int $version, string $prefix, bool $sendMail = true): bool
	{
		$funcname = $prefix . '_' . $version;

		Logger::notice('Update function start.', ['function' => $funcname]);

		if (function_exists($funcname)) {
			// There could be a lot of processes running or about to run.
			// We want exactly one process to run the update command.
			// So store the fact that we're taking responsibility
			// after first checking to see if somebody else already has.
			// If the update fails or times-out completely you may need to
			// delete the config entry to try again.

			if (DI::lock()->acquire('dbupdate_function', 120, Cache\Enum\Duration::INFINITE)) {

				// call the specific update
				Logger::notice('Pre update function start.', ['function' => $funcname]);
				$retval = $funcname();
				Logger::notice('Update function done.', ['function' => $funcname]);

				if ($retval) {
					if ($sendMail) {
						//send the administrator an e-mail
						self::updateFailed(
							$version,
							DI::l10n()->t('Update %s failed. See error logs.', $version)
						);
					}
					Logger::error('Update function ERROR.', ['function' => $funcname, 'retval' => $retval]);
					DI::lock()->release('dbupdate_function');
					return false;
				} else {
					DI::lock()->release('dbupdate_function');
					Logger::notice('Update function finished.', ['function' => $funcname]);
					return true;
				}
			} else {
				Logger::error('Locking failed.', ['function' => $funcname]);
				return false;
			}
		} else {
			Logger::notice('Update function skipped.', ['function' => $funcname]);
			return true;
		}
	}

	/**
	 * send the email and do what is needed to do on update fails
	 *
	 * @param int    $update_id     number of failed update
	 * @param string $error_message error message
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updateFailed(int $update_id, string $error_message)
	{
		$adminEmails = User::getAdminListForEmailing(['uid', 'language', 'email']);
		if (!$adminEmails) {
			Logger::warning('Cannot notify administrators .', ['update' => $update_id, 'message' => $error_message]);
			return;
		}

		foreach($adminEmails as $admin) {
			$l10n = DI::l10n()->withLang($admin['language'] ?: 'en');

			$preamble = Strings::deindent($l10n->t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.",
				$update_id));
			$body     = $l10n->t('The error message is\n[pre]%s[/pre]', $error_message);

			$email = DI::emailer()
				->newSystemMail()
				->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble, $body)
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			DI::emailer()->send($email);
		}

		Logger::alert('Database structure update failed.', ['error' => $error_message]);
	}

	/**
	 * Send a mail to the administrator about the successful update
	 *
	 * @param integer $from_build
	 * @param integer $to_build
	 * @return void
	 */
	private static function updateSuccessful(int $from_build, int $to_build)
	{
		foreach(User::getAdminListForEmailing(['uid', 'language', 'email']) as $admin) {
			$l10n = DI::l10n()->withLang($admin['language'] ?: 'en');

			$preamble = Strings::deindent($l10n->t('
				The friendica database was successfully updated from %s to %s.',
				$from_build, $to_build));

			$email = DI::emailer()
				->newSystemMail()
				->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble)
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			DI::emailer()->send($email);
		}

		Logger::debug('Database structure update successful.');
	}
}
