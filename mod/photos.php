<?php

/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\ACL;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Security\Security;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Strings;

function photos_init()
{
	if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
		return;
	}

	Nav::setSelected('home');

	if (DI::args()->getArgc() > 1) {
		$owner = Profile::load(DI::appHelper(), DI::args()->getArgv()[1], false);
		if (!isset($owner['account_removed']) || $owner['account_removed']) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$albums = Photo::getAlbums($owner['uid']);

		$albums_visible = ((intval($owner['hidewall']) && !DI::userSession()->isAuthenticated()) ? false : true);

		// add various encodings to the array so we can just loop through and pick them out in a template
		$ret = ['success' => false];

		if ($albums) {
			if ($albums_visible) {
				$ret['success'] = true;
			}

			$ret['albums'] = [];
			foreach ($albums as $k => $album) {
				$entry = [
					'text'      => $album['album'],
					'total'     => $album['total'],
					'url'       => 'photos/' . $owner['nickname'] . '/album/' . bin2hex($album['album']),
					'urlencode' => urlencode($album['album']),
					'bin2hex'   => bin2hex($album['album']),
				];
				$ret['albums'][] = $entry;
			}
		}

		if ($ret['success']) {
			$photo_albums_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('photo_albums.tpl'), [
				'$nick'     => $owner['nickname'],
				'$title'    => DI::l10n()->t('Photo Albums'),
				'$recent'   => DI::l10n()->t('Recent Photos'),
				'$albums'   => $ret['albums'],
				'$upload'   => [DI::l10n()->t('Upload photo'), 'photos/' . $owner['nickname'] . '/upload'],
				'$can_post' => (DI::userSession()->getLocalUserId() && $owner['uid'] === DI::userSession()->getLocalUserId()),
			]);
		}

		if (!empty($photo_albums_widget)) {
			DI::page()['aside'] .= $photo_albums_widget;
		}

		$tpl = Renderer::getMarkupTemplate("photos_head.tpl");

		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => DI::l10n()->t('everybody'),
		]);
	}

	return;
}

