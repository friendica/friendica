<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;

class Engagement
{
	const KEYWORDS     = ['source', 'server', 'from', 'to', 'group', 'application', 'tag', 'network', 'platform', 'visibility', 'language', 'media'];
	const SHORTCUTS    = ['lang' => 'language', 'net' => 'network', 'relay' => 'application'];
	const ALTERNATIVES = ['source:news' => 'source:service', 'source:relay' => 'source:application',
		'media:picture'                    => 'media:image', 'media:photo' => 'media:image',
		'network:activitypub'              => 'network:apub', 'network:friendica' => 'network:dfrn',
		'network:diaspora'                 => 'network:dspr', 'network:discourse' => 'network:dscs',
		'network:tumblr'                   => 'network:tmbl', 'network:bluesky' => 'network:bsky'];
	const MEDIA_NONE  = 0;
	const MEDIA_IMAGE = 1;
	const MEDIA_VIDEO = 2;
	const MEDIA_AUDIO = 4;
	const MEDIA_CARD  = 8;
	const MEDIA_POST  = 16;

	/**
	 * Store engagement data from an item array
	 *
	 * @param array $item
	 * @return int uri-id of the engagement post if newly inserted, 0 on update
	 */
	public static function storeFromItem(array $item): int
	{
		if (in_array($item['verb'], [Activity::FOLLOW, Activity::VIEW, Activity::READ])) {
			DI::logger()->debug('Technical activities are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'verb' => $item['verb']]);
			return 0;
		}

		$parent = Post::selectFirst(
			['uri-id', 'created', 'uid', 'private', 'quote-uri-id',
				'contact-contact-type', 'network', 'title', 'content-warning', 'body', 'language',
				'author-id', 'author-contact-type', 'author-nick', 'author-addr', 'author-gsid',
				'owner-id', 'owner-contact-type', 'owner-nick', 'owner-addr', 'owner-gsid'],
			['uri-id' => $item['parent-uri-id']]
		);

		if ($parent['created'] < self::getCreationDateLimit(false)) {
			DI::logger()->debug('Post is too old', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'created' => $parent['created']]);
			return 0;
		}

		$store = ($item['gravity'] != Item::GRAVITY_PARENT);

		if (!$store) {
			$store = Contact::hasFollowers($parent['owner-id']);
		}

		if (!$store && ($parent['owner-id'] != $parent['author-id'])) {
			$store = Contact::hasFollowers($parent['author-id']);
		}

		if (!$store) {
			$tagList = Relay::getSubscribedTags();
			foreach (array_column(Tag::getByURIId($item['parent-uri-id'], [Tag::HASHTAG]), 'name') as $tag) {
				if (in_array(mb_strtolower($tag), $tagList)) {
					$store = true;
					break;
				}
			}
		}

		$mediatype = self::getMediaType($item['parent-uri-id'], $parent['quote-uri-id']);

		if (!$store) {
			$store = !empty($mediatype);
		}

		$searchtext = self::getSearchTextForItem($parent, $mediatype);
		$language   = !empty($parent['language']) ? (array_key_first(json_decode($parent['language'], true)) ?? L10n::UNDETERMINED_LANGUAGE) : L10n::UNDETERMINED_LANGUAGE;
		if (!$store) {
			$store = DI::userDefinedChannel()->match($searchtext, $language);
		}

		$engagement = [
			'uri-id'       => $item['parent-uri-id'],
			'owner-id'     => $parent['owner-id'],
			'contact-type' => $parent['contact-contact-type'],
			'media-type'   => $mediatype,
			'language'     => $language,
			'searchtext'   => $searchtext,
			'size'         => self::getContentSize($parent),
			'created'      => $parent['created'],
			'network'      => $parent['network'],
			'restricted'   => !in_array($item['network'], Protocol::FEDERATED) || ($parent['private'] != Item::PUBLIC),
			'comments'     => DBA::count('post', ['parent-uri-id' => $item['parent-uri-id'], 'gravity' => Item::GRAVITY_COMMENT]),
			'activities'   => DBA::count('post', [
				"`parent-uri-id` = ? AND `gravity` = ? AND NOT `vid` IN (?, ?, ?)",
				$item['parent-uri-id'], Item::GRAVITY_ACTIVITY,
				Verb::getID(Activity::FOLLOW), Verb::getID(Activity::VIEW), Verb::getID(Activity::READ)
			])
		];
		if (!$store && ($engagement['comments'] == 0) && ($engagement['activities'] == 0)) {
			DI::logger()->debug('No media, follower, subscribed tags, comments or activities. Engagement not stored', ['fields' => $engagement]);
			return 0;
		}
		$exists = DBA::exists('post-engagement', ['uri-id' => $engagement['uri-id']]);
		if ($exists) {
			$ret = DBA::update('post-engagement', $engagement, ['uri-id' => $engagement['uri-id']]);
			DI::logger()->debug('Engagement updated', ['uri-id' => $engagement['uri-id'], 'ret' => $ret]);
		} else {
			$ret = DBA::insert('post-engagement', $engagement);
			DI::logger()->debug('Engagement inserted', ['uri-id' => $engagement['uri-id'], 'ret' => $ret]);
		}
		return ($ret && !$exists) ? $engagement['uri-id'] : 0;
	}

