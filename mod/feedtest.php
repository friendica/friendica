<?php

/**
 * @file_tag_list_to_file mod/feedtest.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Protocol\Feed;
use Friendica\Util\Network;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';

function feedtest_content(App $a)
{
	if (!local_user()) {
		info(L10n::t('You must be logged in to use this module'));
		return;
	};

	$result = [];
	if (!empty($_REQUEST['url'])) {
		$url = $_REQUEST['url'];

		$importer = DBA::selectFirst('user', [], ['uid' => local_user()]);

		$contact_id = Contact::getIdForURL($url, local_user(), true);

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);

		$ret = Network::curl($contact['poll']);
		$xml = $ret['body'];

		$dummy = null;
		$import_result = Feed::import($xml, $importer, $contact, $dummy, true);

		$result = [
			'input' => text_highlight($xml, 'xml'),
			'output' => var_export($import_result, true),
		];
	}

	$tpl = get_markup_template('feedtest.tpl');
	$o = replace_macros($tpl, [
		'$url'    => ['url', L10n::t('Source URL'), defaults($_REQUEST, 'url', ''), ''],
		'$result' => $result
	]);

	return $o;
}
