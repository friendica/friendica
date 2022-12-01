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

namespace Friendica\Content;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Photo;
use Friendica\Model\Tag;
use Friendica\Model\Post;
use Friendica\Protocol\Activity;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Friendica\Util\XML;

/**
 * A content helper class for displaying items
 */
class Item
{
	/** @var Activity */
	private $activity;
	/** @var L10n */
	private $l10n;
	/** @var Profiler */
	private $profiler;
	/** @var IHandleUserSessions */
	private $userSession;

	public function __construct(Profiler $profiler, Activity $activity, L10n $l10n, IHandleUserSessions $userSession)
	{
		$this->profiler    = $profiler;
		$this->activity    = $activity;
		$this->l10n        = $l10n;
		$this->userSession = $userSession;
	}

	/**
	 * Return array with details for categories and folders for an item
	 *
	 * @param array $item
	 * @param int   $uid
	 * @return [array, array]
	 *
	 * [
	 *      [ // categories array
	 *          {
	 *               'name': 'category name',
	 *               'removeurl': 'url to remove this category',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ],
	 *       [ //folders array
	 *			{
	 *               'name': 'folder name',
	 *               'removeurl': 'url to remove this folder',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ]
	 *  ]
	 */
	public function determineCategoriesTerms(array $item, int $uid = 0): array
	{
		$categories = [];
		$folders = [];
		$first = true;

		$uid = $item['uid'] ?: $uid;

		if (empty($item['has-categories'])) {
			return [$categories, $folders];
		}

		foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid, Post\Category::CATEGORY) as $savedFolderName) {
			if (!empty($item['author-link'])) {
				$url = $item['author-link'] . "?category=" . rawurlencode($savedFolderName);
			} else {
				$url = '#';
			}
			$categories[] = [
				'name' => $savedFolderName,
				'url' => $url,
				'removeurl' => $this->userSession->getLocalUserId() == $uid ? 'filerm/' . $item['id'] . '?cat=' . rawurlencode($savedFolderName) : '',
				'first' => $first,
				'last' => false
			];
			$first = false;
		}

		if (count($categories)) {
			$categories[count($categories) - 1]['last'] = true;
		}

		if ($this->userSession->getLocalUserId() == $uid) {
			foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid, Post\Category::FILE) as $savedFolderName) {
				$folders[] = [
					'name' => $savedFolderName,
					'url' => "#",
					'removeurl' => $this->userSession->getLocalUserId() == $uid ? 'filerm/' . $item['id'] . '?term=' . rawurlencode($savedFolderName) : '',
					'first' => $first,
					'last' => false
				];
				$first = false;
			}
		}

		if (count($folders)) {
			$folders[count($folders) - 1]['last'] = true;
		}

		return [$categories, $folders];
	}

	/**
	 * This function removes the tag $tag from the text $body and replaces it with
	 * the appropriate link.
	 *
	 * @param string $body        the text to replace the tag in
	 * @param int    $profile_uid the user id to replace the tag for (0 = anyone)
	 * @param string $tag         the tag to replace
	 * @param string $network     The network of the post
	 *
	 * @return array|bool ['replaced' => $replaced, 'contact' => $contact] or "false" on if already replaced
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function replaceTag(string &$body, int $profile_uid, string $tag, string $network = '')
	{
		$replaced = false;

		//is it a person tag?
		if (Tag::isType($tag, Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION)) {
			$tag_type = substr($tag, 0, 1);
			//is it already replaced?
			if (strpos($tag, '[url=')) {
				return $replaced;
			}

			//get the person's name
			$name = substr($tag, 1);

			// Sometimes the tag detection doesn't seem to work right
			// This is some workaround
			$nameparts = explode(' ', $name);
			$name = $nameparts[0];

			// Try to detect the contact in various ways
			if (strpos($name, 'http://') || strpos($name, '@')) {
				$contact = Contact::getByURLForUser($name, $profile_uid);
			} else {
				$contact = false;
				$fields = ['id', 'url', 'nick', 'name', 'alias', 'network', 'forum', 'prv'];

				if (strrpos($name, '+')) {
					// Is it in format @nick+number?
					$tagcid = intval(substr($name, strrpos($name, '+') + 1));
					$contact = DBA::selectFirst('contact', $fields, ['id' => $tagcid, 'uid' => $profile_uid]);
				}

				// select someone by nick in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`nick` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`attag` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				//select someone by name in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['name' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by nick in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`nick` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`attag` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by name in any network
				if (!DBA::isResult($contact)) {
					$condition = ['name' => $name, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}
			}

			// Check if $contact has been successfully loaded
			if (DBA::isResult($contact)) {
				$profile = $contact['url'];
				$newname = ($contact['name'] ?? '') ?: $contact['nick'];
			}

			//if there is an url for this persons profile
			if (isset($profile) && ($newname != '')) {
				$replaced = true;
				// create profile link
				$profile = str_replace(',', '%2c', $profile);
				$newtag = $tag_type.'[url=' . $profile . ']' . $newname . '[/url]';
				$body = str_replace($tag_type . $name, $newtag, $body);
			}
		}

		return ['replaced' => $replaced, 'contact' => $contact];
	}

	/**
	 * Render actions localized
	 *
	 * @param array $item
	 * @return void
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function localize(array &$item)
	{
		$this->profiler->startRecording('rendering');
		/// @todo The following functionality needs to be cleaned up.
		if (!empty($item['verb'])) {
			$xmlhead = '<?xml version="1.0" encoding="UTF-8" ?>';

			if ($this->activity->match($item['verb'], Activity::TAG)) {
				$fields = ['author-id', 'author-link', 'author-name', 'author-network',
					'verb', 'object-type', 'resource-id', 'body', 'plink'];
				$obj = Post::selectFirst($fields, ['uri' => $item['parent-uri']]);
				if (!DBA::isResult($obj)) {
					$this->profiler->stopRecording();
					return;
				}

				$author_arr = [
					'uid' => 0,
					'id' => $item['author-id'],
					'network' => $item['author-network'],
					'url' => $item['author-link'],
				];
				$author  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $item['author-name'] . '[/url]';

				$author_arr = [
					'uid' => 0,
					'id' => $obj['author-id'],
					'network' => $obj['author-network'],
					'url' => $obj['author-link'],
				];
				$objauthor  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $obj['author-name'] . '[/url]';

				switch ($obj['verb']) {
					case Activity::POST:
						switch ($obj['object-type']) {
							case Activity\ObjectType::EVENT:
								$post_type = $this->l10n->t('event');
								break;
							default:
								$post_type = $this->l10n->t('status');
						}
						break;

					default:
						if ($obj['resource-id']) {
							$post_type = $this->l10n->t('photo');
							$m=[]; preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
							$rr['plink'] = $m[1];
						} else {
							$post_type = $this->l10n->t('status');
						}
						// Let's break everthing ... ;-)
						break;
				}
				$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

				$parsedobj = XML::parseString($xmlhead . $item['object']);

				$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
				$item['body'] = $this->l10n->t('%1$s tagged %2$s\'s %3$s with %4$s', $author, $objauthor, $plink, $tag);
			}
		}

		$this->profiler->stopRecording();
	}

	/**
	 * Renders photo menu based on item
	 *
	 * @param array $item
	 * @param string $formSecurityToken
	 * @return string
	 */
	public function photoMenu(array $item, string $formSecurityToken): string
	{
		$this->profiler->startRecording('rendering');
		$sub_link = $contact_url = $pm_url = $status_link = '';
		$photos_link = $posts_link = $block_link = $ignore_link = '';

		if ($this->userSession->getLocalUserId() && $this->userSession->getLocalUserId() == $item['uid'] && $item['gravity'] == ItemModel::GRAVITY_PARENT && !$item['self'] && !$item['mention']) {
			$sub_link = 'javascript:doFollowThread(' . $item['id'] . '); return false;';
		}

		$author = [
			'uid' => 0,
			'id' => $item['author-id'],
			'network' => $item['author-network'],
			'url' => $item['author-link'],
		];
		$profile_link = Contact::magicLinkByContact($author, $item['author-link']);
		$sparkle = (strpos($profile_link, 'contact/redir/') === 0);

		$cid = 0;
		$pcid = $item['author-id'];
		$network = '';
		$rel = 0;
		$condition = ['uid' => $this->userSession->getLocalUserId(), 'uri-id' => $item['author-uri-id']];
		$contact = DBA::selectFirst('contact', ['id', 'network', 'rel'], $condition);
		if (DBA::isResult($contact)) {
			$cid = $contact['id'];
			$network = $contact['network'];
			$rel = $contact['rel'];
		}

		if ($sparkle) {
			$status_link = $profile_link . '/status';
			$photos_link = $profile_link . '/photos';
			$profile_link = $profile_link . '/profile';
		}

		if (!empty($pcid)) {
			$contact_url = 'contact/' . $pcid;
			$posts_link  = $contact_url . '/posts';
			$block_link  = $item['self'] ? '' : $contact_url . '/block?t=' . $formSecurityToken;
			$ignore_link = $item['self'] ? '' : $contact_url . '/ignore?t=' . $formSecurityToken;
		}

		if ($cid && !$item['self']) {
			$contact_url = 'contact/' . $cid;
			$posts_link  = $contact_url . '/posts';

			if (in_array($network, [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
				$pm_url = 'message/new/' . $cid;
			}
		}

		if ($this->userSession->getLocalUserId()) {
			$menu = [
				$this->l10n->t('Follow Thread') => $sub_link,
				$this->l10n->t('View Status') => $status_link,
				$this->l10n->t('View Profile') => $profile_link,
				$this->l10n->t('View Photos') => $photos_link,
				$this->l10n->t('Network Posts') => $posts_link,
				$this->l10n->t('View Contact') => $contact_url,
				$this->l10n->t('Send PM') => $pm_url,
				$this->l10n->t('Block') => $block_link,
				$this->l10n->t('Ignore') => $ignore_link
			];

			if (!empty($item['language'])) {
				$menu[$this->l10n->t('Languages')] = 'javascript:alert(\'' . ItemModel::getLanguageMessage($item) . '\');';
			}

			if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
				in_array($item['network'], Protocol::FEDERATED)) {
				$menu[$this->l10n->t('Connect/Follow')] = 'contact/follow?url=' . urlencode($item['author-link']) . '&auto=1';
			}
		} else {
			$menu = [$this->l10n->t('View Profile') => $item['author-link']];
		}

		$args = ['item' => $item, 'menu' => $menu];

		Hook::callAll('item_photo_menu', $args);

		$menu = $args['menu'];

		$o = '';
		foreach ($menu as $k => $v) {
			if (strpos($v, 'javascript:') === 0) {
				$v = substr($v, 11);
				$o .= '<li role="menuitem"><a onclick="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
			} elseif ($v) {
				$o .= '<li role="menuitem"><a href="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
			}
		}
		$this->profiler->stopRecording();
		return $o;
	}

	/**
	 * Checks if the activity is visible to current user
	 *
	 * @param array $item Activity item
	 * @return bool Whether the item is visible to the user
	 */
	public function isVisibleActivity(array $item): bool
	{
		// Empty verb or hidden?
		if (empty($item['verb']) || $this->activity->isHidden($item['verb'])) {
			return false;
		}

		// Check conditions
		return (!($this->activity->match($item['verb'], Activity::FOLLOW) &&
			$item['object-type'] === Activity\ObjectType::NOTE &&
			empty($item['self']) &&
			$item['uid'] == $this->userSession->getLocalUserId())
		);
	}

	public function expandTags(array $item, bool $setPermissions = false): array
	{
		// Look for any tags and linkify them
		$item['inform'] = '';
		$private_forum  = false;
		$private_id     = null;
		$only_to_forum  = false;
		$forum_contact  = [];
		$receivers      = [];

		// Convert mentions in the body to a unified format
		$item['body'] = BBCode::setMentions($item['body'], $item['uid'], $item['network']);

		// Search for forum mentions
		foreach (Tag::getFromBody($item['body'], Tag::TAG_CHARACTER[Tag::MENTION] . Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION]) as $tag) {
			$contact = Contact::getByURLForUser($tag[2], $item['uid']);
			if (empty($contact)) {
				continue;
			}

			$receivers[] = $contact['id'];

			if (!empty($item['inform'])) {
				$item['inform'] .= ',';
			}
			$item['inform'] .= 'cid:' . $contact['id'];

			if (($item['gravity'] == ItemModel::GRAVITY_COMMENT) || empty($contact['cid']) || ($contact['contact-type'] != Contact::TYPE_COMMUNITY)) {
				continue;
			}

			if (!empty($contact['prv']) || ($tag[1] == Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION])) {
				$private_forum = $contact['prv'];
				$only_to_forum = ($tag[1] == Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION]);
				$private_id = $contact['id'];
				$forum_contact = $contact;
				Logger::info('Private forum or exclusive mention', ['url' => $tag[2], 'mention' => $tag[1]]);
			} elseif ($item['allow_cid'] == '<' . $contact['id'] . '>') {
				$private_forum = false;
				$only_to_forum = true;
				$private_id = $contact['id'];
				$forum_contact = $contact;
				Logger::info('Public forum', ['url' => $tag[2], 'mention' => $tag[1]]);
			} else {
				Logger::info('Post with forum mention will not be converted to a forum post', ['url' => $tag[2], 'mention' => $tag[1]]);
			}
		}
		Logger::info('Got inform', ['inform' => $item['inform']]);

		if (($item['gravity'] == ItemModel::GRAVITY_PARENT) && !empty($forum_contact) && ($private_forum || $only_to_forum)) {
			// we tagged a forum in a top level post. Now we change the post
			$item['private'] = $private_forum ? ItemModel::PRIVATE : ItemModel::UNLISTED;

			if ($only_to_forum) {
				$item['postopts'] = '';
			}

			$item['deny_cid'] = '';
			$item['deny_gid'] = '';

			if ($private_forum) {
				$item['allow_cid'] = '<' . $private_id . '>';
				$item['allow_gid'] = '<' . Group::getIdForForum($forum_contact['id']) . '>';
			} else {
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
			}
		} elseif ($setPermissions && ($item['gravity'] == ItemModel::GRAVITY_PARENT)) {
			if (empty($receivers)) {
				// For security reasons direct posts without any receiver will be posts to yourself
				$self = Contact::selectFirst(['id'], ['uid' => $item['uid'], 'self' => true]);
				$receivers[] = $self['id'];
			}

			$item['private']   = ItemModel::PRIVATE;
			$item['allow_cid'] = '';
			$item['allow_gid'] = '';
			$item['deny_cid']  = '';
			$item['deny_gid']  = '';

			foreach ($receivers as $receiver) {
				$item['allow_cid'] .= '<' . $receiver . '>';
			}
		}
		return $item;
	}

	public function getAuthorAvatar(array $item): string
	{
		if (in_array($item['network'], [Protocol::FEED, Protocol::MAIL])) {
			$author_avatar  = $item['contact-id'];
			$author_updated = '';
			$author_thumb   = $item['contact-avatar'];
		} else {
			$author_avatar  = $item['author-id'];
			$author_updated = $item['author-updated'];
			$author_thumb   = $item['author-avatar'];
		}


		if (empty($author_thumb) || Photo::isPhotoURI($author_thumb)) {
			$author_thumb = Contact::getAvatarUrlForId($author_avatar, Proxy::SIZE_THUMB, $author_updated);
		}

		return $author_thumb;
	}

	public function getOwnerAvatar(array $item): string
	{
		if (in_array($item['network'], [Protocol::FEED, Protocol::MAIL])) {
			$owner_avatar  = $item['contact-id'];
			$owner_updated = '';
			$owner_thumb   = $item['contact-avatar'];
		} else {
			$owner_avatar   = $item['owner-id'];
			$owner_updated  = $item['owner-updated'];
			$owner_thumb    = $item['owner-avatar'];
		}

		if (empty($owner_thumb) || Photo::isPhotoURI($owner_thumb)) {
			$owner_thumb = Contact::getAvatarUrlForId($owner_avatar, Proxy::SIZE_THUMB, $owner_updated);
		}

		return $owner_thumb;
	}

	/**
	 * Add a share block for the given uri-id
	 *
	 * @param array  $item
	 * @param string $body
	 * @return string
	 */
	public function addSharedPost(array $item, string $body = ''): string
	{
		if (empty($body)) {
			$body = $item['body'];
		}

		if (empty($item['quote-uri-id'])) {
			return $body;
		}

		$fields = ['uri-id', 'uri', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink', 'network', 'quote-uri-id'];
		$shared_item = Post::selectFirst($fields, ['uri-id' => $item['quote-uri-id'], 'uid' => [$item['uid'], 0], 'private' => [ItemModel::PUBLIC, ItemModel::UNLISTED]]);
		if (!DBA::isResult($shared_item)) {
			Logger::notice('Post does not exist.', ['uri-id' => $item['quote-uri-id'], 'uid' => $item['uid']]);
			return $body;
		}

		return trim(BBCode::removeSharedData($body) . "\n" . $this->createSharedBlockByArray($shared_item, true));
	}

	/**
	 * Add a share block for the given guid
	 *
	 * @param string $guid
	 * @param integer $uid
	 * @param bool $add_media
	 * @return string
	 */
	private function createSharedPostByGuid(string $guid, bool $add_media): string
	{
		$fields = ['uri-id', 'uri', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink', 'network'];
		$shared_item = Post::selectFirst($fields, ['guid' => $guid, 'uid' => 0, 'private' => [ItemModel::PUBLIC, ItemModel::UNLISTED]]);

		if (!DBA::isResult($shared_item)) {
			Logger::notice('Post does not exist.', ['guid' => $guid]);
			return '';
		}

		return $this->createSharedBlockByArray($shared_item, $add_media);
	}

	/**
	 * Add a share block for the given item array
	 *
	 * @param array $item
	 * @param bool $add_media
	 * @return string
	 */
	public function createSharedBlockByArray(array $item, bool $add_media = false): string
	{
		if ($item['network'] == Protocol::FEED) {
			return PageInfo::getFooterFromUrl($item['plink']);
		} elseif (!in_array($item['network'] ?? '', Protocol::FEDERATED)) {
			$item['guid'] = '';
			$item['uri']  = '';
		}

		if ($add_media) {
			$item['body'] = Post\Media::addAttachmentsToBody($item['uri-id'], $item['body']);
		}

		$shared_content = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'], $item['created'], $item['guid'], $item['uri']);

		if (!empty($item['title'])) {
			$shared_content .= '[h3]' . $item['title'] . "[/h3]\n";
		}

		$shared = $this->getShareArray($item);

		// If it is a reshared post then reformat it to avoid display problems with two share elements
		if (!empty($shared)) {
			if (!empty($shared['guid']) && ($encaspulated_share = $this->createSharedPostByGuid($shared['guid'], true))) {
				if (!empty(BBCode::fetchShareAttributes($item['body']))) {
					$item['body'] = preg_replace("/\[share.*?\](.*)\[\/share\]/ism", $encaspulated_share, $item['body']);
				} else {
					$item['body'] .= $encaspulated_share;
				}
			}
			$item['body'] = HTML::toBBCode(BBCode::convertForUriId($item['uri-id'], $item['body'], BBCode::ACTIVITYPUB));
		}

		$shared_content .= $item['body'] . '[/share]';

		return $shared_content;
	}

	/**
	 * Return the shared post from an item array (if the item is shared item)
	 *
	 * @param array $item
	 * @param array $fields
	 *
	 * @return array with the shared post
	 */
	public function getSharedPost(array $item, array $fields = []): array
	{
		if (!empty($item['quote-uri-id'])) {
			$shared = Post::selectFirst($fields, ['uri-id' => $item['quote-uri-id'], 'uid' => [0, $item['uid'] ?? 0]]);
			if (is_array($shared)) {
				return [
					'comment' => BBCode::removeSharedData($item['body'] ?? ''),
					'post'    => $shared
				];
			}
		}

		$attributes = BBCode::fetchShareAttributes($item['body'] ?? '');
		if (!empty($attributes)) {
			$shared = Post::selectFirst($fields, ['guid' => $attributes['guid'], 'uid' => [0, $item['uid'] ?? 0]]);
			if (is_array($shared)) {
				return [
					'comment' => $attributes['comment'],
					'post'    => $shared
				];
			}
		}

		return [];
	}

	/**
	 * Return share data from an item array (if the item is shared item)
	 * We are providing the complete Item array, because at some time in the future
	 * we hopefully will define these values not in the body anymore but in some item fields.
	 * This function is meant to replace all similar functions in the system.
	 *
	 * @param array $item
	 *
	 * @return array with share information
	 */
	private function getShareArray(array $item): array
	{
		$attributes = BBCode::fetchShareAttributes($item['body'] ?? '');
		if (!empty($attributes)) {
			return $attributes;
		}

		if (!empty($item['quote-uri-id'])) {
			$shared = Post::selectFirst(['author-name', 'author-link', 'author-avatar', 'plink', 'created', 'guid', 'uri', 'body'], ['uri-id' => $item['quote-uri-id']]);
			if (!empty($shared)) {
				return [
					'author'     => $shared['author-name'],
					'profile'    => $shared['author-link'],
					'avatar'     => $shared['author-avatar'],
					'link'       => $shared['plink'],
					'posted'     => $shared['created'],
					'guid'       => $shared['guid'],
					'message_id' => $shared['uri'],
					'comment'    => $item['body'],
					'shared'     => $shared['body'],
				];
			}
		}

		return [];
	}

	/**
	 * Add a link to a shared post at the end of the post
	 *
	 * @param string  $body
	 * @param integer $quote_uri_id
	 * @return string
	 */
	public function addShareLink(string $body, int $quote_uri_id): string
	{
		$post = Post::selectFirstPost(['uri', 'plink'], ['uri-id' => $quote_uri_id]);
		if (empty($post)) {
			return $body;
		}

		$body = BBCode::removeSharedData($body);

		$body .= "\n♲ " . ($post['plink'] ?: $post['uri']);

		return $body;
	}
}
