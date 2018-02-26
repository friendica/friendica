<?php
/**
 * @file mod/photos.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Acl;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Protocol\DFRN;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Temporal;

require_once 'include/items.php';
require_once 'include/acl_selectors.php';
require_once 'include/security.php';

function photos_init(App $a) {

	if ($a->argc > 1) {
		DFRN::autoRedir($a, $a->argv[1]);
	}

	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		return;
	}

	Nav::setSelected('home');

	if ($a->argc > 1) {
		$nick = $a->argv[1];
		$user = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);

		if (!DBM::is_result($user)) {
			return;
		}

		$a->data['user'] = $user[0];
		$a->profile_uid = $user[0]['uid'];
		$is_owner = (local_user() && (local_user() == $a->profile_uid));

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

		$albums = Photo::getAlbums($a->data['user']['uid']);

		$albums_visible = ((intval($a->data['user']['hidewall']) && !local_user() && !remote_user()) ? false : true);

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
				if (!$is_owner && !remote_user() && ($album['album'] == L10n::t('Profile Photos')))
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
		}

		if ($ret['success']) {
			$photo_albums_widget = replace_macros(get_markup_template('photo_albums.tpl'), [
				'$nick'     => $a->data['user']['nickname'],
				'$title'    => L10n::t('Photo Albums'),
				'$recent'   => L10n::t('Recent Photos'),
				'$albums'   => $ret['albums'],
				'$baseurl'  => System::baseUrl(),
				'$upload'   => [L10n::t('Upload New Photos'), 'photos/' . $a->data['user']['nickname'] . '/upload'],
				'$can_post' => $can_post
			]);
		}


		if (!x($a->page, 'aside')) {
			$a->page['aside'] = '';
		}
		$a->page['aside'] .= $vcard_widget;
		$a->page['aside'] .= $photo_albums_widget;

		$tpl = get_markup_template("photos_head.tpl");
		$a->page['htmlhead'] .= replace_macros($tpl,[
			'$ispublic' => L10n::t('everybody')
		]);
	}

	return;
}

function photos_post(App $a)
{
	logger('mod-photos: photos_post: begin' , LOGGER_DEBUG);
	logger('mod_photos: REQUEST ' . print_r($_REQUEST, true), LOGGER_DATA);
	logger('mod_photos: FILES '   . print_r($_FILES, true), LOGGER_DATA);

	$phototypes = Image::supportedTypes();

	$can_post  = false;
	$visitor   = 0;

	$page_owner_uid = $a->data['user']['uid'];
	$community_page = $a->data['user']['page-flags'] == PAGE_COMMUNITY;

	if (local_user() && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} else {
		if ($community_page && remote_user()) {
			$contact_id = 0;
			if (x($_SESSION, 'remote') && is_array($_SESSION['remote'])) {
				foreach ($_SESSION['remote'] as $v) {
					if ($v['uid'] == $page_owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}
			if ($contact_id) {
				$r = q("SELECT `uid` FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 AND `id` = %d AND `uid` = %d LIMIT 1",
					intval($contact_id),
					intval($page_owner_uid)
				);
				if (DBM::is_result($r)) {
					$can_post = true;
					$visitor = $contact_id;
				}
			}
		}
	}

	if (!$can_post) {
		notice(L10n::t('Permission denied.') . EOL );
		killme();
	}

	$r = q("SELECT `contact`.*, `user`.`nickname` FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid`
		WHERE `user`.`uid` = %d AND `self` = 1 LIMIT 1",
		intval($page_owner_uid)
	);

	if (!DBM::is_result($r)) {
		notice(L10n::t('Contact information unavailable') . EOL);
		logger('photos_post: unable to locate contact record for page owner. uid=' . $page_owner_uid);
		killme();
	}

	$owner_record = $r[0];

	if ($a->argc > 3 && $a->argv[2] === 'album') {
		$album = hex2bin($a->argv[3]);

		if ($album === L10n::t('Profile Photos') || $album === 'Contact Photos' || $album === L10n::t('Contact Photos')) {
			goaway($_SESSION['photo_return']);
			return; // NOTREACHED
		}

		$r = q("SELECT `album` FROM `photo` WHERE `album` = '%s' AND `uid` = %d",
			dbesc($album),
			intval($page_owner_uid)
		);
		if (!DBM::is_result($r)) {
			notice(L10n::t('Album not found.') . EOL);
			goaway($_SESSION['photo_return']);
			return; // NOTREACHED
		}

		// Check if the user has responded to a delete confirmation query
		if ($_REQUEST['canceled']) {
			goaway($_SESSION['photo_return']);
		}

		// RENAME photo album
		$newalbum = notags(trim($_POST['albumname']));
		if ($newalbum != $album) {
			q("UPDATE `photo` SET `album` = '%s' WHERE `album` = '%s' AND `uid` = %d",
				dbesc($newalbum),
				dbesc($album),
				intval($page_owner_uid)
			);
			// Update the photo albums cache
			Photo::clearAlbumCache($page_owner_uid);

			$newurl = str_replace(bin2hex($album), bin2hex($newalbum), $_SESSION['photo_return']);
			goaway($newurl);
			return; // NOTREACHED
		}

		/*
		 * DELETE photo album and all its photos
		 */

		if ($_POST['dropalbum'] == L10n::t('Delete Album')) {
			// Check if we should do HTML-based delete confirmation
			if (x($_REQUEST, 'confirm')) {
				$drop_url = $a->query_string;
				$extra_inputs = [
					['name' => 'albumname', 'value' => $_POST['albumname']],
				];
				$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), [
					'$method' => 'post',
					'$message' => L10n::t('Do you really want to delete this photo album and all its photos?'),
					'$extra_inputs' => $extra_inputs,
					'$confirm' => L10n::t('Delete Album'),
					'$confirm_url' => $drop_url,
					'$confirm_name' => 'dropalbum', // Needed so that confirmation will bring us back into this if statement
					'$cancel' => L10n::t('Cancel'),
				]);
				$a->error = 1; // Set $a->error so the other module functions don't execute
				return;
			}

			$res = [];

			// get the list of photos we are about to delete

			if ($visitor) {
				$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d AND `album` = '%s'",
					intval($visitor),
					intval($page_owner_uid),
					dbesc($album)
				);
			} else {
				$r = q("SELECT distinct(`resource-id`) as `rid` FROM `photo` WHERE `uid` = %d AND `album` = '%s'",
					intval(local_user()),
					dbesc($album)
				);
			}
			if (DBM::is_result($r)) {
				foreach ($r as $rr) {
					$res[] = "'" . dbesc($rr['rid']) . "'" ;
				}
			} else {
				goaway($_SESSION['photo_return']);
				return; // NOTREACHED
			}

			$str_res = implode(',', $res);

			// remove the associated photos
			q("DELETE FROM `photo` WHERE `resource-id` IN ( $str_res ) AND `uid` = %d",
				intval($page_owner_uid)
			);

			// find and delete the corresponding item with all the comments and likes/dislikes
			$r = q("SELECT `id` FROM `item` WHERE `resource-id` IN ( $str_res ) AND `uid` = %d",
				intval($page_owner_uid)
			);
			if (DBM::is_result($r)) {
				foreach ($r as $rr) {
					Item::deleteById($rr['id']);
				}
			}

			// Update the photo albums cache
			Photo::clearAlbumCache($page_owner_uid);
		}

		goaway('photos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}


	// Check if the user has responded to a delete confirmation query for a single photo
	if ($a->argc > 2 && x($_REQUEST, 'canceled')) {
		goaway($_SESSION['photo_return']);
	}

	if ($a->argc > 2 && defaults($_POST, 'delete', '') === L10n::t('Delete Photo')) {

		// same as above but remove single photo

		// Check if we should do HTML-based delete confirmation
		if (x($_REQUEST, 'confirm')) {
			$drop_url = $a->query_string;
			$a->page['content'] = replace_macros(get_markup_template('confirm.tpl'), [
				'$method' => 'post',
				'$message' => L10n::t('Do you really want to delete this photo?'),
				'$extra_inputs' => [],
				'$confirm' => L10n::t('Delete Photo'),
				'$confirm_url' => $drop_url,
				'$confirm_name' => 'delete', // Needed so that confirmation will bring us back into this if statement
				'$cancel' => L10n::t('Cancel'),
			]);
			$a->error = 1; // Set $a->error so the other module functions don't execute
			return;
		}

		if ($visitor) {
			$r = q("SELECT `id`, `resource-id` FROM `photo` WHERE `contact-id` = %d AND `uid` = %d AND `resource-id` = '%s' LIMIT 1",
				intval($visitor),
				intval($page_owner_uid),
				dbesc($a->argv[2])
			);
		} else {
			$r = q("SELECT `id`, `resource-id` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' LIMIT 1",
				intval(local_user()),
				dbesc($a->argv[2])
			);
		}
		if (DBM::is_result($r)) {
			q("DELETE FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'",
				intval($page_owner_uid),
				dbesc($r[0]['resource-id'])
			);
			$i = q("SELECT `id` FROM `item` WHERE `resource-id` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($r[0]['resource-id']),
				intval($page_owner_uid)
			);
			if (DBM::is_result($i)) {
				Item::deleteById($i[0]['id']);

				// Update the photo albums cache
				Photo::clearAlbumCache($page_owner_uid);
			}
		}

		goaway('photos/' . $a->data['user']['nickname']);
		return; // NOTREACHED
	}

	if ($a->argc > 2 && (x($_POST, 'desc') !== false || x($_POST, 'newtag') !== false || x($_POST, 'albname') !== false)) {
		$desc        = x($_POST, 'desc')      ? notags(trim($_POST['desc']))      : '';
		$rawtags     = x($_POST, 'newtag')    ? notags(trim($_POST['newtag']))    : '';
		$item_id     = x($_POST, 'item_id')   ? intval($_POST['item_id'])         : 0;
		$albname     = x($_POST, 'albname')   ? notags(trim($_POST['albname']))   : '';
		$origaname   = x($_POST, 'origaname') ? notags(trim($_POST['origaname'])) : '';
		$str_group_allow   = perms2str($_POST['group_allow']);
		$str_contact_allow = perms2str($_POST['contact_allow']);
		$str_group_deny    = perms2str($_POST['group_deny']);
		$str_contact_deny  = perms2str($_POST['contact_deny']);

		$resource_id = $a->argv[2];

		if (!strlen($albname)) {
			$albname = DateTimeFormat::localNow('Y');
		}

		if (x($_POST,'rotate') !== false &&
		   (intval($_POST['rotate']) == 1 || intval($_POST['rotate']) == 2)) {
			logger('rotate');

			$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 0 LIMIT 1",
				dbesc($resource_id),
				intval($page_owner_uid)
			);
			if (DBM::is_result($r)) {
				$Image = new Image($r[0]['data'], $r[0]['type']);
				if ($Image->isValid()) {
					$rotate_deg = ( (intval($_POST['rotate']) == 1) ? 270 : 90 );
					$Image->rotate($rotate_deg);

					$width  = $Image->getWidth();
					$height = $Image->getHeight();

					$x = q("UPDATE `photo` SET `data` = '%s', `height` = %d, `width` = %d WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 0",
						dbesc($Image->asString()),
						intval($height),
						intval($width),
						dbesc($resource_id),
						intval($page_owner_uid)
					);

					if ($width > 640 || $height > 640) {
						$Image->scaleDown(640);
						$width  = $Image->getWidth();
						$height = $Image->getHeight();

						$x = q("UPDATE `photo` SET `data` = '%s', `height` = %d, `width` = %d WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 1",
							dbesc($Image->asString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
					}

					if ($width > 320 || $height > 320) {
						$Image->scaleDown(320);
						$width  = $Image->getWidth();
						$height = $Image->getHeight();

						$x = q("UPDATE `photo` SET `data` = '%s', `height` = %d, `width` = %d WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 2",
							dbesc($Image->asString()),
							intval($height),
							intval($width),
							dbesc($resource_id),
							intval($page_owner_uid)
						);
					}
				}
			}
		}

		$p = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d ORDER BY `scale` DESC",
			dbesc($resource_id),
			intval($page_owner_uid)
		);
		if (DBM::is_result($p)) {
			$ext = $phototypes[$p[0]['type']];
			$r = q("UPDATE `photo` SET `desc` = '%s', `album` = '%s', `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s' WHERE `resource-id` = '%s' AND `uid` = %d",
				dbesc($desc),
				dbesc($albname),
				dbesc($str_contact_allow),
				dbesc($str_group_allow),
				dbesc($str_contact_deny),
				dbesc($str_group_deny),
				dbesc($resource_id),
				intval($page_owner_uid)
			);

			// Update the photo albums cache if album name was changed
			if ($albname !== $origaname) {
				Photo::clearAlbumCache($page_owner_uid);
			}
		}

		/* Don't make the item visible if the only change was the album name */

		$visibility = 0;
		if ($p[0]['desc'] !== $desc || strlen($rawtags)) {
			$visibility = 1;
		}

		if (!$item_id) {
			// Create item container
			$title = '';
			$uri = item_new_uri($a->get_hostname(),$page_owner_uid);

			$arr = [];
			$arr['guid']          = get_guid(32);
			$arr['uid']           = $page_owner_uid;
			$arr['uri']           = $uri;
			$arr['parent-uri']    = $uri;
			$arr['type']          = 'photo';
			$arr['wall']          = 1;
			$arr['resource-id']   = $p[0]['resource-id'];
			$arr['contact-id']    = $owner_record['id'];
			$arr['owner-name']    = $owner_record['name'];
			$arr['owner-link']    = $owner_record['url'];
			$arr['owner-avatar']  = $owner_record['thumb'];
			$arr['author-name']   = $owner_record['name'];
			$arr['author-link']   = $owner_record['url'];
			$arr['author-avatar'] = $owner_record['thumb'];
			$arr['title']         = $title;
			$arr['allow_cid']     = $p[0]['allow_cid'];
			$arr['allow_gid']     = $p[0]['allow_gid'];
			$arr['deny_cid']      = $p[0]['deny_cid'];
			$arr['deny_gid']      = $p[0]['deny_gid'];
			$arr['visible']       = $visibility;
			$arr['origin']        = 1;

			$arr['body']          = '[url=' . System::baseUrl() . '/photos/' . $a->data['user']['nickname'] . '/image/' . $p[0]['resource-id'] . ']'
						. '[img]' . System::baseUrl() . '/photo/' . $p[0]['resource-id'] . '-' . $p[0]['scale'] . '.'. $ext . '[/img]'
						. '[/url]';

			$item_id = Item::insert($arr);
		}

		if ($item_id) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($item_id),
				intval($page_owner_uid)
			);
		}
		if (DBM::is_result($r)) {
			$old_tag    = $r[0]['tag'];
			$old_inform = $r[0]['inform'];
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
			$tags = get_tags($rawtags);

			if (count($tags)) {
				foreach ($tags as $tag) {
					if (strpos($tag, '@') === 0) {
						$profile = '';
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
							$alias = '';
							$tagcid = 0;
							if (strrpos($newname, '+')) {
								$tagcid = intval(substr($newname, strrpos($newname, '+') + 1));
							}

							if ($tagcid) {
								$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
									intval($tagcid),
									intval($profile_uid)
								);
							} else {
								$newname = str_replace('_',' ',$name);

								//select someone from this user's contacts by name
								$r = q("SELECT * FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
										dbesc($newname),
										intval($page_owner_uid)
								);

								if (!DBM::is_result($r)) {
									//select someone by attag or nick and the name passed in
									$r = q("SELECT * FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
											dbesc($name),
											dbesc($name),
											intval($page_owner_uid)
									);
								}
							}

							if (DBM::is_result($r)) {
								$newname = $r[0]['name'];
								$profile = $r[0]['url'];
								$notify = 'cid:' . $r[0]['id'];
								if (strlen($inform)) {
									$inform .= ',';
								}
								$inform .= $notify;
							}
						}
						if ($profile) {
							if (substr($notify, 0, 4) === 'cid:') {
								$taginfo[] = [$newname, $profile, $notify, $r[0], '@[url=' . str_replace(',','%2c',$profile) . ']' . $newname . '[/url]'];
							} else {
								$taginfo[] = [$newname, $profile, $notify, null, $str_tags .= '@[url=' . $profile . ']' . $newname . '[/url]'];
							}
							if (strlen($str_tags)) {
								$str_tags .= ',';
							}
							$profile = str_replace(',', '%2c', $profile);
							$str_tags .= '@[url='.$profile.']'.$newname.'[/url]';
						}
					} elseif (strpos($tag, '#') === 0) {
						$tagname = substr($tag, 1);
						$str_tags .= '#[url=' . System::baseUrl() . "/search?tag=" . $tagname . ']' . $tagname . '[/url]';
					}
				}
			}

			$newtag = $old_tag;
			if (strlen($newtag) && strlen($str_tags)) {
				$newtag .= ',';
			}
			$newtag .= $str_tags;

			$newinform = $old_inform;
			if (strlen($newinform) && strlen($inform)) {
				$newinform .= ',';
			}
			$newinform .= $inform;

			$fields = ['tag' => $newtag, 'inform' => $newinform, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
			$condition = ['id' => $item_id];
			Item::update($fields, $condition);

			$best = 0;
			foreach ($p as $scales) {
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
					$uri = item_new_uri($a->get_hostname(), $page_owner_uid);

					$arr = [];
					$arr['guid']          = get_guid(32);
					$arr['uid']           = $page_owner_uid;
					$arr['uri']           = $uri;
					$arr['parent-uri']    = $uri;
					$arr['type']          = 'activity';
					$arr['wall']          = 1;
					$arr['contact-id']    = $owner_record['id'];
					$arr['owner-name']    = $owner_record['name'];
					$arr['owner-link']    = $owner_record['url'];
					$arr['owner-avatar']  = $owner_record['thumb'];
					$arr['author-name']   = $owner_record['name'];
					$arr['author-link']   = $owner_record['url'];
					$arr['author-avatar'] = $owner_record['thumb'];
					$arr['title']         = '';
					$arr['allow_cid']     = $p[0]['allow_cid'];
					$arr['allow_gid']     = $p[0]['allow_gid'];
					$arr['deny_cid']      = $p[0]['deny_cid'];
					$arr['deny_gid']      = $p[0]['deny_gid'];
					$arr['visible']       = 1;
					$arr['verb']          = ACTIVITY_TAG;
					$arr['object-type']   = ACTIVITY_OBJ_PERSON;
					$arr['target-type']   = ACTIVITY_OBJ_IMAGE;
					$arr['tag']           = $tagged[4];
					$arr['inform']        = $tagged[2];
					$arr['origin']        = 1;
					$arr['body']          = L10n::t('%1$s was tagged in %2$s by %3$s', '[url=' . $tagged[1] . ']' . $tagged[0] . '[/url]', '[url=' . System::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource-id'] . ']' . L10n::t('a photo') . '[/url]', '[url=' . $owner_record['url'] . ']' . $owner_record['name'] . '[/url]') ;
					$arr['body'] .= "\n\n" . '[url=' . System::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource-id'] . ']' . '[img]' . System::baseUrl() . "/photo/" . $p[0]['resource-id'] . '-' . $best . '.' . $ext . '[/img][/url]' . "\n" ;

					$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $tagged[0] . '</title><id>' . $tagged[1] . '/' . $tagged[0] . '</id>';
					$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $tagged[1] . '" />' . "\n");
					if ($tagged[3]) {
						$arr['object'] .= xmlify('<link rel="photo" type="'.$p[0]['type'].'" href="' . $tagged[3]['photo'] . '" />' . "\n");
					}
					$arr['object'] .= '</link></object>' . "\n";

					$arr['target'] = '<target><type>' . ACTIVITY_OBJ_IMAGE . '</type><title>' . $p[0]['desc'] . '</title><id>'
						. System::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource-id'] . '</id>';
					$arr['target'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . System::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $p[0]['resource-id'] . '" />' . "\n" . '<link rel="preview" type="'.$p[0]['type'].'" href="' . System::baseUrl() . "/photo/" . $p[0]['resource-id'] . '-' . $best . '.' . $ext . '" />') . '</link></target>';

					$item_id = Item::insert($arr);
					if ($item_id) {
						Worker::add(PRIORITY_HIGH, "Notifier", "tag", $item_id);
					}
				}
			}
		}
		goaway($_SESSION['photo_return']);
		return; // NOTREACHED
	}


	// default post action - upload a photo
	Addon::callHooks('photo_post_init', $_POST);

	// Determine the album to use
	$album    = x($_REQUEST, 'album') ? notags(trim($_REQUEST['album'])) : '';
	$newalbum = x($_REQUEST, 'newalbum') ? notags(trim($_REQUEST['newalbum'])) : '';

	logger('mod/photos.php: photos_post(): album= ' . $album . ' newalbum= ' . $newalbum , LOGGER_DEBUG);

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

	$r = q("SELECT * FROM `photo` WHERE `album` = '%s' AND `uid` = %d AND `created` > UTC_TIMESTAMP() - INTERVAL 3 HOUR ",
		dbesc($album),
		intval($page_owner_uid)
	);
	if (!DBM::is_result($r) || ($album == L10n::t('Profile Photos'))) {
		$visible = 1;
	} else {
		$visible = 0;
	}

	if (x($_REQUEST, 'not_visible') && $_REQUEST['not_visible'] !== 'false') {
		$visible = 0;
	}

	$group_allow   = defaults($_REQUEST, 'group_allow'  , []);
	$contact_allow = defaults($_REQUEST, 'contact_allow', []);
	$group_deny    = defaults($_REQUEST, 'group_deny'   , []);
	$contact_deny  = defaults($_REQUEST, 'contact_deny' , []);

	$str_group_allow   = perms2str(is_array($group_allow)   ? $group_allow   : explode(',', $group_allow));
	$str_contact_allow = perms2str(is_array($contact_allow) ? $contact_allow : explode(',', $contact_allow));
	$str_group_deny    = perms2str(is_array($group_deny)    ? $group_deny    : explode(',', $group_deny));
	$str_contact_deny  = perms2str(is_array($contact_deny)  ? $contact_deny  : explode(',', $contact_deny));

	$ret = ['src' => '', 'filename' => '', 'filesize' => 0, 'type' => ''];

	Addon::callHooks('photo_post_file', $ret);

	if (x($ret, 'src') && x($ret, 'filesize')) {
		$src      = $ret['src'];
		$filename = $ret['filename'];
		$filesize = $ret['filesize'];
		$type     = $ret['type'];
		$error    = UPLOAD_ERR_OK;
	} else {
		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename($_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$type     = $_FILES['userfile']['type'];
		$error    = $_FILES['userfile']['error'];
	}

	if ($error !== UPLOAD_ERR_OK) {
		switch ($error) {
			case UPLOAD_ERR_INI_SIZE:
				notice(L10n::t('Image exceeds size limit of %s', ini_get('upload_max_filesize')) . EOL);
				break;
			case UPLOAD_ERR_FORM_SIZE:
				notice(L10n::t('Image exceeds size limit of %s', formatBytes(defaults($_REQUEST, 'MAX_FILE_SIZE', 0))) . EOL);
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
		Addon::callHooks('photo_post_end', $foo);
		return;
	}

	if ($type == "") {
		$type = Image::guessType($filename);
	}

	logger('photos: upload: received file: ' . $filename . ' as ' . $src . ' ('. $type . ') ' . $filesize . ' bytes', LOGGER_DEBUG);

	$maximagesize = Config::get('system', 'maximagesize');

	if ($maximagesize && ($filesize > $maximagesize)) {
		notice(L10n::t('Image exceeds size limit of %s', formatBytes($maximagesize)) . EOL);
		@unlink($src);
		$foo = 0;
		Addon::callHooks('photo_post_end', $foo);
		return;
	}

	if (!$filesize) {
		notice(L10n::t('Image file is empty.') . EOL);
		@unlink($src);
		$foo = 0;
		Addon::callHooks('photo_post_end', $foo);
		return;
	}

	logger('mod/photos.php: photos_post(): loading the contents of ' . $src , LOGGER_DEBUG);

	$imagedata = @file_get_contents($src);

	$Image = new Image($imagedata, $type);

	if (!$Image->isValid()) {
		logger('mod/photos.php: photos_post(): unable to process image' , LOGGER_DEBUG);
		notice(L10n::t('Unable to process image.') . EOL);
		@unlink($src);
		$foo = 0;
		Addon::callHooks('photo_post_end',$foo);
		killme();
	}

	$exif = $Image->orient($src);
	@unlink($src);

	$max_length = Config::get('system', 'max_image_length');
	if (!$max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$Image->scaleDown($max_length);
	}

	$width  = $Image->getWidth();
	$height = $Image->getHeight();

	$smallest = 0;

	$photo_hash = Photo::newResource();

	$r = Photo::store($Image, $page_owner_uid, $visitor, $photo_hash, $filename, $album, 0 , 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);

	if (!$r) {
		logger('mod/photos.php: photos_post(): image store failed', LOGGER_DEBUG);
		notice(L10n::t('Image upload failed.') . EOL);
		killme();
	}

	if ($width > 640 || $height > 640) {
		$Image->scaleDown(640);
		Photo::store($Image, $page_owner_uid, $visitor, $photo_hash, $filename, $album, 1, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 1;
	}

	if ($width > 320 || $height > 320) {
		$Image->scaleDown(320);
		Photo::store($Image, $page_owner_uid, $visitor, $photo_hash, $filename, $album, 2, 0, $str_contact_allow, $str_group_allow, $str_contact_deny, $str_group_deny);
		$smallest = 2;
	}

	$uri = item_new_uri($a->get_hostname(), $page_owner_uid);

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

	$arr['guid']          = get_guid(32);
	$arr['uid']           = $page_owner_uid;
	$arr['uri']           = $uri;
	$arr['parent-uri']    = $uri;
	$arr['type']          = 'photo';
	$arr['wall']          = 1;
	$arr['resource-id']   = $photo_hash;
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

	$arr['body']          = '[url=' . System::baseUrl() . '/photos/' . $owner_record['nickname'] . '/image/' . $photo_hash . ']'
				. '[img]' . System::baseUrl() . "/photo/{$photo_hash}-{$smallest}.".$Image->getExt() . '[/img]'
				. '[/url]';

	$item_id = Item::insert($arr);
	// Update the photo albums cache
	Photo::clearAlbumCache($page_owner_uid);

	if ($visible) {
		Worker::add(PRIORITY_HIGH, "Notifier", 'wall-new', $item_id);
	}

	Addon::callHooks('photo_post_end', intval($item_id));

	// addon uploaders should call "killme()" [e.g. exit] within the photo_post_end hook
	// if they do not wish to be redirected

	goaway($_SESSION['photo_return']);
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
	// photos/name/image/xxxxx
	// photos/name/image/xxxxx/edit

	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	require_once 'include/security.php';
	require_once 'include/conversation.php';

	if (!x($a->data,'user')) {
		notice(L10n::t('No photos selected') . EOL );
		return;
	}

	$phototypes = Image::supportedTypes();

	$_SESSION['photo_return'] = $a->cmd;

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

	$owner_uid = $a->data['user']['uid'];

	$community_page = (($a->data['user']['page-flags'] == PAGE_COMMUNITY) ? true : false);

	if (local_user() && (local_user() == $owner_uid)) {
		$can_post = true;
	} else {
		if ($community_page && remote_user()) {
			if (is_array($_SESSION['remote'])) {
				foreach ($_SESSION['remote'] as $v) {
					if ($v['uid'] == $owner_uid) {
						$contact_id = $v['cid'];
						break;
					}
				}
			}
			if ($contact_id) {

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
	if (remote_user() && !$visitor) {
		$contact_id = 0;
		if (is_array($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $v) {
				if ($v['uid'] == $owner_uid) {
					$contact_id = $v['cid'];
					break;
				}
			}
		}
		if ($contact_id) {
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

	if (!$remote_contact && local_user()) {
		$contact_id = $_SESSION['cid'];
		$contact = $a->contact;
	}

	if ($a->data['user']['hidewall'] && (local_user() != $owner_uid) && !$remote_contact) {
		notice(L10n::t('Access to this item is restricted.') . EOL);
		return;
	}

	$sql_extra = permissions_sql($owner_uid, $remote_contact, $groups);

	$o = "";

	// tabs
	$is_owner = (local_user() && (local_user() == $owner_uid));
	$o .= Profile::getTabs($a, $is_owner, $a->data['user']['nickname']);

	// Display upload form
	if ($datatype === 'upload') {
		if (!$can_post) {
			notice(L10n::t('Permission denied.'));
			return;
		}

		$selname = $datum ? hex2bin($datum) : '';

		$albumselect = '';

		$albumselect .= '<option value="" ' . (!$selname ? ' selected="selected" ' : '') . '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		if (count($a->data['albums'])) {
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

		Addon::callHooks('photo_upload_form',$ret);

		$default_upload_box = replace_macros(get_markup_template('photos_default_uploader_box.tpl'), []);
		$default_upload_submit = replace_macros(get_markup_template('photos_default_uploader_submit.tpl'), [
			'$submit' => L10n::t('Submit'),
		]);

		$usage_message = '';

		$tpl = get_markup_template('photos_upload.tpl');

		$aclselect_e = ($visitor ? '' : Acl::getFullSelectorHTML($a->user));

		$o .= replace_macros($tpl,[
			'$pagename' => L10n::t('Upload Photos'),
			'$sessid' => session_id(),
			'$usage' => $usage_message,
			'$nickname' => $a->data['user']['nickname'],
			'$newalbum' => L10n::t('New album name: '),
			'$existalbumtext' => L10n::t('or existing album name: '),
			'$nosharetext' => L10n::t('Do not show a status post for this upload'),
			'$albumselect' => $albumselect,
			'$permissions' => L10n::t('Permissions'),
			'$aclselect' => $aclselect_e,
			'$alt_uploader' => $ret['addon_text'],
			'$default_upload_box' => ($ret['default_upload'] ? $default_upload_box : ''),
			'$default_upload_submit' => ($ret['default_upload'] ? $default_upload_submit : ''),
			'$uploadurl' => $ret['post_url'],

			// ACL permissions box
			'$group_perms' => L10n::t('Show to Groups'),
			'$contact_perms' => L10n::t('Show to Contacts'),
			'$return_path' => $a->query_string,
		]);

		return $o;
	}

	// Display a single photo album
	if ($datatype === 'album') {
		$album = hex2bin($datum);

		$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` = '%s'
			AND `scale` <= 4 $sql_extra GROUP BY `resource-id`",
			intval($owner_uid),
			dbesc($album)
		);
		if (DBM::is_result($r)) {
			$a->set_pager_total(count($r));
			$a->set_pager_itemspage(20);
		}

		/// @TODO I have seen this many times, maybe generalize it script-wide and encapsulate it?
		$order_field = defaults($_GET, 'order', '');
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
			dbesc($album),
			intval($a->pager['start']),
			intval($a->pager['itemspage'])
		);

		// edit album name
		if ($cmd === 'edit') {
			if (($album !== L10n::t('Profile Photos')) && ($album !== 'Contact Photos') && ($album !== L10n::t('Contact Photos'))) {
				if ($can_post) {
					$edit_tpl = get_markup_template('album_edit.tpl');

					$album_e = $album;

					$o .= replace_macros($edit_tpl,[
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
			}
		}

		if ($order_field === 'posted') {
			$order =  [L10n::t('Show Newest First'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album)];
		} else {
			$order = [L10n::t('Show Oldest First'), 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($album) . '?f=&order=posted'];
		}

		$photos = [];

		if (DBM::is_result($r)) {
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

		$tpl = get_markup_template('photo_album.tpl');
		$o .= replace_macros($tpl, [
				'$photos' => $photos,
				'$album' => $album,
				'$can_post' => $can_post,
				'$upload' => [L10n::t('Upload New Photos'), 'photos/' . $a->data['user']['nickname'] . '/upload/' . bin2hex($album)],
				'$order' => $order,
				'$edit' => $edit,
				'$paginate' => paginate($a),
			]);

		return $o;

	}

	// Display one photo
	if ($datatype === 'image') {
		// fetch image, item containing image, then comments
		$ph = q("SELECT * FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'
			$sql_extra ORDER BY `scale` ASC ",
			intval($owner_uid),
			dbesc($datum)
		);

		if (!DBM::is_result($ph)) {
			$ph = q("SELECT `id` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s'
				LIMIT 1",
				intval($owner_uid),
				dbesc($datum)
			);
			if (DBM::is_result($ph)) {
				notice(L10n::t('Permission denied. Access to this item may be restricted.'));
			} else {
				notice(L10n::t('Photo not available') . EOL );
			}
			return;
		}

		$prevlink = '';
		$nextlink = '';

		/// @todo This query is totally bad, the whole functionality has to be changed
		// The query leads to a really intense used index.
		// By now we hide it if someone wants to.
		if (!Config::get('system', 'no_count', false)) {
			$order_field = defaults($_GET, 'order', '');
			if ($order_field === 'posted') {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}

			$prvnxt = q("SELECT `resource-id` FROM `photo` WHERE `album` = '%s' AND `uid` = %d AND `scale` = 0
				$sql_extra ORDER BY `created` $order ",
				dbesc($ph[0]['album']),
				intval($owner_uid)
			);

			if (DBM::is_result($prvnxt)) {
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
				$edit_suffix = ((($cmd === 'edit') && $can_post) ? '/edit' : '');
				$prevlink = 'photos/' . $a->data['user']['nickname'] . '/image/' . $prvnxt[$prv]['resource-id'] . $edit_suffix . ($order_field === 'posted' ? '?f=&order=posted' : '');
				$nextlink = 'photos/' . $a->data['user']['nickname'] . '/image/' . $prvnxt[$nxt]['resource-id'] . $edit_suffix . ($order_field === 'posted' ? '?f=&order=posted' : '');
 			}
		}

		if (count($ph) == 1)
			$hires = $lores = $ph[0];
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
		$lock = null;

		if ($can_post && ($ph[0]['uid'] == $owner_uid)) {
			$tools = [
				'edit'	=> ['photos/' . $a->data['user']['nickname'] . '/image/' . $datum . (($cmd === 'edit') ? '' : '/edit'), (($cmd === 'edit') ? L10n::t('View photo') : L10n::t('Edit photo'))],
				'profile'=>['profile_photo/use/'.$ph[0]['resource-id'], L10n::t('Use as profile photo')],
			];

			// lock
			$lock = ( ( ($ph[0]['uid'] == local_user()) && (strlen($ph[0]['allow_cid']) || strlen($ph[0]['allow_gid'])
					|| strlen($ph[0]['deny_cid']) || strlen($ph[0]['deny_gid'])) )
					? L10n::t('Private Message')
					: Null);


		}

		if ( $cmd === 'edit') {
			$tpl = get_markup_template('photo_edit_head.tpl');
			$a->page['htmlhead'] .= replace_macros($tpl,[
				'$prevlink' => $prevlink,
				'$nextlink' => $nextlink
			]);
		}

		if ($prevlink)
			$prevlink = [$prevlink, '<div class="icon prev"></div>'] ;

		$photo = [
			'href' => 'photo/' . $hires['resource-id'] . '-' . $hires['scale'] . '.' . $phototypes[$hires['type']],
			'title'=> L10n::t('View Full Size'),
			'src'  => 'photo/' . $lores['resource-id'] . '-' . $lores['scale'] . '.' . $phototypes[$lores['type']] . '?f=&_u=' . DateTimeFormat::utcNow('ymdhis'),
			'height' => $hires['height'],
			'width' => $hires['width'],
			'album' => $hires['album'],
			'filename' => $hires['filename'],
		];

		if ($nextlink) {
			$nextlink = [$nextlink, '<div class="icon next"></div>'];
		}


		// Do we have an item for this photo?

		// FIXME! - replace following code to display the conversation with our normal
		// conversation functions so that it works correctly and tracks changes
		// in the evolving conversation code.
		// The difference is that we won't be displaying the conversation head item
		// as a "post" but displaying instead the photo it is linked to

		$linked_items = q("SELECT * FROM `item` WHERE `resource-id` = '%s' $sql_extra LIMIT 1",
			dbesc($datum)
		);

		$map = null;
		$link_item = [];

		if (DBM::is_result($linked_items)) {
			$link_item = $linked_items[0];

			$r = q("SELECT COUNT(*) AS `total`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `parent-uri` = '%s' AND `uri` != '%s' AND `item`.`deleted` = 0 and `item`.`moderated` = 0
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`uid` = %d
				$sql_extra ",
				dbesc($link_item['uri']),
				dbesc($link_item['uri']),
				intval($link_item['uid'])

			);

			if (DBM::is_result($r)) {
				$a->set_pager_total($r[0]['total']);
			}


			$r = q("SELECT `item`.*, `item`.`id` AS `item_id`,
				`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`network`,
				`contact`.`rel`, `contact`.`thumb`, `contact`.`self`,
				`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`
				FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				WHERE `parent-uri` = '%s' AND `uri` != '%s' AND `item`.`deleted` = 0 and `item`.`moderated` = 0
				AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `item`.`uid` = %d
				$sql_extra
				ORDER BY `parent` DESC, `id` ASC LIMIT %d ,%d ",
				dbesc($link_item['uri']),
				dbesc($link_item['uri']),
				intval($link_item['uid']),
				intval($a->pager['start']),
				intval($a->pager['itemspage'])

			);

			if (local_user() && (local_user() == $link_item['uid'])) {
				Item::update(['unseen' => false], ['parent' => $link_item['parent']]);
			}

			if ($link_item['coord']) {
				$map = Map::byCoordinates($link_item['coord']);
			}
		}

		$tags = null;

		if (count($linked_items) && strlen($link_item['tag'])) {
			$arr = explode(',', $link_item['tag']);
			// parse tags and add links
			$tag_str = '';
			foreach ($arr as $t) {
				if (strlen($tag_str)) {
					$tag_str .= ', ';
				}
				$tag_str .= BBCode::convert($t);
			}
			$tags = [L10n::t('Tags: '), $tag_str];
			if ($cmd === 'edit') {
				$tags[] = 'tagrm/' . $link_item['id'];
				$tags[] = L10n::t('[Remove any tag]');
			}
		}


		$edit = Null;
		if ($cmd === 'edit' && $can_post) {
			$edit_tpl = get_markup_template('photo_edit.tpl');

			$album_e = $ph[0]['album'];
			$caption_e = $ph[0]['desc'];
			$aclselect_e = Acl::getFullSelectorHTML($ph[0]);

			$edit = replace_macros($edit_tpl, [
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

				'$item_id' => defaults($link_item, 'id', 0),
				'$submit' => L10n::t('Submit'),
				'$delete' => L10n::t('Delete Photo'),

				// ACL permissions box
				'$group_perms' => L10n::t('Show to Groups'),
				'$contact_perms' => L10n::t('Show to Contacts'),
				'$return_path' => $a->query_string,
			]);
		}

		$like = '';
		$dislike = '';
		$likebuttons = '';
		$comments = '';
		$paginate = '';
		$responses = '';

		if (count($linked_items)) {
			$cmnt_tpl = get_markup_template('comment_item.tpl');
			$tpl = get_markup_template('photo_item.tpl');
			$return_url = $a->cmd;

			if ($can_post || can_write_wall($owner_uid)) {
				$like_tpl = get_markup_template('like_noshare.tpl');
				$likebuttons = replace_macros($like_tpl, [
					'$id' => $link_item['id'],
					'$likethis' => L10n::t("I like this \x28toggle\x29"),
					'$nolike' => (Feature::isEnabled(local_user(), 'dislike') ? L10n::t("I don't like this \x28toggle\x29") : ''),
					'$wait' => L10n::t('Please wait'),
					'$return_path' => $a->query_string,
				]);
			}

			if (!DBM::is_result($r)) {
				if (($can_post || can_write_wall($owner_uid))) {
					$comments .= replace_macros($cmnt_tpl, [
						'$return_path' => '',
						'$jsreload' => $return_url,
						'$type' => 'wall-comment',
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
						'$rand_num' => random_digits(12)
					]);
				}
			}

			$conv_responses = [
				'like' => ['title' => L10n::t('Likes','title')],'dislike' => ['title' => L10n::t('Dislikes','title')],
				'attendyes' => ['title' => L10n::t('Attending','title')], 'attendno' => ['title' => L10n::t('Not attending','title')], 'attendmaybe' => ['title' => L10n::t('Might attend','title')]
			];

			// display comments
			if (DBM::is_result($r)) {
				foreach ($r as $item) {
					builtin_activity_puller($item, $conv_responses);
				}

				if (x($conv_responses['like'], $link_item['uri'])) {
					$like = format_like($conv_responses['like'][$link_item['uri']], $conv_responses['like'][$link_item['uri'] . '-l'], 'like', $link_item['id']);
				}
				if (x($conv_responses['dislike'], $link_item['uri'])) {
					$dislike = format_like($conv_responses['dislike'][$link_item['uri']], $conv_responses['dislike'][$link_item['uri'] . '-l'], 'dislike', $link_item['id']);
				}

				if (($can_post || can_write_wall($owner_uid))) {
					$comments .= replace_macros($cmnt_tpl,[
						'$return_path' => '',
						'$jsreload' => $return_url,
						'$type' => 'wall-comment',
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
						'$rand_num' => random_digits(12)
					]);
				}

				foreach ($r as $item) {
					$comment = '';
					$template = $tpl;
					$sparkle = '';

					if ((activity_match($item['verb'], ACTIVITY_LIKE) || activity_match($item['verb'], ACTIVITY_DISLIKE)) && ($item['id'] != $item['parent'])) {
						continue;
					}

					$redirect_url = 'redir/' . $item['cid'];

					if (local_user() && ($item['contact-uid'] == local_user())
						&& ($item['network'] == NETWORK_DFRN) && !$item['self']) {
						$profile_url = $redirect_url;
						$sparkle = ' sparkle';
					} else {
						$profile_url = $item['url'];
						$sparkle = '';
					}

					$diff_author = (($item['url'] !== $item['author-link']) ? true : false);

					$profile_name   = ((strlen($item['author-name'])   && $diff_author) ? $item['author-name']   : $item['name']);
					$profile_avatar = ((strlen($item['author-avatar']) && $diff_author) ? $item['author-avatar'] : $item['thumb']);

					$profile_link = $profile_url;

					$dropping = (($item['contact-id'] == $contact_id) || ($item['uid'] == local_user()));
					$drop = [
						'dropping' => $dropping,
						'pagedrop' => false,
						'select' => L10n::t('Select'),
						'delete' => L10n::t('Delete'),
					];

					$name_e = $profile_name;
					$title_e = $item['title'];
					$body_e = BBCode::convert($item['body']);

					$comments .= replace_macros($template,[
						'$id' => $item['item_id'],
						'$profile_url' => $profile_link,
						'$name' => $name_e,
						'$thumb' => $profile_avatar,
						'$sparkle' => $sparkle,
						'$title' => $title_e,
						'$body' => $body_e,
						'$ago' => Temporal::getRelativeDate($item['created']),
						'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
						'$drop' => $drop,
						'$comment' => $comment
					]);

					if (($can_post || can_write_wall($owner_uid))) {
						$comments .= replace_macros($cmnt_tpl, [
							'$return_path' => '',
							'$jsreload' => $return_url,
							'$type' => 'wall-comment',
							'$id' => $item['item_id'],
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
							'$rand_num' => random_digits(12)
						]);
					}
				}
			}
			$response_verbs = ['like'];
			if (Feature::isEnabled($owner_uid, 'dislike')) {
				$response_verbs[] = 'dislike';
			}
			$responses = get_responses($conv_responses, $response_verbs, '', $link_item);

			$paginate = paginate($a);
		}

		$photo_tpl = get_markup_template('photo_view.tpl');
		$o .= replace_macros($photo_tpl, [
			'$id' => $ph[0]['id'],
			'$album' => [$album_link, $ph[0]['album']],
			'$tools' => $tools,
			'$lock' => $lock,
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

		$a->page['htmlhead'] .= "\n" . '<meta name="twitter:card" content="photo" />' . "\n";
		$a->page['htmlhead'] .= '<meta name="twitter:title" content="' . $photo["album"] . '" />' . "\n";
		$a->page['htmlhead'] .= '<meta name="twitter:image" content="' . $photo["href"] . '" />' . "\n";
		$a->page['htmlhead'] .= '<meta name="twitter:image:width" content="' . $photo["width"] . '" />' . "\n";
		$a->page['htmlhead'] .= '<meta name="twitter:image:height" content="' . $photo["height"] . '" />' . "\n";

		return $o;
	}

	// Default - show recent photos with upload link (if applicable)
	//$o = '';

	$r = q("SELECT `resource-id`, max(`scale`) AS `scale` FROM `photo` WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s'
		$sql_extra GROUP BY `resource-id`",
		intval($a->data['user']['uid']),
		dbesc('Contact Photos'),
		dbesc(L10n::t('Contact Photos'))
	);
	if (DBM::is_result($r)) {
		$a->set_pager_total(count($r));
		$a->set_pager_itemspage(20);
	}

	$r = q("SELECT `resource-id`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`filename`) AS `filename`,
		ANY_VALUE(`type`) AS `type`, ANY_VALUE(`album`) AS `album`, max(`scale`) AS `scale`,
		ANY_VALUE(`created`) AS `created` FROM `photo`
		WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s'
		$sql_extra GROUP BY `resource-id` ORDER BY `created` DESC LIMIT %d , %d",
		intval($a->data['user']['uid']),
		dbesc('Contact Photos'),
		dbesc(L10n::t('Contact Photos')),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);

	$photos = [];
	if (DBM::is_result($r)) {
		// "Twist" is only used for the duepunto theme with style "slackr"
		$twist = false;
		foreach ($r as $rr) {
			//hide profile photos to others
			if (!$is_owner && !remote_user() && ($rr['album'] == L10n::t('Profile Photos')))
				continue;

			$twist = !$twist;

			$ext = $phototypes[$rr['type']];

			$alt_e = $rr['filename'];
			$name_e = $rr['album'];

			$photos[] = [
				'id'		=> $rr['id'],
				'twist'		=> ' ' . ($twist ? 'rotleft' : 'rotright') . rand(2,4),
				'link'  	=> 'photos/' . $a->data['user']['nickname'] . '/image/' . $rr['resource-id'],
				'title' 	=> L10n::t('View Photo'),
				'src'     	=> 'photo/' . $rr['resource-id'] . '-' . ((($rr['scale']) == 6) ? 4 : $rr['scale']) . '.' . $ext,
				'alt'     	=> $alt_e,
				'album'	=> [
					'link'  => 'photos/' . $a->data['user']['nickname'] . '/album/' . bin2hex($rr['album']),
					'name'  => $name_e,
					'alt'   => L10n::t('View Album'),
				],

			];
		}
	}

	$tpl = get_markup_template('photos_recent.tpl');
	$o .= replace_macros($tpl, [
		'$title' => L10n::t('Recent Photos'),
		'$can_post' => $can_post,
		'$upload' => [L10n::t('Upload New Photos'), 'photos/'.$a->data['user']['nickname'].'/upload'],
		'$photos' => $photos,
		'$paginate' => paginate($a),
	]);

	return $o;
}
