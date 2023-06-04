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

namespace Friendica\Module\User;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Protocol\Delivery;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Import extends \Friendica\BaseModule
{
	const IMPORT_DEBUG = false;

	/** @var App */
	private $app;

	/** @var IManageConfigValues */
	private $config;

	/** @var IManagePersonalConfigValues */
	private $pconfig;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var Database */
	private $database;

	/** @var PermissionSet */
	private $permissionSet;

	public function __construct(PermissionSet $permissionSet, IManagePersonalConfigValues $pconfig, Database $database, SystemMessages $systemMessages, IManageConfigValues $config, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app            = $app;
		$this->config         = $config;
		$this->pconfig        = $pconfig;
		$this->systemMessages = $systemMessages;
		$this->database       = $database;
		$this->permissionSet  = $permissionSet;
	}

	protected function post(array $request = [])
	{
		if ($this->config->get('config', 'register_policy') != \Friendica\Module\Register::OPEN && !$this->app->isSiteAdmin()) {
			throw new HttpException\ForbiddenException($this->t('Permission denied.'));
		}

		$max_dailies = intval($this->config->get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$total = $this->database->count('user', ["`register_date` > UTC_TIMESTAMP - INTERVAL 1 DAY"]);
			if ($total >= $max_dailies) {
				throw new HttpException\ForbiddenException($this->t('Permission denied.'));
			}
		}

		if (!empty($_FILES['accountfile'])) {
			$this->importAccount($_FILES['accountfile']);
		}
	}

