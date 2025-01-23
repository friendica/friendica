<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Content\Feature;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

/**
 * functions for interacting with the event database table
 */
class Event
{
	public static function getHTML(array $event, bool $simple = false, int $uriid = 0): string
	{
		if (empty($event)) {
			return '';
		}

		$uriid = $event['uri-id'] ?? $uriid;

		$bd_format = DI::l10n()->t('l F d, Y \@ g:i A \G\M\TP (e)'); // Friday October 29, 2021 @ 9:15 AM GMT-04:00 (America/New_York)

		$event_start = DI::l10n()->getDay(DateTimeFormat::local($event['start'], $bd_format));

		if (!empty($event['finish'])) {
			$event_end = DI::l10n()->getDay(DateTimeFormat::local($event['finish'], $bd_format));
		} else {
			$event_end = '';
		}

		if ($simple) {
			$o = '';

			if (!empty($event['summary'])) {
				$o .= "<h3>" . strip_tags(BBCode::convertForUriId($uriid, $event['summary'], $simple)) . "</h3>";
			}

			if (!empty($event['desc'])) {
				$o .= "<div>" . BBCode::convertForUriId($uriid, $event['desc'], $simple) . "</div>";
			}

			$o .= "<h4>" . DI::l10n()->t('Starts:') . "</h4><p>" . $event_start . "</p>";

			if (!$event['nofinish']) {
				$o .= "<h4>" . DI::l10n()->t('Finishes:') . "</h4><p>" . $event_end . "</p>";
			}

			if (!empty($event['location'])) {
				$o .= "<h4>" . DI::l10n()->t('Location:') . "</h4><p>" . strip_tags(BBCode::convertForUriId($uriid, $event['location'], $simple)) . "</p>";
			}

			return $o;
		}

		$o = '<div class="vevent">' . "\r\n";

		$o .= '<div class="summary event-summary">' . BBCode::convertForUriId($uriid, $event['summary'], $simple) . '</div>' . "\r\n";

		$o .= '<div class="event-start"><span class="event-label">' . DI::l10n()->t('Starts:') . '</span>&nbsp;<span class="dtstart" title="'
			. DateTimeFormat::local($event['start'], DateTimeFormat::ATOM)
			. '" >' . $event_start
			. '</span></div>' . "\r\n";

		if (!$event['nofinish']) {
			$o .= '<div class="event-end" ><span class="event-label">' . DI::l10n()->t('Finishes:') . '</span>&nbsp;<span class="dtend" title="'
				. DateTimeFormat::local($event['finish'], DateTimeFormat::ATOM)
				. '" >' . $event_end
				. '</span></div>' . "\r\n";
		}

		if (!empty($event['desc'])) {
			$o .= '<div class="description event-description">' . BBCode::convertForUriId($uriid, $event['desc'], $simple) . '</div>' . "\r\n";
		}

		if (!empty($event['location'])) {
			$o .= '<div class="event-location"><span class="event-label">' . DI::l10n()->t('Location:') . '</span>&nbsp;<span class="location">'
				. strip_tags(BBCode::convertForUriId($uriid, $event['location'], $simple))
				. '</span></div>' . "\r\n";

			// Include a map of the location if the [map] BBCode is used.
			if (strpos($event['location'], "[map") !== false) {
				$map = Map::byLocation($event['location'], $simple);
				if ($map !== $event['location']) {
					$o .= $map;
				}
			}
		}

		$o .= '</div>' . "\r\n";
		return $o;
	}

	/**
	 * Convert an array with event data to bbcode.
	 *
	 * @param array $event Array which contains the event data.
	 * @return string The event as a bbcode formatted string.
	 */
	private static function getBBCode(array $event): string
	{
		$o = '';

		if ($event['summary']) {
			$o .= '[event-summary]' . $event['summary'] . '[/event-summary]';
		}

		if ($event['desc']) {
			$o .= '[event-description]' . $event['desc'] . '[/event-description]';
		}

		if ($event['start']) {
			$o .= '[event-start]' . $event['start'] . '[/event-start]';
		}

		if (($event['finish']) && (!$event['nofinish'])) {
			$o .= '[event-finish]' . $event['finish'] . '[/event-finish]';
		}

		if ($event['location']) {
			$o .= '[event-location]' . $event['location'] . '[/event-location]';
		}

		return $o;
	}

