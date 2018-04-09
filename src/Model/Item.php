<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Group;
use Friendica\Model\Term;
use Friendica\Object\Image;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\XML;
use dba;
use Text_LanguageDetect;

require_once 'boot.php';
require_once 'include/items.php';
require_once 'include/text.php';

class Item extends BaseObject
{
	/**
	 * @brief Update existing item entries
	 *
	 * @param array $fields The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		$success = dba::update('item', $fields, $condition);

		if (!$success) {
			return false;
		}

		$rows = dba::affected_rows();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		$items = dba::select('item', ['id', 'origin'], $condition);
		while ($item = dba::fetch($items)) {
			Term::insertFromTagFieldByItemId($item['id']);
			Term::insertFromFileFieldByItemId($item['id']);
			self::updateThread($item['id']);

			// We only need to notfiy others when it is an original entry from us
			if ($item['origin']) {
				Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
			}
		}

		return $rows;
	}

	/**
	 * @brief Delete an item and notify others about it - if it was ours
	 *
	 * @param array $condition The condition for finding the item entries
	 * @param integer $priority Priority for the notification
	 */
	public static function delete($condition, $priority = PRIORITY_HIGH)
	{
		$items = dba::select('item', ['id'], $condition);
		while ($item = dba::fetch($items)) {
			self::deleteById($item['id'], $priority);
		}
		dba::close($items);
	}

	/**
	 * @brief Delete an item and notify others about it - if it was ours
	 *
	 * @param integer $item_id Item ID that should be delete
	 * @param integer $priority Priority for the notification
	 *
	 * @return boolean success
	 */
	public static function deleteById($item_id, $priority = PRIORITY_HIGH)
	{
		// locate item to be deleted
		$fields = ['id', 'uid', 'parent', 'parent-uri', 'origin', 'deleted',
			'file', 'resource-id', 'event-id', 'attach',
			'verb', 'object-type', 'object', 'target', 'contact-id'];
		$item = dba::selectFirst('item', $fields, ['id' => $item_id]);
		if (!DBM::is_result($item)) {
			return false;
		}

		if ($item['deleted']) {
			return false;
		}

		$parent = dba::selectFirst('item', ['origin'], ['id' => $item['parent']]);
		if (!DBM::is_result($parent)) {
			$parent = ['origin' => false];
		}

		logger('delete item: ' . $item['id'], LOGGER_DEBUG);

		// clean up categories and tags so they don't end up as orphans

		$matches = false;
		$cnt = preg_match_all('/<(.*?)>/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],true);
			}
		}

		$matches = false;

