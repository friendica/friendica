<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Exception;
use Friendica\Content\Smilies;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Search;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * Base class for relay handling
 * @see https://github.com/jaywink/social-relay
 * @see https://wiki.diasporafoundation.org/Relay_servers_for_public_posts
 */
class Relay
{
	const SCOPE_NONE = '';
	const SCOPE_ALL  = 'all';
	const SCOPE_TAGS = 'tags';

	/**
	 * Check if a post is wanted
	 *
	 * @param array  $tags
	 * @param string $body
	 * @param int    $authorid
	 * @param string $url
	 * @param string $network
	 * @param int    $causerid
	 * @param array  $languages
	 * @return boolean "true" is the post is wanted by the system
	 */
	public static function isSolicitedPost(array $tags, string $body, int $authorid, string $url, string $network = '', int $causerid = 0, array $languages = []): bool
	{
		$config = DI::config();

		if (Contact::hasFollowers($authorid)) {
			DI::logger()->info('Author has got followers on this server - accepted', ['network' => $network, 'url' => $url, 'author' => $authorid, 'tags' => $tags]);
			return true;
		}

		$scope = $config->get('system', 'relay_scope');

		if ($scope == self::SCOPE_NONE) {
			DI::logger()->info('Server does not accept relay posts - rejected', ['network' => $network, 'url' => $url]);
			return false;
		}

		if (Contact::isBlocked($authorid)) {
			DI::logger()->info('Author is blocked - rejected', ['author' => $authorid, 'network' => $network, 'url' => $url]);
			return false;
		}

		if (Contact::isHidden($authorid)) {
			DI::logger()->info('Author is hidden - rejected', ['author' => $authorid, 'network' => $network, 'url' => $url]);
			return false;
		}

		if (!empty($causerid)) {
			$contact = Contact::getById($causerid, ['url']);
			$causer  = $contact['url'] ?? '';
		} else {
			$causer = '';
		}

		$body = ActivityPub\Processor::normalizeMentionLinks($body);

		if ($scope == self::SCOPE_TAGS) {
			$tagList = self::getSubscribedTags();
		} else {
			$tagList = [];
		}

		$denyTags = Strings::getTagArrayByString($config->get('system', 'relay_deny_tags'));

		if (!empty($tagList) || !empty($denyTags)) {
			$content = mb_strtolower(BBCode::toPlaintext($body, false));

			$max_tags = $config->get('system', 'relay_max_tags');
			if ($max_tags && (count($tags) > $max_tags) && preg_match('/[^@!#]\[url\=.*?\].*?\[\/url\]/ism', $body)) {
				$cleaned         = preg_replace('/[@!#]\[url\=.*?\].*?\[\/url\]/ism', '', $body);
				$content_cleaned = mb_strtolower(BBCode::toPlaintext($cleaned, false));

				if (strlen($content_cleaned) < strlen($content) / 2) {
					DI::logger()->info('Possible hashtag spam detected - rejected', ['hashtags' => $tags, 'network' => $network, 'url' => $url, 'causer' => $causer, 'content' => $content]);
					return false;
				}
			}

			foreach ($tags as $tag) {
				$tag = mb_strtolower($tag);
				if (in_array($tag, $denyTags)) {
					DI::logger()->info('Unwanted hashtag found - rejected', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return false;
				}

				if (in_array($tag, $tagList)) {
					DI::logger()->info('Subscribed hashtag found - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return true;
				}

				// We check with "strpos" for performance issues. Only when this is true, the regular expression check is used
				// RegExp is taken from here: https://medium.com/@shiba1014/regex-word-boundaries-with-unicode-207794f6e7ed
				if ((strpos($content, $tag) !== false) && preg_match('/(?<=[\s,.:;"\']|^)' . preg_quote($tag, '/') . '(?=[\s,.:;"\']|$)/', $content)) {
					DI::logger()->info('Subscribed hashtag found in content - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return true;
				}
			}
		}

		if (!self::isWantedLanguage($body, 0, $authorid, $languages)) {
			DI::logger()->info('Unwanted or Undetected language found - rejected', ['network' => $network, 'url' => $url, 'causer' => $causer, 'tags' => $tags]);
			return false;
		}

		if ($scope == self::SCOPE_ALL) {
			DI::logger()->info('Server accept all posts - accepted', ['network' => $network, 'url' => $url, 'causer' => $causer, 'tags' => $tags]);
			return true;
		}

		DI::logger()->info('No matching hashtags found - rejected', ['network' => $network, 'url' => $url, 'causer' => $causer, 'tags' => $tags]);
		return false;
	}

	/**
	 * Get a list of subscribed tags by both the users and the tags that are defined by the admin
	 *
	 * @return array
	 */
	public static function getSubscribedTags(): array
	{
		$tags = Strings::getTagArrayByString(DI::config()->get('system', 'relay_server_tags'));

		if (DI::config()->get('system', 'relay_user_tags')) {
			$tags = array_merge($tags, Search::getUserTags());
		}

		return array_unique($tags);
	}

	/**
	 * Detect the language of a post and decide if the post should be accepted
	 *
	 * @param string $body
	 * @param int    $uri_id
	 * @param int    $author_id
	 * @param array  $languages
	 * @return boolean
	 */
	public static function isWantedLanguage(string $body, int $uri_id = 0, int $author_id = 0, array $languages = [])
	{
		$detected = [];
		$quality  = DI::config()->get('system', 'relay_language_quality');
		foreach (Item::getLanguageArray($body, DI::config()->get('system', 'relay_languages'), $uri_id, $author_id) as $language => $reliability) {
			if (($reliability >= $quality) && ($quality > 0)) {
				$detected[] = $language;
			}
		}

		if (empty($detected) && empty($languages)) {
			$detected = [L10n::UNDETERMINED_LANGUAGE];
		}

		if (empty($body) || Smilies::isEmojiPost($body)) {
			DI::logger()->debug('Empty body or only emojis', ['body' => $body]);
			return true;
		}

		$user_languages = User::getLanguages();

		foreach ($detected as $language) {
			if (in_array($language, $user_languages)) {
				DI::logger()->debug('Wanted language found in detected languages', ['language' => $language, 'detected' => $detected, 'userlang' => $user_languages, 'body' => $body]);
				return true;
			}
		}
		foreach ($languages as $language) {
			if (in_array($language, $user_languages)) {
				DI::logger()->debug('Wanted language found in defined languages', ['language' => $language, 'languages' => $languages, 'detected' => $detected, 'userlang' => $user_languages, 'body' => $body]);
				return true;
			}
		}
		DI::logger()->debug('No wanted language found', ['languages' => $languages, 'detected' => $detected, 'userlang' => $user_languages, 'body' => $body]);
		return false;
	}

	/**
	 * Update or insert a relay contact
	 *
	 * @param array $gserver Global server record
	 * @param array $fields  Optional network specific fields
	 * @return void
	 * @throws \Exception
	 */
	public static function updateContact(array $gserver, array $fields = [])
	{
		if (in_array($gserver['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$system = APContact::getByURL($gserver['url'] . '/friendica');
			if (!empty($system['sharedinbox'])) {
				DI::logger()->info('Successfully probed for relay contact', ['server' => $gserver['url']]);
				$id = Contact::updateFromProbeByURL($system['url']);
				DI::logger()->info('Updated relay contact', ['server' => $gserver['url'], 'id' => $id]);
				return;
			}
		}

		$condition = ['uid' => 0, 'gsid' => $gserver['id'], 'contact-type' => Contact::TYPE_RELAY];
		$old       = DBA::selectFirst('contact', [], $condition);
		if (!DBA::isResult($old)) {
			$condition = ['uid' => 0, 'nurl' => Strings::normaliseLink($gserver['url'])];
			$old       = DBA::selectFirst('contact', [], $condition);
			if (DBA::isResult($old)) {
				$fields['gsid']         = $gserver['id'];
				$fields['contact-type'] = Contact::TYPE_RELAY;
				DI::logger()->info('Assigning missing data for relay contact', ['server' => $gserver['url'], 'id' => $old['id']]);
			}
		} elseif (empty($fields) && $old['unsearchable']) {
			DI::logger()->info('No content to update, quitting', ['server' => $gserver['url']]);
			return;
		}

		if (DBA::isResult($old)) {
			$fields['updated']      = DateTimeFormat::utcNow();
			$fields['unsearchable'] = true;

			DI::logger()->info('Update relay contact', ['server' => $gserver['url'], 'id' => $old['id'], 'fields' => $fields]);
			Contact::update($fields, ['id' => $old['id']], $old);
		} else {
			$default = ['created' => DateTimeFormat::utcNow(),
				'name'               => 'relay', 'nick' => 'relay', 'url' => $gserver['url'],
				'nurl'               => Strings::normaliseLink($gserver['url']),
				'network'            => Protocol::DIASPORA, 'uid' => 0,
				'batch'              => $gserver['url'] . '/receive/public',
				'rel'                => Contact::FOLLOWER, 'blocked' => false,
				'pending'            => false, 'writable' => true,
				'gsid'               => $gserver['id'],
				'unsearchable'       => true,
				'baseurl'            => $gserver['url'], 'contact-type' => Contact::TYPE_RELAY];

			$fields = array_merge($default, $fields);

			DI::logger()->info('Create relay contact', ['server' => $gserver['url'], 'fields' => $fields]);
			Contact::insert($fields);
		}
	}

	/**
	 * Mark the relay contact of the given contact for archival
	 * This is called whenever there is a communication issue with the server.
	 * It avoids sending stuff to servers who don't exist anymore.
	 * The relay contact is a technical contact entry that exists once per server.
	 *
	 * @param array $contact of the relay contact
	 * @return void
	 */
	public static function markForArchival(array $contact)
	{
		if (!empty($contact['contact-type']) && ($contact['contact-type'] == Contact::TYPE_RELAY)) {
			// This is already the relay contact, we don't need to fetch it
			$relay_contact = $contact;
		} elseif (empty($contact['baseurl'])) {
			if (!empty($contact['batch'])) {
				$condition     = ['uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => Contact::TYPE_RELAY];
				$relay_contact = DBA::selectFirst('contact', [], $condition);
			} else {
				return;
			}
		} else {
			$gserver = ['id' => $contact['gsid'] ?: GServer::getID($contact['baseurl'], true),
				'url'           => $contact['baseurl'], 'network' => $contact['network']];
			$relay_contact = self::getContact($gserver, []);
		}

		if (!empty($relay_contact)) {
			DI::logger()->info('Relay contact will be marked for archival', ['id' => $relay_contact['id'], 'url' => $relay_contact['url']]);
			Contact::markForArchival($relay_contact);
		}
	}

	/**
	 * Return a list of servers that we serve via the direct relay
	 *
	 * @param integer $item_id  id of the item that is sent
	 * @return array of relay servers
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getDirectRelayList(int $item_id): array
	{
		$serverlist = [];

		if (!DI::config()->get('system', 'relay_directly', false)) {
			return [];
		}

		// We distribute our stuff based on the parent to ensure that the thread will be complete
		$parent = Post::selectFirst(['uri-id'], ['id' => $item_id]);
		if (!DBA::isResult($parent)) {
			return [];
		}

		// Servers that want to get all content
		$servers = DBA::select('gserver', ['id', 'url', 'network'], ['relay-subscribe' => true, 'relay-scope' => 'all']);
		while ($server = DBA::fetch($servers)) {
			$serverlist[$server['id']] = $server;
		}
		DBA::close($servers);

		// All tags of the current post
		$tags    = DBA::select('tag-view', ['name'], ['uri-id' => $parent['uri-id'], 'type' => Tag::HASHTAG]);
		$taglist = [];
		while ($tag = DBA::fetch($tags)) {
			$taglist[] = $tag['name'];
		}
		DBA::close($tags);

		// All servers who wants content with this tag
		$tagserverlist = [];
		if (!empty($taglist)) {
			$tagserver = DBA::select('gserver-tag', ['gserver-id'], ['tag' => $taglist]);
			while ($server = DBA::fetch($tagserver)) {
				$tagserverlist[] = $server['gserver-id'];
			}
			DBA::close($tagserver);
		}

		// All addresses with the given id
		if (!empty($tagserverlist)) {
			$servers = DBA::select('gserver', ['id', 'url', 'network'], ['relay-subscribe' => true, 'relay-scope' => 'tags', 'id' => $tagserverlist]);
			while ($server = DBA::fetch($servers)) {
				$serverlist[$server['id']] = $server;
			}
			DBA::close($servers);
		}

		$contacts = [];

		// Now we are collecting all relay contacts
		foreach ($serverlist as $gserver) {
			// We don't send messages to ourselves
			if (Strings::compareLink($gserver['url'], DI::baseUrl())) {
				continue;
			}
			$contact = self::getContact($gserver);
			if (empty($contact)) {
				continue;
			}
		}

		return $contacts;
	}

	/**
	 * Return a list of relay servers
	 *
	 * @param array $fields Field list
	 * @return array List of relay servers
	 * @throws Exception
	 */
	public static function getList(array $fields = []): array
	{
		return DBA::selectToArray(
			'apcontact',
			$fields,
			["`type` IN (?, ?) AND `url` IN (SELECT `url` FROM `contact` WHERE `uid` = ? AND `rel` = ?)", 'Application', 'Service', 0, Contact::FRIEND]
		);
	}

	/**
	 * Return a contact for a given server address or creates a dummy entry
	 *
	 * @param array $gserver Global server record
	 * @param array $fields  Fieldlist
	 * @return array|bool Array with the contact or false on error
	 * @throws \Exception
	 */
	private static function getContact(array $gserver, array $fields = ['batch', 'id', 'url', 'name', 'network', 'protocol', 'archive', 'blocked'])
	{
		// Fetch the relay contact
		$condition = ['uid' => 0, 'gsid' => $gserver['id'], 'contact-type' => Contact::TYPE_RELAY];
		$contact   = DBA::selectFirst('contact', $fields, $condition);
		if (DBA::isResult($contact)) {
			if ($contact['archive'] || $contact['blocked']) {
				return false;
			}
			return $contact;
		} else {
			self::updateContact($gserver);

			$contact = DBA::selectFirst('contact', $fields, $condition);
			if (DBA::isResult($contact)) {
				return $contact;
			}
		}

		// It should never happen that we arrive here
		return [];
	}

	/**
	 * Resubscribe to all relay servers
	 *
	 * @return void
	 */
	public static function reSubscribe()
	{
		foreach (self::getList() as $server) {
			$success = ActivityPub\Transmitter::sendRelayFollow($server['url']);
			DI::logger()->debug('Resubscribed', ['profile' => $server['url'], 'success' => $success]);
		}
	}
}