	public static function getContentSize(array $item): int
	{
		$body = ' ' . $item['title'] . ' ' . $item['content-warning'] . ' ' . $item['body'];
		$body = BBCode::removeAttachment($body);
		$body = BBCode::removeSharedData($body);
		$body = preg_replace('/[^@!#]\[url\=.*?\].*?\[\/url\]/ism', '', $body);
		$body = BBCode::removeLinks($body);
		$msg  = BBCode::toPlaintext($body, false);

		return mb_strlen($msg);
	}

	public static function getSearchTextForActivity(string $content, int $author_id, array $tags, array $receivers): string
	{
		$author = Contact::getById($author_id);

		$item = [
			'uri-id'              => 0,
			'network'             => Protocol::ACTIVITYPUB,
			'title'               => '',
			'content-warning'     => '',
			'body'                => $content,
			'private'             => Item::PRIVATE,
			'author-id'           => $author_id,
			'author-contact-type' => $author['contact-type'],
			'author-nick'         => $author['nick'],
			'author-addr'         => $author['addr'],
			'author-gsid'         => $author['gsid'],
			'owner-id'            => $author_id,
			'owner-contact-type'  => $author['contact-type'],
			'owner-nick'          => $author['nick'],
			'owner-addr'          => $author['addr'],
			'owner-gsid'          => $author['gsid'],
			'quote-uri-id'        => 0,
		];

		foreach ($receivers as $receiver) {
			if ($receiver == Receiver::PUBLIC_COLLECTION) {
				$item['private'] = Item::PUBLIC;
			}
		}

		return self::getSearchText($item, $receivers, $tags, 0);
	}

	public static function getSearchTextForUriId(int $uri_id, bool $refresh = false): string
	{
		if (!$refresh) {
			$engagement = DBA::selectFirst('post-engagement', ['searchtext'], ['uri-id' => $uri_id]);
			if (!empty($engagement['searchtext'])) {
				return $engagement['searchtext'];
			}
		}

		$post = Post::selectFirstPost(['uri-id', 'network', 'title', 'content-warning', 'body', 'private',
			'author-id', 'author-contact-type', 'author-nick', 'author-addr', 'author-gsid', 'quote-uri-id',
			'owner-id', 'owner-contact-type', 'owner-nick', 'owner-addr', 'owner-gsid'], ['uri-id' => $uri_id]);
		if (empty($post['uri-id'])) {
			return '';
		}
		$mediatype = self::getMediaType($uri_id, $post['quote-uri-id']);
		return self::getSearchTextForItem($post, $mediatype);
	}

