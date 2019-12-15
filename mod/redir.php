<?php

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Network;
use Friendica\Util\Strings;

function redir_init(App $a) {

	$url = $_GET['url'] ?? '';
	$quiet = !empty($_GET['quiet']) ? '&quiet=1' : '';

	if ($a->argc > 1 && intval($a->argv[1])) {
		$cid = intval($a->argv[1]);
	} else {
		$cid = 0;
	}

	// Try magic auth before the legacy stuff
	redir_magic($a, $cid, $url);

	if (!empty($cid)) {
		$fields = ['id', 'uid', 'nurl', 'url', 'addr', 'name', 'network', 'poll', 'issued-id', 'dfrn-id', 'duplex', 'pending'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $cid, 'uid' => [0, local_user()]]);
		if (!DBA::isResult($contact)) {
			notice(L10n::t('Contact not found.'));
			DI::baseUrl()->redirect();
		}

		$contact_url = $contact['url'];

		if (!Session::isAuthenticated() // Visitors (not logged in or not remotes) can't authenticate.
			|| (!empty($a->contact['id']) && $a->contact['id'] == $cid)) // Local user is already authenticated.
		{
			$a->redirect($url ?: $contact_url);
		}

		if ($contact['uid'] == 0 && local_user()) {
			// Let's have a look if there is an established connection
			// between the public contact we have found and the local user.
			$contact = DBA::selectFirst('contact', $fields, ['nurl' => $contact['nurl'], 'uid' => local_user()]);

			if (DBA::isResult($contact)) {
				$cid = $contact['id'];
			}

			if (!empty($a->contact['id']) && $a->contact['id'] == $cid) {
				// Local user is already authenticated.
				$target_url = $url ?: $contact_url;
				Logger::log($contact['name'] . " is already authenticated. Redirecting to " . $target_url, Logger::DEBUG);
				$a->redirect($target_url);
			}
		}

		if (remote_user()) {
			$host = substr($a->getBaseURL() . ($a->getURLPath() ? '/' . $a->getURLPath() : ''), strpos($a->getBaseURL(), '://') + 3);
			$remotehost = substr($contact['addr'], strpos($contact['addr'], '@') + 1);

			// On a local instance we have to check if the local user has already authenticated
			// with the local contact. Otherwise the local user would ask the local contact
			// for authentification everytime he/she is visiting a profile page of the local
			// contact.
			if (($host == $remotehost) && (Session::getRemoteContactID(Session::get('visitor_visiting')) == Session::get('visitor_id'))) {
				// Remote user is already authenticated.
				$target_url = $url ?: $contact_url;
				Logger::log($contact['name'] . " is already authenticated. Redirecting to " . $target_url, Logger::DEBUG);
				$a->redirect($target_url);
			}
		}

		// Doing remote auth with dfrn.
		if (local_user() && (!empty($contact['dfrn-id']) || !empty($contact['issued-id'])) && empty($contact['pending'])) {
			$dfrn_id = $orig_id = (($contact['issued-id']) ? $contact['issued-id'] : $contact['dfrn-id']);

			if ($contact['duplex'] && $contact['issued-id']) {
				$orig_id = $contact['issued-id'];
				$dfrn_id = '1:' . $orig_id;
			}
			if ($contact['duplex'] && $contact['dfrn-id']) {
				$orig_id = $contact['dfrn-id'];
				$dfrn_id = '0:' . $orig_id;
			}

			$sec = Strings::getRandomHex();

			$fields = ['uid' => local_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id,
				'sec' => $sec, 'expire' => time() + 45];
			DBA::insert('profile_check', $fields);

			Logger::log('mod_redir: ' . $contact['name'] . ' ' . $sec, Logger::DEBUG);

			$dest = (!empty($url) ? '&destination_url=' . $url : '');

			System::externalRedirect($contact['poll'] . '?dfrn_id=' . $dfrn_id
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest . $quiet);
		}

		$url = $url ?: $contact_url;
	}

	// If we don't have a connected contact, redirect with
	// the 'zrl' parameter.
	if (!empty($url)) {
		$my_profile = Profile::getMyURL();

		if (!empty($my_profile) && !Strings::compareLink($my_profile, $url)) {
			$separator = strpos($url, '?') ? '&' : '?';

			$url .= $separator . 'zrl=' . urlencode($my_profile);
		}

		Logger::log('redirecting to ' . $url, Logger::DEBUG);
		$a->redirect($url);
	}

	notice(L10n::t('Contact not found.'));
	DI::baseUrl()->redirect();
}

function redir_magic($a, $cid, $url)
{
	$visitor = Profile::getMyURL();
	if (!empty($visitor)) {
		Logger::info('Got my url', ['visitor' => $visitor]);
	}

	$contact = DBA::selectFirst('contact', ['url'], ['id' => $cid]);
	if (!DBA::isResult($contact)) {
		Logger::info('Contact not found', ['id' => $cid]);
		// Shouldn't happen under normal conditions
		notice(L10n::t('Contact not found.'));
		if (!empty($url)) {
			$a->redirect($url);
		} else {
			$a->internalRedirect();
		}
	} else {
		$contact_url = $contact['url'];
		$target_url = $url ?: $contact_url;
	}

	$basepath = Contact::getBasepath($contact_url);

	// We don't use magic auth when there is no visitor, we are on the same system or we visit our own stuff
	if (empty($visitor) || Strings::compareLink($basepath, System::baseUrl()) || Strings::compareLink($contact_url, $visitor)) {
		Logger::info('Redirecting without magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
		$a->redirect($target_url);
	}

	// Test for magic auth on the target system
	$serverret = Network::curl($basepath . '/magic');
	if ($serverret->isSuccess()) {
		$separator = strpos($target_url, '?') ? '&' : '?';
		$target_url .= $separator . 'zrl=' . urlencode($visitor) . '&addr=' . urlencode($contact_url);

		Logger::info('Redirecting with magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
		$a->redirect($target_url);
	} else {
		Logger::info('No magic for contact', ['contact' => $contact_url]);
	}
}
