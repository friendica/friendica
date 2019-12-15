<?php

namespace Friendica\Model;

use Exception;
use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Config\PConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Temporal;
use Friendica\Util\XML;
use ImagickException;
use Psr\Log\LoggerInterface;
use Friendica\Network\HTTPException;

/**
 * @brief Methods for read and write notifications from/to database
 *  or for formatting notifications
 */
final class Notify
{
	/** @var int The default limit of notifies per page */
	const DEFAULT_PAGE_LIMIT = 80;

	const NETWORK  = 'network';
	const SYSTEM   = 'system';
	const PERSONAL = 'personal';
	const HOME     = 'home';
	const INTRO    = 'intro';

	/** @var array Array of URL parameters */
	const URL_TYPES = [
		self::NETWORK  => 'network',
		self::SYSTEM   => 'system',
		self::HOME     => 'home',
		self::PERSONAL => 'personal',
		self::INTRO    => 'intros',
	];

	/** @var array Array of the allowed notifies and their printable name */
	const PRINT_TYPES = [
		self::NETWORK  => 'Network',
		self::SYSTEM   => 'System',
		self::HOME     => 'Home',
		self::PERSONAL => 'Personal',
		self::INTRO    => 'Introductions',
	];

	/** @var array The array of access keys for notify pages */
	const ACCESS_KEYS = [
		self::NETWORK  => 'w',
		self::SYSTEM   => 'y',
		self::HOME     => 'h',
		self::PERSONAL => 'r',
		self::INTRO    => 'i',
	];

	/** @var Database */
	private $dba;
	/** @var L10n */
	private $l10n;
	/** @var App\Arguments */
	private $args;
	/** @var App\BaseURL */
	private $baseUrl;
	/** @var PConfiguration */
	private $pConfig;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(Database $dba, L10n $l10n, App\Arguments $args, App\BaseURL $baseUrl,
	                            PConfiguration $pConfig, LoggerInterface $logger)
	{
		$this->dba     = $dba;
		$this->l10n    = $l10n;
		$this->args    = $args;
		$this->baseUrl = $baseUrl;
		$this->pConfig = $pConfig;
		$this->logger  = $logger;
	}

	/**
	 * Set some extra properties to note array from db:
	 *  - timestamp as int in default TZ
	 *  - date_rel : relative date string
	 *  - msg_html: message as html string
	 *  - msg_plain: message as plain text string
	 *
	 * @param array $notes array of note arrays from db
	 *
	 * @return array Copy of input array with added properties
	 *
	 * @throws Exception
	 */
	private function setExtra(array $notes)
	{
		$retNotes = [];
		foreach ($notes as $note) {
			$local_time        = DateTimeFormat::local($note['date']);
			$note['timestamp'] = strtotime($local_time);
			$note['date_rel']  = Temporal::getRelativeDate($note['date']);
			$note['msg_html']  = BBCode::convert($note['msg'], false);
			$note['msg_plain'] = explode("\n", trim(HTML::toPlaintext($note['msg_html'], 0)))[0];

			$retNotes[] = $note;
		}
		return $retNotes;
	}

	/**
	 * Get all notifications for local_user()
	 *
	 * @param array  $filter optional Array "column name"=>value: filter query by columns values
	 * @param array  $order  optional Array to order by
	 * @param string $limit  optional Query limits
	 *
	 * @return array|bool of results or false on errors
	 * @throws Exception
	 */
	public function getAll(array $filter = [], array $order = ['date' => 'DESC'], string $limit = "")
	{
		$params = [];

		$params['order'] = $order;

		if (!empty($limit)) {
			$params['limit'] = $limit;
		}

		$dbFilter = array_merge($filter, ['uid' => local_user()]);

		$stmtNotifies = $this->dba->select('notify', [], $dbFilter, $params);

		if ($this->dba->isResult($stmtNotifies)) {
			return $this->setExtra($this->dba->toArray($stmtNotifies));
		}

		return false;
	}

	/**
	 * Get one note for local_user() by $id value
	 *
	 * @param int $id identity
	 *
	 * @return array note values or null if not found
	 * @throws Exception
	 */
	public function getByID(int $id)
	{
		$stmtNotify = $this->dba->selectFirst('notify', [], ['id' => $id, 'uid' => local_user()]);
		if ($this->dba->isResult($stmtNotify)) {
			return $this->setExtra([$stmtNotify])[0];
		}
		return null;
	}

