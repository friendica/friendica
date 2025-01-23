<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	 * Returns the status of the current update
	 *
	 * @return int
	 */
	public static function getStatus(): int
	{
		return (int)DI::config()->get('system', 'update') ?? static::SUCCESS;
	}

	/**
	 * Returns the latest Version of the Friendica git repository and null, if this node doesn't check updates automatically
	 *
	 * @return string
	 */
	public static function getAvailableVersion(): ?string
	{
		return DI::keyValue()->get('git_friendica_version') ?? null;
	}

	/**
	 * Returns true, if there's a new update and null if this node doesn't check updates automatically
	 *
	 * @return bool|null
	 */
	public static function isAvailable(): ?bool
	{
		if (DI::config()->get('system', 'check_new_version_url', 'none') != 'none') {
			if (version_compare(App::VERSION, static::getAvailableVersion()) < 0) {
				return true;
			} else {
				return false;
			}
		}

		return null;
	}

	/**
	 * Function to check if the Database structure needs an update.
	 *
	 * @param string   $basePath   The base path of this application
	 * @param boolean  $via_worker Is the check run via the worker?
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

			$stored  = intval($build);
			$current = intval(DB_UPDATE_VERSION);
			if ($stored < $current || $force) {
				DI::config()->reload();

				// Compare the current structure with the defined structure
				// If the Lock is acquired, never release it automatically to avoid double updates
				if (DI::lock()->acquire('dbupdate', 0, Cache\Enum\Duration::INFINITE)) {

					DI::logger()->notice('Update starting.', ['from' => $stored, 'to' => $current]);

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
							DI::logger()->notice('Update already done.', ['from' => $build, 'retry' => $retryBuild, 'to' => $current]);
							DI::lock()->release('dbupdate');
							return '';
						}
					}

					DI::config()->set('system', 'maintenance', 1);

					// run the pre_update_nnnn functions in update.php
					for ($version = $stored + 1; $version <= $current; $version++) {
						DI::logger()->notice('Execute pre update.', ['version' => $version]);
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t(
							'%s: executing pre update %d',
							DateTimeFormat::utcNow() . ' ' . date('e'),
							$version
						));
						$r = self::runUpdateFunction($version, 'pre_update', $sendMail);
						if (!$r) {
							DI::logger()->warning('Pre update failed', ['version' => $version]);
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							DI::config()->beginTransaction()
										->set('system', 'maintenance', false)
										->delete('system', 'maintenance_reason')
										->commit();
							return $r;
						} else {
							DI::logger()->notice('Pre update executed.', ['version' => $version]);
						}
					}

					// update the structure in one call
					DI::logger()->notice('Execute structure update');
					$retval = DBStructure::performUpdate(false, $verbose);
					if (!empty($retval)) {
						if ($sendMail) {
							self::updateFailed(
								DB_UPDATE_VERSION,
								$retval
							);
						}
						DI::logger()->error('Update ERROR.', ['from' => $stored, 'to' => $current, 'retval' => $retval]);
						DI::config()->set('system', 'update', Update::FAILED);
						DI::lock()->release('dbupdate');
						DI::config()->beginTransaction()
									->set('system', 'maintenance', false)
									->delete('system', 'maintenance_reason')
									->commit();
						return $retval;
					} else {
						DI::logger()->notice('Database structure update finished.', ['from' => $stored, 'to' => $current]);
					}

					// run the update_nnnn functions in update.php
					for ($version = $stored + 1; $version <= $current; $version++) {
						DI::logger()->notice('Execute post update.', ['version' => $version]);
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t(
							'%s: executing post update %d',
							DateTimeFormat::utcNow() . ' ' . date('e'),
							$version
						));
						$r = self::runUpdateFunction($version, 'update', $sendMail);
						if (!$r) {
							DI::logger()->warning('Post update failed', ['version' => $version]);
							DI::config()->set('system', 'update', Update::FAILED);
							DI::lock()->release('dbupdate');
							DI::config()->beginTransaction()
										->set('system', 'maintenance', false)
										->delete('system', 'maintenance_reason')
										->commit();
							return $r;
						} else {
							DI::config()->set('system', 'build', $version);
							DI::logger()->notice('Post update executed.', ['version' => $version]);
						}
					}

					DI::config()->set('system', 'build', $current);
					DI::config()->set('system', 'update', Update::SUCCESS);
					DI::lock()->release('dbupdate');
					DI::config()->beginTransaction()
								->set('system', 'maintenance', false)
								->delete('system', 'maintenance_reason')
								->commit();

					DI::logger()->notice('Update success.', ['from' => $stored, 'to' => $current]);
					if ($sendMail) {
						self::updateSuccessful($stored, $current);
					}
				} else {
					DI::logger()->warning('Update lock could not be acquired');
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

		DI::logger()->notice('Update function start.', ['function' => $funcname]);

		if (function_exists($funcname)) {
			// There could be a lot of processes running or about to run.
			// We want exactly one process to run the update command.
			// So store the fact that we're taking responsibility
			// after first checking to see if somebody else already has.
			// If the update fails or times-out completely you may need to
			// delete the config entry to try again.

			if (DI::lock()->acquire('dbupdate_function', 120, Cache\Enum\Duration::INFINITE)) {

				// call the specific update
				DI::logger()->notice('Pre update function start.', ['function' => $funcname]);
				$retval = $funcname();
				DI::logger()->notice('Update function done.', ['function' => $funcname]);

				if ($retval) {
					if ($sendMail) {
						//send the administrator an e-mail
						self::updateFailed(
							$version,
							DI::l10n()->t('Update %s failed. See error logs.', $version)
						);
					}
					DI::logger()->error('Update function ERROR.', ['function' => $funcname, 'retval' => $retval]);
					DI::lock()->release('dbupdate_function');
					return false;
				} else {
					DI::lock()->release('dbupdate_function');
					DI::logger()->notice('Update function finished.', ['function' => $funcname]);
					return true;
				}
			} else {
				DI::logger()->error('Locking failed.', ['function' => $funcname]);
				return false;
			}
		} else {
			DI::logger()->notice('Update function skipped.', ['function' => $funcname]);
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
			DI::logger()->warning('Cannot notify administrators .', ['update' => $update_id, 'message' => $error_message]);
			return;
		}

		foreach($adminEmails as $admin) {
			$l10n = DI::l10n()->withLang($admin['language'] ?: 'en');

			$preamble = Strings::deindent($l10n->t(
				"
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.",
				$update_id
			));
			$body = $l10n->t('The error message is\n[pre]%s[/pre]', $error_message);

			$email = DI::emailer()
				->newSystemMail()
				->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble, $body)
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			DI::emailer()->send($email);
		}

		DI::logger()->alert('Database structure update failed.', ['error' => $error_message]);
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

			$preamble = Strings::deindent($l10n->t(
				'
				The friendica database was successfully updated from %s to %s.',
				$from_build,
				$to_build
			));

			$email = DI::emailer()
				->newSystemMail()
				->withMessage($l10n->t('[Friendica Notify] Database update'), $preamble)
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			DI::emailer()->send($email);
		}

		DI::logger()->debug('Database structure update successful.');
	}
}
