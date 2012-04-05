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
			$o .= '<div class="vcard">';
			$o .= '<div class="fn">' . $a->data['contact']['name'] . '</div>';
			$o .= '<div id="profile-photo-wrapper"><img class="photo" style="width: 175px; height: 175px;" src="' . $a->data['contact']['photo'] . '" alt="' . $a->data['contact']['name'] . '" /></div>';
			$o .= '</div>';
			$a->page['aside'] .= $o;

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

	$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `request` = '%s', `confirm` = '%s', `notify` = '%s', `poll` = '%s', `attag` = '%s' 
		WHERE `id` = %d AND `uid` = %d LIMIT 1",
		dbesc($name),
		dbesc($nick),
		dbesc($url),
		dbesc($request),
		dbesc($confirm),
		dbesc($notify),
		dbesc($poll),
		dbesc($attag),
		intval($contact['id']),
		local_user()
	);

	if($photo) {
		logger('mod-crepair: updating photo from ' . $photo);
		require_once("Photo.php");

		$photos = import_profile_photo($photo,local_user(),$contact['id']);

		$x = q("UPDATE `contact` SET `photo` = '%s',
			`thumb` = '%s',
			`micro` = '%s',
			`name-date` = '%s',
			`uri-date` = '%s',
			`avatar-date` = '%s'
			WHERE `id` = %d LIMIT 1
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

	$msg1 = t('Repair Contact Settings');

	$msg2 = t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
	$msg3 = t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

	$o .= '<h2>' . $msg1 . '</h2>';

	$o .= '<div class="error-message">' . $msg2 . EOL . EOL. $msg3 . '</div>';

	$o .= EOL . '<a href="contacts/' . $cid . '">' . t('Return to contact editor') . '</a>' . EOL;

	$tpl = get_markup_template('crepair.tpl');
	$o .= replace_macros($tpl, array(
		'$label_name' => t('Name'),
		'$label_nick' => t('Account Nickname'),
		'$label_attag' => t('@Tagname - overrides Name/Nickname'),
		'$label_url' => t('Account URL'),
		'$label_request' => t('Friend Request URL'),
		'$label_confirm' => t('Friend Confirm URL'),
		'$label_notify' => t('Notification Endpoint URL'),
		'$label_poll' => t('Poll/Feed URL'),
		'$label_photo' => t('New photo from this URL'),
		'$contact_name' => $contact['name'],
		'$contact_nick' => $contact['nick'],
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
