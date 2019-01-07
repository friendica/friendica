<?php
/**
 * @file mod/fsuggest.php
 */

use Friendica\App;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

function fsuggest_post(App $a)
{
	if (! local_user()) {
		return;
	}

	if ($a->argc != 2) {
		return;
	}

	$contact_id = intval($a->argv[1]);

	$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	if (! DBA::isResult($contact)) {
		notice(L10n::t('Contact not found.') . EOL);
		return;
	}

	$new_contact = intval($_POST['suggest']);

	$hash = Strings::getRandomHex();

	$note = Strings::escapeHtml(trim(defaults($_POST, 'note', '')));

	if ($new_contact) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($new_contact),
			intval(local_user())
		);
		if (DBA::isResult($r)) {
			q("INSERT INTO `fsuggest` ( `uid`,`cid`,`name`,`url`,`request`,`photo`,`note`,`created`)
				VALUES ( %d, %d, '%s','%s','%s','%s','%s','%s')",
				intval(local_user()),
				intval($contact_id),
				DBA::escape($contact['name']),
				DBA::escape($contact['url']),
				DBA::escape($contact['request']),
				DBA::escape($contact['photo']),
				DBA::escape($hash),
				DBA::escape(DateTimeFormat::utcNow())
			);
			$r = q("SELECT `id` FROM `fsuggest` WHERE `note` = '%s' AND `uid` = %d LIMIT 1",
				DBA::escape($hash),
				intval(local_user())
			);
			if (DBA::isResult($r)) {
				$fsuggest_id = $contact['id'];
				q("UPDATE `fsuggest` SET `note` = '%s' WHERE `id` = %d AND `uid` = %d",
					DBA::escape($note),
					intval($fsuggest_id),
					intval(local_user())
				);
				Worker::add(PRIORITY_HIGH, 'Notifier', 'suggest', $fsuggest_id);
			}

			info(L10n::t('Friend suggestion sent.') . EOL);
		}
	}
}

function fsuggest_content(App $a)
{
	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc != 2) {
		return;
	}

	$contact_id = intval($a->argv[1]);

	$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user()]);
	if (! DBA::isResult($contact)) {
		notice(L10n::t('Contact not found.') . EOL);
		return;
	}

	$o = '<h3>' . L10n::t('Suggest Friends') . '</h3>';

	$o .= '<div id="fsuggest-desc" >' . L10n::t('Suggest a friend for %s', $contact['name']) . '</div>';

	$o .= '<form id="fsuggest-form" action="fsuggest/' . $contact_id . '" method="post" >';

	$o .= ACL::getSuggestContactSelectHTML(
		'suggest',
		'suggest-select',
		['size' => 4, 'exclude' => $contact_id, 'networks' => 'DFRN_ONLY', 'single' => true]
	);


	$o .= '<div id="fsuggest-submit-wrapper"><input id="fsuggest-submit" type="submit" name="submit" value="' . L10n::t('Submit') . '" /></div>';
	$o .= '</form>';

	return $o;
}
