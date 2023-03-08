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

namespace Friendica\Protocol;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
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
	const SCOPE_ALL = 'all';
	const SCOPE_TAGS = 'tags';

	/**
	 * Check if a post is wanted
	 *
	 * @param array $tags
	 * @param string $body
	 * @param int $authorid
	 * @param string $url
	 * @return boolean "true" is the post is wanted by the system
	 */
	public static function isSolicitedPost(array $tags, string $body, int $authorid, string $url, string $network = '', int $causerid = 0): bool
	{
		$config = DI::config();

		$scope = $config->get('system', 'relay_scope');

		if ($scope == self::SCOPE_NONE) {
			Logger::info('Server does not accept relay posts - rejected', ['network' => $network, 'url' => $url]);
			return false;
		}

		if (Contact::isBlocked($authorid)) {
			Logger::info('Author is blocked - rejected', ['author' => $authorid, 'network' => $network, 'url' => $url]);
			return false;
		}

		if (Contact::isHidden($authorid)) {
			Logger::info('Author is hidden - rejected', ['author' => $authorid, 'network' => $network, 'url' => $url]);
			return false;
		}

		if (!empty($causerid)) {
			$contact = Contact::getById($causerid, ['url']);
			$causer = $contact['url'] ?? '';
		} else {
			$causer = '';
		}

		$body = ActivityPub\Processor::normalizeMentionLinks($body);

		$systemTags = [];
		$userTags = [];
		$denyTags = [];

		if ($scope == self::SCOPE_TAGS) {
			$server_tags = $config->get('system', 'relay_server_tags');
			$tagitems = explode(',', mb_strtolower($server_tags));
			foreach ($tagitems as $tag) {
				$systemTags[] = trim($tag, '# ');
			}

			if ($config->get('system', 'relay_user_tags')) {
				$userTags = Search::getUserTags();
			}
		}

		$tagList = array_unique(array_merge($systemTags, $userTags));

		$deny_tags = $config->get('system', 'relay_deny_tags');
		$tagitems = explode(',', mb_strtolower($deny_tags));
		foreach ($tagitems as $tag) {
			$tag = trim($tag, '# ');
			$denyTags[] = $tag;
		}

		if (!empty($tagList) || !empty($denyTags)) {
			$content = mb_strtolower(BBCode::toPlaintext($body, false));

			foreach ($tags as $tag) {
				$tag = mb_strtolower($tag);
				if (in_array($tag, $denyTags)) {
					Logger::info('Unwanted hashtag found - rejected', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return false;
				}

				if (in_array($tag, $tagList)) {
					Logger::info('Subscribed hashtag found - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return true;
				}

				// We check with "strpos" for performance issues. Only when this is true, the regular expression check is used
				// RegExp is taken from here: https://medium.com/@shiba1014/regex-word-boundaries-with-unicode-207794f6e7ed
				if ((strpos($content, $tag) !== false) && preg_match('/(?<=[\s,.:;"\']|^)' . preg_quote($tag, '/') . '(?=[\s,.:;"\']|$)/', $content)) {
					Logger::info('Subscribed hashtag found in content - accepted', ['hashtag' => $tag, 'network' => $network, 'url' => $url, 'causer' => $causer]);
					return true;
				}
			}
		}

		$languages = [];
		foreach (Item::getLanguageArray($body, 10) as $language => $reliability) {
			if ($reliability > 0) {
				$languages[] = $language;
			}
		}

		Logger::debug('Got languages', ['languages' => $languages, 'body' => $body, 'causer' => $causer]);

		if (!empty($languages)) {
			if (in_array($languages[0], $config->get('system', 'relay_deny_languages'))) {
				Logger::info('Unwanted language found - rejected', ['language' => $languages[0], 'network' => $network, 'url' => $url, 'causer' => $causer]);
				return false;
			}
		} elseif ($config->get('system', 'relay_deny_undetected_language')) {
			Logger::info('Undetected language found - rejected', ['body' => $body, 'network' => $network, 'url' => $url, 'causer' => $causer]);
			return false;
		}

		if ($scope == self::SCOPE_ALL) {
			Logger::info('Server accept all posts - accepted', ['network' => $network, 'url' => $url, 'causer' => $causer]);
			return true;
		}

		Logger::info('No matching hashtags found - rejected', ['network' => $network, 'url' => $url, 'causer' => $causer]);
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
				Logger::info('Sucessfully probed for relay contact', ['server' => $gserver['url']]);
				$id = Contact::updateFromProbeByURL($system['url']);
				Logger::info('Updated relay contact', ['server' => $gserver['url'], 'id' => $id]);
				return;
			}
		}

		$condition = ['uid' => 0, 'gsid' => $gserver['id'], 'contact-type' => Contact::TYPE_RELAY];
		$old = DBA::selectFirst('contact', [], $condition);
		if (!DBA::isResult($old)) {
			$condition = ['uid' => 0, 'nurl' => Strings::normaliseLink($gserver['url'])];
			$old = DBA::selectFirst('contact', [], $condition);
			if (DBA::isResult($old)) {
				$fields['gsid'] = $gserver['id'];
				$fields['contact-type'] = Contact::TYPE_RELAY;
				Logger::info('Assigning missing data for relay contact', ['server' => $gserver['url'], 'id' => $old['id']]);
			}
		} elseif (empty($fields)) {
			Logger::info('No content to update, quitting', ['server' => $gserver['url']]);
			return;
		}

		if (DBA::isResult($old)) {
			$fields['updated'] = DateTimeFormat::utcNow();

			Logger::info('Update relay contact', ['server' => $gserver['url'], 'id' => $old['id'], 'fields' => $fields]);
			Contact::update($fields, ['id' => $old['id']], $old);
		} else {
			$default = ['created' => DateTimeFormat::utcNow(),
				'name' => 'relay', 'nick' => 'relay', 'url' => $gserver['url'],
				'nurl' => Strings::normaliseLink($gserver['url']),
				'network' => Protocol::DIASPORA, 'uid' => 0,
				'batch' => $gserver['url'] . '/receive/public',
				'rel' => Contact::FOLLOWER, 'blocked' => false,
				'pending' => false, 'writable' => true,
				'gsid' => $gserver['id'],
				'baseurl' => $gserver['url'], 'contact-type' => Contact::TYPE_RELAY];

			$fields = array_merge($default, $fields);

			Logger::info('Create relay contact', ['server' => $gserver['url'], 'fields' => $fields]);
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
				$condition = ['uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => Contact::TYPE_RELAY];
				$relay_contact = DBA::selectFirst('contact', [], $condition);
			} else {
				return;
			}
		} else {
			$gserver = ['id' => $contact['gsid'] ?: GServer::getID($contact['baseurl'], true),
				'url' => $contact['baseurl'], 'network' => $contact['network']];
			$relay_contact = self::getContact($gserver, []);
		}

		if (!empty($relay_contact)) {
			Logger::info('Relay contact will be marked for archival', ['id' => $relay_contact['id'], 'url' => $relay_contact['url']]);
			Contact::markForArchival($relay_contact);
		}
	}

	/**
	 * Return a list of servers that we serve via the direct relay
	 *
	 * @param integer $item_id  id of the item that is sent
	 * @param array   $contacts Previously fetched contacts
	 * @param array   $networks Networks of the relay servers
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
		$tags = DBA::select('tag-view', ['name'], ['uri-id' => $parent['uri-id'], 'type' => Tag::HASHTAG]);
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

		// All adresses with the given id
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
		return DBA::selectToArray('apcontact', $fields,
			["`type` = ? AND `url` IN (SELECT `url` FROM `contact` WHERE `uid` = ? AND `rel` = ?)", 'Application', 0, Contact::FRIEND]);
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
		$contact = DBA::selectFirst('contact', $fields, $condition);
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
			Logger::debug('Resubscribed', ['profile' => $server['url'], 'success' => $success]);
		}
	}
}
