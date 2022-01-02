<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\User;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;
use Friendica\Network\HTTPException;

/**
 * DFRN Notify
 */
class Notify extends BaseModule
{
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
			self::dispatchPrivate($user, $postdata);
		} elseif (!self::dispatchPublic($postdata)) {
			require_once 'mod/salmon.php';
			salmon_post(DI::app(), $postdata);
		}
	}

	private static function dispatchPublic($postdata)
	{
		$msg = Diaspora::decodeRaw($postdata, '', true);
		if (!$msg) {
			// We have to fail silently to be able to hand it over to the salmon parser
			return false;
		}

		// Fetch the corresponding public contact
		$contact_id = Contact::getIdForURL($msg['author']);
		if (empty($contact_id)) {
			Logger::notice('Contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		// Fetch the importer (Mixture of sender and receiver)
		$importer = DFRN::getImporter($contact_id);
		if (empty($importer)) {
			Logger::notice('Importer contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		Logger::info('Importing post with the public envelope.', ['transmitter' => $msg['author']]);

		// Now we should be able to import it
		$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::RELAY);
		System::xmlExit($ret, 'Done');
	}

	private static function dispatchPrivate($user, $postdata)
	{
		$msg = Diaspora::decodeRaw($postdata, $user['prvkey'] ?? '');
		if (!$msg) {
			System::xmlExit(4, 'Unable to parse message');
		}

		// Fetch the contact
		$contact = Contact::getByURLForUser($msg['author'], $user['uid'], null, ['id', 'blocked', 'pending']);
		if (empty($contact['id'])) {
			Logger::notice('Contact not found', ['address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		if ($contact['pending'] || $contact['blocked']) {
			Logger::notice('Contact is blocked or pending', ['address' => $msg['author'], 'contact' => $contact]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		// Fetch the importer (Mixture of sender and receiver)
		$importer = DFRN::getImporter($contact['id'], $user['uid']);
		if (empty($importer)) {
			Logger::notice('Importer contact not found for user', ['uid' => $user['uid'], 'cid' => $contact['id'], 'address' => $msg['author']]);
			System::xmlExit(3, 'Contact ' . $msg['author'] . ' not found');
		}

		Logger::info('Importing post with the private envelope.', ['transmitter' => $msg['author'], 'receiver' => $user['nickname']]);

		// Now we should be able to import it
		$ret = DFRN::import($msg['message'], $importer, Conversation::PARCEL_DIASPORA_DFRN, Conversation::PUSH);
		System::xmlExit($ret, 'Done');
	}
}