	protected function content(array $request = []): string
	{
		if (($this->config->get('config', 'register_policy') != \Friendica\Module\Register::OPEN) && !$this->app->isSiteAdmin()) {
			$this->systemMessages->addNotice($this->t('User imports on closed servers can only be done by an administrator.'));
		}

		$max_dailies = intval($this->config->get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$total = $this->database->count('user', ["`register_date` > UTC_TIMESTAMP - INTERVAL 1 DAY"]);
			if ($total >= $max_dailies) {
				$this->logger->notice('max daily registrations exceeded.');
				$this->systemMessages->addNotice($this->t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'));
			}
		}

		$tpl = Renderer::getMarkupTemplate('user/import.tpl');
		return Renderer::replaceMacros($tpl, [
			'$regbutt' => $this->t('Import'),
			'$import'  => [
				'title'    => $this->t('Move account'),
				'intro'    => $this->t('You can import an account from another Friendica server.'),
				'instruct' => $this->t('You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'),
				'warn'     => $this->t("This feature is experimental. We can't import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora"),
				'field'    => ['accountfile', $this->t('Account file'), '<input id="id_accountfile" name="accountfile" type="file">', $this->t('To export your account, go to "Settings->Export your personal data" and select "Export account"')],
			],
		]);
	}

	private function lastInsertId(): int
	{
		if (self::IMPORT_DEBUG) {
			return 1;
		}

		return $this->database->lastInsertId();
	}

	/**
	 * Remove columns from array $arr that aren't in table $table
	 *
	 * @param string $table Table name
	 * @param array &$arr   Column=>Value array from json (by ref)
	 * @throws \Exception
	 */
	private function checkCols(string $table, array &$arr)
	{
		$tableColumns = DBStructure::getColumns($table);

		$tcols = [];
		$ttype = [];
		// get a plain array of column names
		foreach ($tableColumns as $tcol) {
			$tcols[]               = $tcol['Field'];
			$ttype[$tcol['Field']] = $tcol['Type'];
		}

		// remove inexistent columns
		foreach ($arr as $icol => $ival) {
			if (!in_array($icol, $tcols)) {
				unset($arr[$icol]);
				continue;
			}

			if ($ttype[$icol] === 'datetime') {
				$arr[$icol] = $ival ?? DBA::NULL_DATETIME;
			}
		}
	}

	/**
	 * Import data into table $table
	 *
	 * @param string $table Table name
	 * @param array  $arr   Column=>Value array from json
	 * @return bool
	 * @throws \Exception
	 */
	private function dbImportAssoc(string $table, array $arr): bool
	{
		if (isset($arr['id'])) {
			unset($arr['id']);
		}

		$this->checkCols($table, $arr);

		if (self::IMPORT_DEBUG) {
			return true;
		}

		return $this->database->insert($table, $arr);
	}

	/**
	 * Import account file exported from mod/uexport
	 *
	 * @param array $file array from $_FILES
	 * @return void
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 * @throws \ImagickException
	 */
	private function importAccount(array $file)
	{
		$this->logger->notice('Start user import from ' . $file['tmp_name']);
		/*
		STEPS
		1. checks
		2. replace old baseurl with new baseurl
		3. import data (look at user id and contacts id)
		4. archive non-dfrn contacts
		5. send message to dfrn contacts
		*/

		$account = json_decode(file_get_contents($file['tmp_name']), true);
		if ($account === null) {
			$this->systemMessages->addNotice($this->t('Error decoding account file'));
			return;
		}

		if (empty($account['version'])) {
			$this->systemMessages->addNotice($this->t('Error! No version data in file! This is not a Friendica account file?'));
			return;
		}

		// check for username
		// check if username matches deleted account
		if ($this->database->exists('user', ['nickname' => $account['user']['nickname']])
			|| $this->database->exists('userd', ['username' => $account['user']['nickname']])) {
			$this->systemMessages->addNotice($this->t("User '%s' already exists on this server!", $account['user']['nickname']));
			return;
		}

		// Backward compatibility
		$account['circle'] = $account['circle'] ?? $account['group'];
		$account['circle_member'] = $account['circle_member'] ?? $account['group_member'];

		$oldBaseUrl = $account['baseurl'];
		$newBaseUrl = (string)$this->baseUrl;

		$oldAddr = str_replace('http://', '@', Strings::normaliseLink($oldBaseUrl));
		$newAddr = str_replace('http://', '@', Strings::normaliseLink($newBaseUrl));

		if (!empty($account['profile']['addr'])) {
			$oldHandle = $account['profile']['addr'];
		} else {
			$oldHandle = $account['user']['nickname'] . $oldAddr;
		}

		// Creating a new guid to avoid problems with Diaspora
		$account['user']['guid'] = System::createUUID();

		$oldUid = $account['user']['uid'];

		unset($account['user']['uid']);
		unset($account['user']['account_expired']);
		unset($account['user']['account_expires_on']);
		unset($account['user']['expire_notification_sent']);

		array_walk($account['user'], function (&$user) use ($oldBaseUrl, $oldAddr, $newBaseUrl, $newAddr) {
			$user = str_replace([$oldBaseUrl, $oldAddr], [$newBaseUrl, $newAddr], $user);
		});

		// import user
		if ($this->dbImportAssoc('user', $account['user']) === false) {
			$this->logger->warning('Error inserting user', ['user' => $account['user'], 'error' => $this->database->errorMessage()]);
			$this->systemMessages->addNotice($this->t('User creation error'));
			return;
		}

		$newUid = $this->lastInsertId();

		$this->pconfig->set($newUid, 'system', 'previous_addr', $oldHandle);

		$errorCount = 0;

		array_walk($account['contact'], function (&$contact) use (&$errorCount, $oldUid, $oldBaseUrl, $oldAddr, $newUid, $newBaseUrl, $newAddr) {
			if ($contact['uid'] == $oldUid && $contact['self'] == '1') {
				array_walk($contact, function (&$field) use ($oldUid, $oldBaseUrl, $oldAddr, $newUid, $newBaseUrl, $newAddr) {
					$field = str_replace([$oldBaseUrl, $oldAddr], [$newBaseUrl, $newAddr], $field);
					foreach (['profile', 'avatar', 'micro'] as $key) {
						$field = str_replace($oldBaseUrl . '/photo/' . $key . '/' . $oldUid . '.jpg', $newBaseUrl . '/photo/' . $key . '/' . $newUid . '.jpg', $field);
					}
				});
			}

			if ($contact['uid'] == $oldUid && $contact['self'] == '0') {
				// set contacts 'avatar-date' to NULL_DATE to let worker update the URLs
				$contact['avatar-date'] = DBA::NULL_DATETIME;

				switch ($contact['network']) {
					case Protocol::DFRN:
					case Protocol::DIASPORA:
						//  send relocate message (below)
						break;
					case Protocol::FEED:
					case Protocol::MAIL:
						// Nothing to do
						break;
					default:
						// archive other contacts
						$contact['archive'] = '1';
				}
			}

			$contact['uid'] = $newUid;
			if ($this->dbImportAssoc('contact', $contact) === false) {
				$this->logger->warning('Error inserting contact', ['nick' => $contact['nick'], 'network' => $contact['network'], 'error' => $this->database->errorMessage()]);
				$errorCount++;
			} else {
				$contact['newid'] = $this->lastInsertId();
			}
		});

		if ($errorCount > 0) {
			$this->systemMessages->addNotice($this->tt('%d contact not imported', '%d contacts not imported', $errorCount));
		}

		array_walk($account['circle'], function (&$circle) use ($newUid) {
			$circle['uid'] = $newUid;
			if ($this->dbImportAssoc('group', $circle) === false) {
				$this->logger->warning('Error inserting circle', ['name' => $circle['name'], 'error' => $this->database->errorMessage()]);
			} else {
				$circle['newid'] = $this->lastInsertId();
			}
		});

		foreach ($account['circle_member'] as $circle_member) {
			$import = 0;
			foreach ($account['circle'] as $circle) {
				if ($circle['id'] == $circle_member['gid'] && isset($circle['newid'])) {
					$circle_member['gid'] = $circle['newid'];
					$import++;
					break;
				}
			}

			foreach ($account['contact'] as $contact) {
				if ($contact['id'] == $circle_member['contact-id'] && isset($contact['newid'])) {
					$circle_member['contact-id'] = $contact['newid'];
					$import++;
					break;
				}
			}

			if ($import == 2 && $this->dbImportAssoc('group_member', $circle_member) === false) {
				$this->logger->warning('Error inserting circle member', ['gid' => $circle_member['id'], 'error' => $this->database->errorMessage()]);
			}
		}

		foreach ($account['profile'] as $profile) {
			unset($profile['id']);
			$profile['uid'] = $newUid;

			array_walk($profile, function (&$field) use ($oldUid, $oldBaseUrl, $oldAddr, $newUid, $newBaseUrl, $newAddr) {
				$field = str_replace([$oldBaseUrl, $oldAddr], [$newBaseUrl, $newAddr], $field);
				foreach (['profile', 'avatar'] as $key) {
					$field = str_replace($oldBaseUrl . '/photo/' . $key . '/' . $oldUid . '.jpg', $newBaseUrl . '/photo/' . $key . '/' . $newUid . '.jpg', $field);
				}
			});

			if (count($account['profile']) === 1 || $profile['is-default']) {
				if ($this->dbImportAssoc('profile', $profile) === false) {
					$this->logger->warning('Error inserting profile', ['error' => $this->database->errorMessage()]);
					$this->systemMessages->addNotice($this->t('User profile creation error'));
					$this->database->delete('user', ['uid' => $newUid]);
					$this->database->delete('profile_field', ['uid' => $newUid]);
					return;
				}

				$profile['id'] = $this->database->lastInsertId();
			}

			Profile::migrate($profile);
		}

		$permissionSet = $this->permissionSet->selectDefaultForUser($newUid);

		foreach ($account['profile_fields'] ?? [] as $profile_field) {
			$profile_field['uid'] = $newUid;

			///@TODO Replace with permissionset import
			$profile_field['psid'] = $profile_field['psid'] ? $permissionSet->id : PermissionSet::PUBLIC;

			if ($this->dbImportAssoc('profile_field', $profile_field) === false) {
				$this->logger->info('Error inserting profile field', ['profile_id' => $profile_field['id'], 'error' => $this->database->errorMessage()]);
			}
		}

		foreach ($account['photo'] as $photo) {
			$photo['uid']  = $newUid;
			$photo['data'] = hex2bin($photo['data']);

			$r = Photo::store(
				new Image($photo['data'], $photo['type']),
				$photo['uid'], $photo['contact-id'], //0
				$photo['resource-id'], $photo['filename'], $photo['album'], $photo['scale'], $photo['profile'], //1
				$photo['allow_cid'], $photo['allow_gid'], $photo['deny_cid'], $photo['deny_gid']
			);

			if ($r === false) {
				$this->logger->warning('Error inserting photo', ['resource-id' => $photo['resource-id'], 'scale' => $photo['scale'], 'error' => $this->database->errorMessage()]);
			}
		}

		foreach ($account['pconfig'] as $pconfig) {
			$pconfig['uid'] = $newUid;
			if ($this->dbImportAssoc('pconfig', $pconfig) === false) {
				$this->logger->warning('Error inserting pconfig', ['pconfig_id' => $pconfig['id'], 'error' => $this->database->errorMessage()]);
			}
		}

		// send relocate messages
		Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $newUid);

		$this->systemMessages->addInfo($this->t('Done. You can now login with your username and password'));
		$this->baseUrl->redirect('login');
	}
}
