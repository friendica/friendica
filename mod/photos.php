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
use Friendica\Content\Text\BBCode;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Security\Security;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Map;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

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
					'bin2hex'   => bin2hex($album['album'])
				];
				$ret['albums'][] = $entry;
			}
		}

		if (DI::userSession()->getLocalUserId() && $owner['uid'] == DI::userSession()->getLocalUserId()) {
			$can_post = true;
		} else {
			$can_post = false;
		}

		if ($ret['success']) {
			$photo_albums_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('photo_albums.tpl'), [
				'$nick'     => $owner['nickname'],
				'$title'    => DI::l10n()->t('Photo Albums'),
				'$recent'   => DI::l10n()->t('Recent Photos'),
				'$albums'   => $ret['albums'],
				'$upload'   => [DI::l10n()->t('Upload New Photos'), 'photos/' . $owner['nickname'] . '/upload'],
				'$can_post' => $can_post
			]);
		}

		if (!empty($photo_albums_widget)) {
			DI::page()['aside'] .= $photo_albums_widget;
		}

		$tpl = Renderer::getMarkupTemplate("photos_head.tpl");

		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => DI::l10n()->t('everybody')
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
			DI::baseUrl()->redirect('photos/' . $user['nickname'] . '/album');
		}
		$album = hex2bin(DI::args()->getArgv()[3]);

		if (!DBA::exists('photo', ['album' => $album, 'uid' => $page_owner_uid, 'photo-type' => Photo::DEFAULT])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Album not found.'));
			DI::baseUrl()->redirect('photos/' . $user['nickname'] . '/album');
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
					$album
				));
			} else {
				$r = DBA::toArray(DBA::p(
					"SELECT distinct(`resource-id`) AS `rid` FROM `photo` WHERE `uid` = ? AND `album` = ?",
					DI::userSession()->getLocalUserId(),
					$album
				));
			}

			if (DBA::isResult($r)) {
				foreach ($r as $rr) {
					$res[] = $rr['rid'];
				}

				// remove the associated photos
				Photo::delete(['resource-id' => $res, 'uid' => $page_owner_uid]);

				// find and delete the corresponding item with all the comments and likes/dislikes
				Item::deleteForUser(['resource-id' => $res, 'uid' => $page_owner_uid], $page_owner_uid);

				// Update the photo albums cache
				Photo::clearAlbumCache($page_owner_uid);
				DI::sysmsg()->addNotice(DI::l10n()->t('Album successfully deleted'));
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Album was empty.'));
			}
		}

		DI::baseUrl()->redirect('photos/' . $user['nickname'] . '/album');
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

				Item::deleteForUser(['resource-id' => $photo['resource-id'], 'uid' => $page_owner_uid], $page_owner_uid);

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
		$rawtags   = !empty($_POST['newtag'])    ? trim($_POST['newtag'])    : '';
		$item_id   = !empty($_POST['item_id'])   ? intval($_POST['item_id']) : 0;
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
			$ext   = Images::getExtensionByMimeType($photo['type']);
			Photo::update(
				['desc' => $desc, 'album' => $albname, 'allow_cid' => $str_contact_allow, 'allow_gid' => $str_circle_allow, 'deny_cid' => $str_contact_deny, 'deny_gid' => $str_circle_deny],
				['resource-id' => $resource_id, 'uid' => $page_owner_uid]
			);

			// Update the photo albums cache if album name was changed
			if ($albname !== $origaname) {
				Photo::clearAlbumCache($page_owner_uid);
			}
		}

		if (DBA::isResult($photos) && !$item_id) {
			// Create item container
			$title = '';
			$uri   = Item::newURI();

			$arr                  = [];
			$arr['guid']          = System::createUUID();
			$arr['uid']           = $page_owner_uid;
			$arr['uri']           = $uri;
			$arr['post-type']     = Item::PT_IMAGE;
			$arr['wall']          = 1;
			$arr['resource-id']   = $photo['resource-id'];
			$arr['contact-id']    = $owner_record['id'];
			$arr['owner-name']    = $owner_record['name'];
			$arr['owner-link']    = $owner_record['url'];
			$arr['owner-avatar']  = $owner_record['thumb'];
			$arr['author-name']   = $owner_record['name'];
			$arr['author-link']   = $owner_record['url'];
			$arr['author-avatar'] = $owner_record['thumb'];
			$arr['title']         = $title;
			$arr['allow_cid']     = $photo['allow_cid'];
			$arr['allow_gid']     = $photo['allow_gid'];
			$arr['deny_cid']      = $photo['deny_cid'];
			$arr['deny_gid']      = $photo['deny_gid'];
			$arr['visible']       = 0;
			$arr['origin']        = 1;

			$arr['body'] = Images::getBBCodeByResource($photo['resource-id'], $user['nickname'], $photo['scale'], $ext);

			$item_id = Item::insert($arr);
		}

		if ($item_id) {
			$item = Post::selectFirst(['inform', 'uri-id'], ['id' => $item_id, 'uid' => $page_owner_uid]);

			if (DBA::isResult($item)) {
				$old_inform = $item['inform'];
			}
		}

		if (strlen($rawtags)) {
			$inform = '';

			// if the new tag doesn't have a namespace specifier (@foo or #foo) give it a hashtag
			$x = substr($rawtags, 0, 1);
			if ($x !== '@' && $x !== '#') {
				$rawtags = '#' . $rawtags;
			}

			$taginfo = [];
			$tags    = BBCode::getTags($rawtags);

			if (count($tags)) {
				foreach ($tags as $tag) {
					if (strpos($tag, '@') === 0) {
						$profile = '';
						$name    = substr($tag, 1);
						$contact = Contact::getByURL($name);
						if (empty($contact)) {
							$newname = $name;
							if (strrpos($newname, '+')) {
								$tagcid = intval(substr($newname, strrpos($newname, '+') + 1));
							} else {
								$tagcid = 0;
							}

							if ($tagcid) {
								$contact = DBA::selectFirst('contact', [], ['id' => $tagcid, 'uid' => $page_owner_uid]);
							} else {
								$newname = str_replace('_', ' ', $name);

								//select someone from this user's contacts by name
								$contact = DBA::selectFirst('contact', [], ['name' => $newname, 'uid' => $page_owner_uid]);
								if (!DBA::isResult($contact)) {
									//select someone by attag or nick and the name passed in
									$contact = DBA::selectFirst(
										'contact',
										[],
										['(`attag` = ? OR `nick` = ?) AND `uid` = ?', $name, $name, $page_owner_uid],
										['order' => ['attag' => true]]
									);
								}
							}
						}

						if (DBA::isResult($contact)) {
							$newname = $contact['name'];
							$profile = $contact['url'];

							$notify = 'cid:' . $contact['id'];
							if (strlen($inform)) {
								$inform .= ',';
							}
							$inform .= $notify;
						}

						if ($profile) {
							if (!empty($contact)) {
								$taginfo[] = [$newname, $profile, $notify, $contact];
							} else {
								$taginfo[] = [$newname, $profile, $notify, null];
							}

							$profile = str_replace(',', '%2c', $profile);

							if (!empty($item['uri-id'])) {
								Tag::store($item['uri-id'], Tag::MENTION, $newname, $profile);
							}
						}
					} elseif (strpos($tag, '#') === 0) {
						$tagname = substr($tag, 1);
						if (!empty($item['uri-id'])) {
							Tag::store($item['uri-id'], Tag::HASHTAG, $tagname);
						}
					}
				}
			}

			$newinform = $old_inform ?? '';
			if (strlen($newinform) && strlen($inform)) {
				$newinform .= ',';
			}
			$newinform .= $inform;

			$fields    = ['inform' => $newinform, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
			$condition = ['id' => $item_id];
			Item::update($fields, $condition);

			$best = 0;
			foreach ($photos as $scales) {
				if (intval($scales['scale']) == 2) {
					$best = 2;
					break;
				}

				if (intval($scales['scale']) == 4) {
					$best = 4;
					break;
				}
			}

			if (count($taginfo)) {
				foreach ($taginfo as $tagged) {
					$uri = Item::newURI();

					$arr = [
						'guid'          => System::createUUID(),
						'uid'           => $page_owner_uid,
						'uri'           => $uri,
						'wall'          => 1,
						'contact-id'    => $owner_record['id'],
						'owner-name'    => $owner_record['name'],
						'owner-link'    => $owner_record['url'],
						'owner-avatar'  => $owner_record['thumb'],
						'author-name'   => $owner_record['name'],
						'author-link'   => $owner_record['url'],
						'author-avatar' => $owner_record['thumb'],
						'title'         => '',
						'allow_cid'     => $photo['allow_cid'],
						'allow_gid'     => $photo['allow_gid'],
						'deny_cid'      => $photo['deny_cid'],
						'deny_gid'      => $photo['deny_gid'],
						'visible'       => 0,
						'verb'          => Activity::TAG,
						'gravity'       => Item::GRAVITY_PARENT,
						'object-type'   => Activity\ObjectType::PERSON,
						'target-type'   => Activity\ObjectType::IMAGE,
						'inform'        => $tagged[2],
						'origin'        => 1,
						'body'          => DI::l10n()->t('%1$s was tagged in %2$s by %3$s', '[url=' . $tagged[1] . ']' . $tagged[0] . '[/url]', '[url=' . DI::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . ']' . DI::l10n()->t('a photo') . '[/url]', '[url=' . $owner_record['url'] . ']' . $owner_record['name'] . '[/url]') . "\n\n" . '[url=' . DI::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . ']' . '[img]' . DI::baseUrl() . '/photo/' . $photo['resource-id'] . '-' . $best . '.' . $ext . '[/img][/url]' . "\n",
						'object'        => '<object><type>' . Activity\ObjectType::PERSON . '</type><title>' . $tagged[0] . '</title><id>' . $tagged[1] . '/' . $tagged[0] . '</id><link>' . XML::escape('<link rel="alternate" type="text/html" href="' . $tagged[1] . '" />' . "\n"),
						'target'        => '<target><type>' . Activity\ObjectType::IMAGE . '</type><title>' . $photo['desc'] . '</title><id>' . DI::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . '</id><link>' . XML::escape('<link rel="alternate" type="text/html" href="' . DI::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . '" />' . "\n" . '<link rel="preview" type="' . $photo['type'] . '" href="' . DI::baseUrl() . '/photo/' . $photo['resource-id'] . '-' . $best . '.' . $ext . '" />') . '</link></target>',
					];

					if ($tagged[3]) {
						$arr['object'] .= XML::escape('<link rel="photo" type="' . $photo['type'] . '" href="' . $tagged[3]['photo'] . '" />' . "\n");
					}
					$arr['object'] .= '</link></object>' . "\n";

					Item::insert($arr);
				}
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
			'default_upload' => true
		];

		Hook::callAll('photo_upload_form', $ret);

		$default_upload_box    = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_box.tpl'), []);
		$default_upload_submit = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_submit.tpl'), [
			'$submit' => DI::l10n()->t('Submit'),
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
			'$pagename'              => DI::l10n()->t('Upload Photos'),
			'$sessid'                => session_id(),
			'$usage'                 => $usage_message,
			'$nickname'              => $user['nickname'],
			'$newalbum'              => DI::l10n()->t('New album name: '),
			'$existalbumtext'        => DI::l10n()->t('or select existing album:'),
			'$nosharetext'           => DI::l10n()->t('Do not show a status post for this upload'),
			'$albumselect'           => $albumselect,
			'$selname'               => $selname,
			'$permissions'           => DI::l10n()->t('Permissions'),
			'$aclselect'             => $aclselect_e,
			'$lockstate'             => ACL::getLockstateForUserId(DI::userSession()->getLocalUserId()) ? 'lock' : 'unlock',
			'$alt_uploader'          => $ret['addon_text'],
			'$default_upload_box'    => ($ret['default_upload'] ? $default_upload_box : ''),
			'$default_upload_submit' => ($ret['default_upload'] ? $default_upload_submit : ''),
			'$uploadurl'             => $ret['post_url'],

			// ACL permissions box
			'$return_path' => DI::args()->getQueryString(),
		]);

		return $o;
	}

	// Display a single photo album
	if ($datatype === 'album') {
		// if $datum is not a valid hex, redirect to the default page
		if (is_null($datum) || !Strings::isHex($datum)) {
			DI::baseUrl()->redirect('photos/' . $user['nickname'] . '/album');
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
			$album
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
			$pager->getItemsPerPage()
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
					'$nametext'   => DI::l10n()->t('New album name: '),
					'$nickname'   => $user['nickname'],
					'$album'      => $album_e,
					'$hexalbum'   => bin2hex($album),
					'$submit'     => DI::l10n()->t('Submit'),
					'$dropsubmit' => DI::l10n()->t('Delete Album')
				]);
			}
		} elseif ($can_post) {
			$edit = [DI::l10n()->t('Edit Album'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album) . '/edit'];
			$drop = [DI::l10n()->t('Drop Album'), 'photos/' . $user['nickname'] . '/album/' . bin2hex($album) . '/drop'];
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
					'twist' => ' ' . ($twist ? 'rotleft' : 'rotright') . rand(2, 4),
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
			'$upload'   => [DI::l10n()->t('Upload New Photos'), 'photos/' . $user['nickname'] . '/upload/' . bin2hex($album)],
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
					'$nextlink' => $nextlink
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
				$tools['profile'] = ['settings/profile/photo/crop/' . $ph[0]['resource-id'], DI::l10n()->t('Use as profile photo')];
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

		$map       = null;
		$link_item = [];
		$total     = 0;

		// Do we have an item for this photo?

		// FIXME! - replace following code to display the conversation with our normal
		// conversation functions so that it works correctly and tracks changes
		// in the evolving conversation code.
		// The difference is that we won't be displaying the conversation head item
		// as a "post" but displaying instead the photo it is linked to

		$link_item = Post::selectFirst([], ["`resource-id` = ?" . $sql_extra, $datum]);

		if (!empty($link_item['parent']) && !empty($link_item['uid'])) {
			$condition = ["`parent` = ? AND `gravity` = ?",  $link_item['parent'], Item::GRAVITY_COMMENT];
			$total     = Post::count($condition);

			$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

			$params = ['order' => ['id'], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
			$items  = Post::toArray(Post::selectForUser($link_item['uid'], array_merge(Item::ITEM_FIELDLIST, ['author-alias']), $condition, $params));

			if (DI::userSession()->getLocalUserId() == $link_item['uid']) {
				Item::update(['unseen' => false], ['parent' => $link_item['parent']]);
			}
		}

		if (!empty($link_item['coord'])) {
			$map = Map::byCoordinates($link_item['coord']);
		}

		$tags = null;

		if (!empty($link_item['id'])) {
			// parse tags and add links
			$tag_arr = [];
			foreach (explode(',', Tag::getCSVByURIId($link_item['uri-id'])) as $tag_name) {
				if ($tag_name) {
					$tag_arr[] = [
						'name'      => BBCode::toPlaintext($tag_name),
						'removeurl' => 'post/' . $link_item['id'] . '/tag/remove/' . bin2hex($tag_name) . '?return=' . urlencode(DI::args()->getCommand()),
					];
				}
			}
			$tags = ['title' => DI::l10n()->t('Tags: '), 'tags' => $tag_arr];
			if ($cmd === 'edit') {
				$tags['removeanyurl'] = 'post/' . $link_item['id'] . '/tag/remove?return=' . urlencode(DI::args()->getCommand());
				$tags['removetitle']  = DI::l10n()->t('[Select tags to remove]');
			}
		}


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
				'$tags'        => ['newtag', DI::l10n()->t('Add a Tag'), "", DI::l10n()->t('Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping')],
				'$rotate_none' => ['rotate', DI::l10n()->t('Do not rotate'), 0, '', true],
				'$rotate_cw'   => ['rotate', DI::l10n()->t("Rotate CW \x28right\x29"), 1, ''],
				'$rotate_ccw'  => ['rotate', DI::l10n()->t("Rotate CCW \x28left\x29"), 2, ''],

				'$nickname'    => $user['nickname'],
				'$resource_id' => $ph[0]['resource-id'],
				'$permissions' => DI::l10n()->t('Permissions'),
				'$aclselect'   => $aclselect_e,

				'$item_id' => $link_item['id'] ?? 0,
				'$submit'  => DI::l10n()->t('Submit'),
				'$delete'  => DI::l10n()->t('Delete Photo'),

				// ACL permissions box
				'$return_path' => DI::args()->getQueryString(),
			]);
		}

		$like        = '';
		$dislike     = '';
		$likebuttons = '';
		$comments    = '';
		$paginate    = '';

		if (!empty($link_item['id']) && !empty($link_item['uri'])) {
			$cmnt_tpl    = Renderer::getMarkupTemplate('comment_item.tpl');
			$tpl         = Renderer::getMarkupTemplate('photo_item.tpl');
			$return_path = DI::args()->getCommand();
			$addonHelper = DI::addonHelper();

			if (!DBA::isResult($items)) {
				if (($can_post || Security::canWriteToUserWall($owner_uid))) {
					/*
					 * Hmmm, code depending on the presence of a particular addon?
					 * This should be better if done by a hook
					 */
					$qcomment = null;
					if ($addonHelper->isAddonEnabled('qcomment')) {
						$words    = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'qcomment', 'words');
						$qcomment = $words ? explode("\n", $words) : [];
					}

					$comments .= Renderer::replaceMacros($cmnt_tpl, [
						'$return_path' => '',
						'$jsreload'    => $return_path,
						'$id'          => $link_item['id'],
						'$parent'      => $link_item['id'],
						'$profile_uid' => $owner_uid,
						'$mylink'      => $contact['url'],
						'$mytitle'     => DI::l10n()->t('This is you'),
						'$myphoto'     => $contact['thumb'],
						'$comment'     => DI::l10n()->t('Comment'),
						'$submit'      => DI::l10n()->t('Submit'),
						'$preview'     => DI::l10n()->t('Preview'),
						'$loading'     => DI::l10n()->t('Loading...'),
						'$qcomment'    => $qcomment,
						'$rand_num'    => Crypto::randomDigits(12),
					]);
				}
			}

			$conv_responses = [
				'like'        => [],
				'dislike'     => [],
				'attendyes'   => [],
				'attendno'    => [],
				'attendmaybe' => []
			];

			if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'hide_dislike')) {
				unset($conv_responses['dislike']);
			}

			// display comments
			if (DBA::isResult($items)) {
				foreach ($items as $item) {
					DI::conversation()->builtinActivityPuller($item, $conv_responses);
				}

				if (!empty($conv_responses['like'][$link_item['uri']])) {
					$like = DI::conversation()->formatActivity($conv_responses['like'][$link_item['uri']]['links'], 'like', $link_item['id'], '', []);
				}

				if (!empty($conv_responses['dislike'][$link_item['uri']])) {
					$dislike = DI::conversation()->formatActivity($conv_responses['dislike'][$link_item['uri']]['links'], 'dislike', $link_item['id'], '', []);
				}

				if (($can_post || Security::canWriteToUserWall($owner_uid))) {
					/*
					 * Hmmm, code depending on the presence of a particular addon?
					 * This should be better if done by a hook
					 */
					$qcomment = null;
					if ($addonHelper->isAddonEnabled('qcomment')) {
						$words    = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'qcomment', 'words');
						$qcomment = $words ? explode("\n", $words) : [];
					}

					$comments .= Renderer::replaceMacros($cmnt_tpl, [
						'$return_path' => '',
						'$jsreload'    => $return_path,
						'$id'          => $link_item['id'],
						'$parent'      => $link_item['id'],
						'$profile_uid' => $owner_uid,
						'$mylink'      => $contact['url'],
						'$mytitle'     => DI::l10n()->t('This is you'),
						'$myphoto'     => $contact['thumb'],
						'$comment'     => DI::l10n()->t('Comment'),
						'$submit'      => DI::l10n()->t('Submit'),
						'$preview'     => DI::l10n()->t('Preview'),
						'$qcomment'    => $qcomment,
						'$rand_num'    => Crypto::randomDigits(12),
					]);
				}

				foreach ($items as $item) {
					$comment  = '';
					$template = $tpl;

					$activity = DI::activity();

					if (($activity->match($item['verb'], Activity::LIKE) ||
							$activity->match($item['verb'], Activity::DISLIKE)) &&
						($item['gravity'] != Item::GRAVITY_PARENT)
					) {
						continue;
					}

					$author = [
						'uid'     => 0,
						'id'      => $item['author-id'],
						'network' => $item['author-network'],
						'url'     => $item['author-link'],
						'alias'   => $item['author-alias']
					];
					$profile_url = Contact::magicLinkByContact($author);
					if (strpos($profile_url, 'contact/redir/') === 0) {
						$sparkle = ' sparkle';
					} else {
						$sparkle = '';
					}

					$dropping = (($item['contact-id'] == $contact_id) || ($item['uid'] == DI::userSession()->getLocalUserId()));
					$drop     = [
						'dropping' => $dropping,
						'pagedrop' => false,
						'select'   => DI::l10n()->t('Select'),
						'delete'   => DI::l10n()->t('Delete'),
					];

					$title_e = $item['title'];
					$body_e  = BBCode::convertForUriId($item['uri-id'], $item['body']);

					$comments .= Renderer::replaceMacros($template, [
						'$id'          => $item['id'],
						'$profile_url' => $profile_url,
						'$name'        => $item['author-name'],
						'$thumb'       => $item['author-avatar'],
						'$sparkle'     => $sparkle,
						'$title'       => $title_e,
						'$body'        => $body_e,
						'$ago'         => Temporal::getRelativeDate($item['created']),
						'$indent'      => (($item['parent'] != $item['id']) ? ' comment' : ''),
						'$drop'        => $drop,
						'$comment'     => $comment
					]);

					if (($can_post || Security::canWriteToUserWall($owner_uid))) {
						/*
						 * Hmmm, code depending on the presence of a particular addon?
						 * This should be better if done by a hook
						 */
						$qcomment = null;
						if ($addonHelper->isAddonEnabled('qcomment')) {
							$words    = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'qcomment', 'words');
							$qcomment = $words ? explode("\n", $words) : [];
						}

						$comments .= Renderer::replaceMacros($cmnt_tpl, [
							'$return_path' => '',
							'$jsreload'    => $return_path,
							'$id'          => $item['id'],
							'$parent'      => $item['parent'],
							'$profile_uid' => $owner_uid,
							'$mylink'      => $contact['url'],
							'$mytitle'     => DI::l10n()->t('This is you'),
							'$myphoto'     => $contact['thumb'],
							'$comment'     => DI::l10n()->t('Comment'),
							'$submit'      => DI::l10n()->t('Submit'),
							'$preview'     => DI::l10n()->t('Preview'),
							'$qcomment'    => $qcomment,
							'$rand_num'    => Crypto::randomDigits(12),
						]);
					}
				}
			}

			$responses = [];
			foreach ($conv_responses as $verb => $activity) {
				if (isset($activity[$link_item['uri']])) {
					$responses[$verb] = $activity[$link_item['uri']];
				}
			}

			if ($cmd === 'view' && ($can_post || Security::canWriteToUserWall($owner_uid))) {
				$like_tpl    = Renderer::getMarkupTemplate('like_noshare.tpl');
				$likebuttons = Renderer::replaceMacros($like_tpl, [
					'$id'            => $link_item['id'],
					'$like'          => DI::l10n()->t('Like'),
					'$like_title'    => DI::l10n()->t('I like this (toggle)'),
					'$dislike'       => DI::l10n()->t('Dislike'),
					'$wait'          => DI::l10n()->t('Please wait'),
					'$dislike_title' => DI::l10n()->t('I don\'t like this (toggle)'),
					'$hide_dislike'  => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'hide_dislike'),
					'$responses'     => $responses,
					'$return_path'   => DI::args()->getQueryString(),
				]);
			}

			$paginate = $pager->renderFull($total);
		}

		$photo_tpl = Renderer::getMarkupTemplate('photo_view.tpl');
		$o .= Renderer::replaceMacros($photo_tpl, [
			'$id'          => $ph[0]['id'],
			'$album'       => [$album_link, $ph[0]['album']],
			'$tools'       => $tools,
			'$photo'       => $photo,
			'$prevlink'    => $prevlink,
			'$nextlink'    => $nextlink,
			'$desc'        => $ph[0]['desc'],
			'$tags'        => $tags,
			'$edit'        => $edit,
			'$map'         => $map,
			'$map_text'    => DI::l10n()->t('Map'),
			'$likebuttons' => $likebuttons,
			'$like'        => $like,
			'$dislike'     => $dislike,
			'$comments'    => $comments,
			'$paginate'    => $paginate,
		]);

		DI::page()['htmlhead'] .= "\n" . '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:title" content="' . $photo["album"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image" content="' . DI::baseUrl() . "/" . $photo["href"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image:width" content="' . $photo["width"] . '" />' . "\n";
		DI::page()['htmlhead'] .= '<meta name="twitter:image:height" content="' . $photo["height"] . '" />' . "\n";

		return $o;
	}
}