	/**
	 * Extract bbcode formatted event data from a string.
	 *
	 * @param string $text The string which should be parsed for event data.
	 * @return array The array with the event information.
	 */
	public static function fromBBCode(string $text): array
	{
		$ev = [];

		$match = [];
		if (preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is", $text, $match)) {
			$ev['summary'] = $match[1];
		}

		$match = [];
		if (preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is", $text, $match)) {
			$ev['desc'] = $match[1];
		}

		$match = [];
		if (preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is", $text, $match)) {
			$ev['start'] = $match[1];
		}

		$match = [];
		if (preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is", $text, $match)) {
			$ev['finish'] = $match[1];
		}

		$match = [];
		if (preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is", $text, $match)) {
			$ev['location'] = $match[1];
		}

		$ev['nofinish'] = !empty($ev['start']) && empty($ev['finish']) ? 1 : 0;

		return $ev;
	}

	public static function sortByDate(array $event_list): array
	{
		usort($event_list, [self::class, 'compareDatesCallback']);
		return $event_list;
	}

	private static function compareDatesCallback(array $event_a, array $event_b)
	{
		$date_a = DateTimeFormat::local($event_a['start']);
		$date_b = DateTimeFormat::local($event_b['start']);

		if ($date_a === $date_b) {
			return strcasecmp($event_a['desc'], $event_b['desc']);
		}

		return strcmp($date_a, $date_b);
	}

	/**
	 * Delete an event from the event table.
	 *
	 * Note: This function does only delete the event from the event table not its
	 * related entry in the item table.
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 * @throws \Exception
	 */
	public static function delete(int $event_id)
	{
		if ($event_id == 0) {
			return;
		}

		DBA::delete('event', ['id' => $event_id]);
		DI::logger()->info("Deleted event", ['id' => $event_id]);
	}

