<?php
/**
 * @file src/Core/NotificationsManager.php
 * @brief Methods for read and write notifications from/to database
 *  or for formatting notifications
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

/**
 * @brief Methods for read and write notifications from/to database
 *  or for formatting notifications
 */
class NotificationsManager extends BaseObject
{
	/**
	 * @brief set some extra note properties
	 *
	 * @param array $notes array of note arrays from db
	 * @return array Copy of input array with added properties
	 *
	 * Set some extra properties to note array from db:
	 *  - timestamp as int in default TZ
	 *  - date_rel : relative date string
	 *  - msg_html: message as html string
	 *  - msg_plain: message as plain text string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function _set_extra(array $notes)
	{
		$rets = [];
		foreach ($notes as $n) {
			$local_time = DateTimeFormat::local($n['date']);
			$n['timestamp'] = strtotime($local_time);
			$n['date_rel'] = Temporal::getRelativeDate($n['date']);
			$n['msg_html'] = BBCode::convert($n['msg'], false);
			$n['msg_plain'] = explode("\n", trim(HTML::toPlaintext($n['msg_html'], 0)))[0];

			$rets[] = $n;
		}
		return $rets;
	}

	/**
	 * @brief Get all notifications for local_user()
	 *
	 * @param array  $filter optional Array "column name"=>value: filter query by columns values
	 * @param array  $order  optional Array to order by
	 * @param string $limit  optional Query limits
	 *
	 * @return array|bool of results or false on errors
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getAll($filter = [], $order = ['date' => 'DESC'], $limit = "")
	{
		$params = [];

		$params['order'] = $order;

		if (!empty($limit)) {
			$params['limit'] = $limit;
		}

		$dbFilter = array_merge($filter, ['uid' => local_user()]);

		$stmtNotifies = DBA::select('notify', [], $dbFilter, $params);

		if (DBA::isResult($stmtNotifies)) {
			return $this->_set_extra(DBA::toArray($stmtNotifies));
		}

		return false;
	}

	/**
	 * @brief Get one note for local_user() by $id value
	 *
	 * @param int $id identity
	 * @return array note values or null if not found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getByID($id)
	{
		$stmtNotify = DBA::selectFirst('notify', [], ['id' => $id, 'uid' => local_user()]);
		if (DBA::isResult($stmtNotify)) {
			return $this->_set_extra([$stmtNotify])[0];
		}
		return null;
	}

	/**
	 * @brief set seen state of $note of local_user()
	 *
	 * @param array $note note array
	 * @param bool  $seen optional true or false, default true
	 * @return bool true on success, false on errors
	 * @throws \Exception
	 */
	public function setSeen($note, $seen = true)
	{
		return DBA::update('notify', ['seen' => $seen], [
			'(`link` = ? OR (`parent` != 0 AND `parent` = ? AND `otype` = ?)) AND `uid` = ?',
			$note['link'],
			$note['parent'],
			$note['otype'],
			local_user()
		]);
	}

	/**
	 * @brief set seen state of all notifications of local_user()
	 *
	 * @param bool $seen optional true or false. default true
	 * @return bool true on success, false on error
	 * @throws \Exception
	 */
	public function setAllSeen($seen = true)
	{
		return DBA::update('notify', ['seen' => $seen], ['uid' => local_user()]);
	}

	/**
	 * @brief List of pages for the Notifications TabBar
	 *
	 * @return array with with notifications TabBar data
	 * @throws \Exception
	 */
	public function getTabs()
	{
		$selected = defaults(self::getApp()->argv, 1, '');

		$tabs = [
			[
				'label' => L10n::t('System'),
				'url'   => 'notifications/system',
				'sel'   => (($selected == 'system') ? 'active' : ''),
				'id'    => 'system-tab',
				'accesskey' => 'y',
			],
			[
				'label' => L10n::t('Network'),
				'url'   => 'notifications/network',
				'sel'   => (($selected == 'network') ? 'active' : ''),
				'id'    => 'network-tab',
				'accesskey' => 'w',
			],
			[
				'label' => L10n::t('Personal'),
				'url'   => 'notifications/personal',
				'sel'   => (($selected == 'personal') ? 'active' : ''),
				'id'    => 'personal-tab',
				'accesskey' => 'r',
			],
			[
				'label' => L10n::t('Home'),
				'url'   => 'notifications/home',
				'sel'   => (($selected == 'home') ? 'active' : ''),
				'id'    => 'home-tab',
				'accesskey' => 'h',
			],
			[
				'label' => L10n::t('Introductions'),
				'url'   => 'notifications/intros',
				'sel'   => (($selected == 'intros') ? 'active' : ''),
				'id'    => 'intro-tab',
				'accesskey' => 'i',
			],
		];

		return $tabs;
	}

