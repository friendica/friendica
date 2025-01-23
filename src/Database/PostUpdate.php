<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Database;

use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Post\Counts;
use Friendica\Model\Tag;
use Friendica\Model\Verb;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Util\JsonLD;
use Friendica\Util\Strings;
use GuzzleHttp\Psr7\Uri;

/**
 * These database-intensive post update routines are meant to be executed in the background by the cronjob.
 *
 * If there is a need for a intensive migration after a database structure change, update this file
 * by adding a new method at the end with the number of the new DB_UPDATE_VERSION.
 */
class PostUpdate
{
	// Needed for the helper function to read from the legacy term table
	const OBJECT_TYPE_POST = 1;

	const VERSION = 1550;

	/**
	 * Calls the post update functions
	 */
	public static function update()
	{
		if (!self::update1297()) {
			return false;
		}
		if (!self::update1322()) {
			return false;
		}
		if (!self::update1329()) {
			return false;
		}
		if (!self::update1341()) {
			return false;
		}
		if (!self::update1342()) {
			return false;
		}
		if (!self::update1345()) {
			return false;
		}
		if (!self::update1346()) {
			return false;
		}
		if (!self::update1347()) {
			return false;
		}
		if (!self::update1348()) {
			return false;
		}
		if (!self::update1349()) {
			return false;
		}
		if (!self::update1383()) {
			return false;
		}
		if (!self::update1384()) {
			return false;
		}
		if (!self::update1400()) {
			return false;
		}
		if (!self::update1424()) {
			return false;
		}
		if (!self::update1425()) {
			return false;
		}
		if (!self::update1426()) {
			return false;
		}
		if (!self::update1427()) {
			return false;
		}
		if (!self::update1452()) {
			return false;
		}
		if (!self::update1483()) {
			return false;
		}
		if (!self::update1484()) {
			return false;
		}
		if (!self::update1506()) {
			return false;
		}
		if (!self::update1507()) {
			return false;
		}
		if (!self::update1544()) {
			return false;
		}
		if (!self::update1550()) {
			return false;
		}
		return true;
	}

