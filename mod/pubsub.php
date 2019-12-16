<?php

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Protocol\OStatus;
use Friendica\Util\Strings;
use Friendica\Util\Network;
use Friendica\Core\System;

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
	$nick       = (($a->argc > 1) ? Strings::escapeTags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$hub_mode      = Strings::escapeTags(trim($_GET['hub_mode'] ?? ''));
		$hub_topic     = Strings::escapeTags(trim($_GET['hub_topic'] ?? ''));
		$hub_challenge = Strings::escapeTags(trim($_GET['hub_challenge'] ?? ''));
		$hub_verify    = Strings::escapeTags(trim($_GET['hub_verify_token'] ?? ''));

		Logger::log('Subscription from ' . $_SERVER['REMOTE_ADDR'] . ' Mode: ' . $hub_mode . ' Nick: ' . $nick);
		Logger::log('Data: ' . print_r($_GET,true), Logger::DATA);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$owner = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
		if (!DBA::isResult($owner)) {
			Logger::log('Local account not found: ' . $nick);
			hub_return(false, '');
		}

		$condition = ['uid' => $owner['uid'], 'id' => $contact_id, 'blocked' => false, 'pending' => false];

		if (!empty($hub_verify)) {
			$condition['hub-verify'] = $hub_verify;
		}

		$contact = DBA::selectFirst('contact', ['id', 'poll'], $condition);
		if (!DBA::isResult($contact)) {
			Logger::log('Contact ' . $contact_id . ' not found.');
			hub_return(false, '');
		}

		if (!empty($hub_topic) && !Strings::compareLink($hub_topic, $contact['poll'])) {
			Logger::log('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			hub_return(false, '');
		}

		// We must initiate an unsubscribe request with a verify_token.
		// Don't allow outsiders to unsubscribe us.

		if (($hub_mode === 'unsubscribe') && empty($hub_verify)) {
			Logger::log('Bogus unsubscribe');
			hub_return(false, '');
		}

		if (!empty($hub_mode)) {
			DBA::update('contact', ['subhub' => $subscribe], ['id' => $contact['id']]);
			Logger::log($hub_mode . ' success for contact ' . $contact_id . '.');
		}
 		hub_return(true, $hub_challenge);
	}
}

function pubsub_post(App $a)
{
	$xml = Network::postdata();

	Logger::log('Feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  DI::args()->getCommand() . ' with user-agent: ' . $_SERVER['HTTP_USER_AGENT']);
	Logger::log('Data: ' . $xml, Logger::DATA);

	$nick       = (($a->argc > 1) ? Strings::escapeTags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

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
			Logger::log('No record for ' . $nick .' with contact id ' . $contact_id . ' - using '.$author['contact-id'].' instead.');
		}
		if (!DBA::isResult($contact)) {
			Logger::log('Contact ' . $author["author-link"] . ' (' . $contact_id . ') for user ' . $nick . " wasn't found - ignored. XML: " . $xml);
			hub_post_return();
		}
	}

	if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]) && ($contact['network'] != Protocol::FEED)) {
		Logger::log('Contact ' . $contact['id'] . ' is not expected to share with us - ignored.');
		hub_post_return();
	}

	// We import feeds from OStatus, Friendica and ATOM/RSS.
	/// @todo Check if Friendica posts really arrive here - otherwise we can discard some stuff
	if (!in_array($contact['network'], [Protocol::OSTATUS, Protocol::DFRN, Protocol::FEED])) {
		hub_post_return();
	}

	Logger::log('Import item for ' . $nick . ' from ' . $contact['nick'] . ' (' . $contact['id'] . ')');
	$feedhub = '';
	consume_feed($xml, $importer, $contact, $feedhub);

	// do it a second time for DFRN so that any children find their parents.
	if ($contact['network'] === Protocol::DFRN) {
		consume_feed($xml, $importer, $contact, $feedhub);
	}

	hub_post_return();
}
