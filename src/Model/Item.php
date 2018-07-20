<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Lock;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\dba;
use Friendica\Database\DBM;
use Friendica\Object\Image;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\XML;
use Text_LanguageDetect;

require_once 'boot.php';
require_once 'include/items.php';
require_once 'include/text.php';

class Item extends BaseObject
{
	// Posting types, inspired by https://www.w3.org/TR/activitystreams-vocabulary/#object-types
	const PT_ARTICLE = 0;
	const PT_NOTE = 1;
	const PT_PAGE = 2;
	const PT_IMAGE = 16;
	const PT_AUDIO = 17;
	const PT_VIDEO = 18;
	const PT_DOCUMENT = 19;
	const PT_EVENT = 32;
	const PT_PERSONAL_NOTE = 128;

	// Field list that is used to display the items
	const DISPLAY_FIELDLIST = ['uid', 'id', 'parent', 'uri', 'thr-parent', 'parent-uri', 'guid', 'network',
			'commented', 'created', 'edited', 'received', 'verb', 'object-type', 'postopts', 'plink',
			'wall', 'private', 'starred', 'origin', 'title', 'body', 'file', 'attach', 'language',
			'content-warning', 'location', 'coord', 'app', 'rendered-hash', 'rendered-html', 'object',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'item_id',
			'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
			'owner-id', 'owner-link', 'owner-name', 'owner-avatar', 'owner-network',
			'contact-id', 'contact-link', 'contact-name', 'contact-avatar',
			'writable', 'self', 'cid', 'alias',
			'event-id', 'event-created', 'event-edited', 'event-start', 'event-finish',
			'event-summary', 'event-desc', 'event-location', 'event-type',
			'event-nofinish', 'event-adjust', 'event-ignore', 'event-id'];

	// Field list that is used to deliver items via the protocols
	const DELIVER_FIELDLIST = ['uid', 'id', 'parent', 'uri', 'thr-parent', 'parent-uri', 'guid',
			'created', 'edited', 'verb', 'object-type', 'object', 'target',
			'private', 'title', 'body', 'location', 'coord', 'app',
			'attach', 'tag', 'deleted', 'extid', 'post-type',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'author-id', 'author-link', 'owner-link', 'contact-uid',
			'signed_text', 'signature', 'signer', 'network'];

	// Field list for "item-content" table that is mixed with the item table
	const MIXED_CONTENT_FIELDLIST = ['title', 'content-warning', 'body', 'location',
			'coord', 'app', 'rendered-hash', 'rendered-html', 'verb',
			'object-type', 'object', 'target-type', 'target', 'plink'];

	// Field list for "item-content" table that is not present in the "item" table
	const CONTENT_FIELDLIST = ['language'];

	// Field list for additional delivery data
	const DELIVERY_DATA_FIELDLIST = ['postopts', 'inform'];