	/**
	 * Store the event.
	 *
	 * Store the event in the event table and create an event item in the item table.
	 *
	 * @param array $arr Array with event data.
	 * @return int The new event id.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store(array $arr): int
	{
		$guid  = $arr['guid'] ?? '' ?: System::createUUID();
		$uri   = $arr['uri']  ?? '' ?: Item::newURI($guid);
		$event = [
			'id'        => intval($arr['id'] ?? 0),
			'uid'       => intval($arr['uid'] ?? 0),
			'cid'       => intval($arr['cid'] ?? 0),
			'guid'      => $guid,
			'uri'       => $uri,
			'uri-id'    => ItemURI::insert(['uri' => $uri, 'guid' => $guid]),
			'type'      => ($arr['type'] ?? '') ?: 'event',
			'summary'   => $arr['summary']   ?? '',
			'desc'      => $arr['desc']      ?? '',
			'location'  => $arr['location']  ?? '',
			'allow_cid' => $arr['allow_cid'] ?? '',
			'allow_gid' => $arr['allow_gid'] ?? '',
			'deny_cid'  => $arr['deny_cid']  ?? '',
			'deny_gid'  => $arr['deny_gid']  ?? '',
			'nofinish'  => intval($arr['nofinish'] ?? (!empty($arr['start']) && empty($arr['finish']))),
			'created'   => DateTimeFormat::utc(($arr['created'] ?? '') ?: 'now'),
			'edited'    => DateTimeFormat::utc(($arr['edited'] ?? '') ?: 'now'),
			'start'     => DateTimeFormat::utc(($arr['start'] ?? '') ?: DBA::NULL_DATETIME),
			'finish'    => DateTimeFormat::utc(($arr['finish'] ?? '') ?: DBA::NULL_DATETIME),
		];


		if ($event['finish'] < DBA::NULL_DATETIME) {
			$event['finish'] = DBA::NULL_DATETIME;
		}

		// Existing event being modified.
		if ($event['id']) {
			// has the event actually changed?
			$existing_event = DBA::selectFirst('event', ['edited'], ['id' => $event['id'], 'uid' => $event['uid']]);
			if (!DBA::isResult($existing_event)) {
				return 0;
			}

			if ($existing_event['edited'] === $event['edited']) {
				return $event['id'];
			}

			$updated_fields = [
				'edited'   => $event['edited'],
				'start'    => $event['start'],
				'finish'   => $event['finish'],
				'summary'  => $event['summary'],
				'desc'     => $event['desc'],
				'location' => $event['location'],
				'type'     => $event['type'],
				'nofinish' => $event['nofinish'],
			];

			DBA::update('event', $updated_fields, ['id' => $event['id'], 'uid' => $event['uid']]);

			$item = Post::selectFirst(['id', 'uri-id'], ['event-id' => $event['id'], 'uid' => $event['uid']]);
			if (DBA::isResult($item)) {
				$object = '<object><type>' . XML::escape(Activity\ObjectType::EVENT) . '</type><title></title><id>' . XML::escape($event['uri']) . '</id>';
				$object .= '<content>' . XML::escape(self::getBBCode($event)) . '</content>';
				$object .= '</object>' . "\n";

				$fields = ['body' => self::getBBCode($event), 'object' => $object, 'edited' => $event['edited']];
				Item::update($fields, ['id' => $item['id']]);
			}

			Hook::callAll('event_updated', $event['id']);
		} else {
			// New event. Store it.
			DBA::insert('event', $event);

			$event['id'] = DBA::lastInsertId();

			Hook::callAll("event_created", $event['id']);
		}

		return $event['id'];
	}

	public static function getItemArrayForId(int $event_id, array $item = []): array
	{
		if (empty($event_id)) {
			return $item;
		}

		$event = DBA::selectFirst('event', [], ['id' => $event_id]);
		if ($event['type'] != 'event') {
			return $item;
		}

		if ($event['cid']) {
			$conditions = ['id' => $event['cid']];
		} else {
			$conditions = ['uid' => $event['uid'], 'self' => true];
		}

		$contact = DBA::selectFirst('contact', [], $conditions);

		$event['id'] = $event_id;

		$item['uid']           = $event['uid'];
		$item['uri']           = $event['uri'];
		$item['uri-id']        = ItemURI::getIdByURI($event['uri']);
		$item['guid']          = $event['guid'];
		$item['plink']         = '';
		$item['post-type']     = Item::PT_EVENT;
		$item['wall']          = $event['cid'] ? 0 : 1;
		$item['contact-id']    = $contact['id'];
		$item['owner-name']    = $contact['name'];
		$item['owner-link']    = $contact['url'];
		$item['owner-avatar']  = $contact['thumb'];
		$item['author-name']   = $contact['name'];
		$item['author-link']   = $contact['url'];
		$item['author-avatar'] = $contact['thumb'];
		$item['title']         = '';
		$item['allow_cid']     = $event['allow_cid'];
		$item['allow_gid']     = $event['allow_gid'];
		$item['deny_cid']      = $event['deny_cid'];
		$item['deny_gid']      = $event['deny_gid'];
		$item['private']       = $event['allow_cid'] || $event['allow_gid'] || $event['deny_cid'] || $event['deny_gid'];
		$item['visible']       = 1;
		$item['verb']          = Activity::POST;
		$item['object-type']   = Activity\ObjectType::EVENT;
		$item['origin']        = $event['cid'] === 0 ? 1 : 0;
		$item['body']          = self::getBBCode($event);
		$item['event-id']      = $event['id'];

		$item['object'] = '<object><type>' . XML::escape(Activity\ObjectType::EVENT) . '</type><title></title><id>' . XML::escape($event['uri']) . '</id>';
		$item['object'] .= '<content>' . XML::escape(self::getBBCode($event)) . '</content>';
		$item['object'] .= '</object>' . "\n";

		return $item;
	}

	public static function getItemArrayForImportedId(int $event_id, array $item = []): array
	{
		if (empty($event_id)) {
			return $item;
		}

		$event = DBA::selectFirst('event', [], ['id' => $event_id]);
		if ($event['type'] != 'event') {
			return $item;
		}

		$item['post-type']   = Item::PT_EVENT;
		$item['title']       = '';
		$item['object-type'] = Activity\ObjectType::EVENT;
		$item['body']        = self::getBBCode($event);
		$item['event-id']    = $event_id;

		$item['object'] = '<object><type>' . XML::escape(Activity\ObjectType::EVENT) . '</type><title></title><id>' . XML::escape($event['uri']) . '</id>';
		$item['object'] .= '<content>' . XML::escape(self::getBBCode($event)) . '</content>';
		$item['object'] .= '</object>' . "\n";

		return $item;
	}

	/**
	 * Create an array with translation strings used for events.
	 *
	 * @return array Array with translations strings.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getStrings(): array
	{
		// First day of the week (0 = Sunday).
		$firstDay    = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'calendar', 'first_day_of_week') ?? 0;
		$defaultView = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'calendar', 'defaultView')       ?? 'month';

		return [
			'firstDay'    => $firstDay,
			'defaultView' => $defaultView,
			'allday'      => DI::l10n()->t('all-day'),

			'Sun' => DI::l10n()->t('Sun'),
			'Mon' => DI::l10n()->t('Mon'),
			'Tue' => DI::l10n()->t('Tue'),
			'Wed' => DI::l10n()->t('Wed'),
			'Thu' => DI::l10n()->t('Thu'),
			'Fri' => DI::l10n()->t('Fri'),
			'Sat' => DI::l10n()->t('Sat'),

			'Sunday'    => DI::l10n()->t('Sunday'),
			'Monday'    => DI::l10n()->t('Monday'),
			'Tuesday'   => DI::l10n()->t('Tuesday'),
			'Wednesday' => DI::l10n()->t('Wednesday'),
			'Thursday'  => DI::l10n()->t('Thursday'),
			'Friday'    => DI::l10n()->t('Friday'),
			'Saturday'  => DI::l10n()->t('Saturday'),

			'Jan' => DI::l10n()->t('Jan'),
			'Feb' => DI::l10n()->t('Feb'),
			'Mar' => DI::l10n()->t('Mar'),
			'Apr' => DI::l10n()->t('Apr'),
			'May' => DI::l10n()->t('May'),
			'Jun' => DI::l10n()->t('Jun'),
			'Jul' => DI::l10n()->t('Jul'),
			'Aug' => DI::l10n()->t('Aug'),
			'Sep' => DI::l10n()->t('Sept'),
			'Oct' => DI::l10n()->t('Oct'),
			'Nov' => DI::l10n()->t('Nov'),
			'Dec' => DI::l10n()->t('Dec'),

			'January'   => DI::l10n()->t('January'),
			'February'  => DI::l10n()->t('February'),
			'March'     => DI::l10n()->t('March'),
			'April'     => DI::l10n()->t('April'),
			'June'      => DI::l10n()->t('June'),
			'July'      => DI::l10n()->t('July'),
			'August'    => DI::l10n()->t('August'),
			'September' => DI::l10n()->t('September'),
			'October'   => DI::l10n()->t('October'),
			'November'  => DI::l10n()->t('November'),
			'December'  => DI::l10n()->t('December'),

			'today' => DI::l10n()->t('today'),
			'month' => DI::l10n()->t('month'),
			'week'  => DI::l10n()->t('week'),
			'day'   => DI::l10n()->t('day'),

			'noevent' => DI::l10n()->t('No events to display'),

			'dtstart_label'  => DI::l10n()->t('Starts:'),
			'dtend_label'    => DI::l10n()->t('Finishes:'),
			'location_label' => DI::l10n()->t('Location:')
		];
	}

	/**
	 * Removes duplicated birthday events.
	 *
	 * @param array $dates Array of possibly duplicated events.
	 * @return array Cleaned events.
	 *
	 * @todo We should replace this with a separate update function if there is some time left.
	 */
	private static function removeDuplicates(array $dates): array
	{
		$dates2 = [];

		foreach ($dates as $date) {
			if ($date['type'] == 'birthday') {
				$dates2[$date['uid'] . "-" . $date['cid'] . "-" . $date['start']] = $date;
			} else {
				$dates2[] = $date;
			}
		}
		return array_values($dates2);
	}