function photos_post()
{
	$user = User::getByNickname(DI::args()->getArgv()[1]);
	if (!DBA::isResult($user)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	$can_post = false;
	$visitor  = 0;

	$page_owner_uid = intval($user['uid']);
	$community_page = in_array($user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN]);

	if (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(DI::userSession()->getRemoteContactID($page_owner_uid))) {
		$contact_id = DI::userSession()->getRemoteContactID($page_owner_uid);
		$can_post   = true;
		$visitor    = $contact_id;
	}

	if (!$can_post) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		System::exit();
	}

	$owner_record = User::getOwnerDataById($page_owner_uid);

	if (!$owner_record) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Contact information unavailable'));
		DI::logger()->info('photos_post: unable to locate contact record for page owner. uid=' . $page_owner_uid);
		System::exit();
	}

	$aclFormatter      = DI::aclFormatter();
	$str_contact_allow = isset($_REQUEST['contact_allow']) ? $aclFormatter->toString($_REQUEST['contact_allow']) : $owner_record['allow_cid'] ?? '';
	$str_circle_allow  = isset($_REQUEST['circle_allow'])  ? $aclFormatter->toString($_REQUEST['circle_allow'])  : $owner_record['allow_gid'] ?? '';
	$str_contact_deny  = isset($_REQUEST['contact_deny'])  ? $aclFormatter->toString($_REQUEST['contact_deny'])  : $owner_record['deny_cid']  ?? '';
	$str_circle_deny   = isset($_REQUEST['circle_deny'])   ? $aclFormatter->toString($_REQUEST['circle_deny'])   : $owner_record['deny_gid']  ?? '';

	$visibility = $_REQUEST['visibility'] ?? '';
	if ($visibility === 'public') {
		// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
		$str_contact_allow = $str_circle_allow = $str_contact_deny = $str_circle_deny = '';
	} elseif ($visibility === 'custom') {
		// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
		// case that would make it public. So we always append the author's contact id to the allowed contacts.
		// See https://github.com/friendica/friendica/issues/9672
		$str_contact_allow .= $aclFormatter->toString(Contact::getPublicIdByUserId($page_owner_uid));
	}

	if (DI::args()->getArgc() > 3 && DI::args()->getArgv()[2] === 'album') {
		if (!Strings::isHex(DI::args()->getArgv()[3] ?? '')) {
			DI::baseUrl()->redirect('profile/' . $user['nickname'] . '/photos');
		}
		$album = hex2bin(DI::args()->getArgv()[3]);

		if (!DBA::exists('photo', ['album' => $album, 'uid' => $page_owner_uid, 'photo-type' => Photo::DEFAULT])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Album not found.'));
			DI::baseUrl()->redirect('profile/' . $user['nickname'] . '/photos');
			return; // NOTREACHED
		}

		// Check if the user has responded to a delete confirmation query
		if (!empty($_REQUEST['canceled'])) {
			DI::baseUrl()->redirect('photos/' . $user['nickname'] . '/album/' . DI::args()->getArgv()[3]);
		}

		// RENAME photo album
		$newalbum = trim($_POST['albumname'] ?? '');
		if ($newalbum != $album) {
			Photo::update(['album' => $newalbum], ['album' => $album, 'uid' => $page_owner_uid]);
			// Update the photo albums cache
			Photo::clearAlbumCache($page_owner_uid);

			DI::baseUrl()->redirect('photos/' . DI::userSession()->getLocalUserNickname() . '/album/' . bin2hex($newalbum));
			return; // NOTREACHED
		}

		/*
		 * DELETE all photos filed in a given album
		 */
		if (!empty($_POST['dropalbum'])) {
			$res = [];

			// get the list of photos we are about to delete
			if ($visitor) {
				$r = DBA::toArray(DBA::p(
					"SELECT distinct(`resource-id`) AS `rid` FROM `photo` WHERE `contact-id` = ? AND `uid` = ? AND `album` = ?",
					$visitor,
					$page_owner_uid,
					$album,
				));
			} else {
				$r = DBA::toArray(DBA::p(
					"SELECT distinct(`resource-id`) AS `rid` FROM `photo` WHERE `uid` = ? AND `album` = ?",
					DI::userSession()->getLocalUserId(),
					$album,
				));
			}

			if (DBA::isResult($r)) {
				foreach ($r as $rr) {
					$res[] = $rr['rid'];
				}

				// remove the associated photos
				Photo::delete(['resource-id' => $res, 'uid' => $page_owner_uid]);

				// Update the photo albums cache
				Photo::clearAlbumCache($page_owner_uid);
				DI::sysmsg()->addNotice(DI::l10n()->t('Album successfully deleted'));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Album was empty.'));
			}
		}

		DI::baseUrl()->redirect('profile/' . $user['nickname'] . '/photos');
	}

	if (DI::args()->getArgc() > 3 && DI::args()->getArgv()[2] === 'image') {
		// Check if the user has responded to a delete confirmation query for a single photo
		if (!empty($_POST['canceled'])) {
			DI::baseUrl()->redirect('photos/' . DI::args()->getArgv()[1] . '/image/' . DI::args()->getArgv()[3]);
		}

		if (!empty($_POST['delete'])) {
			// same as above but remove single photo
			if ($visitor) {
				$condition = ['contact-id' => $visitor, 'uid' => $page_owner_uid, 'resource-id' => DI::args()->getArgv()[3]];
			} else {
				$condition = ['uid' => DI::userSession()->getLocalUserId(), 'resource-id' => DI::args()->getArgv()[3]];
			}

			$photo = DBA::selectFirst('photo', ['resource-id'], $condition);

			if (DBA::isResult($photo)) {
				Photo::delete(['uid' => $page_owner_uid, 'resource-id' => $photo['resource-id']]);

				// Update the photo albums cache
				Photo::clearAlbumCache($page_owner_uid);
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Failed to delete the photo.'));
				DI::baseUrl()->redirect('photos/' . DI::args()->getArgv()[1] . '/image/' . DI::args()->getArgv()[3]);
			}

			DI::baseUrl()->redirect('profile/' . DI::args()->getArgv()[1] . '/photos');
		}
	}

	if (DI::args()->getArgc() > 2 && (!empty($_POST['desc']) || !empty($_POST['newtag']) || isset($_POST['albname']))) {
		$desc      = !empty($_POST['desc'])      ? trim($_POST['desc'])      : '';
		$albname   = !empty($_POST['albname'])   ? trim($_POST['albname'])   : '';
		$origaname = !empty($_POST['origaname']) ? trim($_POST['origaname']) : '';

		$resource_id = DI::args()->getArgv()[3];

		if (!strlen($albname)) {
			$albname = DateTimeFormat::localNow('Y');
		}

		if (!empty($_POST['rotate']) && (intval($_POST['rotate']) == 1 || intval($_POST['rotate']) == 2)) {
			DI::logger()->debug('rotate');

			$photo = Photo::getPhotoForUser($page_owner_uid, $resource_id);

			if (DBA::isResult($photo)) {
				$image = Photo::getImageForPhoto($photo);

				if ($image->isValid()) {
					$rotate_deg = ((intval($_POST['rotate']) == 1) ? 270 : 90);
					$image->rotate($rotate_deg);

					$width  = $image->getWidth();
					$height = $image->getHeight();

					Photo::update(['height' => $height, 'width' => $width], ['resource-id' => $resource_id, 'uid' => $page_owner_uid, 'scale' => 0], $image);

					if ($width > \Friendica\Util\Proxy::PIXEL_MEDIUM || $height > \Friendica\Util\Proxy::PIXEL_MEDIUM) {
						$image->scaleDown(\Friendica\Util\Proxy::PIXEL_MEDIUM);
						$width  = $image->getWidth();
						$height = $image->getHeight();

						Photo::update(['height' => $height, 'width' => $width], ['resource-id' => $resource_id, 'uid' => $page_owner_uid, 'scale' => 1], $image);
					}

					if ($width > \Friendica\Util\Proxy::PIXEL_SMALL || $height > \Friendica\Util\Proxy::PIXEL_SMALL) {
						$image->scaleDown(\Friendica\Util\Proxy::PIXEL_SMALL);
						$width  = $image->getWidth();
						$height = $image->getHeight();

						Photo::update(['height' => $height, 'width' => $width], ['resource-id' => $resource_id, 'uid' => $page_owner_uid, 'scale' => 2], $image);
					}
				}
			}
		}

		$photos_stmt = DBA::select('photo', [], ['resource-id' => $resource_id, 'uid' => $page_owner_uid], ['order' => ['scale' => true]]);

		$photos = DBA::toArray($photos_stmt);

		if (DBA::isResult($photos)) {
			$photo = $photos[0];
			Photo::update(
				['desc' => $desc, 'album' => $albname, 'allow_cid' => $str_contact_allow, 'allow_gid' => $str_circle_allow, 'deny_cid' => $str_contact_deny, 'deny_gid' => $str_circle_deny],
				['resource-id' => $resource_id, 'uid' => $page_owner_uid],
			);

			// Update the photo albums cache if album name was changed
			if ($albname !== $origaname) {
				Photo::clearAlbumCache($page_owner_uid);
			}
		}

		DI::baseUrl()->redirect($_SESSION['photo_return']);
		return; // NOTREACHED
	}
}

