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

namespace Friendica\Module\DFRN;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\User;
use Friendica\Module\OStatus\Salmon;
use Friendica\Module\Response;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * DFRN Notify
 */
class Notify extends BaseModule
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function post(array $request = [])
	{
		$postdata = Network::postdata();

		if (empty($postdata)) {
			throw new HTTPException\BadRequestException();
		}

		$data = json_decode($postdata);
		if (is_object($data) && !empty($this->parameters['nickname'])) {
			$user = User::getByNickname($this->parameters['nickname']);
			if (empty($user)) {
				throw new \Friendica\Network\HTTPException\InternalServerErrorException();
			}
			$this->dispatchPrivate($user, $postdata);
		} else {
			$this->dispatchPublic($postdata);
		}
	}

	private function dispatchPublic(string $postdata): bool
	{
		$msg = Diaspora::decodeRaw($postdata, '', true);
		if (!is_array($msg)) {
			// We have to fail silently to be able to hand it over to the salmon parser
			$this->logger->warning('Diaspora::decodeRaw() has failed for some reason.', ['post-data' => $postdata]);
			return false;
		}

		// Fetch the corresponding public contact
		$contact_id = Contact::getIdForURL($msg['author']);
		if (empty($contact_id)) {
			$this->logger->notice('Contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		// Fetch the importer (Mixture of sender and receiver)
		$importer = DFRN::getImporter($contact_id);
		if (empty($importer)) {
			$this->logger->notice('Importer contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		$this->logger->debug('Importing post with the public envelope.', ['transmitter' => $msg['author']]);

		// Now we should be able to import it
		$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::RELAY);
		System::xmlExit($ret, 'Done');

		return true;
	}

	private function dispatchPrivate(array $user, string $postdata)
	{
		$msg = Diaspora::decodeRaw($postdata, $user['prvkey'] ?? '');
		if (!is_array($msg)) {
			System::xmlExit(4, 'Unable to parse message');
		}

		// Fetch the contact
		$contact = Contact::getByURLForUser($msg['author'], $user['uid'], null, ['id', 'blocked', 'pending']);
		if (empty($contact['id'])) {
			$this->logger->notice('Contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		if ($contact['pending'] || $contact['blocked']) {
			$this->logger->notice('Contact is blocked or pending', ['address' => $msg['author'], 'contact' => $contact]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		// Fetch the importer (Mixture of sender and receiver)
		$importer = DFRN::getImporter($contact['id'], $user['uid']);
		if (empty($importer)) {
			$this->logger->notice('Importer contact not found for user', ['uid' => $user['uid'], 'cid' => $contact['id'], 'address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		$this->logger->debug('Importing post with the private envelope.', ['transmitter' => $msg['author'], 'receiver' => $user['nickname']]);

		// Now we should be able to import it
		$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::PUSH);
		System::xmlExit($ret, 'Done');
	}
}
