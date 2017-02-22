<?php
require_once('include/items.php');
require_once('include/acl_selectors.php');
require_once('include/bbcode.php');
require_once('include/security.php');
require_once('include/redir.php');


function videos_init(App $a) {

	if($a->argc > 1)
		auto_redir($a, $a->argv[1]);

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	nav_set_selected('home');

	$o = '';

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$user = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);

		if(! count($user))
			return;

		$a->data['user'] = $user[0];
		$a->profile_uid = $user[0]['uid'];

		$profile = get_profiledata_by_nick($nick, $a->profile_uid);

		$account_type = account_type($profile);

		$tpl = get_markup_template("vcard-widget.tpl");

		$vcard_widget .= replace_macros($tpl, array(
			'$name' => $profile['name'],
			'$photo' => $profile['photo'],
			'$addr' => (($profile['addr'] != "") ? $profile['addr'] : ""),
			'$account_type' => $account_type,
			'$pdesc' => (($profile['pdesc'] != "") ? $profile['pdesc'] : ""),
		));


		/*$sql_extra = permissions_sql($a->data['user']['uid']);

		$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d $sql_extra order by created desc",
			intval($a->data['user']['uid'])
		);

		if(count($albums)) {
			$a->data['albums'] = $albums;

			$albums_visible = ((intval($a->data['user']['hidewall']) && (! local_user()) && (! remote_user())) ? false : true);

			if($albums_visible) {
				$o .= '<div id="sidebar-photos-albums" class="widget">';
				$o .= '<h3>' . '<a href="' . App::get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '">' . t('Photo Albums') . '</a></h3>';

				$o .= '<ul>';
				foreach($albums as $album) {

					// don't show contact photos. We once translated this name, but then you could still access it under
					// a different language setting. Now we store the name in English and check in English (and translated for legacy albums).

					if((! strlen($album['album'])) || ($album['album'] === 'Contact Photos') || ($album['album'] === t('Contact Photos')))
						continue;
					$o .= '<li>' . '<a href="photos/' . $a->argv[1] . '/album/' . bin2hex($album['album']) . '" >' . $album['album'] . '</a></li>';
				}
				$o .= '</ul>';
			}
			if(local_user() && $a->data['user']['uid'] == local_user()) {
				$o .= '<div id="photo-albums-upload-link"><a href="' . App::get_baseurl() . '/photos/' . $a->data['user']['nickname'] . '/upload" >' .t('Upload New Photos') . '</a></div>';
			}

			$o .= '</div>';
		}*/

		if(! x($a->page,'aside'))
			$a->page['aside'] = '';
		$a->page['aside'] .= $vcard_widget;


		$tpl = get_markup_template("videos_head.tpl");
		$a->page['htmlhead'] .= replace_macros($tpl,array(
			'$baseurl' => App::get_baseurl(),
		));

		$tpl = get_markup_template("videos_end.tpl");
		$a->page['end'] .= replace_macros($tpl,array(
			'$baseurl' => App::get_baseurl(),
		));

	}

	return;
}



function videos_post(App $a) {

	$owner_uid = $a->data['user']['uid'];

	if (local_user() != $owner_uid) {
		goaway(App::get_baseurl() . '/videos/' . $a->data['user']['nickname']);
	}

	if (($a->argc == 2) && x($_POST,'delete') && x($_POST, 'id')) {

		// Check if we should do HTML-based delete confirmation
		if (!x($_REQUEST,'confirm')) {
			if (x($_REQUEST,'canceled')) {
				goaway(App::get_baseurl() . '/videos/' . $a->data['user']['nickname']);
			}

			$drop_url = $a->query_string;
			$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), array(
				'$method' => 'post',
				'$message' => t('Do you really want to delete this video?'),
				'$extra_inputs' => array(
					array('name'=>'id', 'value'=> $_POST['id']),
					array('name'=>'delete', 'value'=>'x')
				),
				'$confirm' => t('Delete Video'),
				'$confirm_url' => $drop_url,
				'$confirm_name' => 'confirm', // Needed so that confirmation will bring us back into this if statement
				'$cancel' => t('Cancel'),

			));
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}

		$video_id = $_POST['id'];

		$r = q("SELECT `id`  FROM `attach` WHERE `uid` = %d AND `id` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($video_id)
		);

		if (dbm::is_result($r)) {
			q("DELETE FROM `attach` WHERE `uid` = %d AND `id` = '%s'",
				intval(local_user()),
				dbesc($video_id)
			);
			$i = q("SELECT * FROM `item` WHERE `attach` like '%%attach/%s%%' AND `uid` = %d LIMIT 1",
				dbesc($video_id),
				intval(local_user())
			);
			//echo "<pre>"; var_dump($i); killme();
			if (dbm::is_result($i)) {
				q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s' WHERE `parent-uri` = '%s' AND `uid` = %d",
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc($i[0]['uri']),
					intval(local_user())
				);
				create_tags_from_itemuri($i[0]['uri'], local_user());
				delete_thread_uri($i[0]['uri'], local_user());

				$url = App::get_baseurl();
				$drop_id = intval($i[0]['id']);

				if ($i[0]['visible']) {
					proc_run(PRIORITY_HIGH, "include/notifier.php", "drop", $drop_id);
				}
			}
		}

		goaway(App::get_baseurl() . '/videos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}

	goaway(App::get_baseurl() . '/videos/' . $a->data['user']['nickname']);

}



