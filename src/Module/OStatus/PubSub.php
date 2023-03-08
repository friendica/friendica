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

namespace Friendica\Module\OStatus;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Protocol\OStatus;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class PubSub extends \Friendica\BaseModule
{
	/** @var Database */
	private $database;
	/** @var App\Request */
	private $request;

	public function __construct(App\Request $request, Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
		$this->request  = $request;
	}

	protected function post(array $request = [])
	{
		$xml = Network::postdata();

		$this->logger->info('Feed arrived.', ['from' =>  $this->request->getRemoteAddress(), 'for' => $this->args->getCommand(), 'user-agent' => $this->server['HTTP_USER_AGENT']]);
		$this->logger->debug('Data stream.', ['xml' => $xml]);
		$this->logger->debug('Got request data.', ['request' => $request]);

		$nickname   = $this->parameters['nickname'] ?? '';
		$contact_id = $this->parameters['cid']      ?? 0;

		$importer = $this->database->selectFirst('user', [], ['nickname' => $nickname, 'account_expired' => false, 'account_removed' => false]);
		if (!$importer) {
			throw new HTTPException\OKException();
		}

		$condition = ['id' => $contact_id, 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
		$contact   = $this->database->selectFirst('contact', [], $condition);
		if (!$contact) {
			$author = OStatus::salmonAuthor($xml, $importer);
			if (!empty($author['contact-id'])) {
				$condition = ['id' => $author['contact-id'], 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
				$contact   = $this->database->selectFirst('contact', [], $condition);
				$this->logger->notice('No record found for nickname, using author entry instead.', ['nickname' =>  $nickname, 'contact-id' => $contact_id, 'author-contact-id' => $author['contact-id']]);
			}

			if (!$contact) {
				$this->logger->notice("Contact wasn't found - ignored.", ['author-link' => $author['author-link'], 'contact-id' => $contact_id, 'nickname' => $nickname, 'xml' => $xml]);
				throw new HTTPException\OKException();
			}
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::OSTATUS);
		}

		if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]) && ($contact['network'] != Protocol::FEED)) {
			$this->logger->notice('Contact is not expected to share with us - ignored.', ['contact-id' => $contact['id']]);
			throw new HTTPException\OKException();
		}

		// We only import feeds from OStatus here
		if (!in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::OSTATUS])) {
			$this->logger->warning('Unexpected network', ['contact' => $contact, 'network' => $contact['network']]);
			throw new HTTPException\OKException();
		}

		$this->logger->info('Import item from Contact.', ['nickname' => $nickname, 'contact-nickname' => $contact['nick'], 'contact-id' => $contact['id']]);
		$feedhub = '';
		OStatus::import($xml, $importer, $contact, $feedhub);

		throw new HTTPException\OKException();
	}

	protected function rawContent(array $request = [])
	{
		$nickname   = $this->parameters['nickname'] ?? '';
		$contact_id = $this->parameters['cid']      ?? 0;

		$hub_mode      = trim($request['hub_mode']         ?? '');
		$hub_topic     = trim($request['hub_topic']        ?? '');
		$hub_challenge = trim($request['hub_challenge']    ?? '');
		$hub_verify    = trim($request['hub_verify_token'] ?? '');

		$this->logger->notice('Subscription start.', ['from' => $this->request->getRemoteAddress(), 'mode' => $hub_mode, 'nickname' => $nickname]);
		$this->logger->debug('Data: ', ['get' => $request]);

		$owner = $this->database->selectFirst('user', ['uid'], ['nickname' => $nickname, 'account_expired' => false, 'account_removed' => false]);
		if (!$owner) {
			$this->logger->notice('Local account not found.', ['nickname' => $nickname]);
			throw new HTTPException\NotFoundException();
		}

		$condition = ['uid' => $owner['uid'], 'id' => $contact_id, 'blocked' => false, 'pending' => false];

		if (!empty($hub_verify)) {
			$condition['hub-verify'] = $hub_verify;
		}

		$contact = $this->database->selectFirst('contact', ['id', 'poll'], $condition);
		if (!$contact) {
			$this->logger->notice('Contact not found.', ['contact' => $contact_id]);
			throw new HTTPException\NotFoundException();
		}

		if (!empty($hub_topic) && !Strings::compareLink($hub_topic, $contact['poll'])) {
			$this->logger->notice("Hub topic isn't valid for Contact.", ['hub_topic' =>  $hub_topic, 'contact_poll' => $contact['poll']]);
			throw new HTTPException\NotFoundException();
		}

		// We must initiate an unsubscribe request with a verify_token.
		// Don't allow outsiders to unsubscribe us.

		if (($hub_mode === 'unsubscribe') && empty($hub_verify)) {
			$this->logger->notice('Bogus unsubscribe');
			throw new HTTPException\NotFoundException();
		}

		if (!empty($hub_mode)) {
			Contact::update(['subhub' => $hub_mode === 'subscribe'], ['id' => $contact['id']]);
			$this->logger->notice('Success for contact.', ['mode' => $hub_mode, 'contact' => $contact_id]);
		}

		System::httpExit($hub_challenge);
	}
}
