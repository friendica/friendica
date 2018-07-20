<?php

use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Database\DBM;
use Friendica\Protocol\OStatus;

require_once('include/security.php');
require_once('include/items.php');

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
	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$hub_mode      = notags(trim(defaults($_GET, 'hub_mode', '')));
		$hub_topic     = notags(trim(defaults($_GET, 'hub_topic', '')));
		$hub_challenge = notags(trim(defaults($_GET, 'hub_challenge', '')));
		$hub_lease     = notags(trim(defaults($_GET, 'hub_lease_seconds', '')));
		$hub_verify    = notags(trim(defaults($_GET, 'hub_verify_token', '')));

		logger('Subscription from ' . $_SERVER['REMOTE_ADDR'] . ' Mode: ' . $hub_mode . ' Nick: ' . $nick);
		logger('Data: ' . print_r($_GET,true), LOGGER_DATA);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$owner = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
		if (!DBM::is_result($owner)) {
			logger('Local account not found: ' . $nick);
			hub_return(false, '');
		}

		$condition = ['uid' => $owner['uid'], 'id' => $contact_id, 'blocked' => false, 'pending' => false];

		if (!empty($hub_verify)) {
			$condition['hub-verify'] = $hub_verify;
		}

		$contact = DBA::selectFirst('contact', ['id', 'poll'], $condition);
		if (!DBM::is_result($contact)) {
			logger('Contact ' . $contact_id . ' not found.');
			hub_return(false, '');
		}

		if (!empty($hub_topic) && !link_compare($hub_topic, $contact['poll'])) {
			logger('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			hub_return(false, '');
		}

		// We must initiate an unsubscribe request with a verify_token.
		// Don't allow outsiders to unsubscribe us.

		if (($hub_mode === 'unsubscribe') && empty($hub_verify)) {
			logger('Bogus unsubscribe');
			hub_return(false, '');
		}

		if (!empty($hub_mode)) {
			DBA::update('contact', ['subhub' => $subscribe], ['id' => $contact['id']]);
			logger($hub_mode . ' success for contact ' . $contact_id . '.');
		}
 		hub_return(true, $hub_challenge);
	}
}

function pubsub_post(App $a)
{
	$xml = file_get_contents('php://input');

	logger('Feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  $a->cmd . ' with user-agent: ' . $_SERVER['HTTP_USER_AGENT']);
	logger('Data: ' . $xml, LOGGER_DATA);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	$importer = DBA::selectFirst('user', [], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
	if (!DBM::is_result($importer)) {
		hub_post_return();
	}

	$condition = ['id' => $contact_id, 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBM::is_result($contact)) {
		$author = OStatus::salmonAuthor($xml, $importer);
		if (!empty($author['contact-id'])) {
			$condition = ['id' => $author['contact-id'], 'uid' => $importer['uid'], 'subhub' => true, 'blocked' => false];
			$contact = DBA::selectFirst('contact', [], $condition);
			logger('No record for ' . $nick .' with contact id ' . $contact_id . ' - using '.$author['contact-id'].' instead.');
		}
		if (!DBM::is_result($contact)) {
			logger('Contact ' . $author["author-link"] . ' (' . $contact_id . ') for user ' . $nick . " wasn't found - ignored. XML: " . $xml);
			hub_post_return();
		}
	}

	if (!in_array($contact['rel'], [CONTACT_IS_SHARING, CONTACT_IS_FRIEND]) && ($contact['network'] != NETWORK_FEED)) {
		logger('Contact ' . $contact['id'] . ' is not expected to share with us - ignored.');
		hub_post_return();
	}

	// We import feeds from OStatus, Friendica and ATOM/RSS.
	/// @todo Check if Friendica posts really arrive here - otherwise we can discard some stuff
	if (!in_array($contact['network'], [NETWORK_OSTATUS, NETWORK_DFRN, NETWORK_FEED])) {
		hub_post_return();
	}

	logger('Import item for ' . $nick . ' from ' . $contact['nick'] . ' (' . $contact['id'] . ')');
	$feedhub = '';
	consume_feed($xml, $importer, $contact, $feedhub);

	// do it a second time for DFRN so that any children find their parents.
	if ($contact['network'] === NETWORK_DFRN) {
		consume_feed($xml, $importer, $contact, $feedhub);
	}

	hub_post_return();
}
