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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Module to export user data
 **/
class UserExport extends BaseSettings
{
	/** @var DbaDefinition */
	private $dbaDefinition;

	public function __construct(DbaDefinition $dbaDefinition, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dbaDefinition = $dbaDefinition;
	}

	/**
	 * Handle the request to export data.
	 * At the moment one can export three different data set
	 * 1. The profile data that can be used by uimport to resettle
	 *    to a different Friendica instance
	 * 2. The entire data-set, profile plus postings
	 * 3. A list of contacts as CSV file similar to the export of Mastodon
	 *
	 * If there is an action required through the URL / path, react
	 * accordingly and export the requested data.
	 *
	 * @param array $request
	 * @return string
	 * @throws ForbiddenException
	 * @throws InternalServerErrorException
	 * @throws ServiceUnavailableException
	 */
	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		parent::content();

		/**
		 * options shown on "Export personal data" page
		 * list of array( 'link url', 'link text', 'help text' )
		 */
		$options = [
			['settings/userexport/account', $this->l10n->t('Export account'), $this->l10n->t('Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.')],
			['settings/userexport/backup', $this->l10n->t('Export all'), $this->l10n->t('Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)')],
			['settings/userexport/contact', $this->l10n->t('Export Contacts to CSV'), $this->l10n->t('Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.')],
		];
		Hook::callAll('uexport_options', $options);