	/**
	 * @brief Format the notification query in an usable array
	 *
	 * @param array  $notifs The array from the db query
	 * @param string $ident  The notifications identifier (e.g. network)
	 * @return array
	 *                       string 'label' => The type of the notification
	 *                       string 'link' => URL to the source
	 *                       string 'image' => The avatar image
	 *                       string 'url' => The profile url of the contact
	 *                       string 'text' => The notification text
	 *                       string 'when' => The date of the notification
	 *                       string 'ago' => T relative date of the notification
	 *                       bool 'seen' => Is the notification marked as "seen"
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function formatNotifs(array $notifs, $ident = "")
	{
		$arr = [];

		if (DBA::isResult($notifs)) {
			foreach ($notifs as $it) {
				// Because we use different db tables for the notification query
				// we have sometimes $it['unseen'] and sometimes $it['seen].
				// So we will have to transform $it['unseen']
				if (array_key_exists('unseen', $it)) {
					$it['seen'] = ($it['unseen'] > 0 ? false : true);
				}

				// For feed items we use the user's contact, since the avatar is mostly self choosen.
				if (!empty($it['network']) && $it['network'] == Protocol::FEED) {
					$it['author-avatar'] = $it['contact-avatar'];
				}

				// Depending on the identifier of the notification we need to use different defaults
				switch ($ident) {
					case 'system':
						$default_item_label = 'notify';
						$default_item_link = System::baseUrl(true) . '/notify/view/' . $it['id'];
						$default_item_image = ProxyUtils::proxifyUrl($it['photo'], false, ProxyUtils::SIZE_MICRO);
						$default_item_url = $it['url'];
						$default_item_text = strip_tags(BBCode::convert($it['msg']));
						$default_item_when = DateTimeFormat::local($it['date'], 'r');
						$default_item_ago = Temporal::getRelativeDate($it['date']);
						break;

					case 'home':
						$default_item_label = 'comment';
						$default_item_link = System::baseUrl(true) . '/display/' . $it['parent-guid'];
						$default_item_image = ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO);
						$default_item_url = $it['author-link'];
						$default_item_text = L10n::t("%s commented on %s's post", $it['author-name'], $it['parent-author-name']);
						$default_item_when = DateTimeFormat::local($it['created'], 'r');
						$default_item_ago = Temporal::getRelativeDate($it['created']);
						break;

					default:
						$default_item_label = (($it['id'] == $it['parent']) ? 'post' : 'comment');
						$default_item_link = System::baseUrl(true) . '/display/' . $it['parent-guid'];
						$default_item_image = ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO);
						$default_item_url = $it['author-link'];
						$default_item_text = (($it['id'] == $it['parent'])
									? L10n::t("%s created a new post", $it['author-name'])
									: L10n::t("%s commented on %s's post", $it['author-name'], $it['parent-author-name']));
						$default_item_when = DateTimeFormat::local($it['created'], 'r');
						$default_item_ago = Temporal::getRelativeDate($it['created']);
				}

				// Transform the different types of notification in an usable array
				switch ($it['verb']) {
					case ACTIVITY_LIKE:
						$notif = [
							'label' => 'like',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s liked %s's post", $it['author-name'], $it['parent-author-name']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					case ACTIVITY_DISLIKE:
						$notif = [
							'label' => 'dislike',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s disliked %s's post", $it['author-name'], $it['parent-author-name']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					case ACTIVITY_ATTEND:
						$notif = [
							'label' => 'attend',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s is attending %s's event", $it['author-name'], $it['parent-author-name']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					case ACTIVITY_ATTENDNO:
						$notif = [
							'label' => 'attendno',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s is not attending %s's event", $it['author-name'], $it['parent-author-name']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					case ACTIVITY_ATTENDMAYBE:
						$notif = [
							'label' => 'attendmaybe',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s may attend %s's event", $it['author-name'], $it['parent-author-name']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					case ACTIVITY_FRIEND:
						if (!isset($it['object'])) {
							$notif = [
								'label' => 'friend',
								'link' => $default_item_link,
								'image' => $default_item_image,
								'url' => $default_item_url,
								'text' => $default_item_text,
								'when' => $default_item_when,
								'ago' => $default_item_ago,
								'seen' => $it['seen']
							];
							break;
						}
						/// @todo Check if this part here is used at all
						Logger::log('Complete data: ' . json_encode($it) . ' - ' . System::callstack(20), Logger::DEBUG);

						$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
						$obj = XML::parseString($xmlhead . $it['object']);
						$it['fname'] = $obj->title;

						$notif = [
							'label' => 'friend',
							'link' => System::baseUrl(true) . '/display/' . $it['parent-guid'],
							'image' => ProxyUtils::proxifyUrl($it['author-avatar'], false, ProxyUtils::SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => L10n::t("%s is now friends with %s", $it['author-name'], $it['fname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
						break;

					default:
						$notif = [
							'label' => $default_item_label,
							'link' => $default_item_link,
							'image' => $default_item_image,
							'url' => $default_item_url,
							'text' => $default_item_text,
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						];
				}

				$arr[] = $notif;
			}
		}

		return $arr;
	}

	/**
	 * @brief Get network notifications
	 *
	 * @param int|string $seen    If 0 only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array with
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Network notifications
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function networkNotifs($seen = 0, $start = 0, $limit = 80)
	{
		$ident = 'network';
		$notifs = [];

		$condition = ['wall' => false, 'uid' => local_user()];

		if ($seen === 0) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['created' => true], 'limit' => [$start, $limit]];

		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if (DBA::isResult($items)) {
			$notifs = $this->formatNotifs(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifs,
			'ident' => $ident,
		];

		return $arr;
	}

	/**
	 * @brief Get system notifications
	 *
	 * @param int|string $seen    If 0 only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array with
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => System notifications
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function systemNotifs($seen = 0, $start = 0, $limit = 80)
	{
		$ident = 'system';
		$notifs = [];
		$sql_seen = "";

		$filter = ['uid' => local_user()];
		if ($seen === 0) {
			$filter['seen'] = false;
		}

		$params = [];
		$params['order'] = ['date' => 'DESC'];
		$params['limit'] = [$start, $limit];

		$stmtNotifies = DBA::select('notify',
			['id', 'url', 'photo', 'msg', 'date', 'seen', 'verb'],
			$filter,
			$params);

		if (DBA::isResult($stmtNotifies)) {
			$notifs = $this->formatNotifs(DBA::toArray($stmtNotifies), $ident);
		}

		$arr = [
			'notifications' => $notifs,
			'ident' => $ident,
		];

		return $arr;
	}

	/**
	 * @brief Get personal notifications
	 *
	 * @param int|string $seen    If 0 only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array with
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Personal notifications
	 * @throws \Exception
	 */
	public function personalNotifs($seen = 0, $start = 0, $limit = 80)
	{
		$ident = 'personal';
		$notifs = [];

		$myurl = str_replace('http://', '', self::getApp()->contact['nurl']);
		$diasp_url = str_replace('/profile/', '/u/', $myurl);

		$condition = ["NOT `wall` AND `uid` = ? AND (`item`.`author-id` = ? OR `item`.`tag` REGEXP ? OR `item`.`tag` REGEXP ?)",
			local_user(), public_contact(), $myurl . '\\]', $diasp_url . '\\]'];

		if ($seen === 0) {
			$condition[0] .= " AND `unseen`";
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['created' => true], 'limit' => [$start, $limit]];

		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if (DBA::isResult($items)) {
			$notifs = $this->formatNotifs(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifs,
			'ident' => $ident,
		];

		return $arr;
	}

	/**
	 * @brief Get home notifications
	 *
	 * @param int|string $seen    If 0 only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array with
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Home notifications
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function homeNotifs($seen = 0, $start = 0, $limit = 80)
	{
		$ident = 'home';
		$notifs = [];

		$condition = ['wall' => true, 'uid' => local_user()];

		if ($seen === 0) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['created' => true], 'limit' => [$start, $limit]];
		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if (DBA::isResult($items)) {
			$notifs = $this->formatNotifs(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifs,
			'ident' => $ident,
		];

		return $arr;
	}

	/**
	 * @brief Get introductions
	 *
	 * @param bool $all     If false only include introductions into the query
	 *                      which aren't marked as ignored
	 * @param int  $start   Start the query at this point
	 * @param int  $limit   Maximum number of query results
	 *
	 * @return array with
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Introductions
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function introNotifs($all = false, $start = 0, $limit = 80)
	{
		$ident = 'introductions';
		$notifs = [];
		$sql_extra = "";

		if (!$all) {
			$sql_extra = " AND NOT `ignore` ";
		}

		/// @todo Fetch contact details by "Contact::getDetailsByUrl" instead of queries to contact, fcontact and gcontact
		$stmtNotifies = DBA::p(
			"SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*,
				`fcontact`.`name` AS `fname`, `fcontact`.`url` AS `furl`, `fcontact`.`addr` AS `faddr`,
				`fcontact`.`photo` AS `fphoto`, `fcontact`.`request` AS `frequest`,
				`gcontact`.`location` AS `glocation`, `gcontact`.`about` AS `gabout`,
				`gcontact`.`keywords` AS `gkeywords`, `gcontact`.`gender` AS `ggender`,
				`gcontact`.`network` AS `gnetwork`, `gcontact`.`addr` AS `gaddr`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = ? $sql_extra AND `intro`.`blocked` = 0
			LIMIT ?, ?",
			$_SESSION['uid'],
			$start,
			$limit
		);
		if (DBA::isResult($stmtNotifies)) {
			$notifs = $this->formatIntros(DBA::toArray($stmtNotifies));
		}

		$arr = [
			'ident' => $ident,
			'notifications' => $notifs,
		];

		return $arr;
	}

	/**
	 * @brief Format the notification query in an usable array
	 *
	 * @param array $intros The array from the db query
	 * @return array with the introductions
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function formatIntros($intros)
	{
		$knowyou = '';

		$arr = [];

		foreach ($intros as $it) {
			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			// Contact suggestions
			if ($it['fid']) {
				$return_addr = bin2hex(self::getApp()->user['nickname'] . '@' . self::getApp()->getHostName() . ((self::getApp()->getURLPath()) ? '/' . self::getApp()->getURLPath() : ''));

				$intro = [
					'label' => 'friend_suggestion',
					'notify_type' => L10n::t('Friend Suggestion'),
					'intro_id' => $it['intro_id'],
					'madeby' => $it['name'],
					'madeby_url' => $it['url'],
					'madeby_zrl' => Contact::magicLink($it['url']),
					'madeby_addr' => $it['addr'],
					'contact_id' => $it['contact-id'],
					'photo' => (!empty($it['fphoto']) ? ProxyUtils::proxifyUrl($it['fphoto'], false, ProxyUtils::SIZE_SMALL) : "images/person-300.jpg"),
					'name' => $it['fname'],
					'url' => $it['furl'],
					'zrl' => Contact::magicLink($it['furl']),
					'hidden' => $it['hidden'] == 1,
					'post_newfriend' => (intval(PConfig::get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
					'knowyou' => $knowyou,
					'note' => $it['note'],
					'request' => $it['frequest'] . '?addr=' . $return_addr,
				];

				// Normal connection requests
			} else {
				$it = $this->getMissingIntroData($it);

				if (empty($it['url'])) {
					continue;
				}

				// Don't show these data until you are connected. Diaspora is doing the same.
				if ($it['gnetwork'] === Protocol::DIASPORA) {
					$it['glocation'] = "";
					$it['gabout'] = "";
					$it['ggender'] = "";
				}
				$intro = [
					'label' => (($it['network'] !== Protocol::OSTATUS) ? 'friend_request' : 'follower'),
					'notify_type' => (($it['network'] !== Protocol::OSTATUS) ? L10n::t('Friend/Connect Request') : L10n::t('New Follower')),
					'dfrn_id' => $it['issued-id'],
					'uid' => $_SESSION['uid'],
					'intro_id' => $it['intro_id'],
					'contact_id' => $it['contact-id'],
					'photo' => (!empty($it['photo']) ? ProxyUtils::proxifyUrl($it['photo'], false, ProxyUtils::SIZE_SMALL) : "images/person-300.jpg"),
					'name' => $it['name'],
					'location' => BBCode::convert($it['glocation'], false),
					'about' => BBCode::convert($it['gabout'], false),
					'keywords' => $it['gkeywords'],
					'gender' => $it['ggender'],
					'hidden' => $it['hidden'] == 1,
					'post_newfriend' => (intval(PConfig::get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
					'url' => $it['url'],
					'zrl' => Contact::magicLink($it['url']),
					'addr' => $it['gaddr'],
					'network' => $it['gnetwork'],
					'knowyou' => $it['knowyou'],
					'note' => $it['note'],
				];
			}

			$arr[] = $intro;
		}

		return $arr;
	}

	/**
	 * @brief Check for missing contact data and try to fetch the data from
	 *     from other sources
	 *
	 * @param array $arr The input array with the intro data
	 *
	 * @return array The array with the intro data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getMissingIntroData($arr)
	{
		// If the network and the addr isn't available from the gcontact
		// table entry, take the one of the contact table entry
		if (empty($arr['gnetwork']) && !empty($arr['network'])) {
			$arr['gnetwork'] = $arr['network'];
		}
		if (empty($arr['gaddr']) && !empty($arr['addr'])) {
			$arr['gaddr'] = $arr['addr'];
		}

		// If the network and addr is still not available
		// get the missing data data from other sources
		if (empty($arr['gnetwork']) || empty($arr['gaddr'])) {
			$ret = Contact::getDetailsByURL($arr['url']);

			if (empty($arr['gnetwork']) && !empty($ret['network'])) {
				$arr['gnetwork'] = $ret['network'];
			}
			if (empty($arr['gaddr']) && !empty($ret['addr'])) {
				$arr['gaddr'] = $ret['addr'];
			}
		}

		return $arr;
	}
}
