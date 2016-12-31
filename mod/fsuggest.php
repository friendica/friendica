<?php


function fsuggest_post(&$a) {

	if (! local_user()) {
		return;
	}

	if ($a->argc != 2) {
		return;
	}

	$contact_id = intval($a->argv[1]);

	$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);
	if (! dbm::is_result($r)) {
		notice( t('Contact not found.') . EOL);
		return;
	}
	$contact = $r[0];

	$new_contact = intval($_POST['suggest']);

	$hash = random_string();

	$note = escape_tags(trim($_POST['note']));

	if($new_contact) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($new_contact),
			intval(local_user())
		);
		if (dbm::is_result($r)) {

			$x = q("INSERT INTO `fsuggest` ( `uid`,`cid`,`name`,`url`,`request`,`photo`,`note`,`created`)
				VALUES ( %d, %d, '%s','%s','%s','%s','%s','%s')",
				intval(local_user()),
				intval($contact_id),
				dbesc($r[0]['name']), 
				dbesc($r[0]['url']), 
				dbesc($r[0]['request']), 
				dbesc($r[0]['photo']), 
				dbesc($hash), 
				dbesc(datetime_convert())
			);
			$r = q("SELECT `id` FROM `fsuggest` WHERE `note` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($hash),
				intval(local_user())
			);
			if (dbm::is_result($r)) {
				$fsuggest_id = $r[0]['id'];
				q("UPDATE `fsuggest` SET `note` = '%s' WHERE `id` = %d AND `uid` = %d",
					dbesc($note),
					intval($fsuggest_id),
					intval(local_user())
				);
				proc_run(PRIORITY_HIGH, 'include/notifier.php', 'suggest', $fsuggest_id);
			}

			info( t('Friend suggestion sent.') . EOL);
		}

	}


}



function fsuggest_content(&$a) {

	require_once('include/acl_selectors.php');

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc != 2)
		return;

	$contact_id = intval($a->argv[1]);

	$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($contact_id),
		intval(local_user())
	);
	if (! dbm::is_result($r)) {
		notice( t('Contact not found.') . EOL);
		return;
	}
	$contact = $r[0];

	$o = '<h3>' . t('Suggest Friends') . '</h3>';

	$o .= '<div id="fsuggest-desc" >' . sprintf( t('Suggest a friend for %s'), $contact['name']) . '</div>';

	$o .= '<form id="fsuggest-form" action="fsuggest/' . $contact_id . '" method="post" >';

	$o .= contact_selector('suggest','suggest-select', false, 
		array('size' => 4, 'exclude' => $contact_id, 'networks' => 'DFRN_ONLY', 'single' => true));


	$o .= '<div id="fsuggest-submit-wrapper"><input id="fsuggest-submit" type="submit" name="submit" value="' . t('Submit') . '" /></div>';
	$o .= '</form>';

	return $o;
}