	/**
	 * Returns the owner array of a given nickname
	 * Additionally, it can check if the owner array is selectable
	 *
	 * @param string $nickname
	 *
	 * @return array the owner array
	 * @throws InternalServerErrorException
	 * @throws NotFoundException The given nickname does not exist
	 * @throws UnauthorizedException The access for the given nickname is restricted
	 */
	public static function getOwnerForNickname(string $nickname): array
	{
		$owner = User::getOwnerDataByNick($nickname);
		if (empty($owner) || $owner['account_removed'] || $owner['account_expired']) {
			throw new NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!DI::userSession()->isAuthenticated() && $owner['hidewall']) {
			throw new UnauthorizedException(DI::l10n()->t('Access to this profile has been restricted.'));
		}

		if (!DI::userSession()->isAuthenticated() && !Feature::isEnabled($owner['uid'], Feature::PUBLIC_CALENDAR)) {
			throw new UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}

		return $owner;
	}

	/**
	 * Get an event by its event ID. Checks permissions.
	 *
	 * @param int         $owner_uid The User ID of the owner of the event
	 * @param int         $event_id  The ID of the event in the event table
	 * @return array Query result
	 * @throws \Exception
	 */
	public static function getByIdAndUid(int $owner_uid, int $event_id): array
	{
		// Only allow events if there is a valid owner_id.
		if ($owner_uid == 0) {
			return [];
		}

		// get the permissions
		$sql_perms = Item::getPermissionsSQLByUserId($owner_uid);

		// Query for the event by event id
		$events = DBA::toArray(DBA::p(
			"SELECT `event`.*, `post-user`.`id` AS `itemid` FROM `event`
			LEFT JOIN `post-user`
				ON `post-user`.`event-id` = `event`.`id`
				AND `post-user`.`uid` = `event`.`uid`
			WHERE `event`.`id` = ?
			  AND `event`.`uid` = ?
			  $sql_perms",
			$event_id,
			$owner_uid
		));
		if (empty($events)) {
			throw new NotFoundException(DI::l10n()->t('Event not found.'));
		}

		return $events[0];
	}

