<?php
/**
 * @file mod/videos.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Protocol\DFRN;
use Friendica\Util\DateTimeFormat;

require_once 'include/items.php';
require_once 'include/security.php';

function videos_init(App $a) {

	if($a->argc > 1)
		DFRN::autoRedir($a, $a->argv[1]);

	if((Config::get('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	Nav::setSelected('home');

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

		$profile = Profile::getByNickname($nick, $a->profile_uid);

		$account_type = Contact::getAccountType($profile);

		$tpl = get_markup_template("vcard-widget.tpl");

		$vcard_widget = replace_macros($tpl, [
			'$name' => $profile['name'],
			'$photo' => $profile['photo'],
			'$addr' => defaults($profile, 'addr', ''),
			'$account_type' => $account_type,
			'$pdesc' => defaults($profile, 'pdesc', ''),
		]);


		/*$sql_extra = permissions_sql($a->data['user']['uid']);

		$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d $sql_extra order by created desc",
			intval($a->data['user']['uid'])
		);

		if(count($albums)) {
			$a->data['albums'] = $albums;

			$albums_visible = ((intval($a->data['user']['hidewall']) && (! local_user()) && (! remote_user())) ? false : true);

			if($albums_visible) {
				$o .= '<div id="sidebar-photos-albums" class="widget">';
				$o .= '<h3>' . '<a href="' . System::baseUrl() . '/photos/' . $a->data['user']['nickname'] . '">' . L10n::t('Photo Albums') . '</a></h3>';

				$o .= '<ul>';
				foreach($albums as $album) {

					// don't show contact photos. We once translated this name, but then you could still access it under
					// a different language setting. Now we store the name in English and check in English (and translated for legacy albums).

					if((! strlen($album['album'])) || ($album['album'] === 'Contact Photos') || ($album['album'] === L10n::t('Contact Photos')))
						continue;
					$o .= '<li>' . '<a href="photos/' . $a->argv[1] . '/album/' . bin2hex($album['album']) . '" >' . $album['album'] . '</a></li>';
				}
				$o .= '</ul>';
			}
			if(local_user() && $a->data['user']['uid'] == local_user()) {
				$o .= '<div id="photo-albums-upload-link"><a href="' . System::baseUrl() . '/photos/' . $a->data['user']['nickname'] . '/upload" >' .L10n::t('Upload New Photos') . '</a></div>';
			}

			$o .= '</div>';
		}*/

		if(! x($a->page,'aside'))
			$a->page['aside'] = '';
		$a->page['aside'] .= $vcard_widget;


		$tpl = get_markup_template("videos_head.tpl");
		$a->page['htmlhead'] .= replace_macros($tpl,[
			'$baseurl' => System::baseUrl(),
		]);

		$tpl = get_markup_template("videos_end.tpl");
		$a->page['end'] .= replace_macros($tpl,[
			'$baseurl' => System::baseUrl(),
		]);

	}

	return;
}



function videos_post(App $a) {

	$owner_uid = $a->data['user']['uid'];

	if (local_user() != $owner_uid) {
		goaway(System::baseUrl() . '/videos/' . $a->data['user']['nickname']);
	}

	if (($a->argc == 2) && x($_POST,'delete') && x($_POST, 'id')) {

		// Check if we should do HTML-based delete confirmation
		if (!x($_REQUEST,'confirm')) {
			if (x($_REQUEST,'canceled')) {
				goaway(System::baseUrl() . '/videos/' . $a->data['user']['nickname']);
			}

			$drop_url = $a->query_string;
			$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), [
				'$method' => 'post',
				'$message' => L10n::t('Do you really want to delete this video?'),
				'$extra_inputs' => [
					['name'=>'id', 'value'=> $_POST['id']],
					['name'=>'delete', 'value'=>'x']
				],
				'$confirm' => L10n::t('Delete Video'),
				'$confirm_url' => $drop_url,
				'$confirm_name' => 'confirm', // Needed so that confirmation will bring us back into this if statement
				'$cancel' => L10n::t('Cancel'),

			]);
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}

		$video_id = $_POST['id'];

		$r = q("SELECT `id`  FROM `attach` WHERE `uid` = %d AND `id` = '%s' LIMIT 1",
			intval(local_user()),
			dbesc($video_id)
		);

		if (DBM::is_result($r)) {
			q("DELETE FROM `attach` WHERE `uid` = %d AND `id` = '%s'",
				intval(local_user()),
				dbesc($video_id)
			);
			$i = q("SELECT `id` FROM `item` WHERE `attach` like '%%attach/%s%%' AND `uid` = %d LIMIT 1",
				dbesc($video_id),
				intval(local_user())
			);

			if (DBM::is_result($i)) {
				Item::deleteById($i[0]['id']);
			}
		}

		goaway(System::baseUrl() . '/videos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}

	goaway(System::baseUrl() . '/videos/' . $a->data['user']['nickname']);

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


	if((Config::get('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	require_once('include/security.php');
	require_once('include/conversation.php');

	if(! x($a->data,'user')) {
		notice(L10n::t('No videos selected') . EOL );
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
				if (DBM::is_result($r)) {
					$can_post = true;
					$contact = $r[0];
					$remote_contact = true;
					$visitor = $contact_id;
				}
			}
		}
	}

	$groups = [];

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
			$groups = Group::getIdsByContactId($contact_id);
			$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
				intval($contact_id),
				intval($owner_uid)
			);
			if (DBM::is_result($r)) {
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
		notice(L10n::t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = permissions_sql($owner_uid, $remote_contact, $groups);

	$o = "";

	// tabs
	$_is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= Profile::getTabs($a, $_is_owner, $a->data['user']['nickname']);

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
	if (DBM::is_result($r)) {
		$a->set_pager_total(count($r));
		$a->set_pager_itemspage(20);
	}

	$r = q("SELECT hash, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`created`) AS `created`,
		ANY_VALUE(`filename`) AS `filename`, ANY_VALUE(`filetype`) as `filetype`
		FROM `attach`
		WHERE `uid` = %d AND filetype LIKE '%%video%%'
		$sql_extra GROUP BY hash ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);



	$videos = [];
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$alt_e = $rr['filename'];
			$name_e = $rr['album'];

			$videos[] = [
				'id'       => $rr['id'],
				'link'     => System::baseUrl() . '/videos/' . $a->data['user']['nickname'] . '/video/' . $rr['resource-id'],
				'title'    => L10n::t('View Video'),
				'src'      => System::baseUrl() . '/attach/' . $rr['id'] . '?attachment=0',
				'alt'      => $alt_e,
				'mime'     => $rr['filetype'],
				'album' => [
					'link'  => System::baseUrl() . '/videos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => L10n::t('View Album'),
				],

			];
		}
	}

	$tpl = get_markup_template('videos_recent.tpl');
	$o .= replace_macros($tpl, [
		'$title'      => L10n::t('Recent Videos'),
		'$can_post'   => $can_post,
		'$upload'     => [L10n::t('Upload New Videos'), System::baseUrl().'/videos/'.$a->data['user']['nickname'].'/upload'],
		'$videos'     => $videos,
		'$delete_url' => (($can_post)?System::baseUrl().'/videos/'.$a->data['user']['nickname']:False)
	]);


	$o .= paginate($a);
	return $o;
}
