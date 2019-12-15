<?php
/**
 * @file src/Model/Event.php
 */

namespace Friendica\Model;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * @brief functions for interacting with the event database table
 */
class Event
{

	public static function getHTML(array $event, $simple = false)
	{
		if (empty($event)) {
			return '';
		}

		$bd_format = L10n::t('l F d, Y \@ g:i A'); // Friday January 18, 2011 @ 8 AM.

		$event_start = L10n::getDay(
			!empty($event['adjust']) ?
			DateTimeFormat::local($event['start'], $bd_format) : DateTimeFormat::utc($event['start'], $bd_format)
		);

		if (!empty($event['finish'])) {
			$event_end = L10n::getDay(
				!empty($event['adjust']) ?
				DateTimeFormat::local($event['finish'], $bd_format) : DateTimeFormat::utc($event['finish'], $bd_format)
			);
		} else {
			$event_end = '';
		}

		if ($simple) {
			$o = '';

			if (!empty($event['summary'])) {
				$o .= "<h3>" . BBCode::convert(Strings::escapeHtml($event['summary']), false, $simple) . "</h3>";
			}

			if (!empty($event['desc'])) {
				$o .= "<div>" . BBCode::convert(Strings::escapeHtml($event['desc']), false, $simple) . "</div>";
			}

			$o .= "<h4>" . L10n::t('Starts:') . "</h4><p>" . $event_start . "</p>";

			if (!$event['nofinish']) {
				$o .= "<h4>" . L10n::t('Finishes:') . "</h4><p>" . $event_end . "</p>";
			}

			if (!empty($event['location'])) {
				$o .= "<h4>" . L10n::t('Location:') . "</h4><p>" . BBCode::convert(Strings::escapeHtml($event['location']), false, $simple) . "</p>";
			}

			return $o;
		}

		$o = '<div class="vevent">' . "\r\n";

		$o .= '<div class="summary event-summary">' . BBCode::convert(Strings::escapeHtml($event['summary']), false, $simple) . '</div>' . "\r\n";

		$o .= '<div class="event-start"><span class="event-label">' . L10n::t('Starts:') . '</span>&nbsp;<span class="dtstart" title="'
			. DateTimeFormat::utc($event['start'], (!empty($event['adjust']) ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s'))
			. '" >' . $event_start
			. '</span></div>' . "\r\n";

		if (!$event['nofinish']) {
			$o .= '<div class="event-end" ><span class="event-label">' . L10n::t('Finishes:') . '</span>&nbsp;<span class="dtend" title="'
				. DateTimeFormat::utc($event['finish'], (!empty($event['adjust']) ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s'))
				. '" >' . $event_end
				. '</span></div>' . "\r\n";
		}

		if (!empty($event['desc'])) {
			$o .= '<div class="description event-description">' . BBCode::convert(Strings::escapeHtml($event['desc']), false, $simple) . '</div>' . "\r\n";
		}

		if (!empty($event['location'])) {
			$o .= '<div class="event-location"><span class="event-label">' . L10n::t('Location:') . '</span>&nbsp;<span class="location">'
				. BBCode::convert(Strings::escapeHtml($event['location']), false, $simple)
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
	 * @brief Convert an array with event data to bbcode.
	 *
	 * @param array $event Array which contains the event data.
	 * @return string The event as a bbcode formatted string.
	 */
	private static function getBBCode(array $event)
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

		if ($event['adjust']) {
			$o .= '[event-adjust]' . $event['adjust'] . '[/event-adjust]';
		}

		return $o;
	}

	/**
	 * @brief Extract bbcode formatted event data from a string.
	 *
	 * @params: string $s The string which should be parsed for event data.
	 * @param $text
	 * @return array The array with the event information.
	 */
	public static function fromBBCode($text)
	{
		$ev = [];

		$match = '';
		if (preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is", $text, $match)) {
			$ev['summary'] = $match[1];
		}

		$match = '';
		if (preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is", $text, $match)) {
			$ev['desc'] = $match[1];
		}

		$match = '';
		if (preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is", $text, $match)) {
			$ev['start'] = $match[1];
		}

		$match = '';
		if (preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is", $text, $match)) {
			$ev['finish'] = $match[1];
		}

		$match = '';
		if (preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is", $text, $match)) {
			$ev['location'] = $match[1];
		}

		$match = '';
		if (preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is", $text, $match)) {
			$ev['adjust'] = $match[1];
		}

		$ev['nofinish'] = !empty($ev['start']) && empty($ev['finish']) ? 1 : 0;

		return $ev;
	}

	public static function sortByDate($event_list)
	{
		usort($event_list, ['self', 'compareDatesCallback']);
		return $event_list;
	}

	private static function compareDatesCallback($event_a, $event_b)
	{
		$date_a = (($event_a['adjust']) ? DateTimeFormat::local($event_a['start']) : $event_a['start']);
		$date_b = (($event_b['adjust']) ? DateTimeFormat::local($event_b['start']) : $event_b['start']);

		if ($date_a === $date_b) {
			return strcasecmp($event_a['desc'], $event_b['desc']);
		}

		return strcmp($date_a, $date_b);
	}

	/**
	 * @brief Delete an event from the event table.
	 *
	 * Note: This function does only delete the event from the event table not its
	 * related entry in the item table.
	 *
	 * @param int $event_id Event ID.
	 * @return void
	 * @throws \Exception
	 */
	public static function delete($event_id)
	{
		if ($event_id == 0) {
			return;
		}

		DBA::delete('event', ['id' => $event_id], ['cascade' => false]);
		Logger::log("Deleted event ".$event_id, Logger::DEBUG);
	}

	/**
	 * @brief Store the event.
	 *
	 * Store the event in the event table and create an event item in the item table.
	 *
	 * @param array $arr Array with event data.
	 * @return int The new event id.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function store($arr)
	{
		$event = [];
		$event['id']        = intval($arr['id']        ?? 0);
		$event['uid']       = intval($arr['uid']       ?? 0);
		$event['cid']       = intval($arr['cid']       ?? 0);
		$event['guid']      =       ($arr['guid']      ?? '') ?: System::createUUID();
		$event['uri']       =       ($arr['uri']       ?? '') ?: Item::newURI($event['uid'], $event['guid']);
		$event['type']      =       ($arr['type']      ?? '') ?: 'event';
		$event['summary']   =        $arr['summary']   ?? '';
		$event['desc']      =        $arr['desc']      ?? '';
		$event['location']  =        $arr['location']  ?? '';
		$event['allow_cid'] =        $arr['allow_cid'] ?? '';
		$event['allow_gid'] =        $arr['allow_gid'] ?? '';
		$event['deny_cid']  =        $arr['deny_cid']  ?? '';
		$event['deny_gid']  =        $arr['deny_gid']  ?? '';
		$event['adjust']    = intval($arr['adjust']    ?? 0);
		$event['nofinish']  = intval($arr['nofinish'] ?? (!empty($event['start']) && empty($event['finish'])));

		$event['created']   = DateTimeFormat::utc(($arr['created'] ?? '') ?: 'now');
		$event['edited']    = DateTimeFormat::utc(($arr['edited']  ?? '') ?: 'now');
		$event['start']     = DateTimeFormat::utc(($arr['start']   ?? '') ?: DBA::NULL_DATETIME);
		$event['finish']    = DateTimeFormat::utc(($arr['finish']  ?? '') ?: DBA::NULL_DATETIME);
		if ($event['finish'] < DBA::NULL_DATETIME) {
			$event['finish'] = DBA::NULL_DATETIME;
		}
		$private = intval($arr['private'] ?? 0);

		$conditions = ['uid' => $event['uid']];
		if ($event['cid']) {
			$conditions['id'] = $event['cid'];
		} else {
			$conditions['self'] = true;
		}

		$contact = DBA::selectFirst('contact', [], $conditions);

		// Existing event being modified.
		if ($event['id']) {
			// has the event actually changed?
			$existing_event = DBA::selectFirst('event', ['edited'], ['id' => $event['id'], 'uid' => $event['uid']]);
			if (!DBA::isResult($existing_event) || ($existing_event['edited'] === $event['edited'])) {

				$item = Item::selectFirst(['id'], ['event-id' => $event['id'], 'uid' => $event['uid']]);

				return DBA::isResult($item) ? $item['id'] : 0;
			}

			$updated_fields = [
				'edited'   => $event['edited'],
				'start'    => $event['start'],
				'finish'   => $event['finish'],
				'summary'  => $event['summary'],
				'desc'     => $event['desc'],
				'location' => $event['location'],
				'type'     => $event['type'],
				'adjust'   => $event['adjust'],
				'nofinish' => $event['nofinish'],
			];

			DBA::update('event', $updated_fields, ['id' => $event['id'], 'uid' => $event['uid']]);

			$item = Item::selectFirst(['id'], ['event-id' => $event['id'], 'uid' => $event['uid']]);
			if (DBA::isResult($item)) {
				$object = '<object><type>' . XML::escape(Activity\ObjectType::EVENT) . '</type><title></title><id>' . XML::escape($event['uri']) . '</id>';
				$object .= '<content>' . XML::escape(self::getBBCode($event)) . '</content>';
				$object .= '</object>' . "\n";

				$fields = ['body' => self::getBBCode($event), 'object' => $object, 'edited' => $event['edited']];
				Item::update($fields, ['id' => $item['id']]);

				$item_id = $item['id'];
			} else {
				$item_id = 0;
			}

			Hook::callAll('event_updated', $event['id']);
		} else {
			// New event. Store it.
			DBA::insert('event', $event);

			$item_id = 0;

			// Don't create an item for birthday events
			if ($event['type'] == 'event') {
				$event['id'] = DBA::lastInsertId();

				$item_arr = [];

				$item_arr['uid']           = $event['uid'];
				$item_arr['contact-id']    = $event['cid'];
				$item_arr['uri']           = $event['uri'];
				$item_arr['parent-uri']    = $event['uri'];
				$item_arr['guid']          = $event['guid'];
				$item_arr['plink']         = $arr['plink'] ?? '';
				$item_arr['post-type']     = Item::PT_EVENT;
				$item_arr['wall']          = $event['cid'] ? 0 : 1;
				$item_arr['contact-id']    = $contact['id'];
				$item_arr['owner-name']    = $contact['name'];
				$item_arr['owner-link']    = $contact['url'];
				$item_arr['owner-avatar']  = $contact['thumb'];
				$item_arr['author-name']   = $contact['name'];
				$item_arr['author-link']   = $contact['url'];
				$item_arr['author-avatar'] = $contact['thumb'];
				$item_arr['title']         = '';
				$item_arr['allow_cid']     = $event['allow_cid'];
				$item_arr['allow_gid']     = $event['allow_gid'];
				$item_arr['deny_cid']      = $event['deny_cid'];
				$item_arr['deny_gid']      = $event['deny_gid'];
				$item_arr['private']       = $private;
				$item_arr['visible']       = 1;
				$item_arr['verb']          = Activity::POST;
				$item_arr['object-type']   = Activity\ObjectType::EVENT;
				$item_arr['origin']        = $event['cid'] === 0 ? 1 : 0;
				$item_arr['body']          = self::getBBCode($event);
				$item_arr['event-id']      = $event['id'];

				$item_arr['object']  = '<object><type>' . XML::escape(Activity\ObjectType::EVENT) . '</type><title></title><id>' . XML::escape($event['uri']) . '</id>';
				$item_arr['object'] .= '<content>' . XML::escape(self::getBBCode($event)) . '</content>';
				$item_arr['object'] .= '</object>' . "\n";

				$item_id = Item::insert($item_arr);
			}

			Hook::callAll("event_created", $event['id']);
		}

		return $item_id;
	}

	/**
	 * @brief Create an array with translation strings used for events.
	 *
	 * @return array Array with translations strings.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getStrings()
	{
		// First day of the week (0 = Sunday).
		$firstDay = PConfig::get(local_user(), 'system', 'first_day_of_week', 0);

		$i18n = [
			"firstDay" => $firstDay,
			"allday"   => L10n::t("all-day"),

			"Sun" => L10n::t("Sun"),
			"Mon" => L10n::t("Mon"),
			"Tue" => L10n::t("Tue"),
			"Wed" => L10n::t("Wed"),
			"Thu" => L10n::t("Thu"),
			"Fri" => L10n::t("Fri"),
			"Sat" => L10n::t("Sat"),

			"Sunday"    => L10n::t("Sunday"),
			"Monday"    => L10n::t("Monday"),
			"Tuesday"   => L10n::t("Tuesday"),
			"Wednesday" => L10n::t("Wednesday"),
			"Thursday"  => L10n::t("Thursday"),
			"Friday"    => L10n::t("Friday"),
			"Saturday"  => L10n::t("Saturday"),

			"Jan" => L10n::t("Jan"),
			"Feb" => L10n::t("Feb"),
			"Mar" => L10n::t("Mar"),
			"Apr" => L10n::t("Apr"),
			"May" => L10n::t("May"),
			"Jun" => L10n::t("Jun"),
			"Jul" => L10n::t("Jul"),
			"Aug" => L10n::t("Aug"),
			"Sep" => L10n::t("Sept"),
			"Oct" => L10n::t("Oct"),
			"Nov" => L10n::t("Nov"),
			"Dec" => L10n::t("Dec"),

			"January"   => L10n::t("January"),
			"February"  => L10n::t("February"),
			"March"     => L10n::t("March"),
			"April"     => L10n::t("April"),
			"June"      => L10n::t("June"),
			"July"      => L10n::t("July"),
			"August"    => L10n::t("August"),
			"September" => L10n::t("September"),
			"October"   => L10n::t("October"),
			"November"  => L10n::t("November"),
			"December"  => L10n::t("December"),

			"today" => L10n::t("today"),
			"month" => L10n::t("month"),
			"week"  => L10n::t("week"),
			"day"   => L10n::t("day"),

			"noevent" => L10n::t("No events to display"),

			"dtstart_label"  => L10n::t("Starts:"),
			"dtend_label"    => L10n::t("Finishes:"),
			"location_label" => L10n::t("Location:")
		];

		return $i18n;
	}

	/**
	 * @brief Removes duplicated birthday events.
	 *
	 * @param array $dates Array of possibly duplicated events.
	 * @return array Cleaned events.
	 *
	 * @todo We should replace this with a separate update function if there is some time left.
	 */
	private static function removeDuplicates(array $dates)
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
	 * @brief Get an event by its event ID.
	 *
	 * @param int    $owner_uid The User ID of the owner of the event
	 * @param int    $event_id  The ID of the event in the event table
	 * @param string $sql_extra
	 * @return array Query result
	 * @throws \Exception
	 */
	public static function getListById($owner_uid, $event_id, $sql_extra = '')
	{
		$return = [];

		// Ownly allow events if there is a valid owner_id.
		if ($owner_uid == 0) {
			return $return;
		}

		// Query for the event by event id
		$r = q("SELECT `event`.*, `item`.`id` AS `itemid` FROM `event`
			LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
			WHERE `event`.`uid` = %d AND `event`.`id` = %d $sql_extra",
			intval($owner_uid),
			intval($event_id)
		);

		if (DBA::isResult($r)) {
			$return = self::removeDuplicates($r);
		}

		return $return;
	}

	/**
	 * @brief Get all events in a specific time frame.
	 *
	 * @param int    $owner_uid    The User ID of the owner of the events.
	 * @param array  $event_params An associative array with
	 *                             int 'ignore' =>
	 *                             string 'start' => Start time of the timeframe.
	 *                             string 'finish' => Finish time of the timeframe.
	 *                             string 'adjust_start' =>
	 *                             string 'adjust_finish' =>
	 *
	 * @param string $sql_extra    Additional sql conditions (e.g. permission request).
	 *
	 * @return array Query results.
	 * @throws \Exception
	 */
	public static function getListByDate($owner_uid, $event_params, $sql_extra = '')
	{
		$return = [];

		// Only allow events if there is a valid owner_id.
		if ($owner_uid == 0) {
			return $return;
		}

		// Query for the event by date.
		// @todo Slow query (518 seconds to run), to be optimzed
		$r = q("SELECT `event`.*, `item`.`id` AS `itemid` FROM `event`
				LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
				WHERE `event`.`uid` = %d AND event.ignore = %d
				AND ((`adjust` = 0 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s')
				OR  (`adjust` = 1 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s'))
				$sql_extra ",
				intval($owner_uid),
				intval($event_params["ignore"]),
				DBA::escape($event_params["start"]),
				DBA::escape($event_params["start"]),
				DBA::escape($event_params["finish"]),
				DBA::escape($event_params["adjust_start"]),
				DBA::escape($event_params["adjust_start"]),
				DBA::escape($event_params["adjust_finish"])
		);

		if (DBA::isResult($r)) {
			$return = self::removeDuplicates($r);
		}

		return $return;
	}

	/**
	 * @brief Convert an array query results in an array which could be used by the events template.
	 *
	 * @param array $event_result Event query array.
	 * @return array Event array for the template.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function prepareListForTemplate(array $event_result)
	{
		$event_list = [];

		$last_date = '';
		$fmt = L10n::t('l, F j');
		foreach ($event_result as $event) {
			$item = Item::selectFirst(['plink', 'author-name', 'author-avatar', 'author-link'], ['id' => $event['itemid']]);
			if (!DBA::isResult($item)) {
				// Using default values when no item had been found
				$item = ['plink' => '', 'author-name' => '', 'author-avatar' => '', 'author-link' => ''];
			}

			$event = array_merge($event, $item);

			$start = $event['adjust'] ? DateTimeFormat::local($event['start'], 'c')  : DateTimeFormat::utc($event['start'], 'c');
			$j     = $event['adjust'] ? DateTimeFormat::local($event['start'], 'j')  : DateTimeFormat::utc($event['start'], 'j');
			$day   = $event['adjust'] ? DateTimeFormat::local($event['start'], $fmt) : DateTimeFormat::utc($event['start'], $fmt);
			$day   = L10n::getDay($day);

			if ($event['nofinish']) {
				$end = null;
			} else {
				$end = $event['adjust'] ? DateTimeFormat::local($event['finish'], 'c') : DateTimeFormat::utc($event['finish'], 'c');
			}

			$is_first = ($day !== $last_date);

			$last_date = $day;

			// Show edit and drop actions only if the user is the owner of the event and the event
			// is a real event (no bithdays).
			$edit = null;
			$copy = null;
			$drop = null;
			if (local_user() && local_user() == $event['uid'] && $event['type'] == 'event') {
				$edit = !$event['cid'] ? [System::baseUrl() . '/events/event/' . $event['id'], L10n::t('Edit event')     , '', ''] : null;
				$copy = !$event['cid'] ? [System::baseUrl() . '/events/copy/' . $event['id'] , L10n::t('Duplicate event'), '', ''] : null;
				$drop =                  [System::baseUrl() . '/events/drop/' . $event['id'] , L10n::t('Delete event')   , '', ''];
			}

			$title = BBCode::convert(Strings::escapeHtml($event['summary']));
			if (!$title) {
				list($title, $_trash) = explode("<br", BBCode::convert(Strings::escapeHtml($event['desc'])), 2);
			}

			$author_link = $event['author-link'];
			$plink       = $event['plink'];

			$event['author-link'] = Contact::magicLink($author_link);
			$event['plink']       = Contact::magicLink($author_link, $plink);

			$html = self::getHTML($event);
			$event['summary']  = BBCode::convert(Strings::escapeHtml($event['summary']));
			$event['desc']     = BBCode::convert(Strings::escapeHtml($event['desc']));
			$event['location'] = BBCode::convert(Strings::escapeHtml($event['location']));
			$event_list[] = [
				'id'       => $event['id'],
				'start'    => $start,
				'end'      => $end,
				'allDay'   => false,
				'title'    => $title,
				'j'        => $j,
				'd'        => $day,
				'edit'     => $edit,
				'drop'     => $drop,
				'copy'     => $copy,
				'is_first' => $is_first,
				'item'     => $event,
				'html'     => $html,
				'plink'    => [$event['plink'], L10n::t('link to source'), '', ''],
			];
		}

		return $event_list;
	}

	/**
	 * @brief Format event to export format (ical/csv).
	 *
	 * @param array  $events Query result for events.
	 * @param string $format The output format (ical/csv).
	 *
	 * @param        $timezone
	 * @return string Content according to selected export format.
	 *
	 * @todo  Implement timezone support
	 */
	private static function formatListForExport(array $events, $format)
	{
		$o = '';

		if (!count($events)) {
			return $o;
		}

		switch ($format) {
			// Format the exported data as a CSV file.
			case "csv":
				header("Content-type: text/csv");
				$o .= '"Subject", "Start Date", "Start Time", "Description", "End Date", "End Time", "Location"' . PHP_EOL;

				foreach ($events as $event) {
					/// @todo The time / date entries don't include any information about the
					/// timezone the event is scheduled in :-/
					$tmp1 = strtotime($event['start']);
					$tmp2 = strtotime($event['finish']);
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
				header("Content-type: text/ics");
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
					if ($event['adjust'] == 1) {
						$UTC = 'Z';
					} else {
						$UTC = '';
					}
					$o .= 'BEGIN:VEVENT' . PHP_EOL;

					if ($event['start']) {
						$tmp = strtotime($event['start']);
						$dtformat = "%Y%m%dT%H%M%S" . $UTC;
						$o .= 'DTSTART:' . strftime($dtformat, $tmp) . PHP_EOL;
					}

					if (!$event['nofinish']) {
						$tmp = strtotime($event['finish']);
						$dtformat = "%Y%m%dT%H%M%S" . $UTC;
						$o .= 'DTEND:' . strftime($dtformat, $tmp) . PHP_EOL;
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
					$o .= PHP_EOL;
				}

				$o .= 'END:VCALENDAR' . PHP_EOL;
				break;
		}

		return $o;
	}

	/**
	 * @brief Get all events for a user ID.
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
	private static function getListByUserId($uid = 0)
	{
		$return = [];

		if ($uid == 0) {
			return $return;
		}

		$fields = ['start', 'finish', 'adjust', 'summary', 'desc', 'location', 'nofinish'];

		$conditions = ['uid' => $uid, 'cid' => 0];

		// Does the user who requests happen to be the owner of the events
		// requested? then show all of your events, otherwise only those that
		// don't have limitations set in allow_cid and allow_gid.
		if (local_user() != $uid) {
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
	public static function exportListByUserId($uid, $format = 'ical')
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
	 * @brief Format an item array with event data to HTML.
	 *
	 * @param array $item Array with item and event data.
	 * @return string HTML output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getItemHTML(array $item) {
		$same_date = false;
		$finish    = false;

		// Set the different time formats.
		$dformat       = L10n::t('l F d, Y \@ g:i A'); // Friday January 18, 2011 @ 8:01 AM.
		$dformat_short = L10n::t('D g:i A'); // Fri 8:01 AM.
		$tformat       = L10n::t('g:i A'); // 8:01 AM.

		// Convert the time to different formats.
		$dtstart_dt = L10n::getDay(
			$item['event-adjust'] ?
				DateTimeFormat::local($item['event-start'], $dformat)
				: DateTimeFormat::utc($item['event-start'], $dformat)
		);
		$dtstart_title = DateTimeFormat::utc($item['event-start'], $item['event-adjust'] ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s');
		// Format: Jan till Dec.
		$month_short = L10n::getDayShort(
			$item['event-adjust'] ?
				DateTimeFormat::local($item['event-start'], 'M')
				: DateTimeFormat::utc($item['event-start'], 'M')
		);
		// Format: 1 till 31.
		$date_short = $item['event-adjust'] ?
			DateTimeFormat::local($item['event-start'], 'j')
			: DateTimeFormat::utc($item['event-start'], 'j');
		$start_time = $item['event-adjust'] ?
			DateTimeFormat::local($item['event-start'], $tformat)
			: DateTimeFormat::utc($item['event-start'], $tformat);
		$start_short = L10n::getDayShort(
			$item['event-adjust'] ?
				DateTimeFormat::local($item['event-start'], $dformat_short)
				: DateTimeFormat::utc($item['event-start'], $dformat_short)
		);

		// If the option 'nofinisch' isn't set, we need to format the finish date/time.
		if (!$item['event-nofinish']) {
			$finish = true;
			$dtend_dt  = L10n::getDay(
				$item['event-adjust'] ?
					DateTimeFormat::local($item['event-finish'], $dformat)
					: DateTimeFormat::utc($item['event-finish'], $dformat)
			);
			$dtend_title = DateTimeFormat::utc($item['event-finish'], $item['event-adjust'] ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s');
			$end_short = L10n::getDayShort(
				$item['event-adjust'] ?
					DateTimeFormat::local($item['event-finish'], $dformat_short)
					: DateTimeFormat::utc($item['event-finish'], $dformat_short)
			);
			$end_time = $item['event-adjust'] ?
				DateTimeFormat::local($item['event-finish'], $tformat)
				: DateTimeFormat::utc($item['event-finish'], $tformat);
			// Check if start and finish time is at the same day.
			if (substr($dtstart_title, 0, 10) === substr($dtend_title, 0, 10)) {
				$same_date = true;
			}
		} else {
			$dtend_title = '';
			$dtend_dt = '';
			$end_time = '';
			$end_short = '';
		}

		// Format the event location.
		$location = self::locationToArray($item['event-location']);

		// Construct the profile link (magic-auth).
		$profile_link = Contact::magicLinkById($item['author-id']);

		$tpl = Renderer::getMarkupTemplate('event_stream_item.tpl');
		$return = Renderer::replaceMacros($tpl, [
			'$id'             => $item['event-id'],
			'$title'          => BBCode::convert($item['event-summary']),
			'$dtstart_label'  => L10n::t('Starts:'),
			'$dtstart_title'  => $dtstart_title,
			'$dtstart_dt'     => $dtstart_dt,
			'$finish'         => $finish,
			'$dtend_label'    => L10n::t('Finishes:'),
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
			'$description'    => BBCode::convert($item['event-desc']),
			'$location_label' => L10n::t('Location:'),
			'$show_map_label' => L10n::t('Show map'),
			'$hide_map_label' => L10n::t('Hide map'),
			'$map_btn_label'  => L10n::t('Show map'),
			'$location'       => $location
		]);

		return $return;
	}

	/**
	 * @brief Format a string with map bbcode to an array with location data.
	 *
	 * Note: The string must only contain location data. A string with no bbcode will be
	 * handled as location name.
	 *
	 * @param string $s The string with the bbcode formatted location data.
	 *
	 * @return array The array with the location data.
	 *  'name' => The name of the location,<br>
	 * 'address' => The address of the location,<br>
	 * 'coordinates' => Latitude‎ and longitude‎ (e.g. '48.864716,2.349014').<br>
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function locationToArray($s = '') {
		if ($s == '') {
			return [];
		}

		$location = ['name' => $s];

		// Map tag with location name - e.g. [map]Paris[/map].
		if (strpos($s, '[/map]') !== false) {
			$found = preg_match("/\[map\](.*?)\[\/map\]/ism", $s, $match);
			if (intval($found) > 0 && array_key_exists(1, $match)) {
				$location['address'] =  $match[1];
				// Remove the map bbcode from the location name.
				$location['name'] = str_replace($match[0], "", $s);
			}
		// Map tag with coordinates - e.g. [map=48.864716,2.349014].
		} elseif (strpos($s, '[map=') !== false) {
			$found = preg_match("/\[map=(.*?)\]/ism", $s, $match);
			if (intval($found) > 0 && array_key_exists(1, $match)) {
				$location['coordinates'] =  $match[1];
				// Remove the map bbcode from the location name.
				$location['name'] = str_replace($match[0], "", $s);
			}
		}

		$location['name'] = BBCode::convert($location['name']);

		// Construct the map HTML.
		if (isset($location['address'])) {
			$location['map'] = '<div class="map">' . Map::byLocation($location['address']) . '</div>';
		} elseif (isset($location['coordinates'])) {
			$location['map'] = '<div class="map">' . Map::byCoordinates(str_replace('/', ' ', $location['coordinates'])) . '</div>';
		}

		return $location;
	}

	/**
	 * @brief Add new birthday event for this person
	 *
	 * @param array  $contact  Contact array, expects: id, uid, url, name
	 * @param string $birthday Birthday of the contact
	 * @return bool
	 * @throws \Exception
	 */
	public static function createBirthday($contact, $birthday)
	{
		// Check for duplicates
		$condition = [
			'uid' => $contact['uid'],
			'cid' => $contact['id'],
			'start' => DateTimeFormat::utc($birthday),
			'type' => 'birthday'
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
			'summary' => L10n::t('%s\'s birthday', $contact['name']),
			'desc'    => L10n::t('Happy Birthday %s', ' [url=' . $contact['url'] . ']' . $contact['name'] . '[/url]'),
			'type'    => 'birthday',
			'adjust'  => 0
		];

		self::store($values);

		return true;
	}
}