	// All fields in the item table
	const ITEM_FIELDLIST = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent', 'guid',
			'contact-id', 'type', 'wall', 'gravity', 'extid', 'icid', 'iaid', 'psid',
			'created', 'edited', 'commented', 'received', 'changed', 'verb',
			'postopts', 'plink', 'resource-id', 'event-id', 'tag', 'attach', 'inform',
			'file', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'post-type',
			'private', 'pubmail', 'moderated', 'visible', 'starred', 'bookmark',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global', 'network',
			'title', 'content-warning', 'body', 'location', 'coord', 'app',
			'rendered-hash', 'rendered-html', 'object-type', 'object', 'target-type', 'target',
			'author-id', 'author-link', 'author-name', 'author-avatar',
			'owner-id', 'owner-link', 'owner-name', 'owner-avatar'];

	// Never reorder or remove entries from this list. Just add new ones at the end, if needed.
	// The item-activity table only stores the index and needs this array to know the matching activity.
	const ACTIVITIES = [ACTIVITY_LIKE, ACTIVITY_DISLIKE, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE];

	private static $legacy_mode = null;

	public static function isLegacyMode()
	{
		if (is_null(self::$legacy_mode)) {
			self::$legacy_mode = (Config::get("system", "post_update_version") < 1279);
		}

		return self::$legacy_mode;
	}

	/**
	 * @brief returns an activity index from an activity string
	 *
	 * @param string $activity activity string
	 * @return integer Activity index
	 */
	private static function activityToIndex($activity)
	{
		$index = array_search($activity, self::ACTIVITIES);

		if (is_bool($index)) {
			$index = -1;
		}

		return $index;
	}

	/**
	 * @brief returns an activity string from an activity index
	 *
	 * @param integer $index activity index
	 * @return string Activity string
	 */
	private static function indexToActivity($index)
	{
		if (is_null($index) || !array_key_exists($index, self::ACTIVITIES)) {
			return '';
		}

		return self::ACTIVITIES[$index];
	}

	/**
	 * @brief Fetch a single item row
	 *
	 * @param mixed $stmt statement object
	 * @return array current row
	 */
	public static function fetch($stmt)
	{
		$row = dba::fetch($stmt);

		if (is_bool($row)) {
			return $row;
		}

		// ---------------------- Transform item structure data ----------------------

		// We prefer the data from the user's contact over the public one
		if (!empty($row['author-link']) && !empty($row['contact-link']) &&
			($row['author-link'] == $row['contact-link'])) {
			if (isset($row['author-avatar']) && !empty($row['contact-avatar'])) {
				$row['author-avatar'] = $row['contact-avatar'];
			}
			if (isset($row['author-name']) && !empty($row['contact-name'])) {
				$row['author-name'] = $row['contact-name'];
			}
		}

		if (!empty($row['owner-link']) && !empty($row['contact-link']) &&
			($row['owner-link'] == $row['contact-link'])) {
			if (isset($row['owner-avatar']) && !empty($row['contact-avatar'])) {
				$row['owner-avatar'] = $row['contact-avatar'];
			}
			if (isset($row['owner-name']) && !empty($row['contact-name'])) {
				$row['owner-name'] = $row['contact-name'];
			}
		}

		// We can always comment on posts from these networks
		if (array_key_exists('writable', $row) &&
			in_array($row['internal-network'], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS])) {
			$row['writable'] = true;
		}

		// ---------------------- Transform item content data ----------------------

		// Fetch data from the item-content table whenever there is content there
		if (self::isLegacyMode()) {
			$legacy_fields = array_merge(self::DELIVERY_DATA_FIELDLIST, self::MIXED_CONTENT_FIELDLIST);
			foreach ($legacy_fields as $field) {
				if (empty($row[$field]) && !empty($row['internal-item-' . $field])) {
					$row[$field] = $row['internal-item-' . $field];
				}
				unset($row['internal-item-' . $field]);
			}
		}

		if (!empty($row['internal-iaid']) && array_key_exists('verb', $row)) {
			$row['verb'] = self::indexToActivity($row['internal-activity']);
			if (array_key_exists('title', $row)) {
				$row['title'] = '';
			}
			if (array_key_exists('body', $row)) {
				$row['body'] = $row['verb'];
			}
			if (array_key_exists('object', $row)) {
				$row['object'] = '';
			}
			if (array_key_exists('object-type', $row)) {
				$row['object-type'] = ACTIVITY_OBJ_NOTE;
			}
		} elseif (array_key_exists('verb', $row) && in_array($row['verb'], ['', ACTIVITY_POST, ACTIVITY_SHARE])) {
			// Posts don't have an object or target - but having tags or files.
			// We safe some performance by building tag and file strings only here.
			// We remove object and target since they aren't used for this type.
			if (array_key_exists('object', $row)) {
				$row['object'] = '';
			}
			if (array_key_exists('target', $row)) {
				$row['target'] = '';
			}
		}

		if (!array_key_exists('verb', $row) || in_array($row['verb'], ['', ACTIVITY_POST, ACTIVITY_SHARE])) {
			// Build the tag string out of the term entries
			if (array_key_exists('tag', $row) && empty($row['tag'])) {
				$row['tag'] = Term::tagTextFromItemId($row['internal-iid']);
			}

			// Build the file string out of the term entries
			if (array_key_exists('file', $row) && empty($row['file'])) {
				$row['file'] = Term::fileTextFromItemId($row['internal-iid']);
			}
		}

		// Remove internal fields
		unset($row['internal-activity']);
		unset($row['internal-network']);
		unset($row['internal-iid']);
		unset($row['internal-iaid']);
		unset($row['internal-icid']);

		return $row;
	}

	/**
	 * @brief Fills an array with data from an item query
	 *
	 * @param object $stmt statement object
	 * @return array Data array
	 */
	public static function inArray($stmt, $do_close = true) {
		if (is_bool($stmt)) {
			return $stmt;
		}

		$data = [];
		while ($row = self::fetch($stmt)) {
			$data[] = $row;
		}
		if ($do_close) {
			dba::close($stmt);
		}
		return $data;
	}

	/**
	 * @brief Check if item data exists
	 *
	 * @param array $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 */
	public static function exists($condition) {
		$stmt = self::select(['id'], $condition, ['limit' => 1]);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = (dba::num_rows($stmt) > 0);
		}

		dba::close($stmt);

		return $retval;
	}

	/**
	 * Retrieve a single record from the item table for a given user and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 * @param integer $uid User ID
	 * @param array  $fields
	 * @param array  $condition
	 * @param array  $params
	 * @return bool|array
	 * @see dba::select
	 */
	public static function selectFirstForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectFirst($selected, $condition, $params);
	}

	/**
	 * @brief Select rows from the item table for a given user
	 *
	 * @param integer $uid User ID
	 * @param array  $selected  Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return boolean|object
	 */
	public static function selectForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::select($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the item table and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 * @param array  $fields
	 * @param array  $condition
	 * @param array  $params
	 * @return bool|array
	 * @see dba::select
	 */
	public static function selectFirst(array $fields = [], array $condition = [], $params = [])
	{
		$params['limit'] = 1;

		$result = self::select($fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			dba::close($result);
			return $row;
		}
	}

	/**
	 * @brief Select rows from the item table
	 *
	 * @param array  $selected  Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return boolean|object
	 */
	public static function select(array $selected = [], array $condition = [], $params = [])
	{
		$uid = 0;
		$usermode = false;

		if (isset($params['uid'])) {
			$uid = $params['uid'];
			$usermode = true;
		}

		$fields = self::fieldlist($selected);

		$select_fields = self::constructSelectFields($fields, $selected);

		$condition_string = dba::buildCondition($condition);

		$condition_string = self::addTablesToFields($condition_string, $fields);

		if ($usermode) {
			$condition_string = $condition_string . ' AND ' . self::condition(false);
		}

		$param_string = self::addTablesToFields(dba::buildParameter($params), $fields);

		$table = "`item` " . self::constructJoins($uid, $select_fields . $condition_string . $param_string, false, $usermode);

		$sql = "SELECT " . $select_fields . " FROM " . $table . $condition_string . $param_string;

		return dba::p($sql, $condition);
	}

	/**
	 * @brief Select rows from the starting post in the item table
	 *
	 * @param integer $uid User ID
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return boolean|object
	 */
	public static function selectThreadForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectThread($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the starting post in the item table and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 * @param integer $uid User ID
	 * @param array  $selected
	 * @param array  $condition
	 * @param array  $params
	 * @return bool|array
	 * @see dba::select
	 */
	public static function selectFirstThreadForUser($uid, array $selected = [], array $condition = [], $params = [])
	{
		$params['uid'] = $uid;

		if (empty($selected)) {
			$selected = Item::DISPLAY_FIELDLIST;
		}

		return self::selectFirstThread($selected, $condition, $params);
	}

	/**
	 * Retrieve a single record from the starting post in the item table and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 * @param array  $fields
	 * @param array  $condition
	 * @param array  $params
	 * @return bool|array
	 * @see dba::select
	 */
	public static function selectFirstThread(array $fields = [], array $condition = [], $params = [])
	{
		$params['limit'] = 1;
		$result = self::selectThread($fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			dba::close($result);
			return $row;
		}
	}

	/**
	 * @brief Select rows from the starting post in the item table
	 *
	 * @param array  $selected  Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return boolean|object
	 */
	public static function selectThread(array $selected = [], array $condition = [], $params = [])
	{
		$uid = 0;
		$usermode = false;

		if (isset($params['uid'])) {
			$uid = $params['uid'];
			$usermode = true;
		}

		$fields = self::fieldlist($selected);

		$threadfields = ['thread' => ['iid', 'uid', 'contact-id', 'owner-id', 'author-id',
			'created', 'edited', 'commented', 'received', 'changed', 'wall', 'private',
			'pubmail', 'moderated', 'visible', 'starred', 'ignored', 'post-type',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'network']];

		$select_fields = self::constructSelectFields($fields, $selected);

		$condition_string = dba::buildCondition($condition);

		$condition_string = self::addTablesToFields($condition_string, $threadfields);
		$condition_string = self::addTablesToFields($condition_string, $fields);

		if ($usermode) {
			$condition_string = $condition_string . ' AND ' . self::condition(true);
		}

		$param_string = dba::buildParameter($params);
		$param_string = self::addTablesToFields($param_string, $threadfields);
		$param_string = self::addTablesToFields($param_string, $fields);

		$table = "`thread` " . self::constructJoins($uid, $select_fields . $condition_string . $param_string, true, $usermode);

		$sql = "SELECT " . $select_fields . " FROM " . $table . $condition_string . $param_string;

		return dba::p($sql, $condition);
	}

	/**
	 * @brief Returns a list of fields that are associated with the item table
	 *
	 * @return array field list
	 */
	private static function fieldlist($selected)
	{
		$fields = [];

		$fields['item'] = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent', 'guid',
			'contact-id', 'owner-id', 'author-id', 'type', 'wall', 'gravity', 'extid',
			'created', 'edited', 'commented', 'received', 'changed',
			'resource-id', 'event-id', 'tag', 'attach', 'post-type',
			'file', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'psid',
			'private', 'pubmail', 'moderated', 'visible', 'starred', 'bookmark',
			'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global',
			'id' => 'item_id', 'network', 'icid', 'iaid', 'id' => 'internal-iid',
			'network' => 'internal-network', 'icid' => 'internal-icid',
			'iaid' => 'internal-iaid'];

		$fields['item-activity'] = ['activity', 'activity' => 'internal-activity'];

		$fields['item-content'] = array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST);

		$fields['item-delivery-data'] = self::DELIVERY_DATA_FIELDLIST;

		$fields['author'] = ['url' => 'author-link', 'name' => 'author-name',
			'thumb' => 'author-avatar', 'nick' => 'author-nick', 'network' => 'author-network'];

		$fields['owner'] = ['url' => 'owner-link', 'name' => 'owner-name',
			'thumb' => 'owner-avatar', 'nick' => 'owner-nick', 'network' => 'owner-network'];

		$fields['contact'] = ['url' => 'contact-link', 'name' => 'contact-name', 'thumb' => 'contact-avatar',
			'writable', 'self', 'id' => 'cid', 'alias', 'uid' => 'contact-uid',
			'photo', 'name-date', 'uri-date', 'avatar-date', 'thumb', 'dfrn-id'];

		$fields['parent-item'] = ['guid' => 'parent-guid', 'network' => 'parent-network'];

		$fields['parent-item-author'] = ['url' => 'parent-author-link', 'name' => 'parent-author-name'];

		$fields['event'] = ['created' => 'event-created', 'edited' => 'event-edited',
			'start' => 'event-start','finish' => 'event-finish',
			'summary' => 'event-summary','desc' => 'event-desc',
			'location' => 'event-location', 'type' => 'event-type',
			'nofinish' => 'event-nofinish','adjust' => 'event-adjust',
			'ignore' => 'event-ignore', 'id' => 'event-id'];

		$fields['sign'] = ['signed_text', 'signature', 'signer'];

		return $fields;
	}

	/**
	 * @brief Returns SQL condition for the "select" functions
	 *
	 * @param boolean $thread_mode Called for the items (false) or for the threads (true)
	 *
	 * @return string SQL condition
	 */
	private static function condition($thread_mode)
	{
		if ($thread_mode) {
			$master_table = "`thread`";
		} else {
			$master_table = "`item`";
		}
		return "$master_table.`visible` AND NOT $master_table.`deleted` AND NOT $master_table.`moderated` AND (`user-item`.`hidden` IS NULL OR NOT `user-item`.`hidden`) ";
	}

	/**
	 * @brief Returns all needed "JOIN" commands for the "select" functions
	 *
	 * @param integer $uid User ID
	 * @param string $sql_commands The parts of the built SQL commands in the "select" functions
	 * @param boolean $thread_mode Called for the items (false) or for the threads (true)
	 *
	 * @return string The SQL joins for the "select" functions
	 */
	private static function constructJoins($uid, $sql_commands, $thread_mode, $user_mode)
	{
		if ($thread_mode) {
			$master_table = "`thread`";
			$master_table_key = "`thread`.`iid`";
			$joins = "STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid` ";
		} else {
			$master_table = "`item`";
			$master_table_key = "`item`.`id`";
			$joins = '';
		}

		if ($user_mode) {
			$joins .= sprintf("STRAIGHT_JOIN `contact` ON `contact`.`id` = $master_table.`contact-id`
				AND NOT `contact`.`blocked`
				AND ((NOT `contact`.`readonly` AND NOT `contact`.`pending` AND (`contact`.`rel` IN (%s, %s)))
				OR `contact`.`self` OR `item`.`gravity` != %d OR `contact`.`uid` = 0)
				STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = $master_table.`author-id` AND NOT `author`.`blocked`
				STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = $master_table.`owner-id` AND NOT `owner`.`blocked`
				LEFT JOIN `user-item` ON `user-item`.`iid` = $master_table_key AND `user-item`.`uid` = %d",
				CONTACT_IS_SHARING, CONTACT_IS_FRIEND, GRAVITY_PARENT, intval($uid));
		} else {
			if (strpos($sql_commands, "`contact`.") !== false) {
				$joins .= "LEFT JOIN `contact` ON `contact`.`id` = $master_table.`contact-id`";
			}
			if (strpos($sql_commands, "`author`.") !== false) {
				$joins .= " LEFT JOIN `contact` AS `author` ON `author`.`id` = $master_table.`author-id`";
			}
			if (strpos($sql_commands, "`owner`.") !== false) {
				$joins .= " LEFT JOIN `contact` AS `owner` ON `owner`.`id` = $master_table.`owner-id`";
			}
		}

		if (strpos($sql_commands, "`group_member`.") !== false) {
			$joins .= " STRAIGHT_JOIN `group_member` ON `group_member`.`contact-id` = $master_table.`contact-id`";
		}

		if (strpos($sql_commands, "`user`.") !== false) {
			$joins .= " STRAIGHT_JOIN `user` ON `user`.`uid` = $master_table.`uid`";
		}

		if (strpos($sql_commands, "`event`.") !== false) {
			$joins .= " LEFT JOIN `event` ON `event-id` = `event`.`id`";
		}

		if (strpos($sql_commands, "`sign`.") !== false) {
			$joins .= " LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id`";
		}

		if (strpos($sql_commands, "`item-activity`.") !== false) {
			$joins .= " LEFT JOIN `item-activity` ON `item-activity`.`id` = `item`.`iaid`";
		}

		if (strpos($sql_commands, "`item-content`.") !== false) {
			$joins .= " LEFT JOIN `item-content` ON `item-content`.`id` = `item`.`icid`";
		}

		if (strpos($sql_commands, "`item-delivery-data`.") !== false) {
			$joins .= " LEFT JOIN `item-delivery-data` ON `item-delivery-data`.`iid` = `item`.`id`";
		}

		if ((strpos($sql_commands, "`parent-item`.") !== false) || (strpos($sql_commands, "`parent-author`.") !== false)) {
			$joins .= " STRAIGHT_JOIN `item` AS `parent-item` ON `parent-item`.`id` = `item`.`parent`";
		}

		if (strpos($sql_commands, "`parent-item-author`.") !== false) {
			$joins .= " STRAIGHT_JOIN `contact` AS `parent-item-author` ON `parent-item-author`.`id` = `parent-item`.`author-id`";
		}

		return $joins;
	}

	/**
	 * @brief Add the field list for the "select" functions
	 *
	 * @param array $fields The field definition array
	 * @param array $selected The array with the selected fields from the "select" functions
	 *
	 * @return string The field list
	 */
	private static function constructSelectFields($fields, $selected)
	{
		if (!empty($selected)) {
			$selected[] = 'internal-iid';
			$selected[] = 'internal-iaid';
			$selected[] = 'internal-icid';
			$selected[] = 'internal-network';
		}

		if (in_array('verb', $selected)) {
			$selected[] = 'internal-activity';
		}

		$selection = [];
		foreach ($fields as $table => $table_fields) {
			foreach ($table_fields as $field => $select) {
				if (empty($selected) || in_array($select, $selected)) {
					$legacy_fields = array_merge(self::DELIVERY_DATA_FIELDLIST, self::MIXED_CONTENT_FIELDLIST);
					if (self::isLegacyMode() && in_array($select, $legacy_fields)) {
						$selection[] = "`item`.`".$select."` AS `internal-item-" . $select . "`";
					}
					if (is_int($field)) {
						$selection[] = "`" . $table . "`.`" . $select . "`";
					} else {
						$selection[] = "`" . $table . "`.`" . $field . "` AS `" . $select . "`";
					}
				}
			}
		}
		return implode(", ", $selection);
	}

	/**
	 * @brief add table definition to fields in an SQL query
	 *
	 * @param string $query SQL query
	 * @param array $fields The field definition array
	 *
	 * @return string the changed SQL query
	 */
	private static function addTablesToFields($query, $fields)
	{
		foreach ($fields as $table => $table_fields) {
			foreach ($table_fields as $alias => $field) {
				if (is_int($alias)) {
					$replace_field = $field;
				} else {
					$replace_field = $alias;
				}

				$search = "/([^\.])`" . $field . "`/i";
				$replace = "$1`" . $table . "`.`" . $replace_field . "`";
				$query = preg_replace($search, $replace, $query);
			}
		}
		return $query;
	}

	/**
	 * @brief Generate a server unique item hash for linking between the item tables
	 *
	 * @param string $uri     Item URI
	 * @param date   $created Item creation date
	 *
	 * @return string the item hash
	 */
	private static function itemHash($uri, $created)
	{
		return round(strtotime($created) / 100) . hash('ripemd128', $uri);
	}

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

		// To ensure the data integrity we do it in an transaction
		dba::transaction();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		// And we have to execute this query before the update to ensure to fetch the same data.
		$items = dba::select('item', ['id', 'origin', 'uri', 'created', 'uri-hash', 'iaid', 'icid', 'tag', 'file'], $condition);

		$content_fields = [];
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($fields[$field])) {
				$content_fields[$field] = $fields[$field];
				if (in_array($field, self::CONTENT_FIELDLIST) || !self::isLegacyMode()) {
					unset($fields[$field]);
				} else {
					$fields[$field] = null;
				}
			}
		}

		$clear_fields = ['bookmark', 'type', 'author-name', 'author-avatar', 'author-link', 'owner-name', 'owner-avatar', 'owner-link'];
		foreach ($clear_fields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field] = null;
			}
		}

		if (array_key_exists('tag', $fields)) {
			$tags = $fields['tag'];
			$fields['tag'] = null;
		} else {
			$tags = '';
		}

		if (array_key_exists('file', $fields)) {
			$files = $fields['file'];
			$fields['file'] = null;
		} else {
			$files = '';
		}

		$delivery_data = ['postopts' => defaults($fields, 'postopts', ''),
			'inform' => defaults($fields, 'inform', '')];

		$fields['postopts'] = null;
		$fields['inform'] = null;

		if (!empty($fields)) {
			$success = dba::update('item', $fields, $condition);

			if (!$success) {
				dba::close($items);
				dba::rollback();
				return false;
			}
		}

		// When there is no content for the "old" item table, this will count the fetched items
		$rows = dba::affected_rows();

		while ($item = dba::fetch($items)) {

			// This part here can safely be removed when the legacy fields in the item had been removed
			if (empty($item['uri-hash']) && !empty($item['uri']) && !empty($item['created'])) {

				// Fetch the uri-hash from an existing item entry if there is one
				$item_condition = ["`uri` = ? AND `uri-hash` != ''", $item['uri']];
				$existing = dba::selectfirst('item', ['uri-hash'], $item_condition);
				if (DBM::is_result($existing)) {
					$item['uri-hash'] = $existing['uri-hash'];
				} else {
					$item['uri-hash'] = self::itemHash($item['uri'], $item['created']);
				}

				dba::update('item', ['uri-hash' => $item['uri-hash']], ['id' => $item['id']]);
				dba::update('item-activity', ['uri-hash' => $item['uri-hash']], ["`uri` = ? AND `uri-hash` = ''", $item['uri']]);
				dba::update('item-content', ['uri-plink-hash' => $item['uri-hash']], ["`uri` = ? AND `uri-plink-hash` = ''", $item['uri']]);
			}

			if (!empty($item['iaid']) || (!empty($content_fields['verb']) && (self::activityToIndex($content_fields['verb']) >= 0))) {
				if (!empty($item['iaid'])) {
					$update_condition = ['id' => $item['iaid']];
				} else {
					$update_condition = ['uri-hash' => $item['uri-hash']];
				}
				self::updateActivity($content_fields, $update_condition);

				if (empty($item['iaid'])) {
					$item_activity = dba::selectFirst('item-activity', ['id'], ['uri-hash' => $item['uri-hash']]);
					if (DBM::is_result($item_activity)) {
						$item_fields = ['iaid' => $item_activity['id'], 'icid' => null];
						foreach (self::MIXED_CONTENT_FIELDLIST as $field) {
							if (self::isLegacyMode()) {
								$item_fields[$field] = null;
							} else {
								unset($item_fields[$field]);
							}
						}
						dba::update('item', $item_fields, ['id' => $item['id']]);

						if (!empty($item['icid']) && !dba::exists('item', ['icid' => $item['icid']])) {
							dba::delete('item-content', ['id' => $item['icid']]);
						}
					}
				} elseif (!empty($item['icid'])) {
					dba::update('item', ['icid' => null], ['id' => $item['id']]);

					if (!dba::exists('item', ['icid' => $item['icid']])) {
						dba::delete('item-content', ['id' => $item['icid']]);
					}
				}
			} else {
				if (!empty($item['icid'])) {
					$update_condition = ['id' => $item['icid']];
				} else {
					$update_condition = ['uri-plink-hash' => $item['uri-hash']];
				}
				self::updateContent($content_fields, $update_condition);

				if (empty($item['icid'])) {
					$item_content = dba::selectFirst('item-content', [], ['uri-plink-hash' => $item['uri-hash']]);
					if (DBM::is_result($item_content)) {
						$item_fields = ['icid' => $item_content['id']];
						// Clear all fields in the item table that have a content in the item-content table
						foreach ($item_content as $field => $content) {
							if (in_array($field, self::MIXED_CONTENT_FIELDLIST) && !empty($item_content[$field])) {
								if (self::isLegacyMode()) {
									$item_fields[$field] = null;
								} else {
									unset($item_fields[$field]);
								}
							}
						}
						dba::update('item', $item_fields, ['id' => $item['id']]);
					}
				}
			}

			if (!empty($tags)) {
				Term::insertFromTagFieldByItemId($item['id'], $tags);
				if (!empty($item['tag'])) {
					dba::update('item', ['tag' => ''], ['id' => $item['id']]);
				}
			}

			if (!empty($files)) {
				Term::insertFromFileFieldByItemId($item['id'], $files);
				if (!empty($item['file'])) {
					dba::update('item', ['file' => ''], ['id' => $item['id']]);
				}
			}

			self::updateDeliveryData($item['id'], $delivery_data);

			self::updateThread($item['id']);

			// We only need to notfiy others when it is an original entry from us.
			// Only call the notifier when the item has some content relevant change.
			if ($item['origin'] && in_array('edited', array_keys($fields))) {
				Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
			}
		}

		dba::close($items);
		dba::commit();
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
	 * @brief Delete an item for an user and notify others about it - if it was ours
	 *
	 * @param array $condition The condition for finding the item entries
	 * @param integer $uid User who wants to delete this item
	 */
	public static function deleteForUser($condition, $uid)
	{
		if ($uid == 0) {
			return;
		}

		$items = dba::select('item', ['id', 'uid'], $condition);
		while ($item = dba::fetch($items)) {
			// "Deleting" global items just means hiding them
			if ($item['uid'] == 0) {
				dba::update('user-item', ['hidden' => true], ['iid' => $item['id'], 'uid' => $uid], true);
			} elseif ($item['uid'] == $uid) {
				self::deleteById($item['id'], PRIORITY_HIGH);
			} else {
				logger('Wrong ownership. Not deleting item ' . $item['id']);
			}
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
	private static function deleteById($item_id, $priority = PRIORITY_HIGH)
	{
		// locate item to be deleted
		$fields = ['id', 'uri', 'uid', 'parent', 'parent-uri', 'origin',
			'deleted', 'file', 'resource-id', 'event-id', 'attach',
			'verb', 'object-type', 'object', 'target', 'contact-id'];
		$item = self::selectFirst($fields, ['id' => $item_id]);
		if (!DBM::is_result($item)) {
			logger('Item with ID ' . $item_id . " hasn't been found.", LOGGER_DEBUG);
			return false;
		}

		if ($item['deleted']) {
			logger('Item with ID ' . $item_id . ' has already been deleted.', LOGGER_DEBUG);
			return false;
		}

		$parent = self::selectFirst(['origin'], ['id' => $item['parent']]);
		if (!DBM::is_result($parent)) {
			$parent = ['origin' => false];
		}

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
			if (is_array($matches) && count($matches) > 1) {
				dba::delete('attach', ['id' => $matches[1], 'uid' => $item['uid']]);
			}
		}

		// Delete tags that had been attached to other items
		self::deleteTagsFromItem($item);

		// Set the item to "deleted"
		// This erasing of item content is superfluous for items with a matching item-content.
		// But for the next time we will still have old content in the item table.
		$item_fields = ['deleted' => true, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow(),
			'body' => '', 'title' => '', 'content-warning' => '', 'rendered-hash' => '', 'rendered-html' => '',
			'object' => '', 'target' => '', 'tag' => '', 'postopts' => '', 'attach' => '', 'file' => ''];
		dba::update('item', $item_fields, ['id' => $item['id']]);

		Term::insertFromTagFieldByItemId($item['id'], '');
		Term::insertFromFileFieldByItemId($item['id'], '');
		self::deleteThread($item['id'], $item['parent-uri']);

		if (!self::exists(["`uri` = ? AND `uid` != 0 AND NOT `deleted`", $item['uri']])) {
			self::delete(['uri' => $item['uri'], 'uid' => 0, 'deleted' => false], $priority);
		}

		dba::delete('item-delivery-data', ['iid' => $item['id']]);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['id'] == $item['parent']) {
			self::delete(['parent' => $item['parent'], 'deleted' => false], $priority);
		}

		// Is it our comment and/or our thread?
		if ($item['origin'] || $parent['origin']) {

			// When we delete the original post we will delete all existing copies on the server as well
			self::delete(['uri' => $item['uri'], 'deleted' => false], $priority);

			// send the notification upstream/downstream
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", "drop", intval($item['id']));
		} elseif ($item['uid'] != 0) {

			// When we delete just our local user copy of an item, we have to set a marker to hide it
			$global_item = self::selectFirst(['id'], ['uri' => $item['uri'], 'uid' => 0, 'deleted' => false]);
			if (DBM::is_result($global_item)) {
				dba::update('user-item', ['hidden' => true], ['iid' => $global_item['id'], 'uid' => $item['uid']], true);
			}
		}

		logger('Item with ID ' . $item_id . " has been deleted.", LOGGER_DEBUG);

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

		$i = self::selectFirst(['id', 'contact-id', 'tag'], ['uri' => $xt->id, 'uid' => $item['uid']]);
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
		if (!empty($item['guid'])) {
			return notags(trim($item['guid']));
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
			$guid = System::createGUID(32, hash('crc32', $prefix_host));
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

	// This function will finally cover most of the preparation functionality in mod/item.php
	public static function prepare(&$item)
	{
		$data = BBCode::getAttachmentData($item['body']);
		if ((preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $item['body'], $match, PREG_SET_ORDER) || isset($data["type"]))
			&& ($posttype != Item::PT_PERSONAL_NOTE)) {
			$posttype = Item::PT_PAGE;
			$objecttype = ACTIVITY_OBJ_BOOKMARK;
		}
	}

	public static function insert($item, $force_parent = false, $notify = false, $dontcache = false)
	{
		$a = get_app();

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$item['wall'] = 1;
			$item['origin'] = 1;
			$item['network'] = NETWORK_DFRN;
			$item['protocol'] = PROTOCOL_DFRN;

			if (is_int($notify)) {
				$priority = $notify;
			} else {
				$priority = PRIORITY_HIGH;
			}
		} else {
			$item['network'] = trim(defaults($item, 'network', NETWORK_PHANTOM));
		}

		$item['guid'] = self::guid($item, $notify);
		$item['uri'] = notags(trim(defaults($item, 'uri', self::newURI($item['uid'], $item['guid']))));

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

		if (!empty($item['diaspora_signed_text'])) {
			$diaspora_signed_text = $item['diaspora_signed_text'];
			unset($item['diaspora_signed_text']);
		} else {
			$diaspora_signed_text = '';
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

		if (isset($item['gravity'])) {
			$item['gravity'] = intval($item['gravity']);
		} elseif ($item['parent-uri'] === $item['uri']) {
			$item['gravity'] = GRAVITY_PARENT;
		} elseif (activity_match($item['verb'], ACTIVITY_POST)) {
			$item['gravity'] = GRAVITY_COMMENT;
		} else {
			$item['gravity'] = GRAVITY_UNKNOWN;   // Should not happen
			logger('Unknown gravity for verb: ' . $item['verb'], LOGGER_DEBUG);
		}

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
			$existing = self::selectFirst(['id', 'network'], $condition);
			if (DBM::is_result($existing)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($uid != 0) {
					logger("Item with uri ".$item['uri']." already existed for user ".$uid." with id ".$existing["id"]." target network ".$existing["network"]." - new network: ".$item['network']);
				}

				return $existing["id"];
			}
		}

		// Ensure to always have the same creation date.
		$existing = dba::selectfirst('item', ['created', 'uri-hash'], ['uri' => $item['uri']]);
		if (DBM::is_result($existing)) {
			$item['created'] = $existing['created'];
			$item['uri-hash'] = $existing['uri-hash'];
		}

		self::addLanguageToItemArray($item);

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
		$item['post-type']     = defaults($item, 'post-type', self::PT_ARTICLE);
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

		// Unique identifier to be linked against item-activities and item-content
		$item['uri-hash']      = defaults($item, 'uri-hash', self::itemHash($item['uri'], $item['created']));

		// When there is no content then we don't post it
		if ($item['body'].$item['title'] == '') {
			logger('No body, no title.');
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

		$item['plink'] = defaults($item, 'plink', System::baseUrl() . '/display/' . urlencode($item['guid']));

		// The contact-id should be set before "self::insert" was called - but there seems to be issues sometimes
		$item["contact-id"] = self::contactId($item);

		$default = ['url' => $item['author-link'], 'name' => $item['author-name'],
			'photo' => $item['author-avatar'], 'network' => $item['network']];

		$item['author-id'] = defaults($item, 'author-id', Contact::getIdForURL($item["author-link"], 0, false, $default));

		if (Contact::isBlocked($item["author-id"])) {
			logger('Contact '.$item["author-id"].' is blocked, item '.$item["uri"].' will not be stored');
			return 0;
		}

		$default = ['url' => $item['owner-link'], 'name' => $item['owner-name'],
			'photo' => $item['owner-avatar'], 'network' => $item['network']];

		$item['owner-id'] = defaults($item, 'owner-id', Contact::getIdForURL($item["owner-link"], 0, false, $default));

		if (Contact::isBlocked($item["owner-id"])) {
			logger('Contact '.$item["owner-id"].' is blocked, item '.$item["uri"].' will not be stored');
			return 0;
		}

		// These fields aren't stored anymore in the item table, they are fetched upon request
		unset($item['author-link']);
		unset($item['author-name']);
		unset($item['author-avatar']);

		unset($item['owner-link']);
		unset($item['owner-name']);
		unset($item['owner-avatar']);

		if ($item['network'] == NETWORK_PHANTOM) {
			logger('Missing network. Called by: '.System::callstack(), LOGGER_DEBUG);

			$item['network'] = NETWORK_DFRN;
			logger("Set network to " . $item["network"] . " for " . $item["uri"], LOGGER_DEBUG);
		}

		// Checking if there is already an item with the same guid
		logger('Checking for an item for user '.$item['uid'].' on network '.$item['network'].' with the guid '.$item['guid'], LOGGER_DEBUG);
		$condition = ['guid' => $item['guid'], 'network' => $item['network'], 'uid' => $item['uid']];
		if (self::exists($condition)) {
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
				'wall', 'private', 'forum_mode', 'origin'];
			$condition = ['uri' => $item['parent-uri'], 'uid' => $item['uid']];
			$params = ['order' => ['id' => false]];
			$parent = self::selectFirst($fields, $condition, $params);

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
					$toplevel_parent = self::selectFirst($fields, $condition, $params);

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
					$self_id = Contact::getIdForURL($self, 0, true);
					logger("'myself' is ".$self_id." for parent ".$parent_id." checking against ".$item['author-id']." and ".$item['owner-id'], LOGGER_DEBUG);
					if (($item['author-id'] == $self_id) || ($item['owner-id'] == $self_id)) {
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
					$item['gravity'] = GRAVITY_PARENT;
				} else {
					logger('item parent '.$item['parent-uri'].' for '.$item['uid'].' was not found - ignoring item');
					return 0;
				}

				$parent_deleted = 0;
			}
		}

		$condition = ["`uri` = ? AND `network` IN (?, ?) AND `uid` = ?",
			$item['uri'], $item['network'], NETWORK_DFRN, $item['uid']];
		if (self::exists($condition)) {
			logger('duplicated item with the same uri found. '.print_r($item,true));
			return 0;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($item['network'], [NETWORK_DFRN, NETWORK_DIASPORA])) {
			$condition = ['guid' => $item['guid'], 'uid' => $item['uid']];
			if (self::exists($condition)) {
				logger('duplicated item with the same guid found. '.print_r($item,true));
				return 0;
			}
		} else {
			// Check for an existing post with the same content. There seems to be a problem with OStatus.
			$condition = ["`body` = ? AND `network` = ? AND `created` = ? AND `contact-id` = ? AND `uid` = ?",
					$item['body'], $item['network'], $item['created'], $item['contact-id'], $item['uid']];
			if (self::exists($condition)) {
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
			$item["global"] = self::exists(['uid' => 0, 'uri' => $item["uri"]]);
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
			$item['edit'] = false;
			$item['parent'] = $parent_id;
			Addon::callHooks('post_local', $item);
			unset($item['edit']);
			unset($item['parent']);
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
			if (self::exists(['uri' => trim($item['uri']), 'uid' => 0])) {
				logger('Global item already stored. URI: '.$item['uri'].' on network '.$item['network'], LOGGER_DEBUG);
				return 0;
			}
		}

		logger('' . print_r($item,true), LOGGER_DATA);

		if (array_key_exists('tag', $item)) {
			$tags = $item['tag'];
			unset($item['tag']);
		} else {
			$tags = '';
		}

		if (array_key_exists('file', $item)) {
			$files = $item['file'];
			unset($item['file']);
		} else {
			$files = '';
		}

		// Creates the permission set
		// Currently we only store the data but don't using it
		$item['psid'] = PermissionSet::fetchIDForPost($item);

		// We are doing this outside of the transaction to avoid timing problems
		if (!self::insertActivity($item)) {
			self::insertContent($item);
		}

		$delivery_data = ['postopts' => defaults($item, 'postopts', ''),
			'inform' => defaults($item, 'inform', '')];

		unset($item['postopts']);
		unset($item['inform']);

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

				// Ensure to have the removed data from above again in the item array
				$item = array_merge($item, $delivery_data);

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
		if (($item['gravity'] != GRAVITY_ACTIVITY) || !Config::get("system", "like_no_comment")) {
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

		if (!empty($diaspora_signed_text)) {
			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			dba::insert('sign', ['iid' => $current_post, 'signed_text' => $diaspora_signed_text]);
		}

		$deleted = self::tagDeliver($item['uid'], $current_post);

		/*
		 * current post can be deleted if is for a community page and no mention are
		 * in it.
		 */
		if (!$deleted && !$dontcache) {
			$posted_item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $current_post]);
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

		$delivery_data['iid'] = $current_post;

		self::insertDeliveryData($delivery_data);

		dba::commit();

		/*
		 * Due to deadlock issues with the "term" table we are doing these steps after the commit.
		 * This is not perfect - but a workable solution until we found the reason for the problem.
		 */
		if (!empty($tags)) {
			Term::insertFromTagFieldByItemId($current_post, $tags);
		}

		if (!empty($files)) {
			Term::insertFromFileFieldByItemId($current_post, $files);
		}

		if ($item['parent-uri'] === $item['uri']) {
			self::addShadow($current_post);
		} else {
			self::addShadowPost($current_post);
		}

		check_user_notification($current_post);

		if ($notify) {
			Worker::add(['priority' => $priority, 'dont_fork' => true], "Notifier", $notify_type, $current_post);
		} elseif (!empty($parent) && $parent['origin']) {
			Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], "Notifier", "comment-import", $current_post);
		}

		return $current_post;
	}

	/**
	 * @brief Insert a new item delivery data entry
	 *
	 * @param array $item The item fields that are to be inserted
	 */
	private static function insertDeliveryData($delivery_data)
	{
		if (empty($delivery_data['iid']) || (empty($delivery_data['postopts']) && empty($delivery_data['inform']))) {
			return;
		}

		dba::insert('item-delivery-data', $delivery_data);
	}

	/**
	 * @brief Update an existing item delivery data entry
	 *
	 * @param integer $id The item id that is to be updated
	 * @param array $item The item fields that are to be inserted
	 */
	private static function updateDeliveryData($id, $delivery_data)
	{
		if (empty($id) || (empty($delivery_data['postopts']) && empty($delivery_data['inform']))) {
			return;
		}

		dba::update('item-delivery-data', $delivery_data, ['iid' => $id], true);
	}

	/**
	 * @brief Insert a new item content entry
	 *
	 * @param array $item The item fields that are to be inserted
	 */
	private static function insertActivity(&$item)
	{
		$activity_index = self::activityToIndex($item['verb']);

		if ($activity_index < 0) {
			return false;
		}

		$fields = ['uri' => $item['uri'], 'activity' => $activity_index,
			'uri-hash' => $item['uri-hash']];

		// We just remove everything that is content
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			unset($item[$field]);
		}

		// To avoid timing problems, we are using locks.
		$locked = Lock::acquire('item_insert_activity');
		if (!$locked) {
			logger("Couldn't acquire lock for URI " . $item['uri'] . " - proceeding anyway.");
		}

		// Do we already have this content?
		$item_activity = dba::selectFirst('item-activity', ['id'], ['uri-hash' => $item['uri-hash']]);
		if (DBM::is_result($item_activity)) {
			$item['iaid'] = $item_activity['id'];
			logger('Fetched activity for URI ' . $item['uri'] . ' (' . $item['iaid'] . ')');
		} elseif (dba::insert('item-activity', $fields)) {
			$item['iaid'] = dba::lastInsertId();
			logger('Inserted activity for URI ' . $item['uri'] . ' (' . $item['iaid'] . ')');
		} else {
			// This shouldn't happen.
			logger('Could not insert activity for URI ' . $item['uri'] . ' - should not happen');
			return false;
		}
		if ($locked) {
			Lock::release('item_insert_activity');
		}
		return true;
	}

	/**
	 * @brief Insert a new item content entry
	 *
	 * @param array $item The item fields that are to be inserted
	 */
	private static function insertContent(&$item)
	{
		$fields = ['uri' => $item['uri'], 'uri-plink-hash' => $item['uri-hash']];

		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($item[$field])) {
				$fields[$field] = $item[$field];
				unset($item[$field]);
			}
		}

		// To avoid timing problems, we are using locks.
		$locked = Lock::acquire('item_insert_content');
		if (!$locked) {
			logger("Couldn't acquire lock for URI " . $item['uri'] . " - proceeding anyway.");
		}

		// Do we already have this content?
		$item_content = dba::selectFirst('item-content', ['id'], ['uri-plink-hash' => $item['uri-hash']]);
		if (DBM::is_result($item_content)) {
			$item['icid'] = $item_content['id'];
			logger('Fetched content for URI ' . $item['uri'] . ' (' . $item['icid'] . ')');
		} elseif (dba::insert('item-content', $fields)) {
			$item['icid'] = dba::lastInsertId();
			logger('Inserted content for URI ' . $item['uri'] . ' (' . $item['icid'] . ')');
		} else {
			// This shouldn't happen.
			logger('Could not insert content for URI ' . $item['uri'] . ' - should not happen');
		}
		if ($locked) {
			Lock::release('item_insert_content');
		}
	}

	/**
	 * @brief Update existing item content entries
	 *
	 * @param array $item The item fields that are to be changed
	 * @param array $condition The condition for finding the item content entries
	 */
	private static function updateActivity($item, $condition)
	{
		if (empty($item['verb'])) {
			return false;
		}
		$activity_index = self::activityToIndex($item['verb']);

		if ($activity_index < 0) {
			return false;
		}

		$fields = ['activity' => $activity_index];

		logger('Update activity for ' . json_encode($condition));

		dba::update('item-activity', $fields, $condition, true);

		return true;
	}

	/**
	 * @brief Update existing item content entries
	 *
	 * @param array $item The item fields that are to be changed
	 * @param array $condition The condition for finding the item content entries
	 */
	private static function updateContent($item, $condition)
	{
		// We have to select only the fields from the "item-content" table
		$fields = [];
		foreach (array_merge(self::CONTENT_FIELDLIST, self::MIXED_CONTENT_FIELDLIST) as $field) {
			if (isset($item[$field])) {
				$fields[$field] = $item[$field];
			}
		}

		if (empty($fields)) {
			// when there are no fields at all, just use the condition
			// This is to ensure that we always store content.
			$fields = $condition;
		}

		logger('Update content for ' . json_encode($condition));

		dba::update('item-content', $fields, $condition, true);
	}

	/**
	 * @brief Distributes public items to the receivers
	 *
	 * @param integer $itemid      Item ID that should be added
	 * @param string  $signed_text Original text (for Diaspora signatures), JSON encoded.
	 */
	public static function distribute($itemid, $signed_text = '')
	{
		$condition = ["`id` IN (SELECT `parent` FROM `item` WHERE `id` = ?)", $itemid];
		$parent = self::selectFirst(['owner-id'], $condition);
		if (!DBM::is_result($parent)) {
			return;
		}

		// Only distribute public items from native networks
		$condition = ['id' => $itemid, 'uid' => 0,
			'network' => [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""],
			'visible' => true, 'deleted' => false, 'moderated' => false, 'private' => false];
		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);
		if (!DBM::is_result($item)) {
			return;
		}

		$origin = $item['origin'];

		unset($item['id']);
		unset($item['parent']);
		unset($item['mention']);
		unset($item['wall']);
		unset($item['origin']);
		unset($item['starred']);

		$users = [];

		$condition = ["`nurl` IN (SELECT `nurl` FROM `contact` WHERE `id` = ?) AND `uid` != 0 AND NOT `blocked` AND `rel` IN (?, ?)",
			$parent['owner-id'], CONTACT_IS_SHARING,  CONTACT_IS_FRIEND];
		$contacts = dba::select('contact', ['uid'], $condition);
		while ($contact = dba::fetch($contacts)) {
			$users[$contact['uid']] = $contact['uid'];
		}

		$origin_uid = 0;

		if ($item['uri'] != $item['parent-uri']) {
			$parents = self::select(['uid', 'origin'], ["`uri` = ? AND `uid` != 0", $item['parent-uri']]);
			while ($parent = dba::fetch($parents)) {
				$users[$parent['uid']] = $parent['uid'];
				if ($parent['origin'] && !$origin) {
					$origin_uid = $parent['uid'];
				}
			}
		}

		foreach ($users as $uid) {
			if ($origin_uid == $uid) {
				$item['diaspora_signed_text'] = $signed_text;
			}
			self::storeForUser($itemid, $item, $uid);
		}
	}

	/**
	 * @brief Store public items for the receivers
	 *
	 * @param integer $itemid Item ID that should be added
	 * @param array   $item   The item entry that will be stored
	 * @param integer $uid    The user that will receive the item entry
	 */
	private static function storeForUser($itemid, $item, $uid)
	{
		$item['uid'] = $uid;
		$item['origin'] = 0;
		$item['wall'] = 0;
		if ($item['uri'] == $item['parent-uri']) {
			$item['contact-id'] = Contact::getIdForURL($item['owner-link'], $uid);
		} else {
			$item['contact-id'] = Contact::getIdForURL($item['author-link'], $uid);
		}

		if (empty($item['contact-id'])) {
			$self = dba::selectFirst('contact', ['id'], ['self' => true, 'uid' => $uid]);
			if (!DBM::is_result($self)) {
				return;
			}
			$item['contact-id'] = $self['id'];
		}

		/// @todo Handling of "event-id"

		$notify = false;
		if ($item['uri'] == $item['parent-uri']) {
			$contact = dba::selectFirst('contact', [], ['id' => $item['contact-id'], 'self' => false]);
			if (DBM::is_result($contact)) {
				$notify = self::isRemoteSelf($contact, $item);
			}
		}

		$distributed = self::insert($item, false, $notify, true);

		if (!$distributed) {
			logger("Distributed public item " . $itemid . " for user " . $uid . " wasn't stored", LOGGER_DEBUG);
		} else {
			logger("Distributed public item " . $itemid . " for user " . $uid . " with id " . $distributed, LOGGER_DEBUG);
		}
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
		$fields = ['uid', 'private', 'moderated', 'visible', 'deleted', 'network', 'uri'];
		$condition = ['id' => $itemid, 'parent' => [0, $itemid]];
		$item = self::selectFirst($fields, $condition);

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

		if (self::exists(['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);

		if (DBM::is_result($item)) {
			// Preparing public shadow (removing user specific data)
			$item['uid'] = 0;
			unset($item['id']);
			unset($item['parent']);
			unset($item['wall']);
			unset($item['mention']);
			unset($item['origin']);
			unset($item['starred']);
			unset($item['postopts']);
			unset($item['inform']);
			if ($item['uri'] == $item['parent-uri']) {
				$item['contact-id'] = $item['owner-id'];
			} else {
				$item['contact-id'] = $item['author-id'];
			}

			$public_shadow = self::insert($item, false, false, true);

			logger("Stored public shadow for thread ".$itemid." under id ".$public_shadow, LOGGER_DEBUG);
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
		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $itemid]);
		if (!DBM::is_result($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['id'] == $item['parent']) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0) {
			return;
		}

		// Is there a shadow parent?
		if (!self::exists(['uri' => $item['parent-uri'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (self::exists(['uri' => $item['uri'], 'uid' => 0])) {
			return;
		}

		// Save "origin" and "parent" state
		$origin = $item['origin'];
		$parent = $item['parent'];

		// Preparing public shadow (removing user specific data)
		$item['uid'] = 0;
		unset($item['id']);
		unset($item['parent']);
		unset($item['wall']);
		unset($item['mention']);
		unset($item['origin']);
		unset($item['starred']);
		unset($item['postopts']);
		unset($item['inform']);
		$item['contact-id'] = Contact::getIdForURL($item['author-link']);

		$public_shadow = self::insert($item, false, false, true);

		logger("Stored public shadow for comment ".$item['uri']." under id ".$public_shadow, LOGGER_DEBUG);

		// If this was a comment to a Diaspora post we don't get our comment back.
		// This means that we have to distribute the comment by ourselves.
		if ($origin && self::exists(['id' => $parent, 'network' => NETWORK_DIASPORA])) {
			self::distribute($public_shadow);
		}
	}

	 /**
	 * Adds a language specification in a "language" element of given $arr.
	 * Expects "body" element to exist in $arr.
	 */
	private static function addLanguageToItemArray(&$item)
	{
		$naked_body = BBCode::toPlaintext($item['body'], false);

		$ld = new Text_LanguageDetect();
		$ld->setNameMode(2);
		$languages = $ld->detect($naked_body, 3);

		if (is_array($languages)) {
			$item['language'] = json_encode($languages);
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
	 * generate an unique URI
	 *
	 * @param integer $uid User id
	 * @param string $guid An existing GUID (Otherwise it will be generated)
	 *
	 * @return string
	 */
	public static function newURI($uid, $guid = "")
	{
		if ($guid == "") {
			$guid = System::createGUID(32);
		}

		$hostname = self::getApp()->get_hostname();

		$user = dba::selectFirst('user', ['nickname'], ['uid' => $uid]);

		$uri = "urn:X-dfrn:" . $hostname . ':' . $user['nickname'] . ':' . $guid;

		return $uri;
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

		$update = (!$arr['private'] && ((defaults($arr, 'author-link', '') === defaults($arr, 'owner-link', '')) || ($arr["parent-uri"] === $arr["uri"])));

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

	public static function setHashtags(&$item)
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
		$item = self::selectFirst(['guid'], ['id' => $id]);
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

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id]);
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
			'owner-id' => $owner_id, 'owner-link' => $self['url'], 'private' => $private, 'allow_cid' => $user['allow_cid'],
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
		if (!empty($datarray["extid"]) && ($datarray["extid"] == NETWORK_DFRN)) {
			logger('Already forwarded', LOGGER_DEBUG);
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray["app"] == $a->get_hostname()) {
			logger('Already forwarded (second test)', LOGGER_DEBUG);
			return false;
		}

		// Only forward posts
		if ($datarray["verb"] != ACTIVITY_POST) {
			logger('No post', LOGGER_DEBUG);
			return false;
		}

		if (($contact['network'] != NETWORK_FEED) && $datarray['private']) {
			logger('Not public', LOGGER_DEBUG);
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

				unset($datarray['network']);
				unset($datarray['owner-id']);
				unset($datarray['author-id']);
			}

			if ($contact['network'] != NETWORK_FEED) {
				$datarray["guid"] = System::createGUID(32);
				unset($datarray["plink"]);
				$datarray["uri"] = self::newURI($contact['uid'], $datarray["guid"]);
				$datarray["parent-uri"] = $datarray["uri"];
				$datarray["thr-parent"] = $datarray["uri"];
				$datarray["extid"] = NETWORK_DFRN;
				$urlpart = parse_url($datarray2['author-link']);
				$datarray["app"] = $urlpart["host"];
			} else {
				$datarray['private'] = 0;
			}
		}

		if ($contact['network'] != NETWORK_FEED) {
			// Store the original post
			$result = self::insert($datarray2, false, false);
			logger('remote-self post original item - Contact '.$contact['url'].' return '.$result.' Item '.print_r($datarray2, true), LOGGER_DEBUG);
		} else {
			$datarray["app"] = "Feed";
			$result = true;
		}

		// Trigger automatic reactions for addons
		$datarray['api_source'] = true;

		// We have to tell the hooks who we are - this really should be improved
		$_SESSION["authenticated"] = true;
		$_SESSION["uid"] = $contact['uid'];

		return $result;
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
		return !empty($obj['allow_cid']) || !empty($obj['allow_gid']) ||
			!empty($obj['deny_cid']) || !empty($obj['deny_gid']);
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

		$condition = ["`uid` = ? AND NOT `deleted` AND `id` = `parent` AND `gravity` = ?",
			$uid, GRAVITY_PARENT];

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = PConfig::get($uid, 'expire', 'network_only', false);

		if ($expire_network_only) {
			$condition[0] .= " AND NOT `wall`";
		}

		if ($network != "") {
			$condition[0] .= " AND `network` = ?";
			$condition[] = $network;

			/*
			 * There is an index "uid_network_received" but not "uid_network_created"
			 * This avoids the creation of another index just for one purpose.
			 * And it doesn't really matter wether to look at "received" or "created"
			 */
			$condition[0] .= " AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY";
			$condition[] = $days;
		} else {
			$condition[0] .= " AND `created` < UTC_TIMESTAMP() - INTERVAL ? DAY";
			$condition[] = $days;
		}

		$items = self::select(['file', 'resource-id', 'starred', 'type', 'id'], $condition);

		if (!DBM::is_result($items)) {
			return;
		}

		$expire_items = PConfig::get($uid, 'expire', 'items', true);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes = PConfig::get($uid, 'expire', 'notes', true);
		$expire_starred = PConfig::get($uid, 'expire', 'starred', true);
		$expire_photos = PConfig::get($uid, 'expire', 'photos', false);

		$expired = 0;

		while ($item = Item::fetch($items)) {
			// don't expire filed items

			if (strpos($item['file'], '[') !== false) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if (!$expire_photos && strlen($item['resource-id'])) {
				continue;
			} elseif (!$expire_starred && intval($item['starred'])) {
				continue;
			} elseif (!$expire_notes && $item['type'] == 'note') {
				continue;
			} elseif (!$expire_items && $item['type'] != 'note') {
				continue;
			}

			self::deleteById($item['id'], PRIORITY_LOW);

			++$expired;
		}
		dba::close($items);
		logger('User ' . $uid . ": expired $expired items; expire items: $expire_items, expire notes: $expire_notes, expire starred: $expire_starred, expire photos: $expire_photos");
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
				$activity = ACTIVITY_LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$activity = ACTIVITY_DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$activity = ACTIVITY_ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$activity = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$activity = ACTIVITY_ATTENDMAYBE;
				break;
			default:
				logger('like: unknown verb ' . $verb . ' for item ' . $item_id);
				return false;
		}

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === ACTIVITY_ATTEND || $activity === ACTIVITY_ATTENDNO || $activity === ACTIVITY_ATTENDMAYBE;

		logger('like: verb ' . $verb . ' item ' . $item_id);

		$item = self::selectFirst(self::ITEM_FIELDLIST, ['`id` = ? OR `uri` = ?', $item_id, $item_id]);
		if (!DBM::is_result($item)) {
			logger('like: unknown item ' . $item_id);
			return false;
		}

		$item_uri = $item['uri'];

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

		$author_contact = dba::selectFirst('contact', ['url'], ['id' => $author_id]);
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
			$verbs = [ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE];

			// Translate to the index based activity index
			$activities = [];
			foreach ($verbs as $verb) {
				$activities[] = self::activityToIndex($verb);
			}
		} else {
			$activities = self::activityToIndex($activity);
		}

		$condition = ['activity' => $activities, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY,
			'author-id' => $author_id, 'uid' => $item['uid'], 'thr-parent' => $item_uri];

		$like_item = self::selectFirst(['id', 'guid', 'verb'], $condition);

		// If it exists, mark it as deleted
		if (DBM::is_result($like_item)) {
			// Already voted, undo it
			$fields = ['deleted' => true, 'unseen' => true, 'changed' => DateTimeFormat::utcNow()];
			/// @todo Consider using self::update - but before doing so, check the side effects
			dba::update('item', $fields, ['id' => $like_item['id']]);

			// Clean up the Diaspora signatures for this like
			dba::delete('sign', ['iid' => $like_item['id']]);

			Worker::add(PRIORITY_HIGH, "Notifier", "like", $like_item['id']);

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// Verb is "un-something", just trying to delete existing entries
		if (strpos($verb, 'un') === 0) {
			return true;
		}

		$objtype = $item['resource-id'] ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE ;

		$new_item = [
			'guid'          => System::createGUID(32),
			'uri'           => self::newURI($item['uid']),
			'uid'           => $item['uid'],
			'contact-id'    => $item_contact_id,
			'wall'          => $item['wall'],
			'origin'        => 1,
			'network'       => NETWORK_DFRN,
			'gravity'       => GRAVITY_ACTIVITY,
			'parent'        => $item['id'],
			'parent-uri'    => $item['uri'],
			'thr-parent'    => $item['uri'],
			'owner-id'      => $item['owner-id'],
			'author-id'     => $author_id,
			'body'          => $activity,
			'verb'          => $activity,
			'object-type'   => $objtype,
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
			'moderated', 'visible', 'starred', 'contact-id', 'post-type',
			'deleted', 'origin', 'forum_mode', 'mention', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];
		$item = self::selectFirst($fields, $condition);

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
		$fields = ['uid', 'guid', 'created', 'edited', 'commented', 'received', 'changed', 'post-type',
			'wall', 'private', 'pubmail', 'moderated', 'visible', 'starred', 'contact-id',
			'deleted', 'origin', 'forum_mode', 'network', 'author-id', 'owner-id'];
		$condition = ["`id` = ? AND (`parent` = ? OR `parent` = 0)", $itemid, $itemid];

		$item = self::selectFirst($fields, $condition);
		if (!DBM::is_result($item)) {
			return;
		}

		if ($setmention) {
			$item["mention"] = 1;
		}

		$sql = "";

		$fields = [];

		foreach ($item as $field => $data) {
			if (!in_array($field, ["guid"])) {
				$fields[$field] = $data;
			}
		}

		$result = dba::update('thread', $fields, ['iid' => $itemid]);

		logger("Update thread for item ".$itemid." - guid ".$item["guid"]." - ".(int)$result, LOGGER_DEBUG);
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
			if (!self::exists($condition)) {
				dba::delete('item', ['uri' => $itemuri, 'uid' => 0]);
				logger("deleteThread: Deleted shadow for item ".$itemuri, LOGGER_DEBUG);
			}
		}
	}
}
