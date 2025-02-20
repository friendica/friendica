<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\BBCode\Video;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Circle;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\FileTag;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\EMail\ItemCCEMail;
use Friendica\Protocol\Activity;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;
use Friendica\Util\ParseUrl;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Friendica\Util\XML;
use GuzzleHttp\Psr7\Uri;
use ImagickException;

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
	/** @var Video */
	private $bbCodeVideo;
	/** @var ACLFormatter */
	private $aclFormatter;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var BaseURL */
	private $baseURL;
	/** @var Emailer */
	private $emailer;
	/** @var AppHelper */
	private $appHelper;

	public function __construct(Profiler $profiler, Activity $activity, L10n $l10n, IHandleUserSessions $userSession, Video $bbCodeVideo, ACLFormatter $aclFormatter, IManagePersonalConfigValues $pConfig, BaseURL $baseURL, Emailer $emailer, AppHelper $appHelper)
	{
		$this->profiler     = $profiler;
		$this->activity     = $activity;
		$this->l10n         = $l10n;
		$this->userSession  = $userSession;
		$this->bbCodeVideo  = $bbCodeVideo;
		$this->aclFormatter = $aclFormatter;
		$this->baseURL      = $baseURL;
		$this->pConfig      = $pConfig;
		$this->emailer      = $emailer;
		$this->appHelper    = $appHelper;
	}

	/**
	 * Lists categories and folders for an item
	 *
	 * @param array $item
	 * @param int   $uid
	 * @return array
	 * [
	 *     [ // categories array
	 *         {
	 *             'name': 'category name',
	 *             'removeurl': 'url to remove this category',
	 *             'first': 'is the first in this array? true/false',
	 *             'last': 'is the last in this array? true/false',
	 *         },
	 *         ...
	 *     ],
	 *     [ //folders array
	 *         {
	 *             'name': 'folder name',
	 *             'removeurl': 'url to remove this folder',
	 *             'first': 'is the first in this array? true/false',
	 *             'last': 'is the last in this array? true/false',
	 *         } ,
	 *         ...
	 *     ]
	 * ]
	 *
	 * @throws \Exception
	 */
	public function determineCategoriesTerms(array $item, int $uid = 0): array
	{
		$categories = [];
		$folders    = [];
		$first      = true;

		$uid = $item['uid'] ?: $uid;

		if (empty($item['has-categories'])) {
			return [$categories, $folders];
		}

		foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid) as $savedFolderName) {
			if (!empty($item['author-link'])) {
				$url = $item['author-link'] . '/conversations?category=' . rawurlencode($savedFolderName);
			} else {
				$url = '#';
			}
			$categories[] = [
				'name'      => $savedFolderName,
				'url'       => $url,
				'removeurl' => $this->userSession->getLocalUserId() == $uid ? 'filerm/' . $item['id'] . '?cat=' . rawurlencode($savedFolderName) : '',
				'first'     => $first,
				'last'      => false
			];
			$first = false;
		}

		if (count($categories)) {
			$categories[count($categories) - 1]['last'] = true;
		}

		if ($this->userSession->getLocalUserId() == $uid) {
			foreach (Post\Category::getArrayByURIId($item['uri-id'], $uid, Post\Category::FILE) as $savedFolderName) {
				$folders[] = [
					'name'      => $savedFolderName,
					'url'       => "#",
					'removeurl' => $this->userSession->getLocalUserId() == $uid ? 'filerm/' . $item['id'] . '?term=' . rawurlencode($savedFolderName) : '',
					'first'     => $first,
					'last'      => false
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
	 * @throws InternalServerErrorException
	 * @throws ImagickException
	 */
	public static function replaceTag(string &$body, int $profile_uid, string $tag, string $network = '')
	{
		$replaced = false;
		$contact  = [];

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
			$name      = $nameparts[0];

			// Try to detect the contact in various ways
			if (strpos($name, 'http://') || strpos($name, '@')) {
				$contact = Contact::getByURLForUser($name, $profile_uid);
			} else {
				$contact = false;
				$fields  = ['id', 'url', 'nick', 'name', 'alias', 'network', 'forum', 'prv'];

				if (strrpos($name, '+')) {
					// Is it in format @nick+number?
					$tagcid  = intval(substr($name, strrpos($name, '+') + 1));
					$contact = DBA::selectFirst('contact', $fields, ['id' => $tagcid, 'uid' => $profile_uid]);
				}

				// select someone by nick in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['nick' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['attag' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}

				//select someone by name in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['name' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by nick in any network
				if (!DBA::isResult($contact)) {
					$condition = ['nick' => $name, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in any network
				if (!DBA::isResult($contact)) {
					$condition = ['attag' => $name, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by name in any network
				if (!DBA::isResult($contact)) {
					$condition = ['name' => $name, 'uid' => $profile_uid];
					$contact   = DBA::selectFirst('contact', $fields, $condition);
				}
			}

			$newname = '';

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
				$newtag  = $tag_type . '[url=' . $profile . ']' . $newname . '[/url]';
				$body    = str_replace($tag_type . $name, $newtag, $body);
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
	 * @throws InternalServerErrorException
	 */
	public function localize(array &$item)
	{
		$this->profiler->startRecording('rendering');
		/// @todo The following functionality needs to be cleaned up.
		if (!empty($item['verb'])) {
			$xmlhead = '<?xml version="1.0" encoding="UTF-8" ?>';

			if ($this->activity->match($item['verb'], Activity::TAG)) {
				$fields = [
					'author-id', 'author-link', 'author-name', 'author-network', 'author-link', 'author-alias',
					'verb', 'object-type', 'resource-id', 'body', 'plink'
				];
				$obj = Post::selectFirst($fields, ['uri' => $item['parent-uri']]);
				if (!DBA::isResult($obj)) {
					$this->profiler->stopRecording();
					return;
				}

				$author_arr = [
					'uid'     => 0,
					'id'      => $item['author-id'],
					'network' => $item['author-network'],
					'url'     => $item['author-link'],
					'alias'   => $item['author-alias'],
				];
				$author = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $item['author-name'] . '[/url]';

				$author_arr = [
					'uid'     => 0,
					'id'      => $obj['author-id'],
					'network' => $obj['author-network'],
					'url'     => $obj['author-link'],
					'alias'   => $obj['author-alias'],
				];
				$objauthor = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $obj['author-name'] . '[/url]';

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
							preg_match("/\[url=([^]]*)\]/", $obj['body'], $matches);
							$rr['plink'] = $matches[1];
						} else {
							$post_type = $this->l10n->t('status');
						}
						// Let's break everything ... ;-)
						break;
				}
				$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

				$parsedobj = XML::parseString($xmlhead . $item['object']);

				$tag          = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
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
		$sub_link    = $contact_url = $pm_url = $status_link = '';
		$photos_link = $posts_link = $block_link = $ignore_link = $collapse_link = $ignoreserver_link = '';

		if ($this->userSession->getLocalUserId() && $this->userSession->getLocalUserId() == $item['uid'] && $item['gravity'] == ItemModel::GRAVITY_PARENT && !$item['self'] && !$item['mention']) {
			$sub_link = 'javascript:doFollowThread(' . $item['id'] . '); return false;';
		}

		$author = [
			'uid'     => 0,
			'id'      => $item['author-id'],
			'network' => $item['author-network'],
			'url'     => $item['author-link'],
			'alias'   => $item['author-alias'],
		];
		$profile_link = Contact::magicLinkByContact($author, Contact::getProfileLink($author));
		if (strpos($profile_link, 'contact/redir/') === 0) {
			$status_link  = $profile_link . '?' . http_build_query(['url' => $item['author-link'] . '/status']);
			$photos_link  = $profile_link . '?' . http_build_query(['url' => $item['author-link'] . '/photos']);
			$profile_link = $profile_link . '?' . http_build_query(['url' => $item['author-link'] . '/profile']);
		}

		$cid       = 0;
		$pcid      = $item['author-id'];
		$network   = '';
		$rel       = 0;
		$condition = ['uid' => $this->userSession->getLocalUserId(), 'uri-id' => $item['author-uri-id']];
		$contact   = DBA::selectFirst('contact', ['id', 'network', 'rel'], $condition);
		if (DBA::isResult($contact)) {
			$cid     = $contact['id'];
			$network = $contact['network'];
			$rel     = $contact['rel'];
		}

		if (!empty($pcid)) {
			$contact_url   = 'contact/' . $pcid;
			$posts_link    = $contact_url . '/posts';
			$block_link    = $item['self'] ? '' : $contact_url . '/block?t=' . $formSecurityToken;
			$ignore_link   = $item['self'] ? '' : $contact_url . '/ignore?t=' . $formSecurityToken;
			$collapse_link = $item['self'] ? '' : $contact_url . '/collapse?t=' . $formSecurityToken;
		}

		$authorBaseUri = new Uri($item['author-baseurl'] ?? '');
		if (!empty($item['author-gsid']) && $authorBaseUri->getHost() && !DI::baseUrl()->isLocalUrl($authorBaseUri)) {
			$ignoreserver_link = 'settings/server/' . $item['author-gsid'] . '/ignore';
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
				$this->l10n->t('Follow Thread')                               => $sub_link,
				$this->l10n->t('View Status')                                 => $status_link,
				$this->l10n->t('View Profile')                                => $profile_link,
				$this->l10n->t('View Photos')                                 => $photos_link,
				$this->l10n->t('Network Posts')                               => $posts_link,
				$this->l10n->t('View Contact')                                => $contact_url,
				$this->l10n->t('Send PM')                                     => $pm_url,
				$this->l10n->t('Block')                                       => $block_link,
				$this->l10n->t('Ignore')                                      => $ignore_link,
				$this->l10n->t('Collapse')                                    => $collapse_link,
				$this->l10n->t("Ignore %s server", $authorBaseUri->getHost()) => $ignoreserver_link,
			];

			if (!empty($item['language'])) {
				$menu[$this->l10n->t('Languages')] = 'javascript:displayLanguage(' . $item['uri-id'] . ');';
			}

			$menu[$this->l10n->t('Search Text')] = 'javascript:displaySearchText(' . $item['uri-id'] . ');';

			if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
				in_array($item['network'], Protocol::FEDERATED)
			) {
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
		$private_group  = false;
		$private_id     = null;
		$only_to_group  = false;
		$group_contact  = [];
		$receivers      = [];

		// Convert mentions in the body to a unified format
		$item['body'] = BBCode::setMentions($item['body'], $item['uid'], $item['network']);

		// Search for group mentions
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
				$private_group = $contact['prv'];
				$only_to_group = ($tag[1] == Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION]);
				$private_id    = $contact['id'];
				$group_contact = $contact;
				DI::logger()->info('Private group or exclusive mention', ['url' => $tag[2], 'mention' => $tag[1]]);
			} elseif ($item['allow_cid'] == '<' . $contact['id'] . '>') {
				$private_group = false;
				$only_to_group = true;
				$private_id    = $contact['id'];
				$group_contact = $contact;
				DI::logger()->info('Public group', ['url' => $tag[2], 'mention' => $tag[1]]);
			} else {
				DI::logger()->info('Post with group mention will not be converted to a group post', ['url' => $tag[2], 'mention' => $tag[1]]);
			}
		}
		DI::logger()->info('Got inform', ['inform' => $item['inform']]);

		if (($item['gravity'] == ItemModel::GRAVITY_PARENT) && !empty($group_contact) && ($private_group || $only_to_group)) {
			// we tagged a group in a top level post. Now we change the post
			$item['private'] = $private_group ? ItemModel::PRIVATE : ItemModel::UNLISTED;

			if ($only_to_group) {
				$pcid = Contact::getPublicContactId($group_contact['id'], $item['uid']);
				if ($pcid) {
					$item['owner-id'] = $pcid;
					unset($item['owner-link']);
					unset($item['owner-name']);
					unset($item['owner-avatar']);
				}

				$item['postopts'] = '';
			}

			$item['deny_cid'] = '';
			$item['deny_gid'] = '';

			if ($private_group) {
				$item['allow_cid'] = '<' . $private_id . '>';
				$item['allow_gid'] = '<' . Circle::getIdForGroup($group_contact['id']) . '>';
			} else {
				$item['allow_cid'] = '';
				$item['allow_gid'] = '';
			}
		} elseif ($setPermissions) {
			if (empty($receivers)) {
				// For security reasons direct posts without any receiver will be posts to yourself
				$self        = Contact::selectFirst(['id'], ['uid' => $item['uid'], 'self' => true]);
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
			$owner_avatar  = $item['owner-id'];
			$owner_updated = $item['owner-updated'];
			$owner_thumb   = $item['owner-avatar'] ?? '';
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
			$body = $item['body'] ?? '';
		}

		if (empty($item['quote-uri-id']) || ($item['quote-uri-id'] == $item['uri-id'])) {
			return $body;
		}

		$fields      = ['uri-id', 'uri', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink', 'network', 'quote-uri-id'];
		$shared_item = Post::selectFirst($fields, ['uri-id' => $item['quote-uri-id'], 'uid' => [$item['uid'], 0], 'private' => [ItemModel::PUBLIC, ItemModel::UNLISTED]]);
		if (!DBA::isResult($shared_item)) {
			DI::logger()->notice('Post does not exist.', ['uri-id' => $item['quote-uri-id'], 'uid' => $item['uid']]);
			return $body;
		}

		return trim(BBCode::removeSharedData($body) . "\n" . $this->createSharedBlockByArray($shared_item, true));
	}

	/**
	 * Add a share block for the given guid
	 */
	private function createSharedPostByGuid(string $guid, bool $add_media): string
	{
		$fields      = ['uri-id', 'uri', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink', 'network'];
		$shared_item = Post::selectFirst($fields, ['guid' => $guid, 'uid' => 0, 'private' => [ItemModel::PUBLIC, ItemModel::UNLISTED]]);

		if (!DBA::isResult($shared_item)) {
			DI::logger()->notice('Post does not exist.', ['guid' => $guid]);
			return '';
		}

		return $this->createSharedBlockByArray($shared_item, $add_media);
	}

	/**
	 * Add a share block for the given item array
	 *
	 * @param array $item
	 * @param bool $add_media   true = Media is added to the body
	 * @param bool $for_display true = The share block is used for display purposes, false = used for connectors, transport to other systems, ...
	 * @return string
	 */
	public function createSharedBlockByArray(array $item, bool $add_media = false, bool $for_display = false): string
	{
		if ($item['network'] == Protocol::FEED) {
			return PageInfo::getFooterFromUrl($item['plink']);
		} elseif (!in_array($item['network'] ?? '', Protocol::FEDERATED) && !$for_display) {
			$item['guid'] = '';
			$item['uri']  = '';
		}

		if ($add_media) {
			$item['body'] = Post\Media::addAttachmentsToBody($item['uri-id'], $item['body']);
		}

		$shared_content = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'] ?? $item['uri'], $item['created'], $item['guid'], $item['uri']);

		if (!empty($item['title'])) {
			$shared_content .= '[h3]' . $item['title'] . "[/h3]\n";
		}

		$shared = $this->getShareArray($item);

		// If it is a reshared post then reformat it to avoid display problems with two share elements
		if (!empty($shared)) {
			if (($item['network'] != Protocol::BLUESKY) && !empty($shared['guid']) && ($encapsulated_share = $this->createSharedPostByGuid($shared['guid'], true))) {
				if (!empty(BBCode::fetchShareAttributes($item['body']))) {
					$item['body'] = preg_replace("/\[share.*?\](.*)\[\/share\]/ism", $encapsulated_share, $item['body']);
				} else {
					$item['body'] .= $encapsulated_share;
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
		if (!empty($item['quote-uri-id']) && ($item['quote-uri-id'] != $item['uri-id'])) {
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

		if (!empty($item['quote-uri-id']) && ($item['quote-uri-id'] != $item['uri-id'])) {
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
		$post = Post::selectFirstPost(['uri'], ['uri-id' => $quote_uri_id]);
		if (empty($post)) {
			return $body;
		}

		$body = BBCode::removeSharedData($body);

		$body .= "\nRE: " . $post['uri'];

		return $body;
	}

	public function storeAttachmentFromRequest(array $request): string
	{
		$attachment_type  = $request['attachment_type']  ?? '';
		$attachment_title = $request['attachment_title'] ?? '';
		$attachment_text  = $request['attachment_text']  ?? '';

		$attachment_url     = hex2bin($request['attachment_url'] ?? '');
		$attachment_img_src = hex2bin($request['attachment_img_src'] ?? '');

		$attachment_img_width  = $request['attachment_img_width']  ?? 0;
		$attachment_img_height = $request['attachment_img_height'] ?? 0;

		// Fetch the basic attachment data
		$attachment = ParseUrl::getSiteinfoCached($attachment_url);
		unset($attachment['keywords']);

		// Overwrite the basic data with possible changes from the frontend
		$attachment['type']  = $attachment_type;
		$attachment['title'] = $attachment_title;
		$attachment['text']  = $attachment_text;
		$attachment['url']   = $attachment_url;

		if (!empty($attachment_img_src)) {
			$attachment['images'] = [
				0 => [
					'src'    => $attachment_img_src,
					'width'  => $attachment_img_width,
					'height' => $attachment_img_height
				]
			];
		} else {
			unset($attachment['images']);
		}

		return "\n" . PageInfo::getFooterFromData($attachment);
	}

	public function addCategories(array $post, string $category): array
	{
		if (!empty($post['file'])) {
			// get the "fileas" tags for this post
			$filedas = FileTag::fileToArray($post['file']);
		}

		$list_array   = explode(',', trim($category));
		$post['file'] = FileTag::arrayToFile($list_array, 'category');

		if (!empty($filedas) && is_array($filedas)) {
			// append the fileas stuff to the new categories list
			$post['file'] .= FileTag::arrayToFile($filedas);
		}
		return $post;
	}

	public function getACL(array $post, array $toplevel_item, array $request): array
	{
		// If this is a comment, set the permissions from the parent.
		if ($toplevel_item) {
			$post['allow_cid'] = $toplevel_item['allow_cid'] ?? '';
			$post['allow_gid'] = $toplevel_item['allow_gid'] ?? '';
			$post['deny_cid']  = $toplevel_item['deny_cid']  ?? '';
			$post['deny_gid']  = $toplevel_item['deny_gid']  ?? '';
			$post['private']   = $toplevel_item['private'];
			return $post;
		}

		$user = User::getById($post['uid'], ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
		if (!$user) {
			throw new HTTPException\NotFoundException($this->l10n->t('Unable to fetch user.'));
		}

		$post['allow_cid'] = isset($request['contact_allow']) ? $this->aclFormatter->toString($request['contact_allow']) : $user['allow_cid'] ?? '';
		$post['allow_gid'] = isset($request['circle_allow'])  ? $this->aclFormatter->toString($request['circle_allow'])  : $user['allow_gid'] ?? '';
		$post['deny_cid']  = isset($request['contact_deny'])  ? $this->aclFormatter->toString($request['contact_deny'])  : $user['deny_cid']  ?? '';
		$post['deny_gid']  = isset($request['circle_deny'])   ? $this->aclFormatter->toString($request['circle_deny'])   : $user['deny_gid']  ?? '';

		$visibility = $request['visibility'] ?? '';
		if ($visibility === 'public') {
			// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
			$post['allow_cid'] = $post['allow_gid'] = $post['deny_cid'] = $post['deny_gid'] = '';
		} elseif ($visibility === 'custom') {
			// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
			// case that would make it public. So we always append the author's contact id to the allowed contacts.
			// See https://github.com/friendica/friendica/issues/9672
			$post['allow_cid'] .= $this->aclFormatter->toString(Contact::getPublicIdByUserId($post['uid']));
		}

		if ($post['allow_gid'] || $post['allow_cid'] || $post['deny_gid'] || $post['deny_cid']) {
			$post['private'] = ItemModel::PRIVATE;
		} elseif ($this->pConfig->get($post['uid'], 'system', 'unlisted')) {
			$post['private'] = ItemModel::UNLISTED;
		} else {
			$post['private'] = ItemModel::PUBLIC;
		}

		return $post;
	}

	private function setObjectType(array $post): array
	{
		if (empty($post['post-type'])) {
			$post['post-type'] = empty($post['title']) ? ItemModel::PT_NOTE : ItemModel::PT_ARTICLE;
		}

		// embedded bookmark or attachment in post? set bookmark flag
		$data = BBCode::getAttachmentData($post['body']);
		if ((preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $post['body'], $match, PREG_SET_ORDER) || !empty($data['type']))
			&& ($post['post-type'] != ItemModel::PT_PERSONAL_NOTE)
		) {
			$post['post-type']   = ItemModel::PT_PAGE;
			$post['object-type'] = Activity\ObjectType::BOOKMARK;
		}

		// Setting the object type if not defined before
		if (empty($post['object-type'])) {
			$post['object-type'] = ($post['gravity'] == ItemModel::GRAVITY_PARENT) ? Activity\ObjectType::NOTE : Activity\ObjectType::COMMENT;
		}
		return $post;
	}

	public function initializePost(array $post): array
	{
		$post['network']    = Protocol::DFRN;
		$post['protocol']   = Conversation::PARCEL_DIRECT;
		$post['direction']  = Conversation::PUSH;
		$post['received']   = DateTimeFormat::utcNow();
		$post['origin']     = true;
		$post['wall']       = $post['wall']       ?? true;
		$post['guid']       = $post['guid']       ?? System::createUUID();
		$post['verb']       = $post['verb']       ?? Activity::POST;
		$post['uri']        = $post['uri']        ?? ItemModel::newURI($post['guid']);
		$post['thr-parent'] = $post['thr-parent'] ?? $post['uri'];

		if (empty($post['gravity'])) {
			$post['gravity'] = ($post['uri'] == $post['thr-parent']) ? ItemModel::GRAVITY_PARENT : ItemModel::GRAVITY_COMMENT;
		}

		$owner = User::getOwnerDataById($post['uid']);

		if (!isset($post['allow_cid']) || !isset($post['allow_gid']) || !isset($post['deny_cid']) || !isset($post['deny_gid'])) {
			$post['allow_cid'] = $owner['allow_cid'];
			$post['allow_gid'] = $owner['allow_gid'];
			$post['deny_cid']  = $owner['deny_cid'];
			$post['deny_gid']  = $owner['deny_gid'];
		}

		if (!isset($post['private'])) {
			if ($post['allow_gid'] || $post['allow_cid'] || $post['deny_gid'] || $post['deny_cid']) {
				$post['private'] = ItemModel::PRIVATE;
			} elseif ($this->pConfig->get($post['uid'], 'system', 'unlisted')) {
				$post['private'] = ItemModel::UNLISTED;
			} else {
				$post['private'] = ItemModel::PUBLIC;
			}
		}

		if (empty($post['contact-id'])) {
			$post['contact-id'] = $owner['id'];
		}

		if (empty($post['author-link']) && empty($post['author-id'])) {
			$post['author-link']   = $owner['url'];
			$post['author-id']     = Contact::getIdForURL($post['author-link']);
			$post['author-name']   = $owner['name'];
			$post['author-avatar'] = $owner['thumb'];
		}

		if (empty($post['owner-link']) && empty($post['owner-id'])) {
			$post['owner-link']   = $post['author-link'];
			$post['owner-id']     = Contact::getIdForURL($post['owner-link']);
			$post['owner-name']   = $post['author-name'];
			$post['owner-avatar'] = $post['author-avatar'];
		}

		return $post;
	}

	public function finalizePost(array $post, bool $preview): array
	{
		if ($preview) {
			$post['body'] = Attach::addAttachmentToBody($post['body'], $post['uid']);
		} else {
			Attach::setPermissionFromBody($post);
		}
		if (preg_match("/\[attachment\](.*?)\[\/attachment\]/ism", $post['body'], $matches)) {
			$post['body'] = preg_replace("/\[attachment].*?\[\/attachment\]/ism", PageInfo::getFooterFromUrl($matches[1]), $post['body']);
		}

		// Convert links with empty descriptions to links without an explicit description
		$post['body'] = trim(preg_replace('#\[url=([^\]]*?)\]\[/url\]#ism', '[url]$1[/url]', $post['body']));
		$post['body'] = $this->bbCodeVideo->transform($post['body']);
		$post         = $this->setObjectType($post);

		// Personal notes must never be altered to a group post.
		if ($post['post-type'] != ItemModel::PT_PERSONAL_NOTE) {
			// Look for any tags and linkify them
			$post = $this->expandTags($post);
		}

		return $post;
	}

	public function postProcessPost(array $post, array $recipients = [])
	{
		if (!Feature::isEnabled($post['uid'], Feature::EXPLICIT_MENTIONS) && ($post['gravity'] == ItemModel::GRAVITY_COMMENT)) {
			Tag::createImplicitMentions($post['uri-id'], $post['thr-parent-id']);
		}

		Hook::callAll('post_local_end', $post);

		$author = DBA::selectFirst('contact', ['thumb'], ['uid' => $post['uid'], 'self' => true]);

		foreach ($recipients as $recipient) {
			$address = trim($recipient);
			if (!$address) {
				continue;
			}

			$this->emailer->send(new ItemCCEMail(
				$this->userSession,
				$this->l10n,
				$this->baseURL,
				$post,
				$address,
				$author['thumb'] ?? ''
			));
		}
	}

	public function copyPermissions(int $fromUriId, int $toUriId, int $parentUriId)
	{
		$from          = Post::selectFirstPost(['author-id'], ['uri-id' => $fromUriId]);
		$from_author   = DBA::selectFirst('account-view', ['ap-followers'], ['id' => $from['author-id']]);
		$to            = Post::selectFirstPost(['author-id'], ['uri-id' => $toUriId]);
		$to_author     = DBA::selectFirst('account-view', ['ap-followers'], ['id' => $to['author-id']]);
		$parent        = Post::selectFirstPost(['author-id'], ['uri-id' => $parentUriId]);
		$parent_author = DBA::selectFirst('account-view', ['ap-followers'], ['id' => $parent['author-id']]);

		$followers = '';
		foreach (array_column(Tag::getByURIId($parentUriId, [Tag::TO, Tag::CC, Tag::BCC]), 'url') as $url) {
			if ($url == $parent_author['ap-followers']) {
				$followers = $url;
				break;
			}
		}

		$existing = array_column(Tag::getByURIId($toUriId, [Tag::TO, Tag::CC, Tag::BCC]), 'url');

		foreach (Tag::getByURIId($fromUriId, [Tag::TO, Tag::CC, Tag::BCC]) as $receiver) {
			if ($receiver['url'] == $from_author['ap-followers']) {
				if (!empty($followers)) {
					$receiver['url']  = $followers;
					$receiver['name'] = trim(parse_url($receiver['url'], PHP_URL_PATH), '/');
					Tag::store($toUriId, $receiver['type'], $receiver['name'], $receiver['url']);
				}
				$receiver['url']  = $to_author['ap-followers'];
				$receiver['name'] = trim(parse_url($receiver['url'], PHP_URL_PATH), '/');
			}
			if (in_array($receiver['url'], $existing)) {
				continue;
			}
			Tag::store($toUriId, $receiver['type'], $receiver['name'], $receiver['url']);
		}
	}

	/**
	 * Check if the item is too old
	 *
	 * @param string $created
	 * @param integer $uid
	 * @return boolean item is too old
	 */
	public function isTooOld(string $created, int $uid = 0): bool
	{
		// check for create date and expire time
		$expire_interval = DI::config()->get('system', 'dbclean-expire-days', 0);

		if ($uid) {
			$user = DBA::selectFirst('user', ['expire'], ['uid' => $uid]);
			if (DBA::isResult($user) && ($user['expire'] > 0) && (($user['expire'] < $expire_interval) || ($expire_interval == 0))) {
				$expire_interval = $user['expire'];
			}
		}

		if (($expire_interval > 0) && !empty($created)) {
			$expire_date  = time() - ($expire_interval * 86400);
			$created_date = strtotime($created);
			if ($created_date < $expire_date) {
				DI::logger()->notice('Item created before expiration interval.', ['created' => date('c', $created_date), 'expired' => date('c', $expire_date)]);
				return true;
			}
		}

		return false;
	}
}
