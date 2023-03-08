<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Post;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Controller to edit a post
 */
class Edit extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var App\Page */
	protected $page;
	/** @var App\Mode */
	protected $mode;
	/** @var App */
	protected $app;
	/** @var bool */
	protected $isModal = false;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, App\Page $page, App\Mode $mode, App $app, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
		$this->page        = $page;
		$this->mode        = $mode;
		$this->app         = $app;
	}


	protected function content(array $request = []): string
	{
		$this->isModal = $request['mode'] ?? '' === 'none';

		if (!$this->session->getLocalUserId()) {
			$this->errorExit($this->t('Permission denied.'), HTTPException\UnauthorizedException::class);
		}

		$postId = $this->parameters['post_id'] ?? 0;

		if (empty($postId)) {
			$this->errorExit($this->t('Post not found.'), HTTPException\BadRequestException::class);
		}

		$fields = [
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'body', 'title', 'uri-id', 'wall', 'post-type', 'guid'
		];

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), $fields, [
			'id'  => $postId,
			'uid' => $this->session->getLocalUserId(),
		]);

		if (empty($item)) {
			$this->errorExit($this->t('Post not found.'), HTTPException\BadRequestException::class);
		}

		$user = User::getById($this->session->getLocalUserId());

		$output = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => $this->t('Edit post'),
		]);

		$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('jot-header.tpl'), [
			'$ispublic'  => '&nbsp;',
			'$geotag'    => '',
			'$nickname'  => $this->app->getLoggedInUserNickname(),
			'$is_mobile' => $this->mode->isMobile(),
		]);

		if (strlen($item['allow_cid']) || strlen($item['allow_gid']) || strlen($item['deny_cid']) || strlen($item['deny_gid'])) {
			$lockstate = 'lock';
		} else {
			$lockstate = 'unlock';
		}

		$item['body'] = Post\Media::addAttachmentsToBody($item['uri-id'], $item['body']);
		$item = Post\Media::addHTMLAttachmentToItem($item);

		$jotplugins = '';

		Hook::callAll('jot_tool', $jotplugins);

		$output .= Renderer::replaceMacros(Renderer::getMarkupTemplate('jot.tpl'), [
			'$is_edit'             => true,
			'$return_path'         => '/display/' . $item['guid'],
			'$action'              => 'item',
			'$share'               => $this->t('Save'),
			'$loading'             => $this->t('Loading...'),
			'$upload'              => $this->t('Upload photo'),
			'$shortupload'         => $this->t('upload photo'),
			'$attach'              => $this->t('Attach file'),
			'$shortattach'         => $this->t('attach file'),
			'$weblink'             => $this->t('Insert web link'),
			'$shortweblink'        => $this->t('web link'),
			'$video'               => $this->t('Insert video link'),
			'$shortvideo'          => $this->t('video link'),
			'$audio'               => $this->t('Insert audio link'),
			'$shortaudio'          => $this->t('audio link'),
			'$setloc'              => $this->t('Set your location'),
			'$shortsetloc'         => $this->t('set location'),
			'$noloc'               => $this->t('Clear browser location'),
			'$shortnoloc'          => $this->t('clear location'),
			'$wait'                => $this->t('Please wait'),
			'$permset'             => $this->t('Permission settings'),
			'$wall'                => $item['wall'],
			'$posttype'            => $item['post-type'],
			'$content'             => $this->undoPostTagging($item['body']),
			'$post_id'             => $postId,
			'$defloc'              => $user['default-location'],
			'$visitor'             => 'none',
			'$pvisit'              => 'none',
			'$emailcc'             => $this->t('CC: email addresses'),
			'$public'              => $this->t('Public post'),
			'$title'               => $item['title'],
			'$placeholdertitle'    => $this->t('Set title'),
			'$category'            => Post\Category::getCSVByURIId($item['uri-id'], $this->session->getLocalUserId(), Post\Category::CATEGORY),
			'$placeholdercategory' => (Feature::isEnabled($this->session->getLocalUserId(), 'categories') ? $this->t("Categories \x28comma-separated list\x29") : ''),
			'$emtitle'             => $this->t('Example: bob@example.com, mary@example.com'),
			'$lockstate'           => $lockstate,
			'$acl'                 => '',
			'$bang'                => ($lockstate === 'lock' ? '!' : ''),
			'$profile_uid'         => $this->session->getLocalUserId(),
			'$preview'             => $this->t('Preview'),
			'$jotplugins'          => $jotplugins,
			'$cancel'              => $this->t('Cancel'),
			'$rand_num'            => Crypto::randomDigits(12),

			// Formatting button labels
			'$edbold'   => $this->t('Bold'),
			'$editalic' => $this->t('Italic'),
			'$eduline'  => $this->t('Underline'),
			'$edquote'  => $this->t('Quote'),
			'$edcode'   => $this->t('Code'),
			'$edurl'    => $this->t('Link'),
			'$edattach' => $this->t('Link or Media'),

			//jot nav tab (used in some themes)
			'$message'      => $this->t('Message'),
			'$browser'      => $this->t('Browser'),
			'$shortpermset' => $this->t('Permissions'),

			'$compose_link_title' => $this->t('Open Compose page'),
		]);

		return $output;
	}

	/**
	 * Removes Tags from the item-body
	 *
	 * @param string $body The item body
	 *
	 * @return string the new item body without tagging
	 */
	protected function undoPostTagging(string $body)
	{
		$matches = null;
		$content = preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $body, $matches, PREG_SET_ORDER);
		if ($content) {
			foreach ($matches as $match) {
				if (in_array($match[1], ['!', '@'])) {
					$contact  = Contact::getByURL($match[2], false, ['addr']);
					$match[3] = empty($contact['addr']) ? $match[2] : $contact['addr'];
				}
				$body = str_replace($match[0], $match[1] . $match[3], $body);
			}
		}
		return $body;
	}

	/**
	 * Exists the current Module because of an error
	 *
	 * @param string $message        The error message
	 * @param string $exceptionClass In case it's a modal, throw an exception instead of an redirect
	 *
	 * @return void
	 */
	protected function errorExit(string $message, string $exceptionClass)
	{
		if ($this->isModal) {
			throw new $exceptionClass($message);
		} else {
			$this->sysMessages->addNotice($message);
			$this->baseUrl->redirect();
		}
	}
}
