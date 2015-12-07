<?php

function crepair_init(&$a) {
	if(! local_user())
		return;

	$contact_id = 0;

	if(($a->argc == 2) && intval($a->argv[1])) {
		$contact_id = intval($a->argv[1]);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d and `id` = %d LIMIT 1",
			intval(local_user()),
			intval($contact_id)
		);
		if(! count($r)) {
			$contact_id = 0;
		}
	}

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	if($contact_id) {
			$a->data['contact'] = $r[0];
                        $tpl = get_markup_template("vcard-widget.tpl");
                        $vcard_widget .= replace_macros($tpl, array(
                                '$name' => htmlentities($a->data['contact']['name']),
                                '$photo' => $a->data['contact']['photo']
                        ));
			$a->page['aside'] .= $vcard_widget;

	}
}


function crepair_post(&$a) {
	if(! local_user())
		return;

	$cid = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if($cid) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval(local_user())
		);
	}

	if(! count($r))
		return;

	$contact = $r[0];

	$name    = ((x($_POST,'name')) ? $_POST['name'] : $contact['name']);
	$nick    = ((x($_POST,'nick')) ? $_POST['nick'] : '');
	$url     = ((x($_POST,'url')) ? $_POST['url'] : '');
	$request = ((x($_POST,'request')) ? $_POST['request'] : '');
	$confirm = ((x($_POST,'confirm')) ? $_POST['confirm'] : '');
	$notify  = ((x($_POST,'notify')) ? $_POST['notify'] : '');
	$poll    = ((x($_POST,'poll')) ? $_POST['poll'] : '');
	$attag   = ((x($_POST,'attag')) ? $_POST['attag'] : '');
	$photo   = ((x($_POST,'photo')) ? $_POST['photo'] : '');
	$remote_self = ((x($_POST,'remote_self')) ? $_POST['remote_self'] : false);
	$nurl    = normalise_link($url);

	$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `nurl` = '%s', `request` = '%s', `confirm` = '%s', `notify` = '%s', `poll` = '%s', `attag` = '%s' , `remote_self` = %d
		WHERE `id` = %d AND `uid` = %d",
		dbesc($name),
		dbesc($nick),
		dbesc($url),
		dbesc($nurl),
		dbesc($request),
		dbesc($confirm),
		dbesc($notify),
		dbesc($poll),
		dbesc($attag),
		intval($remote_self),
		intval($contact['id']),
		local_user()
	);

	if($photo) {
		logger('mod-crepair: updating photo from ' . $photo);
		require_once("include/Photo.php");

		$photos = import_profile_photo($photo,local_user(),$contact['id']);

		$x = q("UPDATE `contact` SET `photo` = '%s',
			`thumb` = '%s',
			`micro` = '%s',
			`name-date` = '%s',
			`uri-date` = '%s',
			`avatar-date` = '%s'
			WHERE `id` = %d
			",
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact['id'])
		);
	}

	if($r)
		info( t('Contact settings applied.') . EOL);
	else
		notice( t('Contact update failed.') . EOL);


	return;
}



function crepair_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$cid = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	if($cid) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($cid),
			intval(local_user())
		);
	}

	if(! count($r)) {
		notice( t('Contact not found.') . EOL);
		return;
	}

	$contact = $r[0];

	$warning = t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
	$info = t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

	$returnaddr = "contacts/$cid";

	$allow_remote_self = get_config('system','allow_users_remote_self');

	// Disable remote self for everything except feeds.
	// There is an issue when you repeat an item from maybe twitter and you got comments from friendica and twitter
	// Problem is, you couldn't reply to both networks.
	if (!in_array($contact['network'], array(NETWORK_FEED, NETWORK_DFRN, NETWORK_DIASPORA)))
		$allow_remote_self = false;

	if ($contact['network'] == NETWORK_FEED)
		$remote_self_options = array('0'=>t('No mirroring'), '1'=>t('Mirror as forwarded posting'), '2'=>t('Mirror as my own posting'));
	else
		$remote_self_options = array('0'=>t('No mirroring'), '2'=>t('Mirror as my own posting'));

	$update_profile = in_array($contact['network'], array(NETWORK_DFRN, NETWORK_DSPR, NETWORK_OSTATUS));

	$tpl = get_markup_template('crepair.tpl');
	$o .= replace_macros($tpl, array(
		'$title'	=> t('Repair Contact Settings'),
		'$warning'	=> $warning,
		'$info'		=> $info,
		'$returnaddr'	=> $returnaddr,
		'$return'	=> t('Return to contact editor'),
		'$update_profile' => update_profile,
		'$udprofilenow' => t('Refetch contact data'),
		'$label_name' => t('Name'),
		'$label_nick' => t('Account Nickname'),
		'$label_attag' => t('@Tagname - overrides Name/Nickname'),
		'$label_url' => t('Account URL'),
		'$label_request' => t('Friend Request URL'),
		'$label_confirm' => t('Friend Confirm URL'),
		'$label_notify' => t('Notification Endpoint URL'),
		'$label_poll' => t('Poll/Feed URL'),
		'$label_photo' => t('New photo from this URL'),
		'$label_remote_self' => t('Remote Self'),
		'$allow_remote_self' => $allow_remote_self,
		'$remote_self' => array('remote_self',
					t('Mirror postings from this contact'),
					$contact['remote_self'],
					t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
					$remote_self_options
				),
		'$contact_name' => htmlentities($contact['name']),
		'$contact_nick' => htmlentities($contact['nick']),
		'$contact_id'   => $contact['id'],
		'$contact_url'  => $contact['url'],
		'$request'      => $contact['request'],
		'$confirm'      => $contact['confirm'],
		'$notify'       => $contact['notify'],
		'$poll'         => $contact['poll'],
		'$contact_attag'  => $contact['attag'],
		'$lbl_submit'   => t('Submit')
	));

	return $o;

}
