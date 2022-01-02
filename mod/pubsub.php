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

use Friendica\App;
use Friendica\App\Router;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Protocol\OStatus;
use Friendica\Util\Strings;
use Friendica\Util\Network;
use Friendica\Model\GServer;
use Friendica\Model\Post;

function hub_return($valid, $body)
{
	if ($valid) {
		echo $body;
	} else {
		throw new \Friendica\Network\HTTPException\NotFoundException();
	}
	exit();
}

// when receiving an XML feed, always return OK

function hub_post_return()
{
	throw new \Friendica\Network\HTTPException\OKException();
}

function pubsub_init(App $a)
{
	$nick       = ((DI::args()->getArgc() > 1) ? trim(DI::args()->getArgv()[1])   : '');
	$contact_id = ((DI::args()->getArgc() > 2) ? intval(DI::args()->getArgv()[2]) : 0 );

	if (DI::args()->getMethod() == Router::GET) {
		$hub_mode      = trim($_GET['hub_mode']         ?? '');
		$hub_topic     = trim($_GET['hub_topic']        ?? '');
		$hub_challenge = trim($_GET['hub_challenge']    ?? '');
		$hub_verify    = trim($_GET['hub_verify_token'] ?? '');

		Logger::notice('Subscription from ' . $_SERVER['REMOTE_ADDR'] . ' Mode: ' . $hub_mode . ' Nick: ' . $nick);
		Logger::debug('Data: ', ['get' => $_GET]);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$owner = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
		if (!DBA::isResult($owner)) {
			Logger::notice('Local account not found: ' . $nick);
			hub_return(false, '');
		}

		$condition = ['uid' => $owner['uid'], 'id' => $contact_id, 'blocked' => false, 'pending' => false];

		if (!empty($hub_verify)) {
			$condition['hub-verify'] = $hub_verify;
		}

		$contact = DBA::selectFirst('contact', ['id', 'poll'], $condition);
		if (!DBA::isResult($contact)) {
			Logger::notice('Contact ' . $contact_id . ' not found.');
			hub_return(false, '');
		}

		if (!empty($hub_topic) && !Strings::compareLink($hub_topic, $contact['poll'])) {
			Logger::notice('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			hub_return(false, '');
		}

		// We must initiate an unsubscribe request with a verify_token.
		// Don't allow outsiders to unsubscribe us.

		if (($hub_mode === 'unsubscribe') && empty($hub_verify)) {
			Logger::notice('Bogus unsubscribe');
			hub_return(false, '');
		}

		if (!empty($hub_mode)) {
			Contact::update(['subhub' => $subscribe], ['id' => $contact['id']]);
			Logger::notice($hub_mode . ' success for contact ' . $contact_id . '.');
		}
 		hub_return(true, $hub_challenge);
	}
}

function pubsub_post(App $a)
{
	$xml = Network::postdata();

	Logger::info('Feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  DI::args()->getCommand() . ' with user-agent: ' . $_SERVER['HTTP_USER_AGENT']);
	Logger::debug('Data: ' . $xml);

	$nick       = ((DI::args()->getArgc() > 1) ? trim(DI::args()->getArgv()[1])   : '');
	$contact_id = ((DI::args()->getArgc() > 2) ? intval(DI::args()->getArgv()[2]) : 0 );

	$importer = DBA::selectFirst('user', [], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
	if (!DBA::isResult($importer)) {
		hub_post_return();
	}

	$condition = ['id' => $contact_id, 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		$author = OStatus::salmonAuthor($xml, $importer);
		if (!empty($author['contact-id'])) {
			$condition = ['id' => $author['contact-id'], 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
			$contact = DBA::selectFirst('contact', [], $condition);
			Logger::notice('No record for ' . $nick .' with contact id ' . $contact_id . ' - using '.$author['contact-id'].' instead.');
		}
		if (!DBA::isResult($contact)) {
			Logger::notice('Contact ' . $author["author-link"] . ' (' . $contact_id . ') for user ' . $nick . " wasn't found - ignored. XML: " . $xml);
			hub_post_return();
		}
	}

	if (!empty($contact['gsid'])) {
		GServer::setProtocol($contact['gsid'], Post\DeliveryData::OSTATUS);
	}

	if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]) && ($contact['network'] != Protocol::FEED)) {
		Logger::notice('Contact ' . $contact['id'] . ' is not expected to share with us - ignored.');
		hub_post_return();
	}

	// We only import feeds from OStatus here
	if ($contact['network'] != Protocol::OSTATUS) {
		Logger::warning('Unexpected network', ['contact' => $contact]);
		hub_post_return();
	}

	Logger::info('Import item for ' . $nick . ' from ' . $contact['nick'] . ' (' . $contact['id'] . ')');
	$feedhub = '';
	OStatus::import($xml, $importer, $contact, $feedhub);

	hub_post_return();
}