	/**
	 * @brief set seen state of $note of local_user()
	 *
	 * @param array $note note array
	 * @param bool  $seen optional true or false, default true
	 *
	 * @return bool true on success, false on errors
	 * @throws Exception
	 */
	public function setSeen(array $note, bool $seen = true)
	{
		return $this->dba->update('notify', ['seen' => $seen], [
			'(`link` = ? OR (`parent` != 0 AND `parent` = ? AND `otype` = ?)) AND `uid` = ?',
			$note['link'],
			$note['parent'],
			$note['otype'],
			local_user()
		]);
	}

	/**
	 * Set seen state of all notifications of local_user()
	 *
	 * @param bool $seen optional true or false. default true
	 *
	 * @return bool true on success, false on error
	 * @throws Exception
	 */
	public function setAllSeen(bool $seen = true)
	{
		return $this->dba->update('notify', ['seen' => $seen], ['uid' => local_user()]);
	}

	/**
	 * @brief List of pages for the Notifications TabBar
	 *
	 * @return array with with notifications TabBar data
	 * @throws Exception
	 */
	public function getTabs()
	{
		$selected = $this->args->get(1, '');

		$tabs = [];

		foreach (self::URL_TYPES as $type => $url) {
			$tabs[] = [
				'label'     => $this->l10n->t(self::PRINT_TYPES[$type]),
				'url'       => 'notifications/' . $url,
				'sel'       => (($selected == $url) ? 'active' : ''),
				'id'        => $type . '-tab',
				'accesskey' => self::ACCESS_KEYS[$type],
			];
		}

		return $tabs;
	}

	/**
	 * Format the notification query in an usable array
	 *
	 * @param array  $notifies The array from the db query
	 * @param string $ident    The notifications identifier (e.g. network)
	 *
	 * @return array
	 *                       string 'label' => The type of the notification
	 *                       string 'link' => URL to the source
	 *                       string 'image' => The avatar image
	 *                       string 'url' => The profile url of the contact
	 *                       string 'text' => The notification text
	 *                       string 'when' => The date of the notification
	 *                       string 'ago' => T relative date of the notification
	 *                       bool 'seen' => Is the notification marked as "seen"
	 * @throws Exception
	 */
	private function formatList(array $notifies, string $ident = "")
	{
		$formattedNotifies = [];

		foreach ($notifies as $notify) {
			// Because we use different db tables for the notification query
			// we have sometimes $notify['unseen'] and sometimes $notify['seen].
			// So we will have to transform $notify['unseen']
			if (array_key_exists('unseen', $notify)) {
				$notify['seen'] = ($notify['unseen'] > 0 ? false : true);
			}

			// For feed items we use the user's contact, since the avatar is mostly self choosen.
			if (!empty($notify['network']) && $notify['network'] == Protocol::FEED) {
				$notify['author-avatar'] = $notify['contact-avatar'];
			}

			// Depending on the identifier of the notification we need to use different defaults
			switch ($ident) {
				case self::SYSTEM:
					$default_item_label = 'notify';
					$default_item_link  = $this->baseUrl->get(true) . '/notify/view/' . $notify['id'];
					$default_item_image = ProxyUtils::proxifyUrl($notify['photo'], false, ProxyUtils::SIZE_MICRO);
					$default_item_url   = $notify['url'];
					$default_item_text  = strip_tags(BBCode::convert($notify['msg']));
					$default_item_when  = DateTimeFormat::local($notify['date'], 'r');
					$default_item_ago   = Temporal::getRelativeDate($notify['date']);
					break;

				case self::HOME:
					$default_item_label = 'comment';
					$default_item_link  = $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'];
					$default_item_image = ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO);
					$default_item_url   = $notify['author-link'];
					$default_item_text  = $this->l10n->t("%s commented on %s's post", $notify['author-name'], $notify['parent-author-name']);
					$default_item_when  = DateTimeFormat::local($notify['created'], 'r');
					$default_item_ago   = Temporal::getRelativeDate($notify['created']);
					break;

				default:
					$default_item_label = (($notify['id'] == $notify['parent']) ? 'post' : 'comment');
					$default_item_link  = $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'];
					$default_item_image = ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO);
					$default_item_url   = $notify['author-link'];
					$default_item_text  = (($notify['id'] == $notify['parent'])
						? $this->l10n->t("%s created a new post", $notify['author-name'])
						: $this->l10n->t("%s commented on %s's post", $notify['author-name'], $notify['parent-author-name']));
					$default_item_when  = DateTimeFormat::local($notify['created'], 'r');
					$default_item_ago   = Temporal::getRelativeDate($notify['created']);
			}