function photos_content()
{
	// URLs:
	// photos/name/upload
	// photos/name/upload/xxxxx (xxxxx is album name)
	// photos/name/album/xxxxx
	// photos/name/album/xxxxx/edit
	// photos/name/album/xxxxx/drop
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit
	// photos/name/image/xxxxx/drop

	$user = User::getByNickname(DI::args()->getArgv()[1] ?? '');
	if (!DBA::isResult($user)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Public access denied.'));
		return;
	}

	if (empty($user)) {
		DI::sysmsg()->addNotice(DI::l10n()->t('No photos selected'));
		return;
	}

	$profile = Profile::getByUID($user['uid']);

	$_SESSION['photo_return'] = DI::args()->getCommand();

	// Parse arguments
	$datum = null;
	if (DI::args()->getArgc() > 3) {
		$datatype = DI::args()->getArgv()[2];
		$datum    = DI::args()->getArgv()[3];
	} elseif ((DI::args()->getArgc() > 2) && (DI::args()->getArgv()[2] === 'upload')) {
		$datatype = 'upload';
	} else {
		$datatype = 'summary';
	}

	if (DI::args()->getArgc() > 4) {
		$cmd = DI::args()->getArgv()[4];
	} else {
		$cmd = 'view';
	}

	// Setup permissions structures
	$can_post       = false;
	$visitor        = 0;
	$contact        = null;
	$remote_contact = false;
	$contact_id     = 0;
	$edit           = '';
	$drop           = '';

	$owner_uid = $user['uid'];

	$community_page = in_array($user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN]);

	if (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(DI::userSession()->getRemoteContactID($owner_uid))) {
		$contact_id = DI::userSession()->getRemoteContactID($owner_uid);
		$contact    = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => $owner_uid, 'blocked' => false, 'pending' => false]);

		if (DBA::isResult($contact)) {
			$can_post       = true;
			$remote_contact = true;
			$visitor        = $contact_id;
		}
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access
	if (!empty(DI::userSession()->getRemoteContactID($owner_uid)) && !$visitor) {
		$contact_id = DI::userSession()->getRemoteContactID($owner_uid);

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => $owner_uid, 'blocked' => false, 'pending' => false]);

		$remote_contact = DBA::isResult($contact);
	}

	if (!$remote_contact && DI::userSession()->getLocalUserId()) {
		$contact_id = $_SESSION['cid'];

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => $owner_uid, 'blocked' => false, 'pending' => false]);
	}

	if ($user['hidewall'] && !DI::userSession()->isAuthenticated()) {
		DI::baseUrl()->redirect('profile/' . $user['nickname'] . '/restricted');
	}

	$sql_extra = Security::getPermissionsSQLByUserId($owner_uid);

	$o = "";

	// tabs
	$is_owner = (DI::userSession()->getLocalUserId() && (DI::userSession()->getLocalUserId() == $owner_uid));
	$o .= BaseProfile::getTabsHTML('photos', $is_owner, $user['nickname'], $profile['hide-friends']);

	// Display upload form
	if ($datatype === 'upload') {
		if (!$can_post) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return;
		}

		// This prevents the photo upload form to return to itself without a hint the picture has been correctly uploaded.
		DI::session()->remove('photo_return');

		$selname = (!is_null($datum) && Strings::isHex($datum)) ? hex2bin($datum) : '';

		$albumselect = ['' => '<current year>'];

		foreach (Photo::getAlbums($owner_uid) as $album) {
			if ($album['album'] === '') {
				continue;
			}

			$albumselect[$album['album']] = $album['album'];
		}

		$uploader = '';

		$ret = [
			'post_url'       => 'profile/' . $user['nickname'] . '/photos',
			'addon_text'     => $uploader,
			'default_upload' => true,
		];

		$eventDispatcher = DI::eventDispatcher();

		$ret = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PHOTO_UPLOAD_FORM, $ret),
		)->getArray();

		// Determine if we're in album context (uploading to a specific album)
		$is_album_context = !empty($selname);

		$default_upload_box    = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_box.tpl'), []);
		$default_upload_submit = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_submit.tpl'), [
			'$submit' => $is_album_context ? DI::l10n()->t('Upload photo to this album') : DI::l10n()->t('Upload selected photo'),
		]);

		// Get the relevant size limits for uploads. Abbreviated var names: MaxImageSize -> mis; upload_max_filesize -> umf
		$mis_bytes = Strings::getBytesFromShorthand(DI::config()->get('system', 'maximagesize'));
		$umf_bytes = Strings::getBytesFromShorthand(ini_get('upload_max_filesize'));

		// Per Friendica definition a value of '0' means unlimited:
		if ($mis_bytes == 0) {
			$mis_bytes = INF;
		}

		// When PHP is configured with upload_max_filesize less than maximagesize provide this lower limit.
		$maximagesize_bytes = (is_numeric($mis_bytes) && ($mis_bytes < $umf_bytes) ? $mis_bytes : $umf_bytes);

		// @todo We may be want to use appropriate binary prefixed dynamically
		$usage_message = DI::l10n()->t('The maximum accepted image size is %s', Strings::formatBytes($maximagesize_bytes));

		$tpl = Renderer::getMarkupTemplate('photos_upload.tpl');

		$aclselect_e = ($visitor ? '' : ACL::getFullSelectorHTML(DI::page(), DI::userSession()->getLocalUserId()));

		$o .= Renderer::replaceMacros($tpl, [
			'$pagename'              => $is_album_context ? DI::l10n()->t('Upload Photos to %s', $selname) : DI::l10n()->t('Upload Photos'),
			'$sessid'                => session_id(),
			'$usage'                 => $usage_message,
			'$nickname'              => $user['nickname'],
			'$albumtext_label'       => DI::l10n()->t('Album name: '),
			'$albumtext_description' => DI::l10n()->t('If you want to add this photo to an album, begin typing its name, and existing albums will be suggested, which you can select. If you choose something new, it will be created.'),
			'$albumselect'           => $albumselect,
			'$selname'               => $selname,
			'$permissions'           => DI::l10n()->t('Permissions'),
			'$aclselect'             => $aclselect_e,
			'$lockstate'             => ACL::getLockstateForUserId(DI::userSession()->getLocalUserId()) ? 'lock' : 'unlock',
			'$alt_uploader'          => $ret['addon_text'],
			'$default_upload_box'    => ($ret['default_upload'] ? $default_upload_box : ''),
			'$default_upload_submit' => ($ret['default_upload'] ? $default_upload_submit : ''),
			'$uploadurl'             => $ret['post_url'],
			'$is_album_context'      => $is_album_context,
			'$preselected_album'     => $selname,

			// ACL permissions box
			'$return_path' => DI::args()->getQueryString(),
		]);

		return $o;
	}

	// Display a single photo album
	if ($datatype === 'album') {
		// if $datum is not a valid hex, redirect to the default page
		if (is_null($datum) || !Strings::isHex($datum)) {
			DI::baseUrl()->redirect('profile/' . $user['nickname'] . '/photos');
		}
		$album = hex2bin($datum);

		if ($can_post && !Photo::exists(['uid' => $owner_uid, 'album' => $album, 'photo-type' => Photo::DEFAULT])) {
			$can_post = false;
		}

		$total = 0;
		$r     = DBA::toArray(DBA::p(
			"SELECT `resource-id`, MAX(`scale`) AS `scale` FROM `photo` WHERE `uid` = ? AND `album` = ?
			AND `scale` <= 4 $sql_extra GROUP BY `resource-id`",
			$owner_uid,
			$album,
		));
		if (DBA::isResult($r)) {
			$total = count($r);
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 20);

		/// @TODO I have seen this many times, maybe generalize it script-wide and encapsulate it?
		$order_field = $_GET['order'] ?? '';
		if ($order_field === 'created') {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$r = DBA::toArray(DBA::p(
			"SELECT `resource-id`, MIN(`id`) AS `id`, MIN(`filename`) AS `filename`,
			MIN(`type`) AS `type`, MAX(`scale`) AS `scale`, MIN(`desc`) AS `desc`,
			MIN(`created`) AS `created`
			FROM `photo` WHERE `uid` = ? AND `album` = ?
			AND `scale` <= 4 $sql_extra GROUP BY `resource-id` ORDER BY `created` $order LIMIT ? , ?",
			intval($owner_uid),
			DBA::escape($album),
			$pager->getStart(),
			$pager->getItemsPerPage(),
		));

		if ($cmd === 'drop') {
			$drop_url = DI::args()->getQueryString();

			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$l10n' => [
					'message' => DI::l10n()->t('Do you really want to delete this photo album and all its photos?'),
					'confirm' => DI::l10n()->t('Delete Album'),
					'cancel'  => DI::l10n()->t('Cancel'),
				],
				'$method'        => 'post',
				'$confirm_url'   => $drop_url,
				'$confirm_name'  => 'dropalbum',
				'$confirm_value' => 'dropalbum',
			]);
		}

		// edit album name
		if ($cmd === 'edit') {
			if ($can_post) {
				$edit_tpl = Renderer::getMarkupTemplate('album_edit.tpl');

				$album_e = $album;

				$o .= Renderer::replaceMacros($edit_tpl, [
					'$nametext' => DI::l10n()->t('New album name: '),
					'$nickname' => $user['nickname'],
					'$album'    => $album_e,
					'$hexalbum' => bin2hex($album),
					'$submit'   => DI::l10n()->t('Save changes'),
				]);
			}
		} elseif ($can_post) {
			$edit = [DI::l10n()->t('Edit Album'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album) . '/edit'];
			$drop = [DI::l10n()->t('Delete album'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album) . '/drop'];
		}

		if ($order_field === 'created') {
			$order = [DI::l10n()->t('Show Newest First'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album), 'oldest'];
		} else {
			$order = [DI::l10n()->t('Show Oldest First'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album) . '?order=created', 'newest'];
		}

		$photos = [];

		if (DBA::isResult($r)) {
			// "Twist" is only used for the duepunto theme with style "slackr"
			$twist = false;
			foreach ($r as $rr) {
				$twist = !$twist;

				$ext = Images::getExtensionByMimeType($rr['type']);

				$imgalt_e = $rr['filename'];
				$desc_e   = $rr['desc'];

				$photos[] = [
					'id'    => $rr['id'],
					'twist' => ' ' . ($twist ? 'rotleft' : 'rotright') . random_int(2, 4),
					'link'  => 'photos/' . $user['nickname'] . '/image/' . $rr['resource-id']
						. ($order_field === 'created' ? '?order=created' : ''),
					'title' => DI::l10n()->t('View Photo'),
					'src'   => 'photo/' . $rr['resource-id'] . '-' . $rr['scale'] . $ext,
					'alt'   => $imgalt_e,
					'desc'  => $desc_e,
					'ext'   => $ext,
					'hash'  => $rr['resource-id'],
				];
			}
		}

		$tpl = Renderer::getMarkupTemplate('photo_album.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$photos'   => $photos,
			'$album'    => $album,
			'$can_post' => $can_post,
			'$upload'   => [DI::l10n()->t('Upload photo'), 'photos/' . $user['nickname'] . '/upload/' . bin2hex($album)],
			'$order'    => $order,
			'$edit'     => $edit,
			'$drop'     => $drop,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}

	// Display one photo
	if ($datatype === 'image') {
		// fetch image, item containing image, then comments
		$ph = Photo::selectToArray([], ["`uid` = ? AND `resource-id` = ? " . $sql_extra, $owner_uid, $datum], ['order' => ['scale']]);

		if (!DBA::isResult($ph)) {
			if (DBA::exists('photo', ['resource-id' => $datum, 'uid' => $owner_uid])) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied. Access to this item may be restricted.'));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Photo not available'));
			}
			return;
		}

		if ($cmd === 'drop') {
			$drop_url = DI::args()->getQueryString();

			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$l10n' => [
					'message' => DI::l10n()->t('Do you really want to delete this photo?'),
					'confirm' => DI::l10n()->t('Delete Photo'),
					'cancel'  => DI::l10n()->t('Cancel'),
				],
				'$method'        => 'post',
				'$confirm_url'   => $drop_url,
				'$confirm_name'  => 'delete',
				'$confirm_value' => 'delete',
			]);
		}

		$prevlink = '';
		$nextlink = '';

		/*
		 * @todo This query is totally bad, the whole functionality has to be changed
		 * The query leads to a really intense used index.
		 * By now we hide it if someone wants to.
		 */
		if ($cmd === 'view' && !DI::config()->get('system', 'no_count', false)) {
			$order_field = $_GET['order'] ?? '';

			if ($order_field === 'created') {
				$params = ['order' => [$order_field]];
			} elseif (!empty($order_field) && DBStructure::existsColumn('photo', [$order_field])) {
				$params = ['order' => [$order_field => true]];
			} else {
				$params = [];
			}

			$prvnxt = Photo::selectToArray(['resource-id'], ["`album` = ? AND `uid` = ? AND `scale` = ?" . $sql_extra, $ph[0]['album'], $owner_uid, 0], $params);

			if (DBA::isResult($prvnxt)) {
				$prv = null;
				$nxt = null;
				foreach ($prvnxt as $z => $entry) {
					if ($entry['resource-id'] == $ph[0]['resource-id']) {
						$prv = $order_field === 'created' ? $z - 1 : $z + 1;
						$nxt = $order_field === 'created' ? $z + 1 : $z - 1;
						if ($prv < 0) {
							$prv = count($prvnxt) - 1;
						}
						if ($nxt < 0) {
							$nxt = count($prvnxt) - 1;
						}
						if ($prv >= count($prvnxt)) {
							$prv = 0;
						}
						if ($nxt >= count($prvnxt)) {
							$nxt = 0;
						}
						break;
					}
				}

				if (!is_null($prv)) {
					$prevlink = 'photos/' . $user['nickname'] . '/image/' . $prvnxt[$prv]['resource-id'] . ($order_field === 'created' ? '?order=created' : '');
				}
				if (!is_null($nxt)) {
					$nextlink = 'photos/' . $user['nickname'] . '/image/' . $prvnxt[$nxt]['resource-id'] . ($order_field === 'created' ? '?order=created' : '');
				}

				$tpl = Renderer::getMarkupTemplate('photo_edit_head.tpl');
				DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
					'$prevlink' => $prevlink,
					'$nextlink' => $nextlink,
				]);

				if ($prevlink) {
					$prevlink = [$prevlink, '<div class="icon prev"></div>'];
				}

				if ($nextlink) {
					$nextlink = [$nextlink, '<div class="icon next"></div>'];
				}
			}
		}

		if (count($ph) == 1) {
			$hires = $lores = $ph[0];
		}

		if (count($ph) > 1) {
			if ($ph[1]['scale'] == 2) {
				// original is 640 or less, we can display it directly
				$hires = $lores = $ph[0];
			} else {
				$hires = $ph[0];
				$lores = $ph[1];
			}
		}

		$album_link = 'photos/' . $user['nickname'] . '/album/' . bin2hex($ph[0]['album']);

		$tools = null;

		if ($can_post && ($ph[0]['uid'] == $owner_uid)) {
			$tools = [];
			if ($cmd === 'edit') {
				$tools['view'] = ['photos/' . $user['nickname'] . '/image/' . $datum, DI::l10n()->t('View photo')];
			} else {
				$tools['edit']    = ['photos/' . $user['nickname'] . '/image/' . $datum . '/edit', DI::l10n()->t('Edit photo')];
				$tools['delete']  = ['photos/' . $user['nickname'] . '/image/' . $datum . '/drop', DI::l10n()->t('Delete photo')];
				$tools['profile'] = ['settings/profile/photo/crop/' . $ph[0]['resource-id'], DI::l10n()->t('Use as profile picture')];
			}

			if (
				$ph[0]['uid'] == DI::userSession()->getLocalUserId()
				&& (strlen($ph[0]['allow_cid']) || strlen($ph[0]['allow_gid']) || strlen($ph[0]['deny_cid']) || strlen($ph[0]['deny_gid']))
			) {
				$tools['lock'] = DI::l10n()->t('Private Photo');
			}
		}

		$photo = [
			'href'     => 'photo/' . $hires['resource-id'] . '-' . $hires['scale'] . Images::getExtensionByMimeType($hires['type']),
			'title'    => DI::l10n()->t('View Full Size'),
			'src'      => 'photo/' . $lores['resource-id'] . '-' . $lores['scale'] . Images::getExtensionByMimeType($lores['type']) . '?_u=' . DateTimeFormat::utcNow('ymdhis'),
			'height'   => $hires['height'],
			'width'    => $hires['width'],
			'album'    => $hires['album'],
			'filename' => $hires['filename'],
		];

		$total = 0;

		// Do we have an item for this photo?

		// FIXME! - replace following code to display the conversation with our normal
		// conversation functions so that it works correctly and tracks changes
		// in the evolving conversation code.
		// The difference is that we won't be displaying the conversation head item
		// as a "post" but displaying instead the photo it is linked to


		$edit = null;
		if ($cmd === 'edit' && $can_post) {
			$edit_tpl = Renderer::getMarkupTemplate('photo_edit.tpl');

			$album_e     = $ph[0]['album'];
			$caption_e   = $ph[0]['desc'];
			$aclselect_e = ACL::getFullSelectorHTML(DI::page(), DI::userSession()->getLocalUserId(), false, ACL::getDefaultUserPermissions($ph[0]));

			$edit = Renderer::replaceMacros($edit_tpl, [
				'$id'          => $ph[0]['id'],
				'$album'       => ['albname', DI::l10n()->t('New album name'), $album_e, ''],
				'$caption'     => ['desc', DI::l10n()->t('Caption'), $caption_e, ''],
				'$rotate_none' => ['rotate', DI::l10n()->t('Do not rotate'), 0, '', true],
				'$rotate_cw'   => ['rotate', DI::l10n()->t("Rotate CW \x28right\x29"), 1, ''],
				'$rotate_ccw'  => ['rotate', DI::l10n()->t("Rotate CCW \x28left\x29"), 2, ''],

				'$nickname'    => $user['nickname'],
				'$resource_id' => $ph[0]['resource-id'],
				'$permissions' => DI::l10n()->t('Permissions'),
				'$aclselect'   => $aclselect_e,

				'$submit' => DI::l10n()->t('Save changes'),
				'$delete' => DI::l10n()->t('Delete Photo'),

				// ACL permissions box
				'$return_path' => DI::args()->getQueryString(),
			]);
		}

		$photo_tpl = Renderer::getMarkupTemplate('photo_view.tpl');
		$o .= Renderer::replaceMacros($photo_tpl, [
			'$id'                          => $ph[0]['id'],
			'$album'                       => [$album_link, $ph[0]['album']],
			'$tools'                       => $tools,
			'$photo'                       => $photo,
			'$prevlink'                    => $prevlink,
			'$nextlink'                    => $nextlink,
			'$desc'                        => $ph[0]['desc'],
			'$edit'                        => $edit,
			'$edit_text'                   => DI::l10n()->t('Edit'),
			'$delete_text'                 => DI::l10n()->t('Delete'),
			'$use_as_profile_picture_text' => DI::l10n()->t('Use as profile picture'),
			'$back_text'                   => DI::l10n()->t('Back to viewing'),
		]);

		DI::page()['htmlhead'] .= "\n" . '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:title" content="' . $photo["album"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image" content="' . DI::baseUrl() . "/" . $photo["href"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image:width" content="' . $photo["width"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image:height" content="' . $photo["height"] . '" />' . "\n";

		return $o;
	}
}