		$cnt = preg_match_all('/\[(.*?)\]/', $item['file'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				file_tag_unsave_file($item['uid'], $item['id'], $mtch[1],false);
			}
		}

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		if (strlen($item['resource-id'])) {
			dba::delete('photo', ['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, delete the event.
		if (intval($item['event-id'])) {
			Event::delete($item['event-id']);
		}

		// If item has attachments, drop them
		foreach (explode(", ", $item['attach']) as $attach) {
			preg_match("|attach/(\d+)|", $attach, $matches);
			dba::delete('attach', ['id' => $matches[1], 'uid' => $item['uid']]);
		}

		// Delete tags that had been attached to other items
		self::deleteTagsFromItem($item);

		// Set the item to "deleted"
		dba::update('item', ['deleted' => true, 'title' => '', 'body' => '',
					'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()],
				['id' => $item['id']]);

		Term::insertFromTagFieldByItemId($item['id']);
		Term::insertFromFileFieldByItemId($item['id']);
		self::deleteThread($item['id'], $item['parent-uri']);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['id'] == $item['parent']) {
			self::delete(['parent' => $item['parent']], $priority);
		}

		// send the notification upstream/downstream
		if ($item['origin'] || $parent['origin']) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", "drop", intval($item['id']));
		}

		return true;
	}

	private static function deleteTagsFromItem($item)
	{
		if (($item["verb"] != ACTIVITY_TAG) || ($item["object-type"] != ACTIVITY_OBJ_TAGTERM)) {
			return;
		}

		$xo = XML::parseString($item["object"], false);
		$xt = XML::parseString($item["target"], false);

		if ($xt->type != ACTIVITY_OBJ_NOTE) {
			return;
		}

		$i = dba::selectFirst('item', ['id', 'contact-id', 'tag'], ['uri' => $xt->id, 'uid' => $item['uid']]);
		if (!DBM::is_result($i)) {
			return;
		}

		// For tags, the owner cannot remove the tag on the author's copy of the post.
		$owner_remove = ($item["contact-id"] == $i["contact-id"]);
		$author_copy = $item["origin"];

		if (($owner_remove && $author_copy) || !$owner_remove) {
			return;
		}

		$tags = explode(',', $i["tag"]);
		$newtags = [];
		if (count($tags)) {
			foreach ($tags as $tag) {
				if (trim($tag) !== trim($xo->body)) {
				       $newtags[] = trim($tag);
				}
			}
		}
		self::update(['tag' => implode(',', $newtags)], ['id' => $i["id"]]);
	}

	private static function guid($item, $notify)
	{
		$guid =	notags(trim($item['guid']));

		if (!empty($guid)) {
			return $guid;
		}

		if ($notify) {
			// We have to avoid duplicates. So we create the GUID in form of a hash of the plink or uri.
			// We add the hash of our own host because our host is the original creator of the post.
			$prefix_host = get_app()->get_hostname();
		} else {
			$prefix_host = '';

			// We are only storing the post so we create a GUID from the original hostname.
			if (!empty($item['author-link'])) {
				$parsed = parse_url($item['author-link']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['plink'])) {
				$parsed = parse_url($item['plink']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['uri'])) {
				$parsed = parse_url($item['uri']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			// Is it in the format data@host.tld? - Used for mail contacts
			if (empty($prefix_host) && !empty($item['author-link']) && strstr($item['author-link'], '@')) {
				$mailparts = explode('@', $item['author-link']);
				$prefix_host = array_pop($mailparts);
			}
		}

		if (!empty($item['plink'])) {
			$guid = self::guidFromUri($item['plink'], $prefix_host);
		} elseif (!empty($item['uri'])) {
			$guid = self::guidFromUri($item['uri'], $prefix_host);
		} else {
			$guid = get_guid(32, hash('crc32', $prefix_host));
		}

		return $guid;
	}

	private static function contactId($item)
	{
		$contact_id = (int)$item["contact-id"];

		if (!empty($contact_id)) {
			return $contact_id;
		}
		logger('Missing contact-id. Called by: '.System::callstack(), LOGGER_DEBUG);
		/*
		 * First we are looking for a suitable contact that matches with the author of the post
		 * This is done only for comments
		 */
		if ($item['parent-uri'] != $item['uri']) {
			$contact_id = Contact::getIdForURL($item['author-link'], $item['uid']);
		}

		// If not present then maybe the owner was found
		if ($contact_id == 0) {
			$contact_id = Contact::getIdForURL($item['owner-link'], $item['uid']);
		}

		// Still missing? Then use the "self" contact of the current user
		if ($contact_id == 0) {
			$self = dba::selectFirst('contact', ['id'], ['self' => true, 'uid' => $item['uid']]);
			if (DBM::is_result($self)) {
				$contact_id = $self["id"];
			}
		}
		logger("Contact-id was missing for post ".$item['guid']." from user id ".$item['uid']." - now set to ".$contact_id, LOGGER_DEBUG);

		return $contact_id;
	}

	public static function insert($item, $force_parent = false, $notify = false, $dontcache = false)
	{
		$a = get_app();

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$item['wall'] = 1;
			$item['type'] = 'wall';
			$item['origin'] = 1;
			$item['network'] = NETWORK_DFRN;
			$item['protocol'] = PROTOCOL_DFRN;
		} else {
			$item['network'] = trim(defaults($item, 'network', NETWORK_PHANTOM));
		}

		$item['guid'] = self::guid($item, $notify);
		$item['uri'] = notags(trim(defaults($item, 'uri', item_new_uri($a->get_hostname(), $item['uid'], $item['guid']))));

		// Store conversation data
		$item = Conversation::insert($item);

		/*
		 * If a Diaspora signature structure was passed in, pull it out of the
		 * item array and set it aside for later storage.
		 */

		$dsprsig = null;
		if (x($item, 'dsprsig')) {
			$encoded_signature = $item['dsprsig'];
			$dsprsig = json_decode(base64_decode($item['dsprsig']));
			unset($item['dsprsig']);
		}

		// Converting the plink
		/// @TODO Check if this is really still needed
		if ($item['network'] == NETWORK_OSTATUS) {
			if (isset($item['plink'])) {
				$item['plink'] = OStatus::convertHref($item['plink']);
			} elseif (isset($item['uri'])) {
				$item['plink'] = OStatus::convertHref($item['uri']);
			}
		}

		if (!empty($item['thr-parent'])) {
			$item['parent-uri'] = $item['thr-parent'];
		}

		if (x($item, 'gravity')) {
			$item['gravity'] = intval($item['gravity']);
		} elseif ($item['parent-uri'] === $item['uri']) {
			$item['gravity'] = 0;
		} elseif (activity_match($item['verb'],ACTIVITY_POST)) {
			$item['gravity'] = 6;
		} else {
			$item['gravity'] = 6;   // extensible catchall
		}

		$item['type'] = defaults($item, 'type', 'remote');

		$uid = intval($item['uid']);

		// check for create date and expire time
		$expire_interval = Config::get('system', 'dbclean-expire-days', 0);

		$user = dba::selectFirst('user', ['expire'], ['uid' => $uid]);
		if (DBM::is_result($user) && ($user['expire'] > 0) && (($user['expire'] < $expire_interval) || ($expire_interval == 0))) {
			$expire_interval = $user['expire'];
		}

		if (($expire_interval > 0) && !empty($item['created'])) {
			$expire_date = time() - ($expire_interval * 86400);
			$created_date = strtotime($item['created']);
			if ($created_date < $expire_date) {
				logger('item-store: item created ('.date('c', $created_date).') before expiration time ('.date('c', $expire_date).'). ignored. ' . print_r($item,true), LOGGER_DEBUG);
				return 0;
			}
		}

		/*
		 * Do we already have this item?
		 * We have to check several networks since Friendica posts could be repeated
		 * via OStatus (maybe Diasporsa as well)
		 */
		if (in_array($item['network'], [NETWORK_DIASPORA, NETWORK_DFRN, NETWORK_OSTATUS, ""])) {
			$condition = ["`uri` = ? AND `uid` = ? AND `network` IN (?, ?, ?)",
				trim($item['uri']), $item['uid'],
				NETWORK_DIASPORA, NETWORK_DFRN, NETWORK_OSTATUS];
			$existing = dba::selectFirst('item', ['id', 'network'], $condition);
			if (DBM::is_result($existing)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($uid != 0) {
					logger("Item with uri ".$item['uri']." already existed for user ".$uid." with id ".$existing["id"]." target network ".$existing["network"]." - new network: ".$item['network']);
				}

				return $existing["id"];
			}
		}

		self::addLanguageInPostopts($item);

		$item['wall']          = intval(defaults($item, 'wall', 0));
		$item['extid']         = trim(defaults($item, 'extid', ''));
		$item['author-name']   = trim(defaults($item, 'author-name', ''));
		$item['author-link']   = trim(defaults($item, 'author-link', ''));
		$item['author-avatar'] = trim(defaults($item, 'author-avatar', ''));
		$item['owner-name']    = trim(defaults($item, 'owner-name', ''));
		$item['owner-link']    = trim(defaults($item, 'owner-link', ''));
		$item['owner-avatar']  = trim(defaults($item, 'owner-avatar', ''));
		$item['received']      = ((x($item, 'received') !== false) ? DateTimeFormat::utc($item['received']) : DateTimeFormat::utcNow());
		$item['created']       = ((x($item, 'created') !== false) ? DateTimeFormat::utc($item['created']) : $item['received']);
		$item['edited']        = ((x($item, 'edited') !== false) ? DateTimeFormat::utc($item['edited']) : $item['created']);
		$item['changed']       = ((x($item, 'changed') !== false) ? DateTimeFormat::utc($item['changed']) : $item['created']);
		$item['commented']     = ((x($item, 'commented') !== false) ? DateTimeFormat::utc($item['commented']) : $item['created']);
		$item['title']         = trim(defaults($item, 'title', ''));
		$item['location']      = trim(defaults($item, 'location', ''));
		$item['coord']         = trim(defaults($item, 'coord', ''));
		$item['visible']       = ((x($item, 'visible') !== false) ? intval($item['visible'])         : 1);
		$item['deleted']       = 0;
		$item['parent-uri']    = trim(defaults($item, 'parent-uri', $item['uri']));
		$item['verb']          = trim(defaults($item, 'verb', ''));
		$item['object-type']   = trim(defaults($item, 'object-type', ''));
		$item['object']        = trim(defaults($item, 'object', ''));
		$item['target-type']   = trim(defaults($item, 'target-type', ''));
		$item['target']        = trim(defaults($item, 'target', ''));
		$item['plink']         = trim(defaults($item, 'plink', ''));
		$item['allow_cid']     = trim(defaults($item, 'allow_cid', ''));
		$item['allow_gid']     = trim(defaults($item, 'allow_gid', ''));
		$item['deny_cid']      = trim(defaults($item, 'deny_cid', ''));
		$item['deny_gid']      = trim(defaults($item, 'deny_gid', ''));
		$item['private']       = intval(defaults($item, 'private', 0));
		$item['bookmark']      = intval(defaults($item, 'bookmark', 0));
		$item['body']          = trim(defaults($item, 'body', ''));
		$item['tag']           = trim(defaults($item, 'tag', ''));
		$item['attach']        = trim(defaults($item, 'attach', ''));
		$item['app']           = trim(defaults($item, 'app', ''));
		$item['origin']        = intval(defaults($item, 'origin', 0));
		$item['postopts']      = trim(defaults($item, 'postopts', ''));
		$item['resource-id']   = trim(defaults($item, 'resource-id', ''));
		$item['event-id']      = intval(defaults($item, 'event-id', 0));
		$item['inform']        = trim(defaults($item, 'inform', ''));
		$item['file']          = trim(defaults($item, 'file', ''));

		// When there is no content then we don't post it
		if ($item['body'].$item['title'] == '') {
			return 0;
		}

		// Items cannot be stored before they happen ...
		if ($item['created'] > DateTimeFormat::utcNow()) {
			$item['created'] = DateTimeFormat::utcNow();
		}

		// We haven't invented time travel by now.
		if ($item['edited'] > DateTimeFormat::utcNow()) {
			$item['edited'] = DateTimeFormat::utcNow();
		}

		if (($item['author-link'] == "") && ($item['owner-link'] == "")) {
			logger("Both author-link and owner-link are empty. Called by: " . System::callstack(), LOGGER_DEBUG);
		}

		$item['plink'] = defaults($item, 'plink', System::baseUrl() . '/display/' . urlencode($item['guid']));

		// The contact-id should be set before "self::insert" was called - but there seems to be issues sometimes
		$item["contact-id"] = self::contactId($item);

		$item['author-id'] = defaults($item, 'author-id', Contact::getIdForURL($item["author-link"]));

		if (Contact::isBlocked($item["author-id"])) {
			logger('Contact '.$item["author-id"].' is blocked, item '.$item["uri"].' will not be stored');
			return 0;
		}

		$item['owner-id'] = defaults($item, 'owner-id', Contact::getIdForURL($item["owner-link"]));

		if (Contact::isBlocked($item["owner-id"])) {
			logger('Contact '.$item["owner-id"].' is blocked, item '.$item["uri"].' will not be stored');
			return 0;
		}

		if ($item['network'] == NETWORK_PHANTOM) {
			logger('Missing network. Called by: '.System::callstack(), LOGGER_DEBUG);

			$contact = Contact::getDetailsByURL($item['author-link'], $item['uid']);
			if (!empty($contact['network'])) {
				$item['network'] = $contact["network"];
			} else {
				$item['network'] = NETWORK_DFRN;
			}
			logger("Set network to " . $item["network"] . " for " . $item["uri"], LOGGER_DEBUG);
		}

		// Checking if there is already an item with the same guid
		logger('Checking for an item for user '.$item['uid'].' on network '.$item['network'].' with the guid '.$item['guid'], LOGGER_DEBUG);
		$condition = ['guid' => $item['guid'], 'network' => $item['network'], 'uid' => $item['uid']];
		if (dba::exists('item', $condition)) {
			logger('found item with guid '.$item['guid'].' for user '.$item['uid'].' on network '.$item['network'], LOGGER_DEBUG);
			return 0;
		}

		// Check for hashtags in the body and repair or add hashtag links
		self::setHashtags($item);

		$item['thr-parent'] = $item['parent-uri'];

		$notify_type = '';
		$allow_cid = '';
		$allow_gid = '';
		$deny_cid  = '';
		$deny_gid  = '';

		if ($item['parent-uri'] === $item['uri']) {
			$parent_id = 0;
			$parent_deleted = 0;
			$allow_cid = $item['allow_cid'];
			$allow_gid = $item['allow_gid'];
			$deny_cid  = $item['deny_cid'];
			$deny_gid  = $item['deny_gid'];
			$notify_type = 'wall-new';
		} else {
			// find the parent and snarf the item id and ACLs
			// and anything else we need to inherit

			$fields = ['uri', 'parent-uri', 'id', 'deleted',
				'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
				'wall', 'private', 'forum_mode'];
			$condition = ['uri' => $item['parent-uri'], 'uid' => $item['uid']];
			$params = ['order' => ['id' => false]];
			$parent = dba::selectFirst('item', $fields, $condition, $params);

			if (DBM::is_result($parent)) {
				// is the new message multi-level threaded?
				// even though we don't support it now, preserve the info
				// and re-attach to the conversation parent.

				if ($parent['uri'] != $parent['parent-uri']) {
					$item['parent-uri'] = $parent['parent-uri'];

					$condition = ['uri' => $item['parent-uri'],
						'parent-uri' => $item['parent-uri'],
						'uid' => $item['uid']];
					$params = ['order' => ['id' => false]];
					$toplevel_parent = dba::selectFirst('item', $fields, $condition, $params);

					if (DBM::is_result($toplevel_parent)) {
						$parent = $toplevel_parent;
					}
				}

				$parent_id      = $parent['id'];
				$parent_deleted = $parent['deleted'];
				$allow_cid      = $parent['allow_cid'];
				$allow_gid      = $parent['allow_gid'];
				$deny_cid       = $parent['deny_cid'];
				$deny_gid       = $parent['deny_gid'];
				$item['wall']    = $parent['wall'];
				$notify_type    = 'comment-new';

				/*
				 * If the parent is private, force privacy for the entire conversation
				 * This differs from the above settings as it subtly allows comments from
				 * email correspondents to be private even if the overall thread is not.
				 */
				if ($parent['private']) {
					$item['private'] = $parent['private'];
				}

				/*
				 * Edge case. We host a public forum that was originally posted to privately.
				 * The original author commented, but as this is a comment, the permissions
				 * weren't fixed up so it will still show the comment as private unless we fix it here.
				 */
				if ((intval($parent['forum_mode']) == 1) && $parent['private']) {
					$item['private'] = 0;
				}

				// If its a post from myself then tag the thread as "mention"
				logger("Checking if parent ".$parent_id." has to be tagged as mention for user ".$item['uid'], LOGGER_DEBUG);
				$user = dba::selectFirst('user', ['nickname'], ['uid' => $item['uid']]);
				if (DBM::is_result($user)) {
					$self = normalise_link(System::baseUrl() . '/profile/' . $user['nickname']);
					logger("'myself' is ".$self." for parent ".$parent_id." checking against ".$item['author-link']." and ".$item['owner-link'], LOGGER_DEBUG);
					if ((normalise_link($item['author-link']) == $self) || (normalise_link($item['owner-link']) == $self)) {
						dba::update('thread', ['mention' => true], ['iid' => $parent_id]);
						logger("tagged thread ".$parent_id." as mention for user ".$self, LOGGER_DEBUG);
					}
				}
			} else {
				/*
				 * Allow one to see reply tweets from status.net even when
				 * we don't have or can't see the original post.
				 */
				if ($force_parent) {
					logger('$force_parent=true, reply converted to top-level post.');
					$parent_id = 0;
					$item['parent-uri'] = $item['uri'];
					$item['gravity'] = 0;
				} else {
					logger('item parent '.$item['parent-uri'].' for '.$item['uid'].' was not found - ignoring item');
					return 0;
				}

				$parent_deleted = 0;
			}
		}

		$condition = ["`uri` = ? AND `network` IN (?, ?) AND `uid` = ?",
			$item['uri'], $item['network'], NETWORK_DFRN, $item['uid']];
		if (dba::exists('item', $condition)) {
			logger('duplicated item with the same uri found. '.print_r($item,true));
			return 0;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($item['network'], [NETWORK_DFRN, NETWORK_DIASPORA])) {
			$condition = ['guid' => $item['guid'], 'uid' => $item['uid']];
			if (dba::exists('item', $condition)) {
				logger('duplicated item with the same guid found. '.print_r($item,true));
				return 0;
			}
		} else {
			// Check for an existing post with the same content. There seems to be a problem with OStatus.
			$condition = ["`body` = ? AND `network` = ? AND `created` = ? AND `contact-id` = ? AND `uid` = ?",
					$item['body'], $item['network'], $item['created'], $item['contact-id'], $item['uid']];
			if (dba::exists('item', $condition)) {
				logger('duplicated item with the same body found. '.print_r($item,true));
				return 0;
			}
		}

		// Is this item available in the global items (with uid=0)?
		if ($item["uid"] == 0) {
			$item["global"] = true;

			// Set the global flag on all items if this was a global item entry
			dba::update('item', ['global' => true], ['uri' => $item["uri"]]);
		} else {
			$item["global"] = dba::exists('item', ['uid' => 0, 'uri' => $item["uri"]]);
		}

		// ACL settings
		if (strlen($allow_cid) || strlen($allow_gid) || strlen($deny_cid) || strlen($deny_gid)) {
			$private = 1;
		} else {
			$private = $item['private'];
		}

		$item["allow_cid"] = $allow_cid;
		$item["allow_gid"] = $allow_gid;
		$item["deny_cid"] = $deny_cid;
		$item["deny_gid"] = $deny_gid;
		$item["private"] = $private;
		$item["deleted"] = $parent_deleted;

		// Fill the cache field
		put_item_in_cache($item);

		if ($notify) {
			Addon::callHooks('post_local', $item);
		} else {
			Addon::callHooks('post_remote', $item);
		}

		// This array field is used to trigger some automatic reactions
		// It is mainly used in the "post_local" hook.
		unset($item['api_source']);

		if (x($item, 'cancel')) {
			logger('post cancelled by addon.');
			return 0;
		}

		/*
		 * Check for already added items.
		 * There is a timing issue here that sometimes creates double postings.
		 * An unique index would help - but the limitations of MySQL (maximum size of index values) prevent this.
		 */
		if ($item["uid"] == 0) {
			if (dba::exists('item', ['uri' => trim($item['uri']), 'uid' => 0])) {
				logger('Global item already stored. URI: '.$item['uri'].' on network '.$item['network'], LOGGER_DEBUG);
				return 0;
			}
		}

		logger('' . print_r($item,true), LOGGER_DATA);

		dba::transaction();
		$ret = dba::insert('item', $item);

		// When the item was successfully stored we fetch the ID of the item.
		if (DBM::is_result($ret)) {
			$current_post = dba::lastInsertId();
		} else {
			// This can happen - for example - if there are locking timeouts.
			dba::rollback();

			// Store the data into a spool file so that we can try again later.

			// At first we restore the Diaspora signature that we removed above.
			if (isset($encoded_signature)) {
				$item['dsprsig'] = $encoded_signature;
			}

			// Now we store the data in the spool directory
			// We use "microtime" to keep the arrival order and "mt_rand" to avoid duplicates
			$file = 'item-'.round(microtime(true) * 10000).'-'.mt_rand().'.msg';

			$spoolpath = get_spoolpath();
			if ($spoolpath != "") {
				$spool = $spoolpath.'/'.$file;
				file_put_contents($spool, json_encode($item));
				logger("Item wasn't stored - Item was spooled into file ".$file, LOGGER_DEBUG);
			}
			return 0;
		}

		if ($current_post == 0) {
			// This is one of these error messages that never should occur.
			logger("couldn't find created item - we better quit now.");
			dba::rollback();
			return 0;
		}

		// How much entries have we created?
		// We wouldn't need this query when we could use an unique index - but MySQL has length problems with them.
		$entries = dba::count('item', ['uri' => $item['uri'], 'uid' => $item['uid'], 'network' => $item['network']]);

		if ($entries > 1) {
			// There are duplicates. We delete our just created entry.
			logger('Duplicated post occurred. uri = ' . $item['uri'] . ' uid = ' . $item['uid']);

			// Yes, we could do a rollback here - but we are having many users with MyISAM.
			dba::delete('item', ['id' => $current_post]);
			dba::commit();
			return 0;
		} elseif ($entries == 0) {
			// This really should never happen since we quit earlier if there were problems.
			logger("Something is terribly wrong. We haven't found our created entry.");
			dba::rollback();
			return 0;
		}

		logger('created item '.$current_post);
		self::updateContact($item);

		if (!$parent_id || ($item['parent-uri'] === $item['uri'])) {
			$parent_id = $current_post;
		}

		// Set parent id
		dba::update('item', ['parent' => $parent_id], ['id' => $current_post]);

		$item['id'] = $current_post;
		$item['parent'] = $parent_id;

		// update the commented timestamp on the parent
		// Only update "commented" if it is really a comment
		if (($item['verb'] == ACTIVITY_POST) || !Config::get("system", "like_no_comment")) {
			dba::update('item', ['commented' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		} else {
			dba::update('item', ['changed' => DateTimeFormat::utcNow()], ['id' => $parent_id]);
		}

		if ($dsprsig) {
			/*
			 * Friendica servers lower than 3.4.3-2 had double encoded the signature ...
			 * We can check for this condition when we decode and encode the stuff again.
			 */
			if (base64_encode(base64_decode(base64_decode($dsprsig->signature))) == base64_decode($dsprsig->signature)) {
				$dsprsig->signature = base64_decode($dsprsig->signature);
				logger("Repaired double encoded signature from handle ".$dsprsig->signer, LOGGER_DEBUG);
			}

			dba::insert('sign', ['iid' => $current_post, 'signed_text' => $dsprsig->signed_text,
						'signature' => $dsprsig->signature, 'signer' => $dsprsig->signer]);
		}

		$deleted = self::tagDeliver($item['uid'], $current_post);

		/*
		 * current post can be deleted if is for a community page and no mention are
		 * in it.
		 */
		if (!$deleted && !$dontcache) {
			$posted_item = dba::selectFirst('item', [], ['id' => $current_post]);
			if (DBM::is_result($posted_item)) {
				if ($notify) {
					Addon::callHooks('post_local_end', $posted_item);
				} else {
					Addon::callHooks('post_remote_end', $posted_item);
				}
			} else {
				logger('new item not found in DB, id ' . $current_post);
			}
		}

		if ($item['parent-uri'] === $item['uri']) {
			self::addThread($current_post);
		} else {
			self::updateThread($parent_id);
		}

		dba::commit();

		/*
		 * Due to deadlock issues with the "term" table we are doing these steps after the commit.
		 * This is not perfect - but a workable solution until we found the reason for the problem.
		 */
		Term::insertFromTagFieldByItemId($current_post);
		Term::insertFromFileFieldByItemId($current_post);

		if ($item['parent-uri'] === $item['uri']) {
			self::addShadow($current_post);
		} else {
			self::addShadowPost($current_post);
		}

		check_user_notification($current_post);

		if ($notify) {
			Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], "Notifier", $notify_type, $current_post);
		}

		return $current_post;
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadow($itemid)
	{
		$fields = ['uid', 'wall', 'private', 'moderated', 'visible', 'contact-id', 'deleted', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = dba::selectFirst('item', $fields, $condition);

		if (!DBM::is_result($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"] || $item["moderated"] || $item["private"]) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""])) {
			return;
		}

		// Only do these checks if the post isn't a wall post
		if (!$item["wall"]) {
			// Check, if hide-friends is activated - then don't do a shadow entry
			if (dba::exists('profile', ['is-default' => true, 'uid' => $item['uid'], 'hide-friends' => true])) {
				return;
			}

			// Check if the contact is hidden or blocked
			if (!dba::exists('contact', ['hidden' => false, 'blocked' => false, 'id' => $item['contact-id']])) {
				return;
			}
		}

		// Only add a shadow, if the profile isn't hidden
		if (dba::exists('user', ['uid' => $item['uid'], 'hidewall' => true])) {
			return;
		}

		$item = dba::selectFirst('item', [], ['id' => $itemid]);

		if (DBM::is_result($item) && ($item["allow_cid"] == '')  && ($item["allow_gid"] == '') &&
			($item["deny_cid"] == '') && ($item["deny_gid"] == '')) {

			if (!dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
				// Preparing public shadow (removing user specific data)
				unset($item['id']);
				$item['uid'] = 0;
				$item['origin'] = 0;
				$item['wall'] = 0;
				if ($item['uri'] == $item['parent-uri']) {
					$item['contact-id'] = Contact::getIdForURL($item['owner-link']);
				} else {
					$item['contact-id'] = Contact::getIdForURL($item['author-link']);
				}

				if (in_array($item['type'], ["net-comment", "wall-comment"])) {
					$item['type'] = 'remote-comment';
				} elseif ($item['type'] == 'wall') {
					$item['type'] = 'remote';
				}

				$public_shadow = self::insert($item, false, false, true);

				logger("Stored public shadow for thread ".$itemid." under id ".$public_shadow, LOGGER_DEBUG);
			}
		}
	}

	/**
	 * @brief Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 */
	public static function addShadowPost($itemid)
	{
		$item = dba::selectFirst('item', [], ['id' => $itemid]);
		if (!DBM::is_result($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['id'] == $item['parent']) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0)
			return;

		// Is there a shadow parent?
		if (!dba::exists('item', ['uri' => $item['parent-uri'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (dba::exists('item', ['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		// Preparing public shadow (removing user specific data)
		unset($item['id']);
		$item['uid'] = 0;
		$item['origin'] = 0;
		$item['wall'] = 0;
		$item['contact-id'] = Contact::getIdForURL($item['author-link']);

		if (in_array($item['type'], ["net-comment", "wall-comment"])) {
			$item['type'] = 'remote-comment';
		} elseif ($item['type'] == 'wall') {
			$item['type'] = 'remote';
		}

		$public_shadow = self::insert($item, false, false, true);

		logger("Stored public shadow for comment ".$item['uri']." under id ".$public_shadow, LOGGER_DEBUG);
	}

	 /**
	 * Adds a "lang" specification in a "postopts" element of given $arr,
	 * if possible and not already present.
	 * Expects "body" element to exist in $arr.
	 */
	private static function addLanguageInPostopts(&$arr)
	{
		if (x($arr, 'postopts')) {
			if (strstr($arr['postopts'], 'lang=')) {
				// do not override
				return;
			}
			$postopts = $arr['postopts'];
		} else {
			$postopts = "";
		}

		$naked_body = preg_replace('/\[(.+?)\]/','', $arr['body']);
		$l = new Text_LanguageDetect();
		$lng = $l->detect($naked_body, 3);

		if (sizeof($lng) > 0) {
			if ($postopts != "") {
				$postopts .= '&'; // arbitrary separator, to be reviewed
			}

			$postopts .= 'lang=';
			$sep = "";

			foreach ($lng as $language => $score) {
				$postopts .= $sep . $language . ";" . $score;
				$sep = ':';
			}
			$arr['postopts'] = $postopts;
		}
	}

	/**
	 * @brief Creates an unique guid out of a given uri
	 *
	 * @param string $uri uri of an item entry
	 * @param string $host hostname for the GUID prefix
	 * @return string unique guid
	 */
	public static function guidFromUri($uri, $host)
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

		// We use a hash of the hostname as prefix for the guid
		$guid_prefix = hash("crc32", $host);

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed["scheme"]);

		// Glue it together to be able to make a hash from it
		$host_id = implode("/", $parsed);

		// We could use any hash algorithm since it isn't a security issue
		$host_hash = hash("ripemd128", $host_id);

		return $guid_prefix.$host_hash;
	}

	/**
	 * @brief Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @param array $arr Contains the just posted item record
	 */
	private static function updateContact($arr)
	{
		// Unarchive the author
		$contact = dba::selectFirst('contact', [], ['id' => $arr["author-id"]]);
		if (DBM::is_result($contact)) {
			Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it's not our own contact
		$contact = dba::selectFirst('contact', [], ['id' => $arr["contact-id"], 'self' => false]);
		if (DBM::is_result($contact)) {
			Contact::unmarkForArchival($contact);
		}

		$update = (!$arr['private'] && (($arr["author-link"] === $arr["owner-link"]) || ($arr["parent-uri"] === $arr["uri"])));

		// Is it a forum? Then we don't care about the rules from above
		if (!$update && ($arr["network"] == NETWORK_DFRN) && ($arr["parent-uri"] === $arr["uri"])) {
			if (dba::exists('contact', ['id' => $arr['contact-id'], 'forum' => true])) {
				$update = true;
			}
		}

		if ($update) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['contact-id']]);
		}
		// Now do the same for the system wide contacts with uid=0
		if (!$arr['private']) {
			dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]);

			if ($arr['owner-id'] != $arr['author-id']) {
				dba::update('contact', ['success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]);
			}
		}
	}

	private static function setHashtags(&$item)
	{

		$tags = get_tags($item["body"]);

		// No hashtags?
		if (!count($tags)) {
			return false;
		}

		// This sorting is important when there are hashtags that are part of other hashtags
		// Otherwise there could be problems with hashtags like #test and #test2
		rsort($tags);

		$URLSearchString = "^\[\]";

		// All hashtags should point to the home server if "local_tags" is activated
		if (Config::get('system', 'local_tags')) {
			$item["body"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=".System::baseUrl()."/search?tag=$2]$2[/url]", $item["body"]);

			$item["tag"] = preg_replace("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=".System::baseUrl()."/search?tag=$2]$2[/url]", $item["tag"]);
		}

		// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
		$item["body"] = preg_replace_callback("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
			function ($match) {
				return ("[url=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/url]");
			}, $item["body"]);

		$item["body"] = preg_replace_callback("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
			function ($match) {
				return ("[bookmark=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/bookmark]");
			}, $item["body"]);

		$item["body"] = preg_replace_callback("/\[attachment (.*)\](.*?)\[\/attachment\]/ism",
			function ($match) {
				return ("[attachment " . str_replace("#", "&num;", $match[1]) . "]" . $match[2] . "[/attachment]");
			}, $item["body"]);

		// Repair recursive urls
		$item["body"] = preg_replace("/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				"&num;$2", $item["body"]);

		foreach ($tags as $tag) {
			if ((strpos($tag, '#') !== 0) || strpos($tag, '[url=')) {
				continue;
			}

			$basetag = str_replace('_',' ',substr($tag,1));

			$newtag = '#[url=' . System::baseUrl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';

			$item["body"] = str_replace($tag, $newtag, $item["body"]);

			if (!stristr($item["tag"], "/search?tag=" . $basetag . "]" . $basetag . "[/url]")) {
				if (strlen($item["tag"])) {
					$item["tag"] = ','.$item["tag"];
				}
				$item["tag"] = $newtag.$item["tag"];
			}
		}

		// Convert back the masked hashtags
		$item["body"] = str_replace("&num;", "#", $item["body"]);
	}

	public static function getGuidById($id)
	{
		$item = dba::selectFirst('item', ['guid'], ['id' => $id]);
		if (DBM::is_result($item)) {
			return $item['guid'];
		} else {
			return '';
		}
	}

	public static function getIdAndNickByGuid($guid, $uid = 0)
	{
		$nick = "";
		$id = 0;

		if ($uid == 0) {
			$uid == local_user();
		}

		// Does the given user have this item?
		if ($uid) {
			$item = dba::fetch_first("SELECT `item`.`id`, `user`.`nickname` FROM `item`
				INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
				WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `item`.`guid` = ? AND `item`.`uid` = ?", $guid, $uid);
			if (DBM::is_result($item)) {
				$id = $item["id"];
				$nick = $item["nickname"];
			}
		}

		// Or is it anywhere on the server?
		if ($nick == "") {
			$item = dba::fetch_first("SELECT `item`.`id`, `user`.`nickname` FROM `item`
				INNER JOIN `user` ON `user`.`uid` = `item`.`uid`
				WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
					AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
					AND NOT `item`.`private` AND `item`.`wall`
					AND `item`.`guid` = ?", $guid);
			if (DBM::is_result($item)) {
				$id = $item["id"];
				$nick = $item["nickname"];
			}
		}
		return ["nick" => $nick, "id" => $id];
	}

	/**
	 * look for mention tags and setup a second delivery chain for forum/community posts if appropriate
	 * @param int $uid
	 * @param int $item_id
	 * @return bool true if item was deleted, else false
	 */
	private static function tagDeliver($uid, $item_id)
	{
		$mention = false;

		$user = dba::selectFirst('user', [], ['uid' => $uid]);
		if (!DBM::is_result($user)) {
			return;
		}

		$community_page = (($user['page-flags'] == PAGE_COMMUNITY) ? true : false);
		$prvgroup = (($user['page-flags'] == PAGE_PRVGROUP) ? true : false);

		$item = dba::selectFirst('item', [], ['id' => $item_id]);
		if (!DBM::is_result($item)) {
			return;
		}

		$link = normalise_link(System::baseUrl() . '/profile/' . $user['nickname']);

		/*
		 * Diaspora uses their own hardwired link URL in @-tags
		 * instead of the one we supply with webfinger
		 */
		$dlink = normalise_link(System::baseUrl() . '/u/' . $user['nickname']);

		$cnt = preg_match_all('/[\@\!]\[url\=(.*?)\](.*?)\[\/url\]/ism', $item['body'], $matches, PREG_SET_ORDER);
		if ($cnt) {
			foreach ($matches as $mtch) {
				if (link_compare($link, $mtch[1]) || link_compare($dlink, $mtch[1])) {
					$mention = true;
					logger('mention found: ' . $mtch[2]);
				}
			}
		}

		if (!$mention) {
			if (($community_page || $prvgroup) &&
				  !$item['wall'] && !$item['origin'] && ($item['id'] == $item['parent'])) {
				// mmh.. no mention.. community page or private group... no wall.. no origin.. top-post (not a comment)
				// delete it!
				logger("no-mention top-level post to community or private group. delete.");
				dba::delete('item', ['id' => $item_id]);
				return true;
			}
			return;
		}

		$arr = ['item' => $item, 'user' => $user];

		Addon::callHooks('tagged', $arr);

		if (!$community_page && !$prvgroup) {
			return;
		}

		/*
		 * tgroup delivery - setup a second delivery chain
		 * prevent delivery looping - only proceed
		 * if the message originated elsewhere and is a top-level post
		 */
		if ($item['wall'] || $item['origin'] || ($item['id'] != $item['parent'])) {
			return;
		}

		// now change this copy of the post to a forum head message and deliver to all the tgroup members
		$self = dba::selectFirst('contact', ['id', 'name', 'url', 'thumb'], ['uid' => $uid, 'self' => true]);
		if (!DBM::is_result($self)) {
			return;
		}

		$owner_id = Contact::getIdForURL($self['url']);

		// also reset all the privacy bits to the forum default permissions

		$private = ($user['allow_cid'] || $user['allow_gid'] || $user['deny_cid'] || $user['deny_gid']) ? 1 : 0;

		$forum_mode = ($prvgroup ? 2 : 1);

		$fields = ['wall' => true, 'origin' => true, 'forum_mode' => $forum_mode, 'contact-id' => $self['id'],
			'owner-id' => $owner_id, 'owner-name' => $self['name'], 'owner-link' => $self['url'],
			'owner-avatar' => $self['thumb'], 'private' => $private, 'allow_cid' => $user['allow_cid'],
			'allow_gid' => $user['allow_gid'], 'deny_cid' => $user['deny_cid'], 'deny_gid' => $user['deny_gid']];
		dba::update('item', $fields, ['id' => $item_id]);

		self::updateThread($item_id);

		Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], 'Notifier', 'tgroup', $item_id);
	}

	public static function isRemoteSelf($contact, &$datarray)
	{
		$a = get_app();

		if (!$contact['remote_self']) {
			return false;
		}

		// Prevent the forwarding of posts that are forwarded
		if ($datarray["extid"] == NETWORK_DFRN) {
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray["app"] == $a->get_hostname()) {
			return false;
		}

		// Only forward posts
		if ($datarray["verb"] != ACTIVITY_POST) {
			return false;
		}

		if (($contact['network'] != NETWORK_FEED) && $datarray['private']) {
			return false;
		}

		$datarray2 = $datarray;
		logger('remote-self start - Contact '.$contact['url'].' - '.$contact['remote_self'].' Item '.print_r($datarray, true), LOGGER_DEBUG);
		if ($contact['remote_self'] == 2) {
			$self = dba::selectFirst('contact', ['id', 'name', 'url', 'thumb'],
					['uid' => $contact['uid'], 'self' => true]);
			if (DBM::is_result($self)) {
				$datarray['contact-id'] = $self["id"];

				$datarray['owner-name'] = $self["name"];
				$datarray['owner-link'] = $self["url"];
				$datarray['owner-avatar'] = $self["thumb"];

				$datarray['author-name']   = $datarray['owner-name'];
				$datarray['author-link']   = $datarray['owner-link'];
				$datarray['author-avatar'] = $datarray['owner-avatar'];

				unset($datarray['created']);
				unset($datarray['edited']);
			}

			if ($contact['network'] != NETWORK_FEED) {
				$datarray["guid"] = get_guid(32);
				unset($datarray["plink"]);
				$datarray["uri"] = item_new_uri($a->get_hostname(), $contact['uid'], $datarray["guid"]);
				$datarray["parent-uri"] = $datarray["uri"];
				$datarray["extid"] = $contact['network'];
				$urlpart = parse_url($datarray2['author-link']);
				$datarray["app"] = $urlpart["host"];
			} else {
				$datarray['private'] = 0;
			}
		}

		if ($contact['network'] != NETWORK_FEED) {
			// Store the original post
			$r = self::insert($datarray2, false, false);
			logger('remote-self post original item - Contact '.$contact['url'].' return '.$r.' Item '.print_r($datarray2, true), LOGGER_DEBUG);
		} else {
			$datarray["app"] = "Feed";
		}

		// Trigger automatic reactions for addons
		$datarray['api_source'] = true;

		// We have to tell the hooks who we are - this really should be improved
		$_SESSION["authenticated"] = true;
		$_SESSION["uid"] = $contact['uid'];

		return true;
	}

	/**
	 *
	 * @param string $s
	 * @param int    $uid
	 * @param array  $item
	 * @param int    $cid
	 * @return string
	 */
	public static function fixPrivatePhotos($s, $uid, $item = null, $cid = 0)
	{
		if (Config::get('system', 'disable_embedded')) {
			return $s;
		}

		logger('check for photos', LOGGER_DEBUG);
		$site = substr(System::baseUrl(), strpos(System::baseUrl(), '://'));

		$orig_body = $s;
		$new_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);

		while (($img_st_close !== false) && ($img_len !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$image = substr($orig_body, $img_start + $img_st_close, $img_len);

			logger('found photo ' . $image, LOGGER_DEBUG);

			if (stristr($image, $site . '/photo/')) {
				// Only embed locally hosted photos
				$replace = false;
				$i = basename($image);
				$i = str_replace(['.jpg', '.png', '.gif'], ['', '', ''], $i);
				$x = strpos($i, '-');

				if ($x) {
					$res = substr($i, $x + 1);
					$i = substr($i, 0, $x);
					$fields = ['data', 'type', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
					$photo = dba::selectFirst('photo', $fields, ['resource-id' => $i, 'scale' => $res, 'uid' => $uid]);
					if (DBM::is_result($photo)) {
						/*
						 * Check to see if we should replace this photo link with an embedded image
						 * 1. No need to do so if the photo is public
						 * 2. If there's a contact-id provided, see if they're in the access list
						 *    for the photo. If so, embed it.
						 * 3. Otherwise, if we have an item, see if the item permissions match the photo
						 *    permissions, regardless of order but first check to see if they're an exact
						 *    match to save some processing overhead.
						 */
						if (self::hasPermissions($photo)) {
							if ($cid) {
								$recips = self::enumeratePermissions($photo);
								if (in_array($cid, $recips)) {
									$replace = true;
								}
							} elseif ($item) {
								if (self::samePermissions($item, $photo)) {
									$replace = true;
								}
							}
						}
						if ($replace) {
							$data = $photo['data'];
							$type = $photo['type'];

							// If a custom width and height were specified, apply before embedding
							if (preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
								logger('scaling photo', LOGGER_DEBUG);

								$width = intval($match[1]);
								$height = intval($match[2]);

								$Image = new Image($data, $type);
								if ($Image->isValid()) {
									$Image->scaleDown(max($width, $height));
									$data = $Image->asString();
									$type = $Image->getType();
								}
							}

							logger('replacing photo', LOGGER_DEBUG);
							$image = 'data:' . $type . ';base64,' . base64_encode($data);
							logger('replaced: ' . $image, LOGGER_DATA);
						}
					}
				}
			}

			$new_body = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
			$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
			if ($orig_body === false) {
				$orig_body = '';
			}

			$img_start = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_len = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return $new_body;
	}

	private static function hasPermissions($obj)
	{
		return (
			(
				x($obj, 'allow_cid')
			) || (
				x($obj, 'allow_gid')
			) || (
				x($obj, 'deny_cid')
			) || (
				x($obj, 'deny_gid')
			)
		);
	}

	private static function samePermissions($obj1, $obj2)
	{
		// first part is easy. Check that these are exactly the same.
		if (($obj1['allow_cid'] == $obj2['allow_cid'])
			&& ($obj1['allow_gid'] == $obj2['allow_gid'])
			&& ($obj1['deny_cid'] == $obj2['deny_cid'])
			&& ($obj1['deny_gid'] == $obj2['deny_gid'])) {
			return true;
		}

		// This is harder. Parse all the permissions and compare the resulting set.
		$recipients1 = self::enumeratePermissions($obj1);
		$recipients2 = self::enumeratePermissions($obj2);
		sort($recipients1);
		sort($recipients2);

		/// @TODO Comparison of arrays, maybe use array_diff_assoc() here?
		return ($recipients1 == $recipients2);
	}

	// returns an array of contact-ids that are allowed to see this object
	private static function enumeratePermissions($obj)
	{
		$allow_people = expand_acl($obj['allow_cid']);
		$allow_groups = Group::expand(expand_acl($obj['allow_gid']));
		$deny_people  = expand_acl($obj['deny_cid']);
		$deny_groups  = Group::expand(expand_acl($obj['deny_gid']));
		$recipients   = array_unique(array_merge($allow_people, $allow_groups));
		$deny         = array_unique(array_merge($deny_people, $deny_groups));
		$recipients   = array_diff($recipients, $deny);
		return $recipients;
	}

	public static function getFeedTags($item)
	{
		$ret = [];
		$matches = false;
		$cnt = preg_match_all('|\#\[url\=(.*?)\](.*?)\[\/url\]|', $item['tag'], $matches);
		if ($cnt) {
			for ($x = 0; $x < $cnt; $x ++) {
				if ($matches[1][$x]) {
					$ret[$matches[2][$x]] = ['#', $matches[1][$x], $matches[2][$x]];
				}
			}
		}
		$matches = false;
		$cnt = preg_match_all('|\@\[url\=(.*?)\](.*?)\[\/url\]|', $item['tag'], $matches);
		if ($cnt) {
			for ($x = 0; $x < $cnt; $x ++) {
				if ($matches[1][$x]) {
					$ret[] = ['@', $matches[1][$x], $matches[2][$x]];
				}
			}
		}
		return $ret;
	}

	public static function expire($uid, $days, $network = "", $force = false)
	{
		if (!$uid || ($days < 1)) {
			return;
		}

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = PConfig::get($uid,'expire', 'network_only');
		$sql_extra = (intval($expire_network_only) ? " AND wall = 0 " : "");

		if ($network != "") {
			$sql_extra .= sprintf(" AND network = '%s' ", dbesc($network));

			/*
			 * There is an index "uid_network_received" but not "uid_network_created"
			 * This avoids the creation of another index just for one purpose.
			 * And it doesn't really matter wether to look at "received" or "created"
			 */
			$range = "AND `received` < UTC_TIMESTAMP() - INTERVAL %d DAY ";
		} else {
			$range = "AND `created` < UTC_TIMESTAMP() - INTERVAL %d DAY ";
		}

		$r = q("SELECT `file`, `resource-id`, `starred`, `type`, `id` FROM `item`
			WHERE `uid` = %d $range
			AND `id` = `parent`
			$sql_extra
			AND `deleted` = 0",
			intval($uid),
			intval($days)
		);

		if (!DBM::is_result($r)) {
			return;
		}

		$expire_items = PConfig::get($uid, 'expire', 'items', 1);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes = PConfig::get($uid, 'expire', 'notes', 1);
		$expire_starred = PConfig::get($uid, 'expire', 'starred', 1);
		$expire_photos = PConfig::get($uid, 'expire', 'photos', 0);

		logger('User '.$uid.': expire: # items=' . count($r). "; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");

		foreach ($r as $item) {

			// don't expire filed items

			if (strpos($item['file'],'[') !== false) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if ($expire_photos == 0 && strlen($item['resource-id'])) {
				continue;
			} elseif ($expire_starred == 0 && intval($item['starred'])) {
				continue;
			} elseif ($expire_notes == 0 && $item['type'] == 'note') {
				continue;
			} elseif ($expire_items == 0 && $item['type'] != 'note') {
				continue;
			}

			self::deleteById($item['id'], PRIORITY_LOW);
		}
	}

	public static function firstPostDate($uid, $wall = false)
	{
		$condition = ['uid' => $uid, 'wall' => $wall, 'deleted' => false, 'visible' => true, 'moderated' => false];
		$params = ['order' => ['created' => false]];
		$thread = dba::selectFirst('thread', ['created'], $condition, $params);
		if (DBM::is_result($thread)) {
			return substr(DateTimeFormat::local($thread['created']), 0, 10);
		}
		return false;
	}

	/**
	 * @brief add/remove activity to an item
	 *
	 * Toggle activities as like,dislike,attend of an item
	 *
	 * @param string $item_id
	 * @param string $verb
	 * 		Activity verb. One of
	 * 			like, unlike, dislike, undislike, attendyes, unattendyes,
	 * 			attendno, unattendno, attendmaybe, unattendmaybe
	 * @hook 'post_local_end'
	 * 		array $arr
	 * 			'post_id' => ID of posted item
	 */
	public static function performLike($item_id, $verb)
	{
		if (!local_user() && !remote_user()) {
			return false;
		}

		switch ($verb) {
			case 'like':
			case 'unlike':
				$bodyverb = L10n::t('%1$s likes %2$s\'s %3$s');
				$activity = ACTIVITY_LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$bodyverb = L10n::t('%1$s doesn\'t like %2$s\'s %3$s');
				$activity = ACTIVITY_DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$bodyverb = L10n::t('%1$s is attending %2$s\'s %3$s');
				$activity = ACTIVITY_ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$bodyverb = L10n::t('%1$s is not attending %2$s\'s %3$s');
				$activity = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$bodyverb = L10n::t('%1$s may attend %2$s\'s %3$s');
				$activity = ACTIVITY_ATTENDMAYBE;
				break;
			default:
				logger('like: unknown verb ' . $verb . ' for item ' . $item_id);
				return false;
		}

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === ACTIVITY_ATTEND || $activity === ACTIVITY_ATTENDNO || $activity === ACTIVITY_ATTENDMAYBE;

		logger('like: verb ' . $verb . ' item ' . $item_id);

		$item = dba::selectFirst('item', [], ['`id` = ? OR `uri` = ?', $item_id, $item_id]);
		if (!DBM::is_result($item)) {
			logger('like: unknown item ' . $item_id);
			return false;
		}

		$uid = $item['uid'];
		if (($uid == 0) && local_user()) {
			$uid = local_user();
		}

		if (!can_write_wall($uid)) {
			logger('like: unable to write on wall ' . $uid);
			return false;
		}

		// Retrieves the local post owner
		$owner_self_contact = dba::selectFirst('contact', [], ['uid' => $uid, 'self' => true]);
		if (!DBM::is_result($owner_self_contact)) {
			logger('like: unknown owner ' . $uid);
			return false;
		}

		// Retrieve the current logged in user's public contact
		$author_id = public_contact();

		$author_contact = dba::selectFirst('contact', [], ['id' => $author_id]);
		if (!DBM::is_result($author_contact)) {
			logger('like: unknown author ' . $author_id);
			return false;
		}

		// Contact-id is the uid-dependant author contact
		if (local_user() == $uid) {
			$item_contact_id = $owner_self_contact['id'];
			$item_contact = $owner_self_contact;
		} else {
			$item_contact_id = Contact::getIdForURL($author_contact['url'], $uid, true);
			$item_contact = dba::selectFirst('contact', [], ['id' => $item_contact_id]);
			if (!DBM::is_result($item_contact)) {
				logger('like: unknown item contact ' . $item_contact_id);
				return false;
			}
		}

		// Look for an existing verb row
		// event participation are essentially radio toggles. If you make a subsequent choice,
		// we need to eradicate your first choice.
		if ($event_verb_flag) {
			$verbs = "'" . dbesc(ACTIVITY_ATTEND) . "', '" . dbesc(ACTIVITY_ATTENDNO) . "', '" . dbesc(ACTIVITY_ATTENDMAYBE) . "'";
		} else {
			$verbs = "'".dbesc($activity)."'";
		}

		/// @todo This query is expected to be a performance eater due to the "OR" - it has to be changed totally
		$existing_like = q("SELECT `id`, `guid`, `verb` FROM `item`
			WHERE `verb` IN ($verbs)
			AND `deleted` = 0
			AND `author-id` = %d
			AND `uid` = %d
			AND (`parent` = '%s' OR `parent-uri` = '%s' OR `thr-parent` = '%s')
			LIMIT 1",
			intval($author_contact['id']),
			intval($item['uid']),
			dbesc($item_id), dbesc($item_id), dbesc($item['uri'])
		);

		// If it exists, mark it as deleted
		if (DBM::is_result($existing_like)) {
			$like_item = $existing_like[0];

			// Already voted, undo it
			$fields = ['deleted' => true, 'unseen' => true, 'changed' => DateTimeFormat::utcNow()];
			dba::update('item', $fields, ['id' => $like_item['id']]);

			// Clean up the Diaspora signatures for this like
			// Go ahead and do it even if Diaspora support is disabled. We still want to clean up
			// if it had been enabled in the past
			dba::delete('sign', ['iid' => $like_item['id']]);

			$like_item_id = $like_item['id'];
			Worker::add(PRIORITY_HIGH, "Notifier", "like", $like_item_id);

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// Verb is "un-something", just trying to delete existing entries
		if (strpos($verb, 'un') === 0) {
			return true;
		}

		// Else or if event verb different from existing row, create a new item row
		$post_type = (($item['resource-id']) ? L10n::t('photo') : L10n::t('status'));
		if ($item['object-type'] === ACTIVITY_OBJ_EVENT) {
			$post_type = L10n::t('event');
		}
		$objtype = $item['resource-id'] ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE ;
		$link = xmlify('<link rel="alternate" type="text/html" href="' . System::baseUrl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . '" />' . "\n") ;
		$body = $item['body'];

		$obj = <<< EOT

		<object>
			<type>$objtype</type>
			<local>1</local>
			<id>{$item['uri']}</id>
			<link>$link</link>
			<title></title>
			<content>$body</content>
		</object>
EOT;

		$ulink = '[url=' . $author_contact['url'] . ']' . $author_contact['name'] . '[/url]';
		$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$plink = '[url=' . System::baseUrl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . ']' . $post_type . '[/url]';

		$new_item = [
			'guid'          => get_guid(32),
			'uri'           => item_new_uri(self::getApp()->get_hostname(), $item['uid']),
			'uid'           => $item['uid'],
			'contact-id'    => $item_contact_id,
			'type'          => 'activity',
			'wall'          => $item['wall'],
			'origin'        => 1,
			'gravity'       => GRAVITY_LIKE,
			'parent'        => $item['id'],
			'parent-uri'    => $item['uri'],
			'thr-parent'    => $item['uri'],
			'owner-id'      => $item['owner-id'],
			'owner-name'    => $item['owner-name'],
			'owner-link'    => $item['owner-link'],
			'owner-avatar'  => $item['owner-avatar'],
			'author-id'     => $author_contact['id'],
			'author-name'   => $author_contact['name'],
			'author-link'   => $author_contact['url'],
			'author-avatar' => $author_contact['thumb'],
			'body'          => sprintf($bodyverb, $ulink, $alink, $plink),
			'verb'          => $activity,
			'object-type'   => $objtype,
			'object'        => $obj,
			'allow_cid'     => $item['allow_cid'],
			'allow_gid'     => $item['allow_gid'],
			'deny_cid'      => $item['deny_cid'],
			'deny_gid'      => $item['deny_gid'],
			'visible'       => 1,
			'unseen'        => 1,
		];

		$new_item_id = self::insert($new_item);

		// If the parent item isn't visible then set it to visible
		if (!$item['visible']) {
			self::update(['visible' => true], ['id' => $item['id']]);
		}

		// Save the author information for the like in case we need to relay to Diaspora
		Diaspora::storeLikeSignature($item_contact, $new_item_id);

		$new_item['id'] = $new_item_id;

		Addon::callHooks('post_local_end', $new_item);

		Worker::add(PRIORITY_HIGH, "Notifier", "like", $new_item_id);

		return true;
	}

	private static function addThread($itemid, $onlyshadow = false)
	{
		$fields = ['uid', 'created', 'edited', 'commented', 'received', 'changed', 'wall', 'private', 'pubmail',
			'moderated', 'visible', 'spam', 'starred', 'bookmark', 'contact-id',
			'deleted', 'origin', 'forum_mode', 'mention', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = dba::selectFirst('item', $fields, $condition);

		if (!DBM::is_result($item)) {
			return;
		}

		$item['iid'] = $itemid;

		if (!$onlyshadow) {
			$result = dba::insert('thread', $item);

			logger("Add thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);
		}
	}

	private static function updateThread($itemid, $setmention = false)
	{
		$fields = ['uid', 'guid', 'title', 'body', 'created', 'edited', 'commented', 'received', 'changed',
			'wall', 'private', 'pubmail', 'moderated', 'visible', 'spam', 'starred', 'bookmark', 'contact-id',
			'deleted', 'origin', 'forum_mode', 'network', 'rendered-html', 'rendered-hash'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];

		$item = dba::selectFirst('item', $fields, $condition);
		if (!DBM::is_result($item)) {
			return;
		}

		if ($setmention) {
			$item["mention"] = 1;
		}

		$sql = "";

		$fields = [];

		foreach ($item as $field => $data) {
			if (!in_array($field, ["guid", "title", "body", "rendered-html", "rendered-hash"])) {
				$fields[$field] = $data;
			}
		}

		$result = dba::update('thread', $fields, ['iid' => $itemid]);

		logger("Update thread for item ".$itemid." - guid ".$item["guid"]." - ".(int)$result." ".print_r($item, true), LOGGER_DEBUG);

		// Updating a shadow item entry
		$items = dba::selectFirst('item', ['id'], ['guid' => $item['guid'], 'uid' => 0]);

		if (!DBM::is_result($items)) {
			return;
		}

		$fields = ['title' => $item['title'], 'body' => $item['body'],
			'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']];
		$result = dba::update('item', $fields, ['id' => $items['id']]);

		logger("Updating public shadow for post ".$items["id"]." - guid ".$item["guid"]." Result: ".print_r($result, true), LOGGER_DEBUG);
	}

	private static function deleteThread($itemid, $itemuri = "")
	{
		$item = dba::selectFirst('thread', ['uid'], ['iid' => $itemid]);
		if (!DBM::is_result($item)) {
			logger('No thread found for id '.$itemid, LOGGER_DEBUG);
			return;
		}

		// Using dba::delete at this time could delete the associated item entries
		$result = dba::e("DELETE FROM `thread` WHERE `iid` = ?", $itemid);

		logger("deleteThread: Deleted thread for item ".$itemid." - ".print_r($result, true), LOGGER_DEBUG);

		if ($itemuri != "") {
			$condition = ["`uri` = ? AND NOT `deleted` AND NOT (`uid` IN (?, 0))", $itemuri, $item["uid"]];
			if (!dba::exists('item', $condition)) {
				dba::delete('item', ['uri' => $itemuri, 'uid' => 0]);
				logger("deleteThread: Deleted shadow for item ".$itemuri, LOGGER_DEBUG);
			}
		}
	}
}