		$tpl = Renderer::getMarkupTemplate('settings/userexport.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title' => $this->l10n->t('Export personal data'),
			'$options' => $options
		]);
	}

	/**
	 * raw content generated for the different choices made
	 * by the user. At the moment this returns a JSON file
	 * to the browser which then offers a save / open dialog
	 * to the user.
	 *
	 * @throws HTTPException\ForbiddenException
	 */
	protected function rawContent(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		if (isset($this->parameters['action'])) {
			switch ($this->parameters['action']) {
				case 'backup':
					header('Content-type: application/json');
					header('Content-Disposition: attachment; filename="' . DI::app()->getLoggedInUserNickname() . '.' . $this->parameters['action'] . '"');
					$this->echoAll($this->session->getLocalUserId());
					break;
				case 'account':
					header('Content-type: application/json');
					header('Content-Disposition: attachment; filename="' . DI::app()->getLoggedInUserNickname() . '.' . $this->parameters['action'] . '"');
					$this->echoAccount($this->session->getLocalUserId());
					break;
				case 'contact':
					header('Content-type: application/csv');
					header('Content-Disposition: attachment; filename="' . DI::app()->getLoggedInUserNickname() . '-contacts.csv' . '"');
					$this->echoContactsAsCSV($this->session->getLocalUserId());
					break;
			}
			System::exit();
		}
	}

	/**
	 * @param string $query
	 * @return array
	 * @throws \Exception
	 */
	private function exportMultiRow(string $query): array
	{
		$dbStructure = $this->dbaDefinition->getAll();

		preg_match('/\s+from\s+`?([a-z\d_]+)`?/i', $query, $match);
		$table = $match[1];

		$result = [];
		$rows = DBA::p($query);
		while ($row = DBA::fetch($rows)) {
			$p = [];
			foreach ($dbStructure[$table]['fields'] as $column => $field) {
				if (!isset($row[$column])) {
					continue;
				}
				if ($field['type'] == 'datetime') {
					$p[$column] = $row[$column] ?? DBA::NULL_DATETIME;
				} else {
					$p[$column] = $row[$column];
				}
			}
			$result[] = $p;
		}
		DBA::close($rows);
		return $result;
	}

	/**
	 * @param string $query
	 * @return array
	 * @throws \Exception
	 */
	private function exportRow(string $query): array
	{
		$dbStructure = $this->dbaDefinition->getAll();

		preg_match('/\s+from\s+`?([a-z\d_]+)`?/i', $query, $match);
		$table = $match[1];

		$result = [];
		$rows = DBA::p($query);
		while ($row = DBA::fetch($rows)) {
			foreach ($row as $k => $v) {
				if (empty($dbStructure[$table]['fields'][$k])) {
					continue;
				}

				switch ($dbStructure[$table]['fields'][$k]['type']) {
					case 'datetime':
						$result[$k] = $v ?? DBA::NULL_DATETIME;
						break;
					default:
						$result[$k] = $v;
						break;
				}
			}
		}
		DBA::close($rows);

		return $result;
	}

	/**
	 * Export a list of the contacts as CSV file as e.g. Mastodon and Pleroma are doing.
	 *
	 * @param int $user_id
	 * @throws \Exception
	 */
	private function echoContactsAsCSV(int $user_id)
	{
		if (!$user_id) {
			throw new \RuntimeException($this->l10n->t('Permission denied.'));
		}

		// write the table header (like Mastodon)
		echo "Account address, Show boosts\n";
		// get all the contacts
		$contacts = DBA::select('contact', ['addr', 'url'], ['uid' => $user_id, 'self' => false, 'rel' => [Contact::SHARING, Contact::FRIEND], 'deleted' => false, 'archive' => false]);
		while ($contact = DBA::fetch($contacts)) {
			echo ($contact['addr'] ?: $contact['url']) . ", true\n";
		}
		DBA::close($contacts);
	}

	/**
	 * @param int $user_id
	 * @throws \Exception
	 */
	private function echoAccount(int $user_id)
	{
		if (!$user_id) {
			throw new \RuntimeException($this->l10n->t('Permission denied.'));
		}

		$user = $this->exportRow(
			sprintf("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", $user_id)
		);

		$contact = $this->exportMultiRow(
			sprintf("SELECT * FROM `contact` WHERE `uid` = %d ", $user_id)
		);


		$profile = $this->exportMultiRow(
			sprintf("SELECT *, 'default' AS `profile_name`, 1 AS `is-default` FROM `profile` WHERE `uid` = %d ", $user_id)
		);

		$profile_fields = $this->exportMultiRow(
			sprintf("SELECT * FROM `profile_field` WHERE `uid` = %d ", $user_id)
		);

		$photo = $this->exportMultiRow(
			sprintf("SELECT * FROM `photo` WHERE uid = %d AND profile = 1", $user_id)
		);
		foreach ($photo as &$p) {
			$p['data'] = bin2hex($p['data']);
		}

		$pconfig = $this->exportMultiRow(
			sprintf("SELECT * FROM `pconfig` WHERE uid = %d", $user_id)
		);

		$circle = $this->exportMultiRow(
			sprintf("SELECT * FROM `group` WHERE uid = %d", $user_id)
		);

		$circle_member = $this->exportMultiRow(
			sprintf("SELECT `circle_member`.`gid`, `circle_member`.`contact-id` FROM `group_member` AS `circle_member` INNER JOIN `group` AS `circle` ON `circle`.`id` = `circle_member`.`gid` WHERE `circle`.`uid` = %d", $user_id)
		);

		$output = [
			'version' => App::VERSION,
			'schema' => DB_UPDATE_VERSION,
			'baseurl' => $this->baseUrl,
			'user' => $user,
			'contact' => $contact,
			'profile' => $profile,
			'profile_fields' => $profile_fields,
			'photo' => $photo,
			'pconfig' => $pconfig,
			'circle' => $circle,
			'circle_member' => $circle_member,
		];

		echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR);
	}

	/**
	 * echoes account data and items as separated json, one per line
	 *
	 * @param int $user_id
	 * @throws \Exception
	 */
	private function echoAll(int $user_id)
	{
		if (!$user_id) {
			throw new \RuntimeException($this->l10n->t('Permission denied.'));
		}

		$this->echoAccount($user_id);
		echo "\n";

		$total = Post::count(['uid' => $user_id]);
		// chunk the output to avoid exhausting memory

		for ($x = 0; $x < $total; $x += 500) {
			$items = Post::selectToArray(Item::ITEM_FIELDLIST, ['uid' => $user_id], ['limit' => [$x, 500]]);
			$output = ['item' => $items];
			echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
		}
	}
}