	private static function getSearchTextForItem(array $item, int $mediatype): string
	{
		$receivers = array_column(Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION, Tag::AUDIENCE]), 'url');
		$tags      = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		return self::getSearchText($item, $receivers, $tags, $mediatype);
	}

	private static function getSearchText(array $item, array $receivers, array $tags, int $mediatype): string
	{
		$body = '[nosmile]network_' . $item['network'];

		if (!empty($item['author-gsid'])) {
			$gserver  = DBA::selectFirst('gserver', ['platform', 'nurl'], ['id' => $item['author-gsid']]);
			$platform = preg_replace('/[\W]/', '', $gserver['platform'] ?? '');
			if (!empty($platform)) {
				$body .= ' platform_' . $platform;
			}
			$body .= ' server_' . parse_url($gserver['nurl'], PHP_URL_HOST);
		}

		if (($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) && !empty($item['owner-gsid']) && ($item['owner-gsid'] != ($item['author-gsid'] ?? 0))) {
			$gserver  = DBA::selectFirst('gserver', ['platform', 'nurl'], ['id' => $item['owner-gsid']]);
			$platform = preg_replace('/[\W]/', '', $gserver['platform'] ?? '');
			if (!empty($platform) && !strpos($body, 'platform_' . $platform)) {
				$body .= ' platform_' . $platform;
			}
			$body .= ' server_' . parse_url($gserver['nurl'], PHP_URL_HOST);
		}

		switch ($item['private']) {
			case Item::PUBLIC:
				$body .= ' visibility_public';
				break;
			case Item::UNLISTED:
				$body .= ' visibility_unlisted';
				break;
			case Item::PRIVATE:
				$body .= ' visibility_private';
				break;
		}

		if (in_array(Contact::TYPE_COMMUNITY, [$item['author-contact-type'], $item['owner-contact-type']])) {
			$body .= ' source_group';
		} elseif ($item['author-contact-type'] == Contact::TYPE_PERSON) {
			$body .= ' source_person';
		} elseif ($item['author-contact-type'] == Contact::TYPE_NEWS) {
			$body .= ' source_service';
		} elseif ($item['author-contact-type'] == Contact::TYPE_ORGANISATION) {
			$body .= ' source_organization';
		} elseif ($item['author-contact-type'] == Contact::TYPE_RELAY) {
			$body .= ' source_application';
		}

		if ($item['author-contact-type'] == Contact::TYPE_COMMUNITY) {
			$body .= ' group_' . $item['author-nick'] . ' group_' . $item['author-addr'];
		} elseif ($item['author-contact-type'] == Contact::TYPE_RELAY) {
			$body .= ' application_' . $item['author-nick'] . ' application_' . $item['author-addr'];
		} elseif (in_array($item['author-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
			$body .= ' from_' . $item['author-nick'] . ' from_' . $item['author-addr'];
		}

		if ($item['author-id'] != $item['owner-id']) {
			if ($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) {
				$body .= ' group_' . $item['owner-nick'] . ' group_' . $item['owner-addr'];
			} elseif (in_array($item['owner-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' from_' . $item['owner-nick'] . ' from_' . $item['owner-addr'];
			}
		}

		$body = self::addResharers($body, $item['uri-id']);

		foreach ($receivers as $receiver) {
			if (empty($receiver)) {
				continue;
			}
			$contact = Contact::getByURL($receiver, false, ['nick', 'addr', 'contact-type']);
			if (empty($contact)) {
				continue;
			}

			if (($contact['contact-type'] == Contact::TYPE_COMMUNITY) && !strpos($body, 'group_' . $contact['addr'])) {
				$body .= ' group_' . $contact['nick'] . ' group_' . $contact['addr'];
			} elseif (in_array($contact['contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' to_' . $contact['nick'] . ' to_' . $contact['addr'];
			}
		}

		foreach ($tags as $tag) {
			$body .= ' tag_' . $tag;
		}

		if (!empty($item['language'])) {
			$languages = json_decode($item['language'], true);
			$body .= ' language_' . array_key_first($languages);
		}

		if ($mediatype & self::MEDIA_IMAGE) {
			$body .= ' media_image';
		}

		if ($mediatype & self::MEDIA_VIDEO) {
			$body .= ' media_video';
		}

		if ($mediatype & self::MEDIA_AUDIO) {
			$body .= ' media_audio';
		}

		if ($mediatype & self::MEDIA_CARD) {
			$body .= ' media_card';
		}

		if ($mediatype & self::MEDIA_POST) {
			$body .= ' media_post';
		}

		$body .= ' ' . $item['title'] . ' ' . $item['content-warning'] . ' ' . $item['body'];

		return BBCode::toSearchText($body, $item['uri-id']);
	}

	private static function addResharers(string $text, int $uri_id): string
	{
		$result = Post::selectPosts(
			['author-addr', 'author-nick', 'author-contact-type'],
			['thr-parent-id' => $uri_id, 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => Activity::ANNOUNCE, 'author-contact-type' => [Contact::TYPE_RELAY, Contact::TYPE_COMMUNITY]]
		);
		while ($reshare = Post::fetch($result)) {
			$prefix = '';

			switch ($reshare['author-contact-type']) {
				case Contact::TYPE_RELAY:
					$prefix = ' application_';
					break;
				case Contact::TYPE_COMMUNITY:
					$prefix = ' group_';
					break;
			}
			$nick = $prefix . $reshare['author-nick'];
			$addr = $prefix . $reshare['author-addr'];

			if (stripos($text, $addr) === false) {
				$text .= $nick . $addr;
			}
		}
		DBA::close($result);

		return $text;
	}

	public static function getMediaType(int $uri_id, int $quote_uri_id = null): int
	{
		$media = Post\Media::getByURIId($uri_id);
		$type  = !empty($quote_uri_id) ? self::MEDIA_POST : self::MEDIA_NONE;
		foreach ($media as $entry) {
			if ($entry['type'] == Post\Media::IMAGE) {
				$type = $type | self::MEDIA_IMAGE;
			} elseif (in_array($entry['type'], [Post\Media::VIDEO, Post\Media::HLS])) {
				$type = $type | self::MEDIA_VIDEO;
			} elseif ($entry['type'] == Post\Media::AUDIO) {
				$type = $type | self::MEDIA_AUDIO;
			} elseif ($entry['type'] == Post\Media::HTML) {
				$type = $type | self::MEDIA_CARD;
			} elseif ($entry['type'] == Post\Media::ACTIVITY) {
				$type = $type | self::MEDIA_POST;
			}
		}
		return $type;
	}

	/**
	 * Expire old engagement data
	 *
	 * @return void
	 */
	public static function expire()
	{
		$limit = self::getCreationDateLimit(true);
		if (empty($limit)) {
			DI::logger()->notice('Expiration limit not reached');
			return;
		}
		DBA::delete('post-engagement', ["`created` < ?", $limit]);
		DI::logger()->notice('Cleared expired engagements', ['limit' => $limit, 'rows' => DBA::affectedRows()]);
	}

	private static function getCreationDateLimit(bool $forDeletion): string
	{
		$posts = DI::config()->get('channel', 'engagement_post_limit');
		if (!empty($posts)) {
			$limit = DBA::selectToArray('post-engagement', ['created'], [], ['limit' => [$posts, 1], 'order' => ['created' => true]]);
			if (!empty($limit)) {
				return $limit[0]['created'];
			} elseif ($forDeletion) {
				return '';
			}
		}

		return DateTimeFormat::utc('now - ' . DI::config()->get('channel', 'engagement_hours') . ' hour');
	}

	public static function escapeKeywords(string $fullTextSearch): string
	{
		foreach (self::SHORTCUTS as $search => $replace) {
			$fullTextSearch = preg_replace('~' . $search . ':(.[\w\*@\.-]+)~', $replace . ':$1', $fullTextSearch);
		}

		foreach (self::ALTERNATIVES as $search => $replace) {
			$fullTextSearch = str_replace($search, $replace, $fullTextSearch);
		}

		foreach (self::KEYWORDS as $keyword) {
			$fullTextSearch = preg_replace('~(' . $keyword . '):(.[\w\*@\.-]+)~', '"$1_$2"', $fullTextSearch);
		}
		return $fullTextSearch;
	}

	public static function unescapeKeywords(string $fullTextSearch): string
	{
		foreach (self::KEYWORDS as $keyword) {
			$fullTextSearch = preg_replace('~(' . $keyword . ')_(.[\w\*@\.-]+)~', '$1:$2', $fullTextSearch);
		}
		return $fullTextSearch;
	}
}
