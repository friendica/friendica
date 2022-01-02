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
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

function repair_ostatus_content(App $a) {

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('ostatus_repair');
		// NOTREACHED
	}

	$o = '<h2>' . DI::l10n()->t('Resubscribing to OStatus contacts') . '</h2>';

	$uid = local_user();

	$counter = intval($_REQUEST['counter'] ?? 0);

	$condition = ['uid' => $uid, 'network' => Protocol::OSTATUS, 'rel' => [Contact::FRIEND, Contact::SHARING]];
	$total = DBA::count('contact', $condition);

	if (!$total) {
		return ($o . DI::l10n()->t('Error'));
	}

	$contact = Contact::selectToArray(['url'], $condition, ['order' => ['url'], 'limit' => [$counter++, 1]]);
	if (!DBA::isResult($contact)) {
		$o .= DI::l10n()->t('Done');
		return $o;
	}

	$o .= '<p>' . $counter . '/' . $total . ': ' . $contact[0]['url'] . '</p>';

	$o .= '<p>' . DI::l10n()->t('Keep this window open until done.') . '</p>';

	Contact::createFromProbeForUser($a->getLoggedInUserId(), $contact[0]['url']);

	DI::page()['htmlhead'] = '<meta http-equiv="refresh" content="1; URL=' . DI::baseUrl() . '/repair_ostatus?counter=' . $counter . '">';

	return $o;
}