	/**
	 * Set the delivery queue count to a negative value for all items preceding the feature.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1297()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1297) {
			return true;
		}

		if (!DBStructure::existsTable('item-delivery-data')) {
			DI::keyValue()->set('post_update_version', 1297);
			return true;
		}

		$max_item_delivery_data = DBA::selectFirst('item-delivery-data', ['iid'], ['queue_count > 0 OR queue_done > 0'], ['order' => ['iid']]);
		$max_iid                = $max_item_delivery_data['iid'] ?? 0;

		DI::logger()->info('Start update1297 with max iid: ' . $max_iid);

		$condition = ['`queue_count` = 0 AND `iid` < ?', $max_iid];

		DBA::update('item-delivery-data', ['queue_count' => -1], $condition);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error ' . DBA::errorNo() . ':' . DBA::errorMessage());
			return false;
		}

		DI::logger()->info('Processed rows: ' . DBA::affectedRows());

		DI::keyValue()->set('post_update_version', 1297);

		DI::logger()->info('Done');

		return true;
	}
	/**
	 * Remove contact duplicates
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1322()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1322) {
			return true;
		}

		DI::logger()->info('Start');

		$contacts = DBA::p(
			"SELECT `nurl`, `uid` FROM `contact`
			WHERE EXISTS (SELECT `nurl` FROM `contact` AS `c2`
				WHERE `c2`.`nurl` = `contact`.`nurl` AND `c2`.`id` != `contact`.`id` AND `c2`.`uid` = `contact`.`uid` AND `c2`.`network` IN (?, ?, ?) AND NOT `deleted`)
			AND (`network` IN (?, ?, ?) OR (`uid` = ?)) AND NOT `deleted` GROUP BY `nurl`, `uid`",
			Protocol::DIASPORA,
			Protocol::OSTATUS,
			Protocol::ACTIVITYPUB,
			Protocol::DIASPORA,
			Protocol::OSTATUS,
			Protocol::ACTIVITYPUB,
			0
		);

		while ($contact = DBA::fetch($contacts)) {
			DI::logger()->info('Remove duplicates', ['nurl' => $contact['nurl'], 'uid' => $contact['uid']]);
			Contact::removeDuplicates($contact['nurl'], $contact['uid']);
		}

		DBA::close($contact);
		DI::keyValue()->set('post_update_version', 1322);

		DI::logger()->info('Done');

		return true;
	}

	/**
	 * update user notification data
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1329()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1329) {
			return true;
		}

		if (!DBStructure::existsTable('item')) {
			DI::keyValue()->set('post_update_version', 1329);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1329_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$start_id  = $id;
		$rows      = 0;
		$condition = ["`id` > ?", $id];
		$params    = ['order' => ['id'], 'limit' => 10000];
		$items     = DBA::select('item', ['id', 'uri-id', 'uid'], $condition, $params);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id = $item['id'];

			Post\UserNotification::setNotification($item['uri-id'], $item['uid']);

			++$rows;
		}
		DBA::close($items);

		DI::keyValue()->set('post_update_version_1329_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1329);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the body
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1341()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1341) {
			return true;
		}

		if (!DBStructure::existsTable('item-content')) {
			DI::keyValue()->set('post_update_version', 1342);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1341_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$rows = 0;

		$items = DBA::p("SELECT `uri-id`,`body` FROM `item-content` WHERE
			(`body` LIKE ? OR `body` LIKE ? OR `body` LIKE ?) AND `uri-id` >= ?
			ORDER BY `uri-id` LIMIT 100000", '%#%', '%@%', '%!%', $id);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			Tag::storeFromBody($item['uri-id'], $item['body'], '#!@');
			$id = $item['uri-id'];
			++$rows;
			if ($rows % 1000 == 0) {
				DI::keyValue()->set('post_update_version_1341_id', $id);
			}
		}
		DBA::close($items);

		DI::keyValue()->set('post_update_version_1341_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 1,000 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 1000) {
			DI::keyValue()->set('post_update_version', 1341);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the "term" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1342()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1342) {
			return true;
		}

		if (!DBStructure::existsTable('term') || !DBStructure::existsTable('item-content')) {
			DI::keyValue()->set('post_update_version', 1342);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1342_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$rows = 0;

		$terms = DBA::p(
			"SELECT `term`.`tid`, `item`.`uri-id`, `term`.`type`, `term`.`term`, `term`.`url`, `item-content`.`body`
			FROM `term`
			INNER JOIN `item` ON `item`.`id` = `term`.`oid`
			INNER JOIN `item-content` ON `item-content`.`uri-id` = `item`.`uri-id`
			WHERE term.type IN (?, ?, ?, ?) AND `tid` >= ? ORDER BY `tid` LIMIT 100000",
			Tag::HASHTAG,
			Tag::MENTION,
			Tag::EXCLUSIVE_MENTION,
			Tag::IMPLICIT_MENTION,
			$id
		);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($term = DBA::fetch($terms)) {
			if (($term['type'] == Tag::MENTION) && !empty($term['url']) && !strstr($term['body'], $term['url'])) {
				$condition = ['nurl' => Strings::normaliseLink($term['url']), 'uid' => 0, 'deleted' => false];
				$contact   = DBA::selectFirst('contact', ['url', 'alias'], $condition, ['order' => ['id']]);
				if (!DBA::isResult($contact)) {
					$ssl_url   = str_replace('http://', 'https://', $term['url']);
					$condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $term['url'], Strings::normaliseLink($term['url']), $ssl_url, 0];
					$contact   = DBA::selectFirst('contact', ['url', 'alias'], $condition, ['order' => ['id']]);
				}

				if (DBA::isResult($contact) && (!strstr($term['body'], $contact['url']) && (empty($contact['alias']) || !strstr($term['body'], $contact['alias'])))) {
					$term['type'] = Tag::IMPLICIT_MENTION;
				}
			}

			Tag::store($term['uri-id'], $term['type'], $term['term'], $term['url']);

			$id = $term['tid'];
			++$rows;
			if ($rows % 1000 == 0) {
				DI::keyValue()->set('post_update_version_1342_id', $id);
			}
		}
		DBA::close($terms);

		DI::keyValue()->set('post_update_version_1342_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 1,000 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 1000) {
			DI::keyValue()->set('post_update_version', 1342);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the "post-delivery-data" table with data from the "item-delivery-data" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1345()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1345) {
			return true;
		}

		if (!DBStructure::existsTable('item-delivery-data')) {
			DI::keyValue()->set('post_update_version', 1345);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1345_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$rows = 0;

		$deliveries = DBA::p("SELECT `uri-id`, `iid`, `item-delivery-data`.`postopts`, `item-delivery-data`.`inform`,
			`queue_count`, `queue_done`, `activitypub`, `dfrn`, `diaspora`, `ostatus`, `legacy_dfrn`, `queue_failed`
			FROM `item-delivery-data`
			INNER JOIN `item` ON `item`.`id` = `item-delivery-data`.`iid`
			WHERE `iid` >= ? ORDER BY `iid` LIMIT 10000", $id);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($delivery = DBA::fetch($deliveries)) {
			$id = $delivery['iid'];
			unset($delivery['iid']);
			DBA::insert('post-delivery-data', $delivery, Database::INSERT_UPDATE);
			++$rows;
		}
		DBA::close($deliveries);

		DI::keyValue()->set('post_update_version_1345_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 100 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 100) {
			DI::keyValue()->set('post_update_version', 1345);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Generates the legacy item.file field string from an item ID.
	 * Includes only file and category terms.
	 *
	 * @param int $item_id
	 * @return string
	 * @throws \Exception
	 */
	private static function fileTextFromItemId($item_id)
	{
		$file_text = '';

		$condition = ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => [Category::FILE, Category::CATEGORY]];
		$tags      = DBA::selectToArray('term', ['type', 'term', 'url'], $condition);
		foreach ($tags as $tag) {
			if ($tag['type'] == Category::CATEGORY) {
				$file_text .= '<' . $tag['term'] . '>';
			} else {
				$file_text .= '[' . $tag['term'] . ']';
			}
		}