	/**
	 * Get all events in a specific time frame.
	 *
	 * @param int         $owner_uid The User ID of the owner of the events.
	 * @param string|null $start     Start time of the timeframe.
	 * @param string|null $finish    Finish time of the timeframe.
	 * @param bool|null   $ignore    Filters ignored events (false: unignored events, true: ignored events, null: all events)
	 *
	 * @return array Query results.
	 * @throws NotFoundException
	 * @throws UnauthorizedException
	 */
	public static function getListByDate(int $owner_uid, string $start = null, string $finish = null, ?bool $ignore = false): array
	{
		// Only allow events if there is a valid owner_id.
		if ($owner_uid == 0) {
			return [];
		}

		// get the permissions
		$sql_perms = Item::getPermissionsSQLByUserId($owner_uid);

		if (empty($start) || empty($finish)) {
			$y = intval(DateTimeFormat::localNow('Y'));
			$m = intval(DateTimeFormat::localNow('m'));

			if (empty($start)) {
				$start = sprintf('%d-%d-%d %d:%d:%d', $y, $m, 1, 0, 0, 0);
			} else {
				$dim    = Temporal::getDaysInMonth($y, $m);
				$finish = sprintf('%d-%d-%d %d:%d:%d', $y, $m, $dim, 23, 59, 59);
			}
		}

		if ($ignore === true) {
			$sql_ignore = " AND `event`.`ignore` = 1";
		} elseif ($ignore === false) {
			$sql_ignore = " AND `event`.`ignore` = 0";
		} else {
			$sql_ignore = "";
		}

		// Query for the event by date.
		$events = DBA::toArray(DBA::p(
			"SELECT `event`.*, `post-user`.`id` AS `itemid` FROM `event`
			LEFT JOIN `post-user`
			    ON `post-user`.`event-id` = `event`.`id`
	            AND `post-user`.`uid` = `event`.`uid`
			WHERE `event`.`uid` = ?
			  $sql_ignore
			  AND (`finish` >= ? OR (`nofinish` AND `start` >= ?))
			  AND `start` <= ?
			  $sql_perms",
			$owner_uid,
			$start,
			$start,
			$finish
		));

		$events = self::removeDuplicates($events);
		return self::sortByDate($events);
	}

