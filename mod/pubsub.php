<?php

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Protocol\OStatus;

require_once 'include/items.php';

function hub_return($valid, $body)
{
	if ($valid) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
		echo $body;
	} else {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
	}
	killme();
}

// when receiving an XML feed, always return OK

function hub_post_return()
{
	header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
	killme();
}

function pubsub_init(App $a)
{
	$nick       = (($a->argc > 1) ? Text::noTags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$hub_mode      = Text::noTags(trim(defaults($_GET, 'hub_mode', '')));
		$hub_topic     = Text::noTags(trim(defaults($_GET, 'hub_topic', '')));
		$hub_challenge = Text::noTags(trim(defaults($_GET, 'hub_challenge', '')));
		$hub_lease     = Text::noTags(trim(defaults($_GET, 'hub_lease_seconds', '')));
		$hub_verify    = Text::noTags(trim(defaults($_GET, 'hub_verify_token', '')));

		Text::logger('Subscription from ' . $_SERVER['REMOTE_ADDR'] . ' Mode: ' . $hub_mode . ' Nick: ' . $nick);
		Text::logger('Data: ' . print_r($_GET,true), LOGGER_DATA);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$owner = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
		if (!DBA::isResult($owner)) {
			Text::logger('Local account not found: ' . $nick);
			hub_return(false, '');
		}

		$condition = ['uid' => $owner['uid'], 'id' => $contact_id, 'blocked' => false, 'pending' => false];

		if (!empty($hub_verify)) {
			$condition['hub-verify'] = $hub_verify;
		}

		$contact = DBA::selectFirst('contact', ['id', 'poll'], $condition);
		if (!DBA::isResult($contact)) {
			Text::logger('Contact ' . $contact_id . ' not found.');
			hub_return(false, '');
		}

		if (!empty($hub_topic) && !link_compare($hub_topic, $contact['poll'])) {
			Text::logger('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			hub_return(false, '');
		}

		// We must initiate an unsubscribe request with a verify_token.
		// Don't allow outsiders to unsubscribe us.

		if (($hub_mode === 'unsubscribe') && empty($hub_verify)) {
			Text::logger('Bogus unsubscribe');
			hub_return(false, '');
		}

		if (!empty($hub_mode)) {
			DBA::update('contact', ['subhub' => $subscribe], ['id' => $contact['id']]);
			Text::logger($hub_mode . ' success for contact ' . $contact_id . '.');
		}
 		hub_return(true, $hub_challenge);
	}
}

function pubsub_post(App $a)
{
	$xml = file_get_contents('php://input');

	Text::logger('Feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  $a->cmd . ' with user-agent: ' . $_SERVER['HTTP_USER_AGENT']);
	Text::logger('Data: ' . $xml, LOGGER_DATA);

	$nick       = (($a->argc > 1) ? Text::noTags(trim($a->argv[1])) : '');
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
			Text::logger('No record for ' . $nick .' with contact id ' . $contact_id . ' - using '.$author['contact-id'].' instead.');
		}
		if (!DBA::isResult($contact)) {
			Text::logger('Contact ' . $author["author-link"] . ' (' . $contact_id . ') for user ' . $nick . " wasn't found - ignored. XML: " . $xml);
			hub_post_return();
		}
	}

	if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]) && ($contact['network'] != Protocol::FEED)) {
		Text::logger('Contact ' . $contact['id'] . ' is not expected to share with us - ignored.');
		hub_post_return();
	}

	// We import feeds from OStatus, Friendica and ATOM/RSS.
	/// @todo Check if Friendica posts really arrive here - otherwise we can discard some stuff
	if (!in_array($contact['network'], [Protocol::OSTATUS, Protocol::DFRN, Protocol::FEED])) {
		hub_post_return();
	}

	Text::logger('Import item for ' . $nick . ' from ' . $contact['nick'] . ' (' . $contact['id'] . ')');
	$feedhub = '';
	consume_feed($xml, $importer, $contact, $feedhub);

	// do it a second time for DFRN so that any children find their parents.
	if ($contact['network'] === Protocol::DFRN) {
		consume_feed($xml, $importer, $contact, $feedhub);
	}

	hub_post_return();
}