		return $file_text;
	}

	/**
	 * Fill the "tag" table with tags and mentions from the "term" table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function update1346()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1346) {
			return true;
		}

		if (!DBStructure::existsTable('term')) {
			DI::keyValue()->set('post_update_version', 1346);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1346_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$rows = 0;

		$terms = DBA::select(
			'term',
			['oid'],
			["`type` IN (?, ?) AND `oid` >= ?", Category::CATEGORY, Category::FILE, $id],
			['order' => ['oid'], 'limit' => 1000, 'group_by' => ['oid']]
		);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($term = DBA::fetch($terms)) {
			$item = Post::selectFirst(['uri-id', 'uid'], ['id' => $term['oid']]);
			if (!DBA::isResult($item)) {
				continue;
			}

			$file = self::fileTextFromItemId($term['oid']);
			if (!empty($file)) {
				Category::storeTextByURIId($item['uri-id'], $item['uid'], $file);
			}

			$id = $term['oid'];
			++$rows;
			if ($rows % 100 == 0) {
				DI::keyValue()->set('post_update_version_1346_id', $id);
			}
		}
		DBA::close($terms);

		DI::keyValue()->set('post_update_version_1346_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		// When there are less than 10 items processed this means that we reached the end
		// The other entries will then be processed with the regular functionality
		if ($rows < 10) {
			DI::keyValue()->set('post_update_version', 1346);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "vid" (verb) field in the item table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1347()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1347) {
			return true;
		}

		if (!DBStructure::existsTable('item-activity') || !DBStructure::existsTable('item')) {
			DI::keyValue()->set('post_update_version', 1347);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1347_id') ?? 0;

		DI::logger()->info('Start', ['item' => $id]);

		$start_id = $id;
		$rows     = 0;

		$items = DBA::p("SELECT `item`.`id`, `item`.`verb` AS `item-verb`, `item-content`.`verb`, `item-activity`.`activity`
			FROM `item` LEFT JOIN `item-content` ON `item-content`.`uri-id` = `item`.`uri-id`
			LEFT JOIN `item-activity` ON `item-activity`.`uri-id` = `item`.`uri-id` AND `item`.`gravity` = ?
			WHERE `item`.`id` >= ? AND `item`.`vid` IS NULL ORDER BY `item`.`id` LIMIT 10000", Item::GRAVITY_ACTIVITY, $id);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			$id   = $item['id'];
			$verb = $item['item-verb'];
			if (empty($verb)) {
				$verb = $item['verb'];
			}
			if (empty($verb) && is_int($item['activity'])) {
				$verb = Item::ACTIVITIES[$item['activity']];
			}
			if (empty($verb)) {
				continue;
			}

			DBA::update('item', ['vid' => Verb::getID($verb)], ['id' => $item['id']]);
			++$rows;
		}
		DBA::close($items);

		DI::keyValue()->set('post_update_version_1347_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1347);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the contact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1348()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1348) {
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1348_id') ?? 0;

		DI::logger()->info('Start', ['contact' => $id]);

		$start_id  = $id;
		$rows      = 0;
		$condition = ["`id` > ? AND `gsid` IS NULL AND `baseurl` != '' AND NOT `baseurl` IS NULL", $id];
		$params    = ['order' => ['id'], 'limit' => 10000];
		$contacts  = DBA::select('contact', ['id', 'baseurl'], $condition, $params);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($contact = DBA::fetch($contacts)) {
			$id = $contact['id'];

			DBA::update(
				'contact',
				['gsid' => GServer::getID($contact['baseurl'], true), 'baseurl' => GServer::cleanURL($contact['baseurl'])],
				['id'   => $contact['id']]
			);

			++$rows;
		}
		DBA::close($contacts);

		DI::keyValue()->set('post_update_version_1348_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1348);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the apcontact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1349()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1349) {
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1349_id') ?? '';

		DI::logger()->info('Start', ['apcontact' => $id]);

		$start_id   = $id;
		$rows       = 0;
		$condition  = ["`url` > ? AND `gsid` IS NULL AND `baseurl` != '' AND NOT `baseurl` IS NULL", $id];
		$params     = ['order' => ['url'], 'limit' => 10000];
		$apcontacts = DBA::select('apcontact', ['url', 'baseurl'], $condition, $params);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($apcontact = DBA::fetch($apcontacts)) {
			$id = $apcontact['url'];

			DBA::update(
				'apcontact',
				['gsid' => GServer::getID($apcontact['baseurl'], true), 'baseurl' => GServer::cleanURL($apcontact['baseurl'])],
				['url'  => $apcontact['url']]
			);

			++$rows;
		}
		DBA::close($apcontacts);

		DI::keyValue()->set('post_update_version_1349_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1349);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Remove orphaned photo entries
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1383()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1383) {
			return true;
		}

		DI::logger()->info('Start');

		$deleted = 0;
		$avatar  = [4 => 'photo', 5 => 'thumb', 6 => 'micro'];

		$photos = DBA::select('photo', ['id', 'contact-id', 'resource-id', 'scale'], ["`contact-id` != ? AND `album` = ?", 0, Photo::CONTACT_PHOTOS]);
		while ($photo = DBA::fetch($photos)) {
			$delete = !in_array($photo['scale'], [4, 5, 6]);

			if (!$delete) {
				// Check if there is a contact entry with that photo
				$delete = !DBA::exists('contact', ["`id` = ? AND `" . $avatar[$photo['scale']] . "` LIKE ?",
					$photo['contact-id'], '%' . $photo['resource-id'] . '%']);
			}

			if ($delete) {
				Photo::delete(['id' => $photo['id']]);
				$deleted++;
			}
		}
		DBA::close($photos);

		DI::keyValue()->set('post_update_version', 1383);
		DI::logger()->info('Done', ['deleted' => $deleted]);
		return true;
	}

	/**
	 * update the "hash" field in the photo table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1384()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1384) {
			return true;
		}

		$condition = ["`hash` IS NULL"];
		DI::logger()->info('Start', ['rest' => DBA::count('photo', $condition)]);

		$rows   = 0;
		$photos = DBA::select('photo', [], $condition, ['limit' => 100]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($photo = DBA::fetch($photos)) {
			$img = Photo::getImageForPhoto($photo);
			$md5 = md5($img->asString());

			DBA::update('photo', ['hash' => $md5], ['id' => $photo['id']]);
			++$rows;
		}
		DBA::close($photos);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1384);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "external-id" field in the post table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1400()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1400) {
			return true;
		}

		if (!DBStructure::existsTable('item')) {
			DI::keyValue()->set('post_update_version', 1400);
			return true;
		}

		$condition = ["`extid` != ? AND EXISTS(SELECT `id` FROM `post-user` WHERE `uri-id` = `item`.`uri-id` AND `uid` = `item`.`uid` AND `external-id` IS NULL)", ''];
		DI::logger()->info('Start', ['rest' => DBA::count('item', $condition)]);

		$rows  = 0;
		$items = DBA::select('item', ['uri-id', 'uid', 'extid'], $condition, ['order' => ['id'], 'limit' => 10000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($item = DBA::fetch($items)) {
			Post::update(['external-id' => ItemURI::getIdByURI($item['extid'])], ['uri-id' => $item['uri-id'], 'uid' => $item['uid']]);
			++$rows;
		}
		DBA::close($items);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1400);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "uri-id" field in the contact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1424()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1424) {
			return true;
		}

		$condition = ["`uri-id` IS NULL"];
		DI::logger()->info('Start', ['rest' => DBA::count('contact', $condition)]);

		$rows     = 0;
		$contacts = DBA::select('contact', ['id', 'url'], $condition, ['limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($contact = DBA::fetch($contacts)) {
			DBA::update('contact', ['uri-id' => ItemURI::getIdByURI($contact['url'])], ['id' => $contact['id']]);
			++$rows;
		}
		DBA::close($contacts);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1424);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "uri-id" field in the fcontact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1425()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1425) {
			return true;
		}

		if (!DBStructure::existsTable('fcontact')) {
			DI::keyValue()->set('post_update_version', 1425);
			return true;
		}

		$condition = ["`uri-id` IS NULL"];
		DI::logger()->info('Start', ['rest' => DBA::count('fcontact', $condition)]);

		$rows      = 0;
		$fcontacts = DBA::select('fcontact', ['id', 'url', 'guid'], $condition, ['limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($fcontact = DBA::fetch($fcontacts)) {
			if (!empty($fcontact['guid'])) {
				$uriid = ItemURI::insert(['uri' => $fcontact['url'], 'guid' => $fcontact['guid']]);
			} else {
				$uriid = ItemURI::getIdByURI($fcontact['url']);
			}
			DBA::update('fcontact', ['uri-id' => $uriid], ['id' => $fcontact['id']]);
			++$rows;
		}
		DBA::close($fcontacts);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1425);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "uri-id" field in the apcontact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1426()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1426) {
			return true;
		}

		$condition = ["`uri-id` IS NULL"];
		DI::logger()->info('Start', ['rest' => DBA::count('apcontact', $condition)]);

		$rows       = 0;
		$apcontacts = DBA::select('apcontact', ['url', 'uuid'], $condition, ['limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($apcontact = DBA::fetch($apcontacts)) {
			if (!empty($apcontact['uuid'])) {
				$uriid = ItemURI::insert(['uri' => $apcontact['url'], 'guid' => $apcontact['uuid']]);
			} else {
				$uriid = ItemURI::getIdByURI($apcontact['url']);
			}
			DBA::update('apcontact', ['uri-id' => $uriid], ['url' => $apcontact['url']]);
			++$rows;
		}
		DBA::close($apcontacts);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1426);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "uri-id" field in the event table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1427()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1427) {
			return true;
		}

		$condition = ["`uri-id` IS NULL"];
		DI::logger()->info('Start', ['rest' => DBA::count('event', $condition)]);

		$rows   = 0;
		$events = DBA::select('event', ['id', 'uri', 'guid'], $condition, ['limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($event = DBA::fetch($events)) {
			if (!empty($event['guid'])) {
				$uriid = ItemURI::insert(['uri' => $event['uri'], 'guid' => $event['guid']]);
			} else {
				$uriid = ItemURI::getIdByURI($event['uri']);
			}
			DBA::update('event', ['uri-id' => $uriid], ['id' => $event['id']]);
			++$rows;
		}
		DBA::close($events);

		DI::logger()->info('Processed', ['rows' => $rows]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1427);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Fill the receivers of the post via the raw source
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1452()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1452) {
			return true;
		}

		if (!DBStructure::existsTable('conversation')) {
			DI::keyValue()->set('post_update_version', 1452);
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1452_id') ?? 0;

		DI::logger()->info('Start', ['uri-id' => $id]);

		$rows     = 0;
		$received = '';

		$conversations = DBA::p(
			"SELECT `post-view`.`uri-id`, `conversation`.`source`, `conversation`.`received` FROM `conversation`
			INNER JOIN `post-view` ON `post-view`.`uri` = `conversation`.`item-uri`
			WHERE NOT `source` IS NULL AND `conversation`.`protocol` = ? AND `uri-id` > ? LIMIT ?",
			Conversation::PARCEL_ACTIVITYPUB,
			$id,
			1000
		);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($conversation = DBA::fetch($conversations)) {
			$id       = $conversation['uri-id'];
			$received = $conversation['received'];

			$raw = json_decode($conversation['source'], true);
			if (empty($raw)) {
				continue;
			}
			$activity = JsonLD::compact($raw);

			$urls = Receiver::getReceiverURL($activity);
			Processor::storeReceivers($conversation['uri-id'], $urls);

			if (!empty($activity['as:object'])) {
				$urls = array_merge($urls, Receiver::getReceiverURL($activity['as:object']));
				Processor::storeReceivers($conversation['uri-id'], $urls);
			}
			++$rows;
		}

		DBA::close($conversations);

		DI::keyValue()->set('post_update_version_1452_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id, 'last-received' => $received]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1452);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Correct the parent.
	 * This fixes a bug that was introduced in the development of version 2022.09
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1483()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1483) {
			return true;
		}

		DI::logger()->info('Start');

		$posts = DBA::select('post-view', ['uri-id'], ['conversation' => './']);
		while ($post = DBA::fetch($posts)) {
			$parent = Item::getParent($post['uri-id']);
			if ($parent != 0) {
				DBA::update('post', ['parent-uri-id' => $parent], ['uri-id' => $post['uri-id']]);
				DBA::update('post-user', ['parent-uri-id' => $parent], ['uri-id' => $post['uri-id']]);
			}
		}
		DBA::close($posts);

		DI::keyValue()->set('post_update_version', 1483);
		DI::logger()->info('Done');
		return true;
	}

	/**
	 * Handle duplicate contact entries
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1484()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1484) {
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1484_id') ?? 0;

		DI::logger()->info('Start', ['id' => $id]);

		$rows = 0;

		$contacts = DBA::select('contact', ['id', 'uid', 'uri-id', 'url'], ["`id` > ?", $id], ['order' => ['id'], 'limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($contact = DBA::fetch($contacts)) {
			$id = $contact['id'];
			if (is_null($contact['uri-id'])) {
				$contact['uri-id'] = ItemURI::getIdByURI($contact['url']);
				DBA::update('contact', ['uri-id' => $contact['uri-id']], ['id' => $contact['id']]);
			}
			Contact::setAccountUser($contact['id'], $contact['uid'], $contact['uri-id'], $contact['url']);
			++$rows;
		}
		DBA::close($contacts);

		DI::keyValue()->set('post_update_version_1484_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1484);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the contact table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1506()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1506) {
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1506_id') ?? 0;

		DI::logger()->info('Start', ['contact' => $id]);

		$start_id  = $id;
		$rows      = 0;
		$condition = ["`id` > ? AND `gsid` IS NULL AND `network` = ?", $id, Protocol::DIASPORA];
		$params    = ['order' => ['id'], 'limit' => 10000];
		$contacts  = DBA::select('contact', ['id', 'url'], $condition, $params);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($contact = DBA::fetch($contacts)) {
			$id = $contact['id'];

			$parts = parse_url($contact['url']);
			unset($parts['path']);
			$server = (string)Uri::fromParts($parts);

			DBA::update(
				'contact',
				['gsid' => GServer::getID($server, true), 'baseurl' => GServer::cleanURL($server)],
				['id'   => $contact['id']]
			);

			++$rows;
		}
		DBA::close($contacts);

		DI::keyValue()->set('post_update_version_1506_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1506);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * update the "gsid" (global server id) field in the inbox-status table
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1507()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1507) {
			return true;
		}

		$id = DI::keyValue()->get('post_update_version_1507_id') ?? '';

		DI::logger()->info('Start', ['apcontact' => $id]);

		$start_id   = $id;
		$rows       = 0;
		$condition  = ["`url` > ? AND NOT `gsid` IS NULL", $id];
		$params     = ['order' => ['url'], 'limit' => 10000];
		$apcontacts = DBA::select('apcontact', ['url', 'gsid', 'sharedinbox', 'inbox'], $condition, $params);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($apcontact = DBA::fetch($apcontacts)) {
			$id = $apcontact['url'];

			$inbox = [$apcontact['inbox']];
			if (!empty($apcontact['sharedinbox'])) {
				$inbox[] = $apcontact['sharedinbox'];
			}
			$condition = DBA::mergeConditions(['url' => $inbox], ["`gsid` IS NULL"]);
			DBA::update('inbox-status', ['gsid' => $apcontact['gsid'], 'archive' => GServer::isDefunctById($apcontact['gsid'])], $condition);
			++$rows;
		}
		DBA::close($apcontacts);

		DI::keyValue()->set('post_update_version_1507_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($start_id == $id) {
			DI::keyValue()->set('post_update_version', 1507);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Create "post-counts" entries for old entries.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1544()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1544) {
			return true;
		}

		$id = (int)(DI::keyValue()->get('post_update_version_1544_id') ?? 0);
		if ($id == 0) {
			$post = Post::selectFirstPost(['uri-id'], [], ['order' => ['uri-id' => true]]);
			$id   = (int)($post['uri-id'] ?? 0);
		}

		DI::logger()->info('Start', ['uri-id' => $id]);

		$rows = 0;

		$posts = Post::selectPosts(['uri-id', 'parent-uri-id'], ["`uri-id` < ? AND `gravity` IN (?, ?)", $id, Item::GRAVITY_COMMENT, Item::GRAVITY_PARENT], ['order' => ['uri-id' => true], 'limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($post = Post::fetch($posts)) {
			$id = $post['uri-id'];
			Counts::updateForPost($post['uri-id'], $post['parent-uri-id']);
			++$rows;
		}
		DBA::close($posts);

		DI::keyValue()->set('post_update_version_1544_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1544);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}

	/**
	 * Create "post-searchindex" entries for old entries.
	 *
	 * @return bool "true" when the job is done
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function update1550()
	{
		// Was the script completed?
		if (DI::keyValue()->get('post_update_version') >= 1550) {
			return true;
		}

		$engagements = DBA::select('post-engagement', ['uri-id'], ["`language` IS NULL"], ['order' => ['uri-id' => true], 'limit' => 1000]);
		while ($engagement = DBA::fetch($engagements)) {
			$item = Post::selectFirst([], ['uri-id' => $engagement['uri-id']]);
			if (empty($item)) {
				continue;
			}
			try {
				Post\Engagement::storeFromItem($item);
			} catch (\Throwable $th) {
				DI::logger()->notice('Exception on storing engagement', ['uri-id' => $engagement['uri-id'], 'code' => $th->getCode(), 'message' => $th->getMessage()]);
			}
		}
		DBA::close($engagements);

		$id = (int)(DI::keyValue()->get('post_update_version_1550_id') ?? 0);
		if ($id == 0) {
			$post = Post::selectFirstPost(['uri-id'], [], ['order' => ['uri-id' => true]]);
			$id   = (int)($post['uri-id'] ?? 0);
		}

		DI::logger()->info('Start', ['uri-id' => $id]);

		$rows = 0;

		$condition = ["`uri-id` < ? AND `gravity` IN (?, ?)", $id, Item::GRAVITY_COMMENT, Item::GRAVITY_PARENT];

		$limit = Post\SearchIndex::searchAgeDateLimit();
		if (!empty($limit)) {
			DBA::mergeConditions($condition, ["`created` > ?", $limit]);
		}

		$posts = Post::selectPosts(['uri-id', 'created'], $condition, ['order' => ['uri-id' => true], 'limit' => 1000]);

		if (DBA::errorNo() != 0) {
			DI::logger()->error('Database error', ['no' => DBA::errorNo(), 'message' => DBA::errorMessage()]);
			return false;
		}

		while ($post = Post::fetch($posts)) {
			$id = $post['uri-id'];
			Post\SearchIndex::insert($post['uri-id'], $post['created'], true);
			++$rows;
		}
		DBA::close($posts);

		DI::keyValue()->set('post_update_version_1550_id', $id);

		DI::logger()->info('Processed', ['rows' => $rows, 'last' => $id]);

		if ($rows <= 100) {
			DI::keyValue()->set('post_update_version', 1550);
			DI::logger()->info('Done');
			return true;
		}

		return false;
	}
}
