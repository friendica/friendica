<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Pager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Security\Security;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Photos extends \Friendica\Module\BaseProfile
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var App\Page */
	private $page;
	/** @var IManageConfigValues */
	private $config;
	/** @var App */
	private $app;
	/** @var Database */
	private $database;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var ACLFormatter */
	private $aclFormatter;
	/** @var array owner-view record */
	private $owner;

	public function __construct(ACLFormatter $aclFormatter, SystemMessages $systemMessages, Database $database, App $app, IManageConfigValues $config, App\Page $page, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->page           = $page;
		$this->config         = $config;
		$this->app            = $app;
		$this->database       = $database;
		$this->systemMessages = $systemMessages;
		$this->aclFormatter   = $aclFormatter;

		$owner = Profile::load($this->app, $this->parameters['nickname'] ?? '', false);
		if (!$owner || $owner['account_removed'] || $owner['account_expired']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$this->owner = $owner;
	}

	protected function post(array $request = [])
	{
		if ($this->session->getLocalUserId() != $this->owner['uid']) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$str_contact_allow = isset($request['contact_allow']) ? $this->aclFormatter->toString($request['contact_allow']) : $this->owner['allow_cid'] ?? '';
		$str_circle_allow  = isset($request['circle_allow'])  ? $this->aclFormatter->toString($request['circle_allow'])  : $this->owner['allow_gid'] ?? '';
		$str_contact_deny  = isset($request['contact_deny'])  ? $this->aclFormatter->toString($request['contact_deny'])  : $this->owner['deny_cid']  ?? '';
		$str_circle_deny   = isset($request['circle_deny'])   ? $this->aclFormatter->toString($request['circle_deny'])   : $this->owner['deny_gid']  ?? '';

		$visibility = $request['visibility'] ?? '';
		if ($visibility === 'public') {
			// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
			$str_contact_allow = $str_circle_allow = $str_contact_deny = $str_circle_deny = '';
		} else if ($visibility === 'custom') {
			// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
			// case that would make it public. So we always append the author's contact id to the allowed contacts.
			// See https://github.com/friendica/friendica/issues/9672
			$str_contact_allow .= $this->aclFormatter->toString(Contact::getPublicIdByUserId($this->owner['uid']));
		}

		// default post action - upload a photo
		Hook::callAll('photo_post_init', $request);

		// Determine the album to use
		$album    = trim($request['album'] ?? '');
		$newalbum = trim($request['newalbum'] ?? '');

		$this->logger->debug('album= ' . $album . ' newalbum= ' . $newalbum);

		$album = $album ?: $newalbum ?: DateTimeFormat::localNow('Y');

		/*
		 * We create a wall item for every photo, but we don't want to
		 * overwhelm the data stream with a hundred newly uploaded photos.
		 * So we will make the first photo uploaded to this album in the last several hours
		 * visible by default, the rest will become visible over time when and if
		 * they acquire comments, likes, dislikes, and/or tags
		 */

		$r = Photo::selectToArray([], ['`album` = ? AND `uid` = ? AND `created` > ?', $album, $this->owner['uid'], DateTimeFormat::utc('now - 3 hours')]);
		if (!$r || ($album == $this->t(Photo::PROFILE_PHOTOS))) {
			$visible = 1;
		} else {
			$visible = 0;
		}

		if (!empty($request['not_visible']) && $request['not_visible'] !== 'false') {
			$visible = 0;
		}

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
					$this->systemMessages->addNotice($this->t('Image exceeds size limit of %s', ini_get('upload_max_filesize')));
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$this->systemMessages->addNotice($this->t('Image exceeds size limit of %s', Strings::formatBytes($request['MAX_FILE_SIZE'] ?? 0)));
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->systemMessages->addNotice($this->t('Image upload didn\'t complete, please try again'));
					break;
				case UPLOAD_ERR_NO_FILE:
					$this->systemMessages->addNotice($this->t('Image file is missing'));
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
				case UPLOAD_ERR_CANT_WRITE:
				case UPLOAD_ERR_EXTENSION:
					$this->systemMessages->addNotice($this->t('Server can\'t accept new file upload at this time, please contact your administrator'));
					break;
			}
			@unlink($src);
			$foo = 0;
			Hook::callAll('photo_post_end', $foo);
			return;
		}

		$this->logger->info('photos: upload: received file: ' . $filename . ' as ' . $src . ' ('. $type . ') ' . $filesize . ' bytes');

		$maximagesize = Strings::getBytesFromShorthand($this->config->get('system', 'maximagesize'));

		if ($maximagesize && ($filesize > $maximagesize)) {
			$this->systemMessages->addNotice($this->t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)));
			@unlink($src);
			$foo = 0;
			Hook::callAll('photo_post_end', $foo);
			return;
		}

		if (!$filesize) {
			$this->systemMessages->addNotice($this->t('Image file is empty.'));
			@unlink($src);
			$foo = 0;
			Hook::callAll('photo_post_end', $foo);
			return;
		}

		$this->logger->debug('loading contents', ['src' => $src]);

		$imagedata = @file_get_contents($src);

		$image = new Image($imagedata, $type, $filename);

		if (!$image->isValid()) {
			$this->logger->notice('unable to process image');
			$this->systemMessages->addNotice($this->t('Unable to process image.'));
			@unlink($src);
			$foo = 0;
			Hook::callAll('photo_post_end',$foo);
			return;
		}

		$exif = $image->orient($src);
		@unlink($src);

		$max_length = $this->config->get('system', 'max_image_length');
		if ($max_length > 0) {
			$image->scaleDown($max_length);
		}

		$resource_id = Photo::newResource();

		$preview = Photo::storeWithPreview($image, $this->owner['uid'], $resource_id, $filename, $filesize, $album, '', $str_contact_allow, $str_circle_allow, $str_contact_deny, $str_circle_deny);
		if ($preview < 0) {
			$this->logger->warning('image store failed');
			$this->systemMessages->addNotice($this->t('Image upload failed.'));
			return;
		}

		$uri = Item::newURI();

		// Create item container
		$lat = $lon = null;
		if (!empty($exif['GPS']) && Feature::isEnabled($this->owner['uid'], 'photo_location')) {
			$lat = Photo::getGps($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
			$lon = Photo::getGps($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
		}

		$arr = [];
		if ($lat && $lon) {
			$arr['coord'] = $lat . ' ' . $lon;
		}

		$arr['guid']          = System::createUUID();
		$arr['uid']           = $this->owner['uid'];
		$arr['uri']           = $uri;
		$arr['post-type']     = Item::PT_IMAGE;
		$arr['wall']          = 1;
		$arr['resource-id']   = $resource_id;
		$arr['contact-id']    = $this->owner['id'];
		$arr['owner-name']    = $this->owner['name'];
		$arr['owner-link']    = $this->owner['url'];
		$arr['owner-avatar']  = $this->owner['thumb'];
		$arr['author-name']   = $this->owner['name'];
		$arr['author-link']   = $this->owner['url'];
		$arr['author-avatar'] = $this->owner['thumb'];
		$arr['title']         = '';
		$arr['allow_cid']     = $str_contact_allow;
		$arr['allow_gid']     = $str_circle_allow;
		$arr['deny_cid']      = $str_contact_deny;
		$arr['deny_gid']      = $str_circle_deny;
		$arr['visible']       = $visible;
		$arr['origin']        = 1;

		$arr['body']          = Images::getBBCodeByResource($resource_id, $this->owner['nickname'], $preview, $image->getExt());

		$item_id = Item::insert($arr);
		// Update the photo albums cache
		Photo::clearAlbumCache($this->owner['uid']);

		Hook::callAll('photo_post_end', $item_id);

		// addon uploaders should call "exit()" within the photo_post_end hook
		// if they do not wish to be redirected

		$this->baseUrl->redirect($this->session->get('photo_return') ?? 'profile/' . $this->owner['nickname'] . '/photos');
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HttpException\ForbiddenException($this->t('Public access denied.'));
		}

		$owner_uid = $this->owner['uid'];
		$is_owner  = $this->session->getLocalUserId() == $owner_uid;

		if ($this->owner['hidewall'] && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $this->owner['nickname'] . '/restricted');
		}

		$this->session->set('photo_return', $this->args->getCommand());

		$sql_extra = Security::getPermissionsSQLByUserId($owner_uid);

		$photo = $this->database->toArray($this->database->p(
			"SELECT COUNT(DISTINCT `resource-id`) AS `count`
			FROM `photo`
			WHERE `uid` = ?
			  AND `photo-type` = ?
			  $sql_extra",
			$this->owner['uid'],
			Photo::DEFAULT,
		));
		$total = $photo[0]['count'];

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 20);

		$photos = $this->database->toArray($this->database->p(
			"SELECT
				`resource-id`,
				MIN(`id`) AS `id`,
				MIN(`filename`) AS `filename`,
				MIN(`type`) AS `type`,
				MIN(`album`) AS `album`,
				MAX(`scale`) AS `scale`,
				MIN(`created`) AS `created`
			FROM `photo`
			WHERE `uid` = ?
			  AND `photo-type` = ?
			  $sql_extra
			GROUP BY `resource-id`
			ORDER BY `created` DESC
			LIMIT ? , ?",
			$this->owner['uid'],
			Photo::DEFAULT,
			$pager->getStart(),
			$pager->getItemsPerPage()
		));

		$photos = array_map(function ($photo){
			return [
				'id'    => $photo['id'],
				'link'  => 'photos/' . $this->owner['nickname'] . '/image/' . $photo['resource-id'],
				'title' => $this->t('View Photo'),
				'src'   => 'photo/' . $photo['resource-id'] . '-' . ((($photo['scale']) == 6) ? 4 : $photo['scale']) . Images::getExtensionByMimeType($photo['type']),
				'alt'   => $photo['filename'],
				'album' => [
					'link' => 'photos/' . $this->owner['nickname'] . '/album/' . bin2hex($photo['album']),
					'name' => $photo['album'],
					'alt'  => $this->t('View Album'),
				],
			];
		}, $photos);

		$tpl = Renderer::getMarkupTemplate('photos_head.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => $this->t('everybody')
		]);

		if ($albums = Photo::getAlbums($this->owner['uid'])) {
			$albums = array_map(function ($album) {
				return [
					'text'      => $album['album'],
					'total'     => $album['total'],
					'url'       => 'photos/' . $this->owner['nickname'] . '/album/' . bin2hex($album['album']),
					'urlencode' => urlencode($album['album']),
					'bin2hex'   => bin2hex($album['album'])
				];
			}, $albums);

			$photo_albums_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('photo_albums.tpl'), [
				'$nick'     => $this->owner['nickname'],
				'$title'    => $this->t('Photo Albums'),
				'$recent'   => $this->t('Recent Photos'),
				'$albums'   => $albums,
				'$upload'   => [$this->t('Upload New Photos'), 'photos/' . $this->owner['nickname'] . '/upload'],
				'$can_post' => $this->session->getLocalUserId() && $this->owner['uid'] == $this->session->getLocalUserId(),
			]);
		}

		// Removing vCard for owner
		if ($is_owner) {
			$this->page['aside'] = '';
		}

		if (!empty($photo_albums_widget)) {
			$this->page['aside'] .= $photo_albums_widget;
		}

		$o = self::getTabsHTML('photos', $is_owner, $this->owner['nickname'], Profile::getByUID($this->owner['uid'])['hide-friends'] ?? false);

		$tpl = Renderer::getMarkupTemplate('photos_recent.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $this->t('Recent Photos'),
			'$can_post' => $is_owner,
			'$upload'   => [$this->t('Upload New Photos'), 'photos/' . $this->owner['nickname'] . '/upload'],
			'$photos'   => $photos,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
