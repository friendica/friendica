<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Security\Security;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
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

	public function __construct(Database $database, App $app, IManageConfigValues $config, App\Page $page, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session  = $session;
		$this->page     = $page;
		$this->config   = $config;
		$this->app      = $app;
		$this->database = $database;
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HttpException\ForbiddenException($this->t('Public access denied.'));
		}

		$owner = Profile::load($this->app, $this->parameters['nickname'] ?? '');
		if (!$owner || $owner['account_removed'] || $owner['account_expired']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$owner_uid = $owner['uid'];
		$is_owner  = $this->session->getLocalUserId() && ($this->session->getLocalUserId() == $owner_uid);

		$remote_contact = false;
		if ($this->session->getRemoteContactID($owner_uid)) {
			$contact_id = $this->session->getRemoteContactID($owner_uid);

			$contact        = Contact::getContactForUser($contact_id, $owner_uid, ['blocked', 'pending']);
			$remote_contact = $contact && !$contact['blocked'] && !$contact['pending'];
		}

		if ($owner['hidewall'] && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $owner['nickname'] . '/restricted');
		}

		$this->session->set('photo_return', $this->args->getCommand());

		$sql_extra = Security::getPermissionsSQLByUserId($owner_uid);

		$photo = $this->database->toArray($this->database->p(
			"SELECT COUNT(DISTINCT `resource-id`) AS `count`
			FROM `photo`
			WHERE `uid` = ?
			  AND `photo-type` = ?
			  $sql_extra",
			$owner['uid'],
			Photo::DEFAULT,
		));
		$total = $photo[0]['count'];

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 20);

		$photos = $this->database->toArray($this->database->p(
			"SELECT
				`resource-id`,
				ANY_VALUE(`id`) AS `id`,
				ANY_VALUE(`filename`) AS `filename`,
				ANY_VALUE(`type`) AS `type`,
				ANY_VALUE(`album`) AS `album`,
				max(`scale`) AS `scale`,
				ANY_VALUE(`created`) AS `created`
			FROM `photo`
			WHERE `uid` = ?
			  AND `photo-type` = ?
			  $sql_extra
			GROUP BY `resource-id`
			ORDER BY `created` DESC
		    LIMIT ? , ?",
			$owner['uid'],
			Photo::DEFAULT,
			$pager->getStart(),
			$pager->getItemsPerPage()
		));

		$phototypes = Images::supportedTypes();

		$photos = array_map(function ($photo) use ($owner, $phototypes) {
			return [
				'id'    => $photo['id'],
				'link'  => 'photos/' . $owner['nickname'] . '/image/' . $photo['resource-id'],
				'title' => $this->t('View Photo'),
				'src'   => 'photo/' . $photo['resource-id'] . '-' . ((($photo['scale']) == 6) ? 4 : $photo['scale']) . '.' . $phototypes[$photo['type']],
				'alt'   => $photo['filename'],
				'album' => [
					'link' => 'photos/' . $owner['nickname'] . '/album/' . bin2hex($photo['album']),
					'name' => $photo['album'],
					'alt'  => $this->t('View Album'),
				],
			];
		}, $photos);

		$tpl = Renderer::getMarkupTemplate('photos_head.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => $this->t('everybody')
		]);

		if ($albums = Photo::getAlbums($owner['uid'])) {
			$albums = array_map(function ($album) use ($owner) {
				return [
					'text'      => $album['album'],
					'total'     => $album['total'],
					'url'       => 'photos/' . $owner['nickname'] . '/album/' . bin2hex($album['album']),
					'urlencode' => urlencode($album['album']),
					'bin2hex'   => bin2hex($album['album'])
				];
			}, $albums);

			$photo_albums_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('photo_albums.tpl'), [
				'$nick'     => $owner['nickname'],
				'$title'    => $this->t('Photo Albums'),
				'$recent'   => $this->t('Recent Photos'),
				'$albums'   => $albums,
				'$upload'   => [$this->t('Upload New Photos'), 'photos/' . $owner['nickname'] . '/upload'],
				'$can_post' => $this->session->getLocalUserId() && $owner['uid'] == $this->session->getLocalUserId(),
			]);
		}

		if (!empty($photo_albums_widget)) {
			$this->page['aside'] .= $photo_albums_widget;
		}

		$o = self::getTabsHTML('photos', $is_owner, $owner['nickname'], Profile::getByUID($owner['uid'])['hide-friends'] ?? false);

		$tpl = Renderer::getMarkupTemplate('photos_recent.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $this->t('Recent Photos'),
			'$can_post' => $is_owner || $remote_contact && $owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY,
			'$upload'   => [$this->t('Upload New Photos'), 'photos/' . $owner['nickname'] . '/upload'],
			'$photos'   => $photos,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
