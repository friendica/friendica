<?php
/**
 * @file mod/photos.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Protocol\Activity;
use Friendica\Registry\App as A;
use Friendica\Registry\Protocol;
use Friendica\Registry\Util;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Map;
use Friendica\Util\Security;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

function photos_init(App $a) {

	if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
		return;
	}

	Nav::setSelected('home');

	if ($a->argc > 1) {
		$nick = $a->argv[1];
		$user = DBA::selectFirst('user', [], ['nickname' => $nick, 'blocked' => false]);

		if (!DBA::isResult($user)) {
			return;
		}

		$a->data['user'] = $user;
		$a->profile_uid = $user['uid'];
		$is_owner = (local_user() && (local_user() == $a->profile_uid));

		$profile = Profile::getByNickname($nick, $a->profile_uid);

		$account_type = Contact::getAccountType($profile);

		$tpl = Renderer::getMarkupTemplate("widget/vcard.tpl");

		$vcard_widget = Renderer::replaceMacros($tpl, [
			'$name' => $profile['name'],
			'$photo' => $profile['photo'],
			'$addr' => $profile['addr'] ?? '',
			'$account_type' => $account_type,
			'$pdesc' => $profile['pdesc'] ?? '',
		]);

		$albums = Photo::getAlbums($a->data['user']['uid']);

		$albums_visible = ((intval($a->data['user']['hidewall']) && !Session::isAuthenticated()) ? false : true);

		// add various encodings to the array so we can just loop through and pick them out in a template
		$ret = ['success' => false];

		if ($albums) {
			$a->data['albums'] = $albums;

			if ($albums_visible) {
				$ret['success'] = true;
			}

			$ret['albums'] = [];
			foreach ($albums as $k => $album) {
				//hide profile photos to others
				if (!$is_owner && !Session::getRemoteContactID($a->profile_uid) && ($album['album'] == L10n::t('Profile Photos')))
					continue;
				$entry = [
					'text'      => $album['album'],
					'total'     => $album['total'],
					'url'       => 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album['album']),
					'urlencode' => urlencode($album['album']),
					'bin2hex'   => bin2hex($album['album'])
				];
				$ret['albums'][] = $entry;
			}
		}

		if (local_user() && $a->data['user']['uid'] == local_user()) {
			$can_post = true;
		} else {
			$can_post = false;
		}

		if ($ret['success']) {
			$photo_albums_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('photo_albums.tpl'), [
				'$nick'     => $a->data['user']['nickname'],
				'$title'    => L10n::t('Photo Albums'),
				'$recent'   => L10n::t('Recent Photos'),
				'$albums'   => $ret['albums'],
				'$upload'   => [L10n::t('Upload New Photos'), 'photos/' . $a->data['user']['nickname'] . '/upload'],
				'$can_post' => $can_post
			]);
		}

		if (empty(A::page()['aside'])) {
			A::page()['aside'] = '';
		}

		A::page()['aside'] .= $vcard_widget;

		if (!empty($photo_albums_widget)) {
			A::page()['aside'] .= $photo_albums_widget;
		}

		$tpl = Renderer::getMarkupTemplate("photos_head.tpl");

		A::page()['htmlhead'] .= Renderer::replaceMacros($tpl,[
			'$ispublic' => L10n::t('everybody')
		]);
	}

	return;
}

function photos_post(App $a)
{
	Logger::log('mod-photos: photos_post: begin' , Logger::DEBUG);
	Logger::log('mod_photos: REQUEST ' . print_r($_REQUEST, true), Logger::DATA);
	Logger::log('mod_photos: FILES '   . print_r($_FILES, true), Logger::DATA);

	$phototypes = Images::supportedTypes();

	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid = intval($a->data['user']['uid']);
	$community_page = $a->data['user']['page-flags'] == User::PAGE_FLAGS_COMMUNITY;

	if (local_user() && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($page_owner_uid))) {
		$contact_id = Session::getRemoteContactID($page_owner_uid);
		$can_post = true;
		$visitor = $contact_id;
	}

	if (!$can_post) {
		notice(L10n::t('Permission denied.') . EOL);
		exit();
	}

	$owner_record = User::getOwnerDataById($page_owner_uid);

	if (!$owner_record) {
		notice(L10n::t('Contact information unavailable') . EOL);
		Logger::log('photos_post: unable to locate contact record for page owner. uid=' . $page_owner_uid);
		exit();
	}

	if ($a->argc > 3 && $a->argv[2] === 'album') {
		if (!Strings::isHex($a->argv[3])) {
			A::baseUrl()->redirect('photos/' . $a->data['user']['nickname'] . '/album');
		}
		$album = hex2bin($a->argv[3]);

		if ($album === L10n::t('Profile Photos') || $album === 'Contact Photos' || $album === L10n::t('Contact Photos')) {
			A::baseUrl()->redirect($_SESSION['photo_return']);
			return; // NOTREACHED
		}

		$r = q("SELECT `album` FROM `photo` WHERE `album` = '%s' AND `uid` = %d",
			DBA::escape($album),
			intval($page_owner_uid)
		);

		if (!DBA::isResult($r)) {
			notice(L10n::t('Album not found.') . EOL);
			A::baseUrl()->redirect($_SESSION['photo_return']);
			return; // NOTREACHED
		}

		// Check if the user has responded to a delete confirmation query
		if (!empty($_REQUEST['canceled'])) {
			A::baseUrl()->redirect($_SESSION['photo_return']);
		}

		// RENAME photo album
		$newalbum = Strings::escapeTags(trim($_POST['albumname']));
		if ($newalbum != $album) {
			q("UPDATE `photo` SET `album` = '%s' WHERE `album` = '%s' AND `uid` = %d",
				DBA::escape($newalbum),
				DBA::escape($album),
				intval($page_owner_uid)
			);
			// Update the photo albums cache
			Photo::clearAlbumCache($page_owner_uid);

			A::baseUrl()->redirect('photos/' . $a->user['nickname'] . '/album/' . bin2hex($newalbum));
			return; // NOTREACHED
		}

		/*
		 * DELETE all photos filed in a given album
		 */
		if (!empty($_POST['dropalbum'])) {
			$res = [];

			// get the list of photos we are about to delete
			if ($visitor) {
				$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d AND `album` = '%s'",
					intval($visitor),
					intval($page_owner_uid),
					DBA::escape($album)
				);
			} else {
				$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `uid` = %d AND `album` = '%s'",
					intval(local_user()),
					DBA::escape($album)
				);
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
				notice(L10n::t('Album successfully deleted'));
			} else {
				notice(L10n::t('Album was empty.'));
			}
		}

		A::baseUrl()->redirect('photos/' . $a->argv[1]);
	}

	if ($a->argc > 3 && $a->argv[2] === 'image') {
		// Check if the user has responded to a delete confirmation query for a single photo
		if (!empty($_POST['canceled'])) {
			A::baseUrl()->redirect('photos/' . $a->argv[1] . '/image/' . $a->argv[3]);
		}

		if (!empty($_POST['delete'])) {
			// same as above but remove single photo
			if ($visitor) {
				$condition = ['contact-id' => $visitor, 'uid' => $page_owner_uid, 'resource-id' => $a->argv[3]];

			} else {
				$condition = ['uid' => local_user(), 'resource-id' => $a->argv[3]];
			}

			$photo = DBA::selectFirst('photo', ['resource-id'], $condition);

			if (DBA::isResult($photo)) {
				Photo::delete(['uid' => $page_owner_uid, 'resource-id' => $photo['resource-id']]);

				Item::deleteForUser(['resource-id' => $photo['resource-id'], 'uid' => $page_owner_uid], $page_owner_uid);

				// Update the photo albums cache
				Photo::clearAlbumCache($page_owner_uid);
				notice('Successfully deleted the photo.');
			} else {
				notice('Failed to delete the photo.');
				A::baseUrl()->redirect('photos/' . $a->argv[1] . '/image/' . $a->argv[3]);
			}

			A::baseUrl()->redirect('photos/' . $a->argv[1]);
			return; // NOTREACHED
		}
	}

	if ($a->argc > 2 && (!empty($_POST['desc']) || !empty($_POST['newtag']) || isset($_POST['albname']))) {
		$desc        = !empty($_POST['desc'])      ? Strings::escapeTags(trim($_POST['desc']))      : '';
		$rawtags     = !empty($_POST['newtag'])    ? Strings::escapeTags(trim($_POST['newtag']))    : '';
		$item_id     = !empty($_POST['item_id'])   ? intval($_POST['item_id'])                      : 0;
		$albname     = !empty($_POST['albname'])   ? Strings::escapeTags(trim($_POST['albname']))   : '';
		$origaname   = !empty($_POST['origaname']) ? Strings::escapeTags(trim($_POST['origaname'])) : '';

		$aclFormatter = Util::aclFormatter();

		$str_group_allow   = !empty($_POST['group_allow'])   ? $aclFormatter->toString($_POST['group_allow'])   : '';
		$str_contact_allow = !empty($_POST['contact_allow']) ? $aclFormatter->toString($_POST['contact_allow']) : '';
		$str_group_deny    = !empty($_POST['group_deny'])    ? $aclFormatter->toString($_POST['group_deny'])    : '';
		$str_contact_deny  = !empty($_POST['contact_deny'])  ? $aclFormatter->toString($_POST['contact_deny'])  : '';

		$resource_id = $a->argv[3];

		if (!strlen($albname)) {
			$albname = DateTimeFormat::localNow('Y');
		}

		if (!empty($_POST['rotate']) && (intval($_POST['rotate']) == 1 || intval($_POST['rotate']) == 2)) {
			Logger::log('rotate');

			$photo = Photo::getPhotoForUser($page_owner_uid, $resource_id);

			if (DBA::isResult($photo)) {
				$image = Photo::getImageForPhoto($photo);

				if ($image->isValid()) {
					$rotate_deg = ((intval($_POST['rotate']) == 1) ? 270 : 90);
					$image->rotate($rotate_deg);

					$width  = $image->getWidth();
					$height = $image->getHeight();

					Photo::update(['height' => $height, 'width' => $width], ['resource-id' => $resource_id, 'uid' => $page_owner_uid, 'scale' => 0], $image);

					if ($width > 640 || $height > 640) {
						$image->scaleDown(640);
						$width  = $image->getWidth();
						$height = $image->getHeight();

						Photo::update(['height' => $height, 'width' => $width], ['resource-id' => $resource_id, 'uid' => $page_owner_uid, 'scale' => 1], $image);
					}

					if ($width > 320 || $height > 320) {
						$image->scaleDown(320);
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
			$ext = $phototypes[$photo['type']];
			Photo::update(
				['desc' => $desc, 'album' => $albname, 'allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow, 'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny],
				['resource-id' => $resource_id, 'uid' => $page_owner_uid]
			);

			// Update the photo albums cache if album name was changed
			if ($albname !== $origaname) {
				Photo::clearAlbumCache($page_owner_uid);
			}
			/* Don't make the item visible if the only change was the album name */

			$visibility = 0;
			if ($photo['desc'] !== $desc || strlen($rawtags)) {
				$visibility = 1;
			}
		}

		if (DBA::isResult($photos) && !$item_id) {
			// Create item container
			$title = '';
			$uri = Item::newURI($page_owner_uid);

			$arr = [];
			$arr['guid']          = System::createUUID();
			$arr['uid']           = $page_owner_uid;
			$arr['uri']           = $uri;
			$arr['parent-uri']    = $uri;
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
			$arr['visible']       = $visibility;
			$arr['origin']        = 1;

			$arr['body']          = '[url=' . A::baseUrl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $photo['resource-id'] . ']'
						. '[img]' . A::baseUrl() . '/photo/' . $photo['resource-id'] . '-' . $photo['scale'] . '.' . $ext . '[/img]'
						. '[/url]';

			$item_id = Item::insert($arr);
		}

		if ($item_id) {
			$item = Item::selectFirst(['tag', 'inform'], ['id' => $item_id, 'uid' => $page_owner_uid]);

			if (DBA::isResult($item)) {
				$old_tag    = $item['tag'];
				$old_inform = $item['inform'];
			}
		}

		if (strlen($rawtags)) {
			$str_tags = '';
			$inform   = '';

			// if the new tag doesn't have a namespace specifier (@foo or #foo) give it a hashtag
			$x = substr($rawtags, 0, 1);
			if ($x !== '@' && $x !== '#') {
				$rawtags = '#' . $rawtags;
			}

			$taginfo = [];
			$tags = BBCode::getTags($rawtags);

			if (count($tags)) {
				foreach ($tags as $tag) {
					if (strpos($tag, '@') === 0) {
						$profile = '';
						$contact = null;
						$name = substr($tag,1);

						if ((strpos($name, '@')) || (strpos($name, 'http://'))) {
							$newname = $name;
							$links = @Probe::lrdd($name);

							if (count($links)) {
								foreach ($links as $link) {
									if ($link['@attributes']['rel'] === 'http://webfinger.net/rel/profile-page') {
										$profile = $link['@attributes']['href'];
									}

									if ($link['@attributes']['rel'] === 'salmon') {
										$salmon = '$url:' . str_replace(',', '%sc', $link['@attributes']['href']);

										if (strlen($inform)) {
											$inform .= ',';
										}

										$inform .= $salmon;
									}
								}
							}

							$taginfo[] = [$newname, $profile, $salmon];
						} else {
							$newname = $name;
							$tagcid = 0;

							if (strrpos($newname, '+')) {
								$tagcid = intval(substr($newname, strrpos($newname, '+') + 1));
							}

							if ($tagcid) {
								$contact = DBA::selectFirst('contact', [], ['id' => $tagcid, 'uid' => $page_owner_uid]);
							} else {
								$newname = str_replace('_',' ',$name);

								//select someone from this user's contacts by name
								$contact = DBA::selectFirst('contact', [], ['name' => $newname, 'uid' => $page_owner_uid]);
								if (!DBA::isResult($contact)) {
									//select someone by attag or nick and the name passed in
									$contact = DBA::selectFirst('contact', [],
										['(`attag` = ? OR `nick` = ?) AND `uid` = ?', $name, $name, $page_owner_uid],
										['order' => ['attag' => true]]
									);
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
						}

						if ($profile) {
							if (!empty($contact)) {
								$taginfo[] = [$newname, $profile, $notify, $contact, '@[url=' . str_replace(',', '%2c', $profile) . ']' . $newname . '[/url]'];
							} else {
								$taginfo[] = [$newname, $profile, $notify, null, $str_tags .= '@[url=' . $profile . ']' . $newname . '[/url]'];
							}

							if (strlen($str_tags)) {
								$str_tags .= ',';
							}

							$profile = str_replace(',', '%2c', $profile);
							$str_tags .= '@[url=' . $profile . ']' . $newname . '[/url]';
						}
					} elseif (strpos($tag, '#') === 0) {
						$tagname = substr($tag, 1);
						$str_tags .= '#[url=' . A::baseUrl() . "/search?tag=" . $tagname . ']' . $tagname . '[/url],';
					}
				}
			}

			$newtag = $old_tag ?? '';
			if (strlen($newtag) && strlen($str_tags)) {
				$newtag .= ',';
			}
			$newtag .= $str_tags;

			$newinform = $old_inform ?? '';
			if (strlen($newinform) && strlen($inform)) {
				$newinform .= ',';
			}
			$newinform .= $inform;

			$fields = ['tag' => $newtag, 'inform' => $newinform, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
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
					$uri = Item::newURI($page_owner_uid);

					$arr = [];
					$arr['guid']          = System::createUUID();
					$arr['uid']           = $page_owner_uid;
					$arr['uri']           = $uri;
					$arr['parent-uri']    = $uri;
					$arr['wall']          = 1;
					$arr['contact-id']    = $owner_record['id'];
					$arr['owner-name']    = $owner_record['name'];
					$arr['owner-link']    = $owner_record['url'];
					$arr['owner-avatar']  = $owner_record['thumb'];
					$arr['author-name']   = $owner_record['name'];
					$arr['author-link']   = $owner_record['url'];
					$arr['author-avatar'] = $owner_record['thumb'];
					$arr['title']         = '';
					$arr['allow_cid']     = $photo['allow_cid'];
					$arr['allow_gid']     = $photo['allow_gid'];
					$arr['deny_cid']      = $photo['deny_cid'];
					$arr['deny_gid']      = $photo['deny_gid'];
					$arr['visible']       = 1;
					$arr['verb']          = Activity::TAG;
					$arr['gravity']       = GRAVITY_PARENT;
					$arr['object-type']   = Activity\ObjectType::PERSON;
					$arr['target-type']   = Activity\ObjectType::IMAGE;
					$arr['tag']           = $tagged[4];
					$arr['inform']        = $tagged[2];
					$arr['origin']        = 1;
					$arr['body']          = L10n::t('%1$s was tagged in %2$s by %3$s', '[url=' . $tagged[1] . ']' . $tagged[0] . '[/url]', '[url=' . A::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . ']' . L10n::t('a photo') . '[/url]', '[url=' . $owner_record['url'] . ']' . $owner_record['name'] . '[/url]') ;
					$arr['body'] .= "\n\n" . '[url=' . A::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . ']' . '[img]' . A::baseUrl() . "/photo/" . $photo['resource-id'] . '-' . $best . '.' . $ext . '[/img][/url]' . "\n" ;

					$arr['object'] = '<object><type>' . Activity\ObjectType::PERSON . '</type><title>' . $tagged[0] . '</title><id>' . $tagged[1] . '/' . $tagged[0] . '</id>';
					$arr['object'] .= '<link>' . XML::escape('<link rel="alternate" type="text/html" href="' . $tagged[1] . '" />' . "\n");
					if ($tagged[3]) {
						$arr['object'] .= XML::escape('<link rel="photo" type="' . $photo['type'] . '" href="' . $tagged[3]['photo'] . '" />' . "\n");
					}
					$arr['object'] .= '</link></object>' . "\n";

					$arr['target'] = '<target><type>' . Activity\ObjectType::IMAGE . '</type><title>' . $photo['desc'] . '</title><id>'
					                 . A::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . '</id>';
					$arr['target'] .= '<link>' . XML::escape('<link rel="alternate" type="text/html" href="' . A::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo['resource-id'] . '" />' . "\n" . '<link rel="preview" type="' . $photo['type'] . '" href="' . A::baseUrl() . "/photo/" . $photo['resource-id'] . '-' . $best . '.' . $ext . '" />') . '</link></target>';

					Item::insert($arr);
				}
			}
		}
		A::baseUrl()->redirect($_SESSION['photo_return']);
		return; // NOTREACHED
	}


	// default post action - upload a photo
	Hook::callAll('photo_post_init', $_POST);

	// Determine the album to use
	$album    = !empty($_REQUEST['album'])    ? Strings::escapeTags(trim($_REQUEST['album']))    : '';
	$newalbum = !empty($_REQUEST['newalbum']) ? Strings::escapeTags(trim($_REQUEST['newalbum'])) : '';

	Logger::log('mod/photos.php: photos_post(): album= ' . $album . ' newalbum= ' . $newalbum , Logger::DEBUG);

	if (!strlen($album)) {
		if (strlen($newalbum)) {
			$album = $newalbum;
		} else {
			$album = DateTimeFormat::localNow('Y');
		}
	}

	/*
	 * We create a wall item for every photo, but we don't want to
	 * overwhelm the data stream with a hundred newly uploaded photos.
	 * So we will make the first photo uploaded to this album in the last several hours
	 * visible by default, the rest will become visible over time when and if
	 * they acquire comments, likes, dislikes, and/or tags
	 */

	$r = Photo::selectToArray([], ['`album` = ? AND `uid` = ? AND `created` > UTC_TIMESTAMP() - INTERVAL 3 HOUR', $album, $page_owner_uid]);

	if (!DBA::isResult($r) || ($album == L10n::t('Profile Photos'))) {
		$visible = 1;
	} else {
		$visible = 0;
	}

	if (!empty($_REQUEST['not_visible']) && $_REQUEST['not_visible'] !== 'false') {
		$visible = 0;
	}

	$group_allow   = $_REQUEST['group_allow']   ?? [];
	$contact_allow = $_REQUEST['contact_allow'] ?? [];
	$group_deny    = $_REQUEST['group_deny']    ?? [];
	$contact_deny  = $_REQUEST['contact_deny']  ?? [];

	$aclFormatter = Util::aclFormatter();

	$str_group_allow   = $aclFormatter->toString(is_array($group_allow)   ? $group_allow   : explode(',', $group_allow));
	$str_contact_allow = $aclFormatter->toString(is_array($contact_allow) ? $contact_allow : explode(',', $contact_allow));
	$str_group_deny    = $aclFormatter->toString(is_array($group_deny)    ? $group_deny    : explode(',', $group_deny));
	$str_contact_deny  = $aclFormatter->toString(is_array($contact_deny)  ? $contact_deny  : explode(',', $contact_deny));

	$ret = ['src' => '', 'filename' => '', 'filesize' => 0, 'type' => ''];

	Hook::callAll('photo_post_file', $ret);

	if (!empty($ret['src']) && !empty($ret['filesize'])) {
		$src      = $ret['src'];
		$filename = $ret['filename'];
		$filesize = $ret['filesize'];
		$type     = $ret['type'];
		$error    = UPLOAD_ERR_OK;
	} elseif (!empty($_FILES['userfile'])) {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$type     = $_FILES['userfile']['type'];
		$error    = $_FILES['userfile']['error'];
	} else {
		$error    = UPLOAD_ERR_NO_FILE;
	}

	if ($error !== UPLOAD_ERR_OK) {
		switch ($error) {
			case UPLOAD_ERR_INI_SIZE:
				notice(L10n::t('Image exceeds size limit of %s', ini_get('upload_max_filesize')) . EOL);
				break;
			case UPLOAD_ERR_FORM_SIZE:
				notice(L10n::t('Image exceeds size limit of %s', Strings::formatBytes($_REQUEST['MAX_FILE_SIZE'] ?? 0)) . EOL);
				break;
			case UPLOAD_ERR_PARTIAL:
				notice(L10n::t('Image upload didn\'t complete, please try again') . EOL);
				break;
			case UPLOAD_ERR_NO_FILE:
				notice(L10n::t('Image file is missing') . EOL);
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				notice(L10n::t('Server can\'t accept new file upload at this time, please contact your administrator') . EOL);
				break;
		}
		@unlink($src);
		$foo = 0;
		Hook::callAll('photo_post_end', $foo);
		return;
	}

	if ($type == "") {
		$type = Images::guessType($filename);
	}

	Logger::log('photos: upload: received file: ' . $filename . ' as ' . $src . ' ('. $type . ') ' . $filesize . ' bytes', Logger::DEBUG);

	$maximagesize = Config::get('system', 'maximagesize');

	if ($maximagesize && ($filesize > $maximagesize)) {
		notice(L10n::t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)) . EOL);
		@unlink($src);
		$foo = 0;
		Hook::callAll('photo_post_end', $foo);
		return;
	}

	if (!$filesize) {
		notice(L10n::t('Image file is empty.') . EOL);
		@unlink($src);
		$foo = 0;
		Hook::callAll('photo_post_end', $foo);
		return;
	}

	Logger::log('mod/photos.php: photos_post(): loading the contents of ' . $src , Logger::DEBUG);

	$imagedata = @file_get_contents($src);

	$image = new Image($imagedata, $type);

	if (!$image->isValid()) {
		Logger::log('mod/photos.php: photos_post(): unable to process image' , Logger::DEBUG);
		notice(L10n::t('Unable to process image.') . EOL);
		@unlink($src);
		$foo = 0;
		Hook::callAll('photo_post_end',$foo);
		return;
	}

	$exif = $image->orient($src);
	@unlink($src);

	$max_length = Config::get('system', 'max_image_length');
	if (!$max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$image->scaleDown($max_length);
	}

	$width  = $image->getWidth();
	$height = $image->getHeight();

	$smallest = 0;

	$resource_id = Photo::newResource();

	$r = Photo::store($image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 0 , 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);

	if (!$r) {
		Logger::log('mod/photos.php: photos_post(): image store failed', Logger::DEBUG);
		notice(L10n::t('Image upload failed.') . EOL);
		return;
	}

	if ($width > 640 || $height > 640) {
		$image->scaleDown(640);
		Photo::store($image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 1, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 1;
	}

	if ($width > 320 || $height > 320) {
		$image->scaleDown(320);
		Photo::store($image, $page_owner_uid, $visitor, $resource_id, $filename, $album, 2, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 2;
	}

	$uri = Item::newURI($page_owner_uid);

	// Create item container
	$lat = $lon = null;
	if ($exif && $exif['GPS'] && Feature::isEnabled($page_owner_uid, 'photo_location')) {
		$lat = Photo::getGps($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
		$lon = Photo::getGps($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
	}

	$arr = [];
	if ($lat && $lon) {
		$arr['coord'] = $lat . ' ' . $lon;
	}

	$arr['guid']          = System::createUUID();
	$arr['uid']           = $page_owner_uid;
	$arr['uri']           = $uri;
	$arr['parent-uri']    = $uri;
	$arr['type']          = 'photo';
	$arr['wall']          = 1;
	$arr['resource-id']   = $resource_id;
	$arr['contact-id']    = $owner_record['id'];
	$arr['owner-name']    = $owner_record['name'];
	$arr['owner-link']    = $owner_record['url'];
	$arr['owner-avatar']  = $owner_record['thumb'];
	$arr['author-name']   = $owner_record['name'];
	$arr['author-link']   = $owner_record['url'];
	$arr['author-avatar'] = $owner_record['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $str_contact_allow;
	$arr['allow_gid']     = $str_group_allow;
	$arr['deny_cid']      = $str_contact_deny;
	$arr['deny_gid']      = $str_group_deny;
	$arr['visible']       = $visible;
	$arr['origin']        = 1;

	$arr['body']          = '[url=' . A::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $resource_id . ']'
				. '[img]' . A::baseUrl() . "/photo/{$resource_id}-{$smallest}." . $image->getExt() . '[/img]'
				. '[/url]';

	$item_id = Item::insert($arr);
	// Update the photo albums cache
	Photo::clearAlbumCache($page_owner_uid);

	Hook::callAll('photo_post_end', $item_id);

	// addon uploaders should call "exit()" within the photo_post_end hook
	// if they do not wish to be redirected

	A::baseUrl()->redirect($_SESSION['photo_return']);
	// NOTREACHED
}

function photos_content(App $a)
{
	// URLs:
	// photos/name
	// photos/name/upload
	// photos/name/upload/xxxxx (xxxxx is album name)
	// photos/name/album/xxxxx
	// photos/name/album/xxxxx/edit
	// photos/name/album/xxxxx/drop
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit
	// photos/name/image/xxxxx/drop

	if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	if (empty($a->data['user'])) {
		notice(L10n::t('No photos selected') . EOL);
		return;
	}

	$phototypes = Images::supportedTypes();

	$_SESSION['photo_return'] = A::args()->getCommand();

	// Parse arguments
	$datum = null;
	if ($a->argc > 3) {
		$datatype = $a->argv[2];
		$datum = $a->argv[3];
	} elseif (($a->argc > 2) && ($a->argv[2] === 'upload')) {
		$datatype = 'upload';
	} else {
		$datatype = 'summary';
	}

	if ($a->argc > 4) {
		$cmd = $a->argv[4];
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

	$owner_uid = $a->data['user']['uid'];

	$community_page = (($a->data['user']['page-flags'] == User::PAGE_FLAGS_COMMUNITY) ? true : false);

	if (local_user() && (local_user() == $owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($owner_uid))) {
		$contact_id = Session::getRemoteContactID($owner_uid);
		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => $owner_uid, 'blocked' => false, 'pending' => false]);

		if (DBA::isResult($contact)) {
			$can_post = true;
			$remote_contact = true;
			$visitor = $contact_id;
		}
	}

	// perhaps they're visiting - but not a community page, so they wouldn't have write access
	if (!empty(Session::getRemoteContactID($owner_uid)) && !$visitor) {
		$contact_id = Session::getRemoteContactID($owner_uid);

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => $owner_uid, 'blocked' => false, 'pending' => false]);

		$remote_contact = DBA::isResult($contact);
	}

	if (!$remote_contact && local_user()) {
		$contact_id = $_SESSION['cid'];
		$contact = $a->contact;
	}

	if ($a->data['user']['hidewall'] && (local_user() != $owner_uid) && !$remote_contact) {
		notice(L10n::t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = Security::getPermissionsSQLByUserId($owner_uid);

	$o = "";

	// tabs
	$is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= Profile::getTabs($a, 'photos', $is_owner, $a->data['user']['nickname']);

	// Display upload form
	if ($datatype === 'upload') {
		if (!$can_post) {
			notice(L10n::t('Permission denied.'));
			return;
		}

		$selname = Strings::isHex($datum) ? hex2bin($datum) : '';

		$albumselect = '';

		$albumselect .= '<option value="" ' . (!$selname ? ' selected="selected" ' : '') . '>&lt;current year&gt;</option>';
		if (!empty($a->data['albums'])) {
			foreach ($a->data['albums'] as $album) {
				if (($album['album'] === '') || ($album['album'] === 'Contact Photos') || ($album['album'] === L10n::t('Contact Photos'))) {
					continue;
				}
				$selected = (($selname === $album['album']) ? ' selected="selected" ' : '');
				$albumselect .= '<option value="' . $album['album'] . '"' . $selected . '>' . $album['album'] . '</option>';
			}
		}

		$uploader = '';

		$ret = ['post_url' => 'photos/' . $a->data['user']['nickname'],
				'addon_text' => $uploader,
				'default_upload' => true];

		Hook::callAll('photo_upload_form',$ret);

		$default_upload_box = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_box.tpl'), []);
		$default_upload_submit = Renderer::replaceMacros(Renderer::getMarkupTemplate('photos_default_uploader_submit.tpl'), [
			'$submit' => L10n::t('Submit'),
		]);

		$usage_message = '';

		$tpl = Renderer::getMarkupTemplate('photos_upload.tpl');

		$aclselect_e = ($visitor ? '' : ACL::getFullSelectorHTML(A::page(), $a->user));

		$o .= Renderer::replaceMacros($tpl,[
			'$pagename' => L10n::t('Upload Photos'),
			'$sessid' => session_id(),
			'$usage' => $usage_message,
			'$nickname' => $a->data['user']['nickname'],
			'$newalbum' => L10n::t('New album name: '),
			'$existalbumtext' => L10n::t('or select existing album:'),
			'$nosharetext' => L10n::t('Do not show a status post for this upload'),
			'$albumselect' => $albumselect,
			'$permissions' => L10n::t('Permissions'),
			'$aclselect' => $aclselect_e,
			'$lockstate' => is_array($a->user)
					&& (strlen($a->user['allow_cid'])
						|| strlen($a->user['allow_gid'])
						|| strlen($a->user['deny_cid'])
						|| strlen($a->user['deny_gid'])
					) ? 'lock' : 'unlock',
			'$alt_uploader' => $ret['addon_text'],
			'$default_upload_box' => ($ret['default_upload'] ? $default_upload_box : ''),
			'$default_upload_submit' => ($ret['default_upload'] ? $default_upload_submit : ''),
			'$uploadurl' => $ret['post_url'],

			// ACL permissions box
			'$group_perms' => L10n::t('Show to Groups'),
			'$contact_perms' => L10n::t('Show to Contacts'),
			'$return_path' => A::args()->getQueryString(),
		]);

		return $o;
	}

	// Display a single photo album
	if ($datatype === 'album') {
		// if $datum is not a valid hex, redirect to the default page
		if (!Strings::isHex($datum)) {
			A::baseUrl()->redirect('photos/' . $a->data['user']['nickname'] . '/album');
		}
		$album = hex2bin($datum);

		$total = 0;
		$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s'
			AND `scale` <= 4 $sql_extra GROUP BY `resource-id`",
			intval($owner_uid),
			DBA::escape($album)
		);
		if (DBA::isResult($r)) {
			$total = count($r);
		}

		$pager = new Pager(A::args()->getQueryString(), 20);

		/// @TODO I have seen this many times, maybe generalize it script-wide and encapsulate it?
		$order_field = $_GET['order'] ?? '';
		if ($order_field === 'posted') {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$r = q("SELECT `resource-id`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`filename`) AS `filename`,
			ANY_VALUE(`type`) AS `type`, max(`scale`) AS `scale`, ANY_VALUE(`desc`) as `desc`,
			ANY_VALUE(`created`) as `created`
			FROM `photo` WHERE `uid` = %d AND `album` = '%s'
			AND `scale` <= 4 $sql_extra GROUP BY `resource-id` ORDER BY `created` $order LIMIT %d , %d",
			intval($owner_uid),
			DBA::escape($album),
			$pager->getStart(),
			$pager->getItemsPerPage()
		);

		if ($cmd === 'drop') {
			$drop_url = A::args()->getQueryString();

			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$method' => 'post',
				'$message' => L10n::t('Do you really want to delete this photo album and all its photos?'),
				'$extra_inputs' => [],
				'$confirm' => L10n::t('Delete Album'),
				'$confirm_url' => $drop_url,
				'$confirm_name' => 'dropalbum',
				'$cancel' => L10n::t('Cancel'),
			]);
		}

		// edit album name
		if ($cmd === 'edit') {
			if (($album !== L10n::t('Profile Photos')) && ($album !== 'Contact Photos') && ($album !== L10n::t('Contact Photos'))) {
				if ($can_post) {
					$edit_tpl = Renderer::getMarkupTemplate('album_edit.tpl');

					$album_e = $album;

					$o .= Renderer::replaceMacros($edit_tpl,[
						'$nametext' => L10n::t('New album name: '),
						'$nickname' => $a->data['user']['nickname'],
						'$album' => $album_e,
						'$hexalbum' => bin2hex($album),
						'$submit' => L10n::t('Submit'),
						'$dropsubmit' => L10n::t('Delete Album')
					]);
				}
			}
		} else {
			if (($album !== L10n::t('Profile Photos')) && ($album !== 'Contact Photos') && ($album !== L10n::t('Contact Photos')) && $can_post) {
				$edit = [L10n::t('Edit Album'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '/edit'];
				$drop = [L10n::t('Drop Album'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '/drop'];
			}
		}

		if ($order_field === 'posted') {
			$order =  [L10n::t('Show Newest First'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album), 'oldest'];
		} else {
			$order = [L10n::t('Show Oldest First'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '?f=&order=posted', 'newest'];
		}

		$photos = [];

		if (DBA::isResult($r)) {
			// "Twist" is only used for the duepunto theme with style "slackr"
			$twist = false;
			foreach ($r as $rr) {
				$twist = !$twist;

				$ext = $phototypes[$rr['type']];

				$imgalt_e = $rr['filename'];
				$desc_e = $rr['desc'];

				$photos[] = [
					'id' => $rr['id'],
					'twist' => ' ' . ($twist ? 'rotleft' : 'rotright') . rand(2,4),
					'link' => 'photos/' . $a->data['user']['nickname'] . '/image/' . $rr['resource-id']
						. ($order_field === 'posted' ? '?f=&order=posted' : ''),
					'title' => L10n::t('View Photo'),
					'src' => 'photo/' . $rr['resource-id'] . '-' . $rr['scale'] . '.' .$ext,
					'alt' => $imgalt_e,
					'desc'=> $desc_e,
					'ext' => $ext,
					'hash'=> $rr['resource-id'],
				];
			}
		}

		$tpl = Renderer::getMarkupTemplate('photo_album.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$photos' => $photos,
			'$album' => $album,
			'$can_post' => $can_post,
			'$upload' => [L10n::t('Upload New Photos'), 'photos/' . $a->data['user']['nickname'] . '/upload/' . bin2hex($album)],
			'$order' => $order,
			'$edit' => $edit,
			'$drop' => $drop,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;

	}

	// Display one photo
	if ($datatype === 'image') {
		// fetch image, item containing image, then comments
		$ph = q("SELECT * FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'
			$sql_extra ORDER BY `scale` ASC ",
			intval($owner_uid),
			DBA::escape($datum)
		);

		if (!DBA::isResult($ph)) {
			if (DBA::exists('photo', ['resource-id' => $datum, 'uid' => $owner_uid])) {
				notice(L10n::t('Permission denied. Access to this item may be restricted.'));
			} else {
				notice(L10n::t('Photo not available') . EOL);
			}
			return;
		}

		if ($cmd === 'drop') {
			$drop_url = A::args()->getQueryString();

			return Renderer::replaceMacros(Renderer::getMarkupTemplate('confirm.tpl'), [
				'$method' => 'post',
				'$message' => L10n::t('Do you really want to delete this photo?'),
				'$extra_inputs' => [],
				'$confirm' => L10n::t('Delete Photo'),
				'$confirm_url' => $drop_url,
				'$confirm_name' => 'delete',
				'$cancel' => L10n::t('Cancel'),
			]);
		}

		$prevlink = '';
		$nextlink = '';

		/*
		 * @todo This query is totally bad, the whole functionality has to be changed
		 * The query leads to a really intense used index.
		 * By now we hide it if someone wants to.
		 */
		if ($cmd === 'view' && !Config::get('system', 'no_count', false)) {
			$order_field = $_GET['order'] ?? '';

			if ($order_field === 'posted') {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}

			$prvnxt = q("SELECT `resource-id` FROM `photo` WHERE `album` = '%s' AND `uid` = %d AND `scale` = 0
				$sql_extra ORDER BY `created` $order ",
				DBA::escape($ph[0]['album']),
				intval($owner_uid)
			);

			if (DBA::isResult($prvnxt)) {
				$prv = null;
				$nxt = null;
				foreach ($prvnxt as $z => $entry) {
					if ($entry['resource-id'] == $ph[0]['resource-id']) {
						$prv = $z - 1;
						$nxt = $z + 1;
						if ($prv < 0) {
							$prv = count($prvnxt) - 1;
						}
						if ($nxt >= count($prvnxt)) {
							$nxt = 0;
						}
						break;
					}
				}

				if (!is_null($prv)) {
					$prevlink = 'photos/' . $a->data['user']['nickname'] . '/image/' . $prvnxt[$prv]['resource-id'] . ($order_field === 'posted' ? '?f=&order=posted' : '');
				}
				if (!is_null($nxt)) {
					$nextlink = 'photos/' . $a->data['user']['nickname'] . '/image/' . $prvnxt[$nxt]['resource-id'] . ($order_field === 'posted' ? '?f=&order=posted' : '');
				}

				$tpl                         = Renderer::getMarkupTemplate('photo_edit_head.tpl');
				A::page()['htmlhead'] .= Renderer::replaceMacros($tpl,[
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

		$album_link = 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($ph[0]['album']);

		$tools = null;

		if ($can_post && ($ph[0]['uid'] == $owner_uid)) {
			$tools = [];
			if ($cmd === 'edit') {
				$tools['view'] = ['photos/' . $a->data['user']['nickname'] . '/image/' . $datum, L10n::t('View photo')];
			} else {
				$tools['edit'] = ['photos/' . $a->data['user']['nickname'] . '/image/' . $datum . '/edit', L10n::t('Edit photo')];
				$tools['delete'] = ['photos/' . $a->data['user']['nickname'] . '/image/' . $datum . '/drop', L10n::t('Delete photo')];
				$tools['profile'] = ['profile_photo/use/'.$ph[0]['resource-id'], L10n::t('Use as profile photo')];
			}

			if (
				$ph[0]['uid'] == local_user()
				&& (strlen($ph[0]['allow_cid']) || strlen($ph[0]['allow_gid']) || strlen($ph[0]['deny_cid']) || strlen($ph[0]['deny_gid']))
			) {
				$tools['lock'] = L10n::t('Private Photo');
			}
		}

		$photo = [
			'href' => 'photo/' . $hires['resource-id'] . '-' . $hires['scale'] . '.' . $phototypes[$hires['type']],
			'title'=> L10n::t('View Full Size'),
			'src'  => 'photo/' . $lores['resource-id'] . '-' . $lores['scale'] . '.' . $phototypes[$lores['type']] . '?f=&_u=' . DateTimeFormat::utcNow('ymdhis'),
			'height' => $hires['height'],
			'width' => $hires['width'],
			'album' => $hires['album'],
			'filename' => $hires['filename'],
		];

		$map = null;
		$link_item = [];
		$total = 0;

		// Do we have an item for this photo?

		// FIXME! - replace following code to display the conversation with our normal
		// conversation functions so that it works correctly and tracks changes
		// in the evolving conversation code.
		// The difference is that we won't be displaying the conversation head item
		// as a "post" but displaying instead the photo it is linked to

		/// @todo Rewrite this query. To do so, $sql_extra must be changed
		$linked_items = q("SELECT `id` FROM `item` WHERE `resource-id` = '%s' $sql_extra LIMIT 1",
			DBA::escape($datum)
		);
		if (DBA::isResult($linked_items)) {
			// This is a workaround to not being forced to rewrite the while $sql_extra handling
			$link_item = Item::selectFirst([], ['id' => $linked_items[0]['id']]);
		}

		if (!empty($link_item['parent']) && !empty($link_item['uid'])) {
			$condition = ["`parent` = ? AND `parent` != `id`",  $link_item['parent']];
			$total = DBA::count('item', $condition);

			$pager = new Pager(A::args()->getQueryString());

			$params = ['order' => ['id'], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
			$result = Item::selectForUser($link_item['uid'], Item::ITEM_FIELDLIST, $condition, $params);
			$items = Item::inArray($result);

			if (local_user() == $link_item['uid']) {
				Item::update(['unseen' => false], ['parent' => $link_item['parent']]);
			}
		}

		if (!empty($link_item['coord'])) {
			$map = Map::byCoordinates($link_item['coord']);
		}

		$tags = null;

		if (!empty($link_item['id']) && !empty($link_item['tag'])) {
			$arr = explode(',', $link_item['tag']);
			// parse tags and add links
			$tag_arr = [];
			foreach ($arr as $tag) {
				$tag_arr[] = [
					'name' => BBCode::convert($tag),
					'removeurl' => '/tagrm/' . $link_item['id'] . '/' . bin2hex($tag)
				];
			}
			$tags = ['title' => L10n::t('Tags: '), 'tags' => $tag_arr];
			if ($cmd === 'edit') {
				$tags['removeanyurl'] = 'tagrm/' . $link_item['id'];
				$tags['removetitle'] = L10n::t('[Select tags to remove]');
			}
		}


		$edit = Null;
		if ($cmd === 'edit' && $can_post) {
			$edit_tpl = Renderer::getMarkupTemplate('photo_edit.tpl');

			$album_e = $ph[0]['album'];
			$caption_e = $ph[0]['desc'];
			$aclselect_e = ACL::getFullSelectorHTML(A::page(), $a->user, false, ACL::getDefaultUserPermissions($ph[0]));

			$edit = Renderer::replaceMacros($edit_tpl, [
				'$id' => $ph[0]['id'],
				'$album' => ['albname', L10n::t('New album name'), $album_e,''],
				'$caption' => ['desc', L10n::t('Caption'), $caption_e, ''],
				'$tags' => ['newtag', L10n::t('Add a Tag'), "", L10n::t('Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping')],
				'$rotate_none' => ['rotate', L10n::t('Do not rotate'),0,'', true],
				'$rotate_cw' => ['rotate', L10n::t("Rotate CW \x28right\x29"),1,''],
				'$rotate_ccw' => ['rotate', L10n::t("Rotate CCW \x28left\x29"),2,''],

				'$nickname' => $a->data['user']['nickname'],
				'$resource_id' => $ph[0]['resource-id'],
				'$permissions' => L10n::t('Permissions'),
				'$aclselect' => $aclselect_e,

				'$item_id' => $link_item['id'] ?? 0,
				'$submit' => L10n::t('Submit'),
				'$delete' => L10n::t('Delete Photo'),

				// ACL permissions box
				'$group_perms' => L10n::t('Show to Groups'),
				'$contact_perms' => L10n::t('Show to Contacts'),
				'$return_path' => A::args()->getQueryString(),
			]);
		}

		$like = '';
		$dislike = '';
		$likebuttons = '';
		$comments = '';
		$paginate = '';
		$responses = '';

		if (!empty($link_item['id']) && !empty($link_item['uri'])) {
			$cmnt_tpl = Renderer::getMarkupTemplate('comment_item.tpl');
			$tpl = Renderer::getMarkupTemplate('photo_item.tpl');
			$return_path = A::args()->getCommand();

			if ($cmd === 'view' && ($can_post || Security::canWriteToUserWall($owner_uid))) {
				$like_tpl = Renderer::getMarkupTemplate('like_noshare.tpl');
				$likebuttons = Renderer::replaceMacros($like_tpl, [
					'$id' => $link_item['id'],
					'$likethis' => L10n::t("I like this \x28toggle\x29"),
					'$nolike' => L10n::t("I don't like this \x28toggle\x29"),
					'$wait' => L10n::t('Please wait'),
					'$return_path' => A::args()->getQueryString(),
				]);
			}

			if (!DBA::isResult($items)) {
				if (($can_post || Security::canWriteToUserWall($owner_uid))) {
					$comments .= Renderer::replaceMacros($cmnt_tpl, [
						'$return_path' => '',
						'$jsreload' => $return_path,
						'$id' => $link_item['id'],
						'$parent' => $link_item['id'],
						'$profile_uid' =>  $owner_uid,
						'$mylink' => $contact['url'],
						'$mytitle' => L10n::t('This is you'),
						'$myphoto' => $contact['thumb'],
						'$comment' => L10n::t('Comment'),
						'$submit' => L10n::t('Submit'),
						'$preview' => L10n::t('Preview'),
						'$sourceapp' => L10n::t($a->sourcename),
						'$ww' => '',
						'$rand_num' => Crypto::randomDigits(12)
					]);
				}
			}

			$conv_responses = [
				'like' => ['title' => L10n::t('Likes','title')],'dislike' => ['title' => L10n::t('Dislikes','title')],
				'attendyes' => ['title' => L10n::t('Attending','title')], 'attendno' => ['title' => L10n::t('Not attending','title')], 'attendmaybe' => ['title' => L10n::t('Might attend','title')]
			];

			// display comments
			if (DBA::isResult($items)) {
				foreach ($items as $item) {
					builtin_activity_puller($item, $conv_responses);
				}

				if (!empty($conv_responses['like'][$link_item['uri']])) {
					$like = format_like($conv_responses['like'][$link_item['uri']], $conv_responses['like'][$link_item['uri'] . '-l'], 'like', $link_item['id']);
				}

				if (!empty($conv_responses['dislike'][$link_item['uri']])) {
					$dislike = format_like($conv_responses['dislike'][$link_item['uri']], $conv_responses['dislike'][$link_item['uri'] . '-l'], 'dislike', $link_item['id']);
				}

				if (($can_post || Security::canWriteToUserWall($owner_uid))) {
					$comments .= Renderer::replaceMacros($cmnt_tpl,[
						'$return_path' => '',
						'$jsreload' => $return_path,
						'$id' => $link_item['id'],
						'$parent' => $link_item['id'],
						'$profile_uid' =>  $owner_uid,
						'$mylink' => $contact['url'],
						'$mytitle' => L10n::t('This is you'),
						'$myphoto' => $contact['thumb'],
						'$comment' => L10n::t('Comment'),
						'$submit' => L10n::t('Submit'),
						'$preview' => L10n::t('Preview'),
						'$sourceapp' => L10n::t($a->sourcename),
						'$ww' => '',
						'$rand_num' => Crypto::randomDigits(12)
					]);
				}

				foreach ($items as $item) {
					$comment = '';
					$template = $tpl;
					$sparkle = '';

					$activity = Protocol::activity();

					if (($activity->match($item['verb'], Activity::LIKE) ||
					     $activity->match($item['verb'], Activity::DISLIKE)) &&
					    ($item['id'] != $item['parent'])) {
						continue;
					}

					$profile_url = Contact::magicLinkbyId($item['author-id']);
					if (strpos($profile_url, 'redir/') === 0) {
						$sparkle = ' sparkle';
					} else {
						$sparkle = '';
					}

					$dropping = (($item['contact-id'] == $contact_id) || ($item['uid'] == local_user()));
					$drop = [
						'dropping' => $dropping,
						'pagedrop' => false,
						'select' => L10n::t('Select'),
						'delete' => L10n::t('Delete'),
					];

					$title_e = $item['title'];
					$body_e = BBCode::convert($item['body']);

					$comments .= Renderer::replaceMacros($template,[
						'$id' => $item['id'],
						'$profile_url' => $profile_url,
						'$name' => $item['author-name'],
						'$thumb' => $item['author-avatar'],
						'$sparkle' => $sparkle,
						'$title' => $title_e,
						'$body' => $body_e,
						'$ago' => Temporal::getRelativeDate($item['created']),
						'$indent' => (($item['parent'] != $item['id']) ? ' comment' : ''),
						'$drop' => $drop,
						'$comment' => $comment
					]);

					if (($can_post || Security::canWriteToUserWall($owner_uid))) {
						$comments .= Renderer::replaceMacros($cmnt_tpl, [
							'$return_path' => '',
							'$jsreload' => $return_path,
							'$id' => $item['id'],
							'$parent' => $item['parent'],
							'$profile_uid' =>  $owner_uid,
							'$mylink' => $contact['url'],
							'$mytitle' => L10n::t('This is you'),
							'$myphoto' => $contact['thumb'],
							'$comment' => L10n::t('Comment'),
							'$submit' => L10n::t('Submit'),
							'$preview' => L10n::t('Preview'),
							'$sourceapp' => L10n::t($a->sourcename),
							'$ww' => '',
							'$rand_num' => Crypto::randomDigits(12)
						]);
					}
				}
			}
			$response_verbs = ['like'];
			$response_verbs[] = 'dislike';
			$responses = get_responses($conv_responses, $response_verbs, $link_item);

			$paginate = $pager->renderFull($total);
		}

		$photo_tpl = Renderer::getMarkupTemplate('photo_view.tpl');
		$o .= Renderer::replaceMacros($photo_tpl, [
			'$id' => $ph[0]['id'],
			'$album' => [$album_link, $ph[0]['album']],
			'$tools' => $tools,
			'$photo' => $photo,
			'$prevlink' => $prevlink,
			'$nextlink' => $nextlink,
			'$desc' => $ph[0]['desc'],
			'$tags' => $tags,
			'$edit' => $edit,
			'$map' => $map,
			'$map_text' => L10n::t('Map'),
			'$likebuttons' => $likebuttons,
			'$like' => $like,
			'$dislike' => $dislike,
			'responses' => $responses,
			'$comments' => $comments,
			'$paginate' => $paginate,
		]);

		A::page()['htmlhead'] .= "\n" . '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		A::page()['htmlhead'] .= '<meta name="twitter:title" content="' . $photo["album"] . '" />' . "\n";
		A::page()['htmlhead'] .= '<meta name="twitter:image" content="' . A::baseUrl() . "/" . $photo["href"] . '" />' . "\n";
		A::page()['htmlhead'] .= '<meta name="twitter:image:width" content="' . $photo["width"] . '" />' . "\n";
		A::page()['htmlhead'] .= '<meta name="twitter:image:height" content="' . $photo["height"] . '" />' . "\n";

		return $o;
	}

	// Default - show recent photos with upload link (if applicable)
	//$o = '';
	$total = 0;
	$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s'
		$sql_extra GROUP BY `resource-id`",
		intval($a->data['user']['uid']),
		DBA::escape('Contact Photos'),
		DBA::escape(L10n::t('Contact Photos'))
	);
	if (DBA::isResult($r)) {
		$total = count($r);
	}

	$pager = new Pager(A::args()->getQueryString(), 20);

	$r = q("SELECT `resource-id`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`filename`) AS `filename`,
		ANY_VALUE(`type`) AS `type`, ANY_VALUE(`album`) AS `album`, max(`scale`) AS `scale`,
		ANY_VALUE(`created`) AS `created` FROM `photo`
		WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s'
		$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		DBA::escape('Contact Photos'),
		DBA::escape(L10n::t('Contact Photos')),
		$pager->getStart(),
		$pager->getItemsPerPage()
	);

	$photos = [];
	if (DBA::isResult($r)) {
		// "Twist" is only used for the duepunto theme with style "slackr"
		$twist = false;
		foreach ($r as $rr) {
			//hide profile photos to others
			if (!$is_owner && !Session::getRemoteContactID($owner_uid) && ($rr['album'] == L10n::t('Profile Photos'))) {
				continue;
			}

			$twist = !$twist;
			$ext = $phototypes[$rr['type']];

			$alt_e = $rr['filename'];
			$name_e = $rr['album'];

			$photos[] = [
				'id'    => $rr['id'],
				'twist' => ' ' . ($twist ? 'rotleft' : 'rotright') . rand(2,4),
				'link'  => 'photos/' . $a->data['user']['nickname'] . '/image/' . $rr['resource-id'],
				'title' => L10n::t('View Photo'),
				'src'   => 'photo/' . $rr['resource-id'] . '-' . ((($rr['scale']) == 6) ? 4 : $rr['scale']) . '.' . $ext,
				'alt'   => $alt_e,
				'album' => [
					'link' => 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
					'name' => $name_e,
					'alt'  => L10n::t('View Album'),
				],

			];
		}
	}

	$tpl = Renderer::getMarkupTemplate('photos_recent.tpl');
	$o .= Renderer::replaceMacros($tpl, [
		'$title' => L10n::t('Recent Photos'),
		'$can_post' => $can_post,
		'$upload' => [L10n::t('Upload New Photos'), 'photos/'.$a->data['user']['nickname'].'/upload'],
		'$photos' => $photos,
		'$paginate' => $pager->renderFull($total),
	]);

	return $o;
}