	/**
	 * Convert an event in an array which could be used by the event template.
	 *
	 * @param array $event Event query array.
	 * @return array Event array for the template.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function prepareForItem(array $event): array
	{
		$fmt = DI::l10n()->t('l, F j');

		$item = Post::selectFirst(['plink', 'author-name', 'author-network', 'author-id', 'author-avatar', 'author-link', 'author-alias', 'private', 'uri-id'], ['id' => $event['itemid']]);
		if (empty($item)) {
			// Using default values when no item had been found
			$item = ['plink' => '', 'author-name' => '', 'author-avatar' => '', 'author-link' => '', 'private' => Item::PUBLIC, 'uri-id' => ($event['uri-id'] ?? 0)];
		}

		$event = array_merge($event, $item);

		$start = DateTimeFormat::local($event['start'], 'c');
		$j     = DateTimeFormat::local($event['start'], 'j');
		$day   = DateTimeFormat::local($event['start'], $fmt);
		$day   = DI::l10n()->getDay($day);

		if ($event['nofinish']) {
			$end = null;
		} else {
			$end = DateTimeFormat::local($event['finish'], 'c');
		}

		// Show edit and drop actions only if the user is the owner of the event and the event
		// is a real event (no birthdays).
		$edit = null;
		$copy = null;
		$drop = null;
		if (DI::userSession()->getLocalUserId() && DI::userSession()->getLocalUserId() == $event['uid'] && $event['type'] == 'event') {
			$edit = !$event['cid'] ? ['calendar/event/edit/' . $event['id'], DI::l10n()->t('Edit event'), '', ''] : null;
			$copy = !$event['cid'] ? ['calendar/event/copy/' . $event['id'], DI::l10n()->t('Duplicate event'), '', ''] : null;
			$drop = ['calendar/api/delete/' . $event['id'], DI::l10n()->t('Delete event'), '', ''];
		}

		$title = strip_tags(BBCode::convertForUriId($event['uri-id'], $event['summary']));
		if (!$title) {
			list($title, $_trash) = explode("<br", BBCode::convertForUriId($event['uri-id'], Strings::escapeHtml($event['desc'])), BBCode::TWITTER_API);
		}

		$event['author-link'] = Contact::magicLink($event['author-link']);

		return [
			'id'     => $event['id'],
			'start'  => $start,
			'end'    => $end,
			'allDay' => false,
			'title'  => $title,
			'j'      => $j,
			'd'      => $day,
			'edit'   => $edit,
			'drop'   => $drop,
			'copy'   => $copy,
			'item'   => $event,
			'html'   => self::getHTML($event),
			'plink'  => Item::getPlink($event),
		];
	}

	/**
	 * Format event to export format (ical/csv).
	 *
	 * @param array  $events Query result for events.
	 * @param string $format The output format (ical/csv).
	 * @return string Content according to selected export format.
	 *
	 * @todo  Implement timezone support
	 */
	private static function formatListForExport(array $events, string $format): string
	{
		$o = '';

		if (!count($events)) {
			return $o;
		}

		switch ($format) {
			// Format the exported data as a CSV file.
			case "csv":
				$o .= '"Subject", "Start Date", "Start Time", "Description", "End Date", "End Time", "Location"' . PHP_EOL;

				foreach ($events as $event) {
					/// @todo The time / date entries don't include any information about the
					/// timezone the event is scheduled in :-/
					$tmp1        = strtotime($event['start']);
					$tmp2        = strtotime($event['finish']);
					$time_format = "%H:%M:%S";
					$date_format = "%Y-%m-%d";

					$o .= '"' . $event['summary'] . '", "' . strftime($date_format, $tmp1) .
						'", "' . strftime($time_format, $tmp1) . '", "' . $event['desc'] .
						'", "' . strftime($date_format, $tmp2) .
						'", "' . strftime($time_format, $tmp2) .
						'", "' . $event['location'] . '"' . PHP_EOL;
				}
				break;

				// Format the exported data as a ics file.
			case "ical":
				$o = 'BEGIN:VCALENDAR' . PHP_EOL
					. 'VERSION:2.0' . PHP_EOL
					. 'PRODID:-//friendica calendar export//0.1//EN' . PHP_EOL;
				///  @todo include timezone informations in cases were the time is not in UTC
				//  see http://tools.ietf.org/html/rfc2445#section-4.8.3
				//		. 'BEGIN:VTIMEZONE' . PHP_EOL
				//		. 'TZID:' . $timezone . PHP_EOL
				//		. 'END:VTIMEZONE' . PHP_EOL;
				//  TODO instead of PHP_EOL CRLF should be used for long entries
				//       but test your solution against http://icalvalid.cloudapp.net/
				//       also long lines SHOULD be split at 75 characters length
				foreach ($events as $event) {
					$o .= 'BEGIN:VEVENT' . PHP_EOL;
					$o .= 'UID:' . $event['id'] . PHP_EOL;
					$o .= 'DTSTAMP:' . DateTimeFormat::utc($event['created'], 'Ymd\THis\Z') . PHP_EOL;

					if ($event['start']) {
						$o .= 'DTSTART:' . DateTimeFormat::utc($event['start'], 'Ymd\THis\Z') . PHP_EOL;
					}

					if (!$event['nofinish']) {
						$o .= 'DTEND:' . DateTimeFormat::utc($event['finish'], 'Ymd\THis\Z') . PHP_EOL;
					}

					if ($event['summary']) {
						$tmp = $event['summary'];
						$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
						$tmp = addcslashes($tmp, ',;');
						$o .= 'SUMMARY:' . $tmp . PHP_EOL;
					}

					if ($event['desc']) {
						$tmp = $event['desc'];
						$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
						$tmp = addcslashes($tmp, ',;');
						$o .= 'DESCRIPTION:' . $tmp . PHP_EOL;
					}

					if ($event['location']) {
						$tmp = $event['location'];
						$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
						$tmp = addcslashes($tmp, ',;');
						$o .= 'LOCATION:' . $tmp . PHP_EOL;
					}

					$o .= 'END:VEVENT' . PHP_EOL;
				}

				$o .= 'END:VCALENDAR' . PHP_EOL;
				break;
		}

		return $o;
	}

	/**
	 * Get all events for a user ID.
	 *
	 *    The query for events is done permission sensitive.
	 *    If the user is the owner of the calendar they
	 *    will get all of their available events.
	 *    If the user is only a visitor only the public events will
	 *    be available.
	 *
	 * @param int $uid The user ID.
	 *
	 * @return array Query results.
	 * @throws \Exception
	 */
	private static function getListByUserId(int $uid = 0): array
	{
		$return = [];

		if ($uid == 0) {
			return $return;
		}

		$fields = ['id', 'created', 'start', 'finish', 'summary', 'desc', 'location', 'nofinish'];

		$conditions = ['uid' => $uid, 'cid' => 0];

		// Does the user who requests happen to be the owner of the events
		// requested? then show all of your events, otherwise only those that
		// don't have limitations set in allow_cid and allow_gid.
		if (DI::userSession()->getLocalUserId() != $uid) {
			$conditions += ['allow_cid' => '', 'allow_gid' => ''];
		}

		$events = DBA::select('event', $fields, $conditions);
		if (DBA::isResult($events)) {
			$return = DBA::toArray($events);
		}

		return $return;
	}

