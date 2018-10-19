<?php

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

function redir_init(App $a) {

	$url = defaults($_GET, 'url', '');
	$quiet = !empty($_GET['quiet']) ? '&quiet=1' : '';
	$con_url = defaults($_GET, 'conurl', '');

	if ($a->argc > 1 && intval($a->argv[1])) {
		$cid = intval($a->argv[1]);
	} elseif (local_user() && !empty($con_url)) {
		$cid = Contact::getIdForURL($con_url, local_user());
	} else {
		$cid = 0;
	}

	if (!empty($cid)) {
		$fields = ['id', 'uid', 'nurl', 'url', 'addr', 'name', 'network', 'poll', 'issued-id', 'dfrn-id', 'duplex', 'pending'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $cid, 'uid' => [0, local_user()]]);
		if (!DBA::isResult($contact)) {
			notice(L10n::t('Contact not found.'));
			$a->internalRedirect();
		}

		$contact_url = $contact['url'];

		if ($contact['network'] !== Protocol::DFRN // Authentication isn't supported for non DFRN contacts.
			|| (!local_user() && !remote_user()) // Visitors (not logged in or not remotes) can't authenticate.
			|| (!empty($a->contact['id']) && $a->contact['id'] == $cid)) // Local user is already authenticated.
		{
			System::externalRedirect($url != '' ? $url : $contact_url);
		}

		if ($contact['uid'] == 0 && local_user()) {
			// Let's have a look if there is an established connection
			// between the puplic contact we have found and the local user.
			$contact = DBA::selectFirst('contact', $fields, ['nurl' => $contact['nurl'], 'uid' => local_user()]);

			if (DBA::isResult($contact)) {
				$cid = $contact['id'];
			}

			if (!empty($a->contact['id']) && $a->contact['id'] == $cid) {
				// Local user is already authenticated.
				$target_url = $url != '' ? $url : $contact_url;
				logger($contact['name'] . " is already authenticated. Redirecting to " . $target_url, LOGGER_DEBUG);
				System::externalRedirect($target_url);
			}
		}

		if (remote_user()) {
			$host = substr($a->getBaseURL() . ($a->getURLPath() ? '/' . $a->getURLPath() : ''), strpos($a->getBaseURL(), '://') + 3);
			$remotehost = substr($contact['addr'], strpos($contact['addr'], '@') + 1);

			// On a local instance we have to check if the local user has already authenticated
			// with the local contact. Otherwise the local user would ask the local contact
			// for authentification everytime he/she is visiting a profile page of the local
			// contact.
			if ($host == $remotehost
				&& x($_SESSION, 'remote')
				&& is_array($_SESSION['remote']))
			{
				foreach ($_SESSION['remote'] as $v) {
					if ($v['uid'] == $_SESSION['visitor_visiting'] && $v['cid'] == $_SESSION['visitor_id']) {
						// Remote user is already authenticated.
						$target_url = $url != '' ? $url : $contact_url;
						logger($contact['name'] . " is already authenticated. Redirecting to " . $target_url, LOGGER_DEBUG);
						System::externalRedirect($target_url);
					}
				}
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

			$sec = random_string();

			$fields = ['uid' => local_user(), 'cid' => $cid, 'dfrn_id' => $dfrn_id,
				'sec' => $sec, 'expire' => time() + 45];
			DBA::insert('profile_check', $fields);

			logger('mod_redir: ' . $contact['name'] . ' ' . $sec, LOGGER_DEBUG);

			$dest = (!empty($url) ? '&destination_url=' . $url : '');

			System::externalRedirect($contact['poll'] . '?dfrn_id=' . $dfrn_id
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest . $quiet);
		}

		$url = $url != '' ? $url : $contact_url;
	}

	// If we don't have a connected contact, redirect with
	// the 'zrl' parameter.
	if (!empty($url)) {
		$my_profile = Profile::getMyURL();

		if (!empty($my_profile) && !link_compare($my_profile, $url)) {
			$separator = strpos($url, '?') ? '&' : '?';

			$url .= $separator . 'zrl=' . urlencode($my_profile);
		}

		logger('redirecting to ' . $url, LOGGER_DEBUG);
		$a->internalRedirect($url);
	}

	notice(L10n::t('Contact not found.'));
	$a->internalRedirect();
}