			// Transform the different types of notification in an usable array
			switch ($notify['verb']) {
				case Activity::LIKE:
					$formattedNotify = [
						'label' => 'like',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s liked %s's post", $notify['author-name'], $notify['parent-author-name']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				case Activity::DISLIKE:
					$formattedNotify = [
						'label' => 'dislike',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s disliked %s's post", $notify['author-name'], $notify['parent-author-name']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				case Activity::ATTEND:
					$formattedNotify = [
						'label' => 'attend',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s is attending %s's event", $notify['author-name'], $notify['parent-author-name']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				case Activity::ATTENDNO:
					$formattedNotify = [
						'label' => 'attendno',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s is not attending %s's event", $notify['author-name'], $notify['parent-author-name']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				case Activity::ATTENDMAYBE:
					$formattedNotify = [
						'label' => 'attendmaybe',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s may attend %s's event", $notify['author-name'], $notify['parent-author-name']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				case Activity::FRIEND:
					if (!isset($notify['object'])) {
						$formattedNotify = [
							'label' => 'friend',
							'link'  => $default_item_link,
							'image' => $default_item_image,
							'url'   => $default_item_url,
							'text'  => $default_item_text,
							'when'  => $default_item_when,
							'ago'   => $default_item_ago,
							'seen'  => $notify['seen']
						];
						break;
					}
					/// @todo Check if this part here is used at all
					$this->logger->info('Complete data.', ['notify' => $notify, 'callStack' => System::callstack(20)]);

					$xmlHead         = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
					$obj             = XML::parseString($xmlHead . $notify['object']);
					$notify['fname'] = $obj->title;

					$formattedNotify = [
						'label' => 'friend',
						'link'  => $this->baseUrl->get(true) . '/display/' . $notify['parent-guid'],
						'image' => ProxyUtils::proxifyUrl($notify['author-avatar'], false, ProxyUtils::SIZE_MICRO),
						'url'   => $notify['author-link'],
						'text'  => $this->l10n->t("%s is now friends with %s", $notify['author-name'], $notify['fname']),
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
					break;

				default:
					$formattedNotify = [
						'label' => $default_item_label,
						'link'  => $default_item_link,
						'image' => $default_item_image,
						'url'   => $default_item_url,
						'text'  => $default_item_text,
						'when'  => $default_item_when,
						'ago'   => $default_item_ago,
						'seen'  => $notify['seen']
					];
			}

			$formattedNotifies[] = $formattedNotify;
		}

		return $formattedNotifies;
	}

	/**
	 * Get network notifications
	 *
	 * @param bool $seen    False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array [string, array]
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Network notifications
	 *
	 * @throws Exception
	 */
	public function getNetworkList(bool $seen = false, int $start = 0, int $limit = self::DEFAULT_PAGE_LIMIT)
	{
		$ident    = self::NETWORK;
		$notifies = [];

		$condition = ['wall' => false, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if ($this->dba->isResult($items)) {
			$notifies = $this->formatList(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifies,
			'ident'         => $ident,
		];

		return $arr;
	}

	/**
	 * Get system notifications
	 *
	 * @param bool $seen    False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array [string, array]
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => System notifications
	 *
	 * @throws Exception
	 */
	public function getSystemList(bool $seen = false, int $start = 0, int $limit = self::DEFAULT_PAGE_LIMIT)
	{
		$ident    = self::SYSTEM;
		$notifies = [];

		$filter = ['uid' => local_user()];
		if (!$seen) {
			$filter['seen'] = false;
		}

		$params          = [];
		$params['order'] = ['date' => 'DESC'];
		$params['limit'] = [$start, $limit];

		$stmtNotifies = $this->dba->select('notify',
			['id', 'url', 'photo', 'msg', 'date', 'seen', 'verb'],
			$filter,
			$params);

		if ($this->dba->isResult($stmtNotifies)) {
			$notifies = $this->formatList($this->dba->toArray($stmtNotifies), $ident);
		}

		$arr = [
			'notifications' => $notifies,
			'ident'         => $ident,
		];

		return $arr;
	}

	/**
	 * Get personal notifications
	 *
	 * @param bool $seen    False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array [string, array]
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Personal notifications
	 *
	 * @throws Exception
	 */
	public function getPersonalList(bool $seen = false, int $start = 0, int $limit = self::DEFAULT_PAGE_LIMIT)
	{
		$ident    = self::PERSONAL;
		$notifies = [];

		$myurl     = str_replace('http://', '', DI::app()->contact['nurl']);
		$diasp_url = str_replace('/profile/', '/u/', $myurl);

		$condition = ["NOT `wall` AND `uid` = ? AND (`item`.`author-id` = ? OR `item`.`tag` REGEXP ? OR `item`.`tag` REGEXP ?)",
			local_user(), public_contact(), $myurl . '\\]', $diasp_url . '\\]'];

		if (!$seen) {
			$condition[0] .= " AND `unseen`";
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if ($this->dba->isResult($items)) {
			$notifies = $this->formatList(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifies,
			'ident'         => $ident,
		];

		return $arr;
	}

	/**
	 * @brief Get home notifications
	 *
	 * @param bool $seen    False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int        $start   Start the query at this point
	 * @param int        $limit   Maximum number of query results
	 *
	 * @return array [string, array]
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Home notifications
	 *
	 * @throws Exception
	 */
	public function getHomeList(bool $seen = false, int $start = 0, int $limit = self::DEFAULT_PAGE_LIMIT)
	{
		$ident    = self::HOME;
		$notifies = [];

		$condition = ['wall' => true, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$items = Item::selectForUser(local_user(), $fields, $condition, $params);

		if ($this->dba->isResult($items)) {
			$notifies = $this->formatList(Item::inArray($items), $ident);
		}

		$arr = [
			'notifications' => $notifies,
			'ident'         => $ident,
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
	 * @param int  $id      When set, only the introduction with this id is displayed
	 *
	 * @return array [string, array]
	 *    string 'ident' => Notification identifier
	 *    array 'notifications' => Introductions
	 *
	 * @throws ImagickException
	 * @throws Exception
	 */
	public function getIntroList(bool $all = false, int $start = 0, int $limit = self::DEFAULT_PAGE_LIMIT, int $id = 0)
	{
		/// @todo sanitize wording according to SELF::INTRO
		$ident     = 'introductions';
		$notifies  = [];
		$sql_extra = "";

		if (empty($id)) {
			if (!$all) {
				$sql_extra = " AND NOT `ignore` ";
			}

			$sql_extra .= " AND NOT `intro`.`blocked` ";
		} else {
			$sql_extra = sprintf(" AND `intro`.`id` = %d ", intval($id));
		}

		/// @todo Fetch contact details by "Contact::getDetailsByUrl" instead of queries to contact, fcontact and gcontact
		$stmtNotifies = $this->dba->p(
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
			WHERE `intro`.`uid` = ? $sql_extra
			LIMIT ?, ?",
			$_SESSION['uid'],
			$start,
			$limit
		);
		if ($this->dba->isResult($stmtNotifies)) {
			$notifies = $this->formatIntroList($this->dba->toArray($stmtNotifies));
		}

		$arr = [
			'ident'         => $ident,
			'notifications' => $notifies,
		];

		return $arr;
	}

	/**
	 * @brief Format the notification query in an usable array
	 *
	 * @param array $intros The array from the db query
	 *
	 * @return array with the introductions
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException
	 */
	private function formatIntroList(array $intros)
	{
		$knowyou = '';

		$formattedIntros = [];

		foreach ($intros as $intro) {
			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			// Contact suggestions
			if ($intro['fid']) {
				$return_addr = bin2hex(DI::app()->user['nickname'] . '@' .
				                       $this->baseUrl->getHostName() .
				                       (($this->baseUrl->getURLPath()) ? '/' . $this->baseUrl->getURLPath() : ''));

				$intro = [
					'label'          => 'friend_suggestion',
					'notify_type'    => $this->l10n->t('Friend Suggestion'),
					'intro_id'       => $intro['intro_id'],
					'madeby'         => $intro['name'],
					'madeby_url'     => $intro['url'],
					'madeby_zrl'     => Contact::magicLink($intro['url']),
					'madeby_addr'    => $intro['addr'],
					'contact_id'     => $intro['contact-id'],
					'photo'          => (!empty($intro['fphoto']) ? ProxyUtils::proxifyUrl($intro['fphoto'], false, ProxyUtils::SIZE_SMALL) : "images/person-300.jpg"),
					'name'           => $intro['fname'],
					'url'            => $intro['furl'],
					'zrl'            => Contact::magicLink($intro['furl']),
					'hidden'         => $intro['hidden'] == 1,
					'post_newfriend' => (intval($this->pConfig->get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
					'knowyou'        => $knowyou,
					'note'           => $intro['note'],
					'request'        => $intro['frequest'] . '?addr=' . $return_addr,
				];

				// Normal connection requests
			} else {
				$intro = $this->getMissingIntroData($intro);

				if (empty($intro['url'])) {
					continue;
				}

				// Don't show these data until you are connected. Diaspora is doing the same.
				if ($intro['gnetwork'] === Protocol::DIASPORA) {
					$intro['glocation'] = "";
					$intro['gabout']    = "";
					$intro['ggender']   = "";
				}
				$intro = [
					'label'          => (($intro['network'] !== Protocol::OSTATUS) ? 'friend_request' : 'follower'),
					'notify_type'    => (($intro['network'] !== Protocol::OSTATUS) ? $this->l10n->t('Friend/Connect Request') : $this->l10n->t('New Follower')),
					'dfrn_id'        => $intro['issued-id'],
					'uid'            => $_SESSION['uid'],
					'intro_id'       => $intro['intro_id'],
					'contact_id'     => $intro['contact-id'],
					'photo'          => (!empty($intro['photo']) ? ProxyUtils::proxifyUrl($intro['photo'], false, ProxyUtils::SIZE_SMALL) : "images/person-300.jpg"),
					'name'           => $intro['name'],
					'location'       => BBCode::convert($intro['glocation'], false),
					'about'          => BBCode::convert($intro['gabout'], false),
					'keywords'       => $intro['gkeywords'],
					'gender'         => $intro['ggender'],
					'hidden'         => $intro['hidden'] == 1,
					'post_newfriend' => (intval($this->pConfig->get(local_user(), 'system', 'post_newfriend')) ? '1' : 0),
					'url'            => $intro['url'],
					'zrl'            => Contact::magicLink($intro['url']),
					'addr'           => $intro['gaddr'],
					'network'        => $intro['gnetwork'],
					'knowyou'        => $intro['knowyou'],
					'note'           => $intro['note'],
				];
			}

			$formattedIntros[] = $intro;
		}

		return $formattedIntros;
	}

	/**
	 * @brief Check for missing contact data and try to fetch the data from
	 *     from other sources
	 *
	 * @param array $intro The input array with the intro data
	 *
	 * @return array The array with the intro data
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function getMissingIntroData(array $intro)
	{
		// If the network and the addr isn't available from the gcontact
		// table entry, take the one of the contact table entry
		if (empty($intro['gnetwork']) && !empty($intro['network'])) {
			$intro['gnetwork'] = $intro['network'];
		}
		if (empty($intro['gaddr']) && !empty($intro['addr'])) {
			$intro['gaddr'] = $intro['addr'];
		}

		// If the network and addr is still not available
		// get the missing data data from other sources
		if (empty($intro['gnetwork']) || empty($intro['gaddr'])) {
			$ret = Contact::getDetailsByURL($intro['url']);

			if (empty($intro['gnetwork']) && !empty($ret['network'])) {
				$intro['gnetwork'] = $ret['network'];
			}
			if (empty($intro['gaddr']) && !empty($ret['addr'])) {
				$intro['gaddr'] = $ret['addr'];
			}
		}

		return $intro;
	}
}