	/**
	 *
	 * @param int    $uid    The user ID.
	 * @param string $format Output format (ical/csv).
	 * @return array With the results:
	 *                       bool 'success' => True if the processing was successful,<br>
	 *                       string 'format' => The output format,<br>
	 *                       string 'extension' => The file extension of the output format,<br>
	 *                       string 'content' => The formatted output content.<br>
	 *
	 * @throws \Exception
	 * @todo Respect authenticated users with events_by_uid().
	 */
	public static function exportListByUserId(int $uid, string $format = 'ical'): array
	{
		$process = false;

		// Get all events which are owned by a uid (respects permissions).
		$events = self::getListByUserId($uid);

		// We have the events that are available for the requestor.
		// Now format the output according to the requested format.
		$res = self::formatListForExport($events, $format);

		// If there are results the precess was successful.
		if (!empty($res)) {
			$process = true;
		}

		// Get the file extension for the format.
		switch ($format) {
			case "ical":
				$file_ext = "ics";
				break;

			case "csv":
				$file_ext = "csv";
				break;

			default:
				$file_ext = "";
		}

		$return = [
			'success'   => $process,
			'format'    => $format,
			'extension' => $file_ext,
			'content'   => $res,
		];

		return $return;
	}

	/**
	 * Format an item array with event data to HTML.
	 *
	 * @param array $item Array with item and event data.
	 * @return string HTML output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getItemHTML(array $item): string
	{
		$same_date = false;
		$finish    = false;

		// Set the different time formats.
		$dformat       = DI::l10n()->t('l F d, Y \@ g:i A'); // Friday January 18, 2011 @ 8:01 AM.
		$dformat_short = DI::l10n()->t('D g:i A'); // Fri 8:01 AM.
		$tformat       = DI::l10n()->t('g:i A'); // 8:01 AM.

		// Convert the time to different formats.
		$dtstart_dt    = DI::l10n()->getDay(DateTimeFormat::local($item['event-start'], $dformat));
		$dtstart_title = DateTimeFormat::utc($item['event-start'], DateTimeFormat::ATOM);
		// Format: Jan till Dec.
		$month_short = DI::l10n()->getDayShort(DateTimeFormat::local($item['event-start'], 'M'));
		// Format: 1 till 31.
		$date_short  = DateTimeFormat::local($item['event-start'], 'j');
		$start_time  = DateTimeFormat::local($item['event-start'], $tformat);
		$start_short = DI::l10n()->getDayShort(DateTimeFormat::local($item['event-start'], $dformat_short));

		// If the option 'nofinisch' isn't set, we need to format the finish date/time.
		if (!$item['event-nofinish']) {
			$finish      = true;
			$dtend_dt    = DI::l10n()->getDay(DateTimeFormat::local($item['event-finish'], $dformat));
			$dtend_title = DateTimeFormat::utc($item['event-finish'], DateTimeFormat::ATOM);
			$end_short   = DI::l10n()->getDayShort(DateTimeFormat::utc($item['event-finish'], $dformat_short));
			$end_time    = DateTimeFormat::local($item['event-finish'], $tformat);
			// Check if start and finish time is at the same day.
			if (substr($dtstart_title, 0, 10) === substr($dtend_title, 0, 10)) {
				$same_date = true;
			}
		} else {
			$dtend_title = '';
			$dtend_dt    = '';
			$end_time    = '';
			$end_short   = '';
		}

		// Construct the profile link (magic-auth).
		$author = [
			'uid'     => 0,
			'id'      => $item['author-id'],
			'network' => $item['author-network'],
			'url'     => $item['author-link'],
			'alias'   => $item['author-alias']
		];
		$profile_link = Contact::magicLinkByContact($author);

		$tpl    = Renderer::getMarkupTemplate('event_stream_item.tpl');
		$return = Renderer::replaceMacros($tpl, [
			'$id'             => $item['event-id'],
			'$title'          => BBCode::convertForUriId($item['uri-id'], $item['event-summary']),
			'$dtstart_label'  => DI::l10n()->t('Starts:'),
			'$dtstart_title'  => $dtstart_title,
			'$dtstart_dt'     => $dtstart_dt,
			'$finish'         => $finish,
			'$dtend_label'    => DI::l10n()->t('Finishes:'),
			'$dtend_title'    => $dtend_title,
			'$dtend_dt'       => $dtend_dt,
			'$month_short'    => $month_short,
			'$date_short'     => $date_short,
			'$same_date'      => $same_date,
			'$start_time'     => $start_time,
			'$start_short'    => $start_short,
			'$end_time'       => $end_time,
			'$end_short'      => $end_short,
			'$author_name'    => $item['author-name'],
			'$author_link'    => $profile_link,
			'$author_avatar'  => $item['author-avatar'],
			'$description'    => BBCode::convertForUriId($item['uri-id'], $item['event-desc']),
			'$location_label' => DI::l10n()->t('Location:'),
			'$show_map_label' => DI::l10n()->t('Show map'),
			'$hide_map_label' => DI::l10n()->t('Hide map'),
			'$map_btn_label'  => DI::l10n()->t('Show map'),
			'$location'       => self::locationToTemplateVars($item['event-location']),
		]);

		return $return;
	}

	/**
	 * Format a string with map bbcode to an array with location data.
	 *
	 * Note: The string must only contain location data. A string with no bbcode will be
	 * handled as location name.
	 *
	 * @param string $s The string with the bbcode formatted location data.
	 *
	 * @return array The array with the location data.
	 *  'name' => The name of the location,<br>
	 * 'address' => The address of the location,<br>
	 * 'coordinates' => Latitude and longitude (e.g. '48.864716,2.349014').<br>
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function locationToTemplateVars(string $s = ''): array
	{
		if ($s == '') {
			return [];
		}

		$location = ['name' => $s];

		// Map tag with location name - e.g. [map]Paris[/map].
		if (strpos($s, '[/map]') !== false) {
			$found = preg_match("/\[map\](.*?)\[\/map\]/ism", $s, $match);
			if (intval($found) > 0 && array_key_exists(1, $match)) {
				$location['address'] = $match[1];
				// Remove the map bbcode from the location name.
				$location['name'] = str_replace($match[0], "", $s);
			}
			// Map tag with coordinates - e.g. [map=48.864716,2.349014].
		} elseif (strpos($s, '[map=') !== false) {
			$found = preg_match("/\[map=(.*?)\]/ism", $s, $match);
			if (intval($found) > 0 && array_key_exists(1, $match)) {
				$location['coordinates'] = $match[1];
				// Remove the map bbcode from the location name.
				$location['name'] = str_replace($match[0], "", $s);
			}
		}

		$location['name'] = BBCode::toPlaintext($location['name'], false);

		// Construct the map HTML.
		if (isset($location['address'])) {
			$location['map'] = '<div class="map">' . Map::byLocation($location['address']) . '</div>';
		} elseif (isset($location['coordinates'])) {
			$location['map'] = '<div class="map">' . Map::byCoordinates(str_replace('/', ' ', $location['coordinates'])) . '</div>';
		}

		return $location;
	}

	/**
	 * Add new birthday event for this person
	 *
	 * @param array  $contact  Contact array, expects: id, uid, url, name
	 * @param string $birthday Birthday of the contact
	 * @return bool
	 * @throws \Exception
	 */
	public static function createBirthday(array $contact, string $birthday): bool
	{
		// Check for duplicates
		$condition = [
			'uid'   => $contact['uid'],
			'cid'   => $contact['id'],
			'start' => DateTimeFormat::utc($birthday),
			'type'  => 'birthday'
		];
		if (DBA::exists('event', $condition)) {
			return false;
		}

		/*
		 * Add new birthday event for this person
		 *
		 * summary is just a readable placeholder in case the event is shared
		 * with others. We will replace it during presentation to our $importer
		 * to contain a sparkle link and perhaps a photo.
		 */
		$values = [
			'uid'     => $contact['uid'],
			'cid'     => $contact['id'],
			'start'   => DateTimeFormat::utc($birthday),
			'finish'  => DateTimeFormat::utc($birthday . ' + 1 day '),
			'summary' => DI::l10n()->t('%s\'s birthday', $contact['name']),
			'desc'    => DI::l10n()->t('Happy Birthday %s', ' [url=' . $contact['url'] . ']' . $contact['name'] . '[/url]'),
			'type'    => 'birthday',
		];

		// Check if self::store() was success
		return (self::store($values) > 0);
	}

	public static function setIgnore(int $uid, int $eventId, bool $ignore = true)
	{
		DBA::update('event', ['ignore' => $ignore], ['id' => $eventId, 'uid' => $uid]);
	}
}