function videos_content(App $a) {

	// URLs (most aren't currently implemented):
	// videos/name
	// videos/name/upload
	// videos/name/upload/xxxxx (xxxxx is album name)
	// videos/name/album/xxxxx
	// videos/name/album/xxxxx/edit
	// videos/name/video/xxxxx
	// videos/name/video/xxxxx/edit


	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}


	require_once('include/bbcode.php');
	require_once('include/security.php');
	require_once('include/conversation.php');

	if(! x($a->data,'user')) {
		notice( t('No videos selected') . EOL );
		return;
	}

	//$phototypes = Photo::supportedTypes();

	$_SESSION['video_return'] = $a->cmd;

	//
	// Parse arguments
	//

	if($a->argc > 3) {
		$datatype = $a->argv[2];
		$datum = $a->argv[3];
	}
	elseif(($a->argc > 2) && ($a->argv[2] === 'upload'))
		$datatype = 'upload';
	else
		$datatype = 'summary';

	if($a->argc > 4)
		$cmd = $a->argv[4];
	else
		$cmd = 'view';

	//
	// Setup permissions structures
	//

	$can_post       = false;
	$visitor        = 0;
	$contact        = null;
	$remote_contact = false;
	$contact_id     = 0;

	$owner_uid = $a->data['user']['uid'];

	$community_page = (($a->data['user']['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if((local_user()) && (local_user() == $owner_uid))
		$can_post = true;
	else {
		if($community_page && remote_user()) {
			if(is_array($_SESSION['remote'])) {
				foreach($_SESSION['remote'] as $v) {
					if($v['uid'] == $owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}
			if($contact_id) {

				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($contact_id),
					intval($owner_uid)
				);
				if (dbm::is_result($r)) {
					$can_post = true;
					$contact = $r[0];
					$remote_contact = true;
					$visitor = $contact_id;
				}
			}
		}
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access

	if(remote_user() && (! $visitor)) {
		$contact_id = 0;
		if(is_array($_SESSION['remote'])) {
			foreach($_SESSION['remote'] as $v) {
				if($v['uid'] == $owner_uid) {
					$contact_id = $v['cid'];
					break;
				}
			}
		}
		if($contact_id) {
			$groups = init_groups_visitor($contact_id);
			$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($owner_uid)
			);
			if (dbm::is_result($r)) {
				$contact = $r[0];
				$remote_contact = true;
			}
		}
	}

	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}

	if($a->data['user']['hidewall'] && (local_user() != $owner_uid) && (! $remote_contact)) {
		notice( t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = permissions_sql($owner_uid,$remote_contact,$groups);

	$o = "";

	// tabs
	$_is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= profile_tabs($a,$_is_owner, $a->data['user']['nickname']);

	//
	// dispatch request
	//


	if($datatype === 'upload') {
		return; // no uploading for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	if($datatype === 'album') {

		return; // no albums for now

		// DELETED -- look at mod/photos.php if you want to implement
	}


	if($datatype === 'video') {

		return; // no single video view for now

		// DELETED -- look at mod/photos.php if you want to implement
	}

	// Default - show recent videos (no upload link for now)
	//$o = '';

	$r = q("SELECT hash FROM `attach` WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash",
		intval($a->data['user']['uid'])
	);
	if (dbm::is_result($r)) {
		$a->set_pager_total(count($r));
		$a->set_pager_itemspage(20);
	}

	$r = q("SELECT hash, `id`, `filename`, filetype FROM `attach`
		WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);



	$videos = array();
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			if ($a->theme['template_engine'] === 'internal') {
				$alt_e = template_escape($rr['filename']);
				$name_e = template_escape($rr['album']);
			}
			else {
				$alt_e = $rr['filename'];
				$name_e = $rr['album'];
			}

			$videos[] = array(
				'id'       => $rr['id'],
				'link'     => App::get_baseurl() . '/videos/' . $a->data['user']['nickname'] . '/video/' . $rr['resource-id'],
				'title'    => t('View Video'),
				'src'      => App::get_baseurl() . '/attach/' . $rr['id'] . '?attachment=0',
				'alt'      => $alt_e,
				'mime'     => $rr['filetype'],
				'album' => array(
					'link'  => App::get_baseurl() . '/videos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => t('View Album'),
				),

			);
		}
	}

	$tpl = get_markup_template('videos_recent.tpl');
	$o .= replace_macros($tpl, array(
		'$title'      => t('Recent Videos'),
		'$can_post'   => $can_post,
		'$upload'     => array(t('Upload New Videos'), App::get_baseurl().'/videos/'.$a->data['user']['nickname'].'/upload'),
		'$videos'     => $videos,
		'$delete_url' => (($can_post)?App::get_baseurl().'/videos/'.$a->data['user']['nickname']:False)
	));


	$o .= paginate($a);
	return $o;
}
