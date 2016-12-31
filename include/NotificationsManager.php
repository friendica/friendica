<?php
/**
 * @file include/NotificationsManager.php
 * @brief Methods for read and write notifications from/to database
 *  or for formatting notifications
 */
require_once('include/html2plain.php');
require_once("include/datetime.php");
require_once("include/bbcode.php");

/**
 * @brief Methods for read and write notifications from/to database
 *  or for formatting notifications
 */
class NotificationsManager {
	private $a;

	public function __construct() {
		$this->a = get_app();
	}

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
	 */
	private function _set_extra($notes) {
		$rets = array();
		foreach($notes as $n) {
			$local_time = datetime_convert('UTC',date_default_timezone_get(),$n['date']);
			$n['timestamp'] = strtotime($local_time);
			$n['date_rel'] = relative_date($n['date']);
				$n['msg_html'] = bbcode($n['msg'], false, false, false, false);
				$n['msg_plain'] = explode("\n",trim(html2plain($n['msg_html'], 0)))[0];

			$rets[] = $n;
		}
		return $rets;
	}


	/**
	 * @brief Get all notifications for local_user()
	 *
	 * @param array $filter optional Array "column name"=>value: filter query by columns values
	 * @param string $order optional Space separated list of column to sort by. prepend name with "+" to sort ASC, "-" to sort DESC. Default to "-date"
	 * @param string $limit optional Query limits
	 *
	 * @return array of results or false on errors
	 */
	public function getAll($filter = array(), $order="-date", $limit="") {
		$filter_str = array();
		$filter_sql = "";
		foreach($filter as $column => $value) {
			$filter_str[] = sprintf("`%s` = '%s'", $column, dbesc($value));
		}
		if (count($filter_str)>0) {
			$filter_sql = "AND ".implode(" AND ", $filter_str);
		}

		$aOrder = explode(" ", $order);
		$asOrder = array();
		foreach($aOrder as $o) {
			$dir = "asc";
			if ($o[0]==="-") {
				$dir = "desc";
				$o = substr($o,1);
			}
			if ($o[0]==="+") {
				$dir = "asc";
				$o = substr($o,1);
			}
			$asOrder[] = "$o $dir";
		}
		$order_sql = implode(", ", $asOrder);

		if($limit!="")
			$limit = " LIMIT ".$limit;

			$r = q("SELECT * FROM `notify` WHERE `uid` = %d $filter_sql ORDER BY $order_sql $limit",
				intval(local_user())
			);

		if (dbm::is_result($r))
			return $this->_set_extra($r);

		return false;
	}

	/**
	 * @brief Get one note for local_user() by $id value
	 *
	 * @param int $id
	 * @return array note values or null if not found
	 */
	public function getByID($id) {
		$r = q("SELECT * FROM `notify` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($id),
			intval(local_user())
		);
		if (dbm::is_result($r)) {
			return $this->_set_extra($r)[0];
		}
		return null;
	}

	/**
	 * @brief set seen state of $note of local_user()
	 *
	 * @param array $note
	 * @param bool $seen optional true or false, default true
	 * @return bool true on success, false on errors
	 */
	public function setSeen($note, $seen = true) {
		return q("UPDATE `notify` SET `seen` = %d WHERE ( `link` = '%s' OR ( `parent` != 0 AND `parent` = %d AND `otype` = '%s' )) AND `uid` = %d",
			intval($seen),
			dbesc($note['link']),
			intval($note['parent']),
			dbesc($note['otype']),
			intval(local_user())
		);
	}

	/**
	 * @brief set seen state of all notifications of local_user()
	 *
	 * @param bool $seen optional true or false. default true
	 * @return bool true on success, false on error
	 */
	public function setAllSeen($seen = true) {
		return q("UPDATE `notify` SET `seen` = %d WHERE `uid` = %d",
			intval($seen),
			intval(local_user())
		);
	}

	/**
	 * @brief List of pages for the Notifications TabBar
	 * 
	 * @param app $a The 
	 * @return array with with notifications TabBar data
	 */
	public function getTabs() {
		$tabs = array(
			array(
				'label' => t('System'),
				'url'=>'notifications/system',
				'sel'=> (($this->a->argv[1] == 'system') ? 'active' : ''),
				'id' => 'system-tab',
				'accesskey' => 'y',
			),
			array(
				'label' => t('Network'),
				'url'=>'notifications/network',
				'sel'=> (($this->a->argv[1] == 'network') ? 'active' : ''),
				'id' => 'network-tab',
				'accesskey' => 'w',
			),
			array(
				'label' => t('Personal'),
				'url'=>'notifications/personal',
				'sel'=> (($this->a->argv[1] == 'personal') ? 'active' : ''),
				'id' => 'personal-tab',
				'accesskey' => 'r',
			),
			array(
				'label' => t('Home'),
				'url' => 'notifications/home',
				'sel'=> (($this->a->argv[1] == 'home') ? 'active' : ''),
				'id' => 'home-tab',
				'accesskey' => 'h',
			),
			array(
				'label' => t('Introductions'),
				'url' => 'notifications/intros',
				'sel'=> (($this->a->argv[1] == 'intros') ? 'active' : ''),
				'id' => 'intro-tab',
				'accesskey' => 'i',
			),
		);

		return $tabs;
	}

	/**
	 * @brief Format the notification query in an usable array
	 * 
	 * @param array $notifs The array from the db query
	 * @param string $ident The notifications identifier (e.g. network)
	 * @return array
	 *	string 'label' => The type of the notification
	 *	string 'link' => URL to the source
	 *	string 'image' => The avatar image
	 *	string 'url' => The profile url of the contact
	 *	string 'text' => The notification text
	 *	string 'when' => The date of the notification
	 *	string 'ago' => T relative date of the notification
	 *	bool 'seen' => Is the notification marked as "seen"
	 */
	private function formatNotifs($notifs, $ident = "") {

		$notif = array();
		$arr = array();

		if (dbm::is_result($notifs)) {

			foreach ($notifs as $it) {
				// Because we use different db tables for the notification query
				// we have sometimes $it['unseen'] and sometimes $it['seen].
				// So we will have to transform $it['unseen']
				if (array_key_exists('unseen', $it)) {
					$it['seen'] = ($it['unseen'] > 0 ? false : true);
				}

				// Depending on the identifier of the notification we need to use different defaults
				switch ($ident) {
					case 'system':
						$default_item_label = 'notify';
						$default_item_link = $this->a->get_baseurl(true).'/notify/view/'. $it['id'];
						$default_item_image = proxy_url($it['photo'], false, PROXY_SIZE_MICRO);
						$default_item_url = $it['url'];
						$default_item_text = strip_tags(bbcode($it['msg']));
						$default_item_when = datetime_convert('UTC', date_default_timezone_get(), $it['date'], 'r');
						$default_item_ago = relative_date($it['date']);
						break;

					case 'home':
						$default_item_label = 'comment';
						$default_item_link = $this->a->get_baseurl(true).'/display/'.$it['pguid'];
						$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
						$default_item_url = $it['author-link'];
						$default_item_text = sprintf(t("%s commented on %s's post"), $it['author-name'], $it['pname']);
						$default_item_when = datetime_convert('UTC', date_default_timezone_get(), $it['created'], 'r');
						$default_item_ago = relative_date($it['created']);
						break;

					default:
						$default_item_label = (($it['id'] == $it['parent']) ? 'post' : 'comment');
						$default_item_link = $this->a->get_baseurl(true).'/display/'.$it['pguid'];
						$default_item_image = proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO);
						$default_item_url = $it['author-link'];
						$default_item_text = (($it['id'] == $it['parent'])
									? sprintf(t("%s created a new post"), $it['author-name'])
									: sprintf(t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$default_item_when = datetime_convert('UTC', date_default_timezone_get(), $it['created'], 'r');
						$default_item_ago = relative_date($it['created']);

				}

				// Transform the different types of notification in an usable array
				switch ($it['verb']){
					case ACTIVITY_LIKE:
						$notif = array(
							'label' => 'like',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf(t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_DISLIKE:
						$notif = array(
							'label' => 'dislike',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf(t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_ATTEND:
						$notif = array(
							'label' => 'attend',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf(t("%s is attending %s's event"), $it['author-name'], $it['pname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_ATTENDNO:
						$notif = array(
							'label' => 'attendno',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf( t("%s is not attending %s's event"), $it['author-name'], $it['pname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_ATTENDMAYBE:
						$notif = array(
							'label' => 'attendmaybe',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf(t("%s may attend %s's event"), $it['author-name'], $it['pname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					case ACTIVITY_FRIEND:
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;

						$notif = array(
							'label' => 'friend',
							'link' => $this->a->get_baseurl(true).'/display/'.$it['pguid'],
							'image' => proxy_url($it['author-avatar'], false, PROXY_SIZE_MICRO),
							'url' => $it['author-link'],
							'text' => sprintf(t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
						break;

					default:
						$notif = array(
							'label' => $default_item_label,
							'link' => $default_item_link,
							'image' => $default_item_image,
							'url' => $default_item_url,
							'text' => $default_item_text,
							'when' => $default_item_when,
							'ago' => $default_item_ago,
							'seen' => $it['seen']
						);
				}

				$arr[] = $notif;
			}
		}

		return $arr;

	}

	/**
	 * @brief Total number of network notifications 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @return int Number of network notifications
	 */
	private function networkTotal($seen = 0) {
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total`
				FROM `item` INNER JOIN `item` AS `pitem` ON `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1 AND `pitem`.`parent` != 0 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0
				$sql_seen",
			intval(local_user())
		);

		if (dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	/**
	 * @brief Get network notifications
	 * 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @param int $start Start the query at this point
	 * @param int $limit Maximum number of query results
	 * 
	 * @return array with
	 *	string 'ident' => Notification identifier
	 *	int 'total' => Total number of available network notifications
	 *	array 'notifications' => Network notifications
	 */
	public function networkNotifs($seen = 0, $start = 0, $limit = 80) {
		$ident = 'network';
		$total = $this->networkTotal($seen);
		$notifs = array();
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";


		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` AS `object`,
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`, `pitem`.`guid` AS `pguid`
			FROM `item` INNER JOIN `item` AS `pitem` ON `pitem`.`id`=`item`.`parent`
			WHERE `item`.`visible` = 1 AND `pitem`.`parent` != 0 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0
				$sql_seen
			ORDER BY `item`.`created` DESC LIMIT %d, %d ",
				intval(local_user()),
				intval($start),
				intval($limit)
		);

		if (dbm::is_result($r))
			$notifs = $this->formatNotifs($r, $ident);

		$arr = array (
			'notifications' => $notifs,
			'ident' => $ident,
			'total' => $total,
		);

		return $arr;
	}

	/**
	 * @brief Total number of system notifications 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @return int Number of system notifications
	 */
	private function systemTotal($seen = 0) {
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `seen` = 0 ";

		$r = q("SELECT COUNT(*) AS `total` FROM `notify` WHERE `uid` = %d $sql_seen",
			intval(local_user())
		);

		if (dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	/**
	 * @brief Get system notifications
	 * 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @param int $start Start the query at this point
	 * @param int $limit Maximum number of query results
	 * 
	 * @return array with
	 *	string 'ident' => Notification identifier
	 *	int 'total' => Total number of available system notifications
	 *	array 'notifications' => System notifications
	 */
	public function systemNotifs($seen = 0, $start = 0, $limit = 80) {
		$ident = 'system';
		$total = $this->systemTotal($seen);
		$notifs = array();
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `seen` = 0 ";

		$r = q("SELECT `id`, `url`, `photo`, `msg`, `date`, `seen` FROM `notify`
				WHERE `uid` = %d $sql_seen ORDER BY `date` DESC LIMIT %d, %d ",
			intval(local_user()),
			intval($start),
			intval($limit)
		);

		if (dbm::is_result($r))
			$notifs = $this->formatNotifs($r, $ident);

		$arr = array (
			'notifications' => $notifs,
			'ident' => $ident,
			'total' => $total,
		);

		return $arr;
	}

	/**
	 * @brief Addional SQL query string for the personal notifications
	 * 
	 * @return string The additional sql query
	 */
	private function _personal_sql_extra() {
		$myurl = $this->a->get_baseurl(true) . '/profile/'. $this->a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);
		$sql_extra = sprintf(" AND ( `item`.`author-link` regexp '%s' or `item`.`tag` regexp '%s' or `item`.`tag` regexp '%s' ) ",
			dbesc($myurl . '$'),
			dbesc($myurl . '\\]'),
			dbesc($diasp_url . '\\]')
		);

		return $sql_extra;
	}

	/**
	 * @brief Total number of personal notifications 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @return int Number of personal notifications
	 */
	private function personalTotal($seen = 0) {
		$sql_seen = "";
		$sql_extra = $this->_personal_sql_extra();

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total`
				FROM `item` INNER JOIN `item` AS `pitem` ON  `pitem`.`id`=`item`.`parent`
				WHERE `item`.`visible` = 1
				$sql_extra
				$sql_seen
				AND `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 " ,
			intval(local_user())
		);

		if (dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	/**
	 * @brief Get personal notifications
	 * 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @param int $start Start the query at this point
	 * @param int $limit Maximum number of query results
	 * 
	 * @return array with
	 *	string 'ident' => Notification identifier
	 *	int 'total' => Total number of available personal notifications
	 *	array 'notifications' => Personal notifications
	 */
	public function personalNotifs($seen = 0, $start = 0, $limit = 80) {
		$ident = 'personal';
		$total = $this->personalTotal($seen);
		$sql_extra = $this->_personal_sql_extra();
		$notifs = array();
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` AS `object`, 
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`, `pitem`.`guid` AS `pguid` 
			FROM `item` INNER JOIN `item` AS `pitem` ON  `pitem`.`id`=`item`.`parent`
			WHERE `item`.`visible` = 1
				$sql_extra
				$sql_seen
				AND `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 0 
			ORDER BY `item`.`created` DESC LIMIT %d, %d " ,
				intval(local_user()),
				intval($start),
				intval($limit)
		);

		if (dbm::is_result($r))
			$notifs = $this->formatNotifs($r, $ident);
		
		$arr = array (
			'notifications' => $notifs,
			'ident' => $ident,
			'total' => $total,
		);

		return $arr;
	}

	/**
	 * @brief Total number of home notifications 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @return int Number of home notifications
	 */
	private function homeTotal($seen = 0) {
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT COUNT(*) AS `total` FROM `item`
				WHERE `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1
				$sql_seen",
			intval(local_user())
		);

		if (dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	/**
	 * @brief Get home notifications
	 * 
	 * @param int|string $seen
	 *	If 0 only include notifications into the query
	 *	which aren't marked as "seen"
	 * @param int $start Start the query at this point
	 * @param int $limit Maximum number of query results
	 * 
	 * @return array with
	 *	string 'ident' => Notification identifier
	 *	int 'total' => Total number of available home notifications
	 *	array 'notifications' => Home notifications
	 */
	public function homeNotifs($seen = 0, $start = 0, $limit = 80) {
		$ident = 'home';
		$total = $this->homeTotal($seen);
		$notifs = array();
		$sql_seen = "";

		if($seen === 0)
			$sql_seen = " AND `item`.`unseen` = 1 ";

		$r = q("SELECT `item`.`id`,`item`.`parent`, `item`.`verb`, `item`.`author-name`, `item`.`unseen`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`created`, `item`.`object` AS `object`,
				`pitem`.`author-name` AS `pname`, `pitem`.`author-link` AS `plink`, `pitem`.`guid` AS `pguid`
			FROM `item` INNER JOIN `item` AS `pitem` ON `pitem`.`id`=`item`.`parent`
			WHERE `item`.`visible` = 1 AND
				 `item`.`deleted` = 0 AND `item`.`uid` = %d AND `item`.`wall` = 1
				$sql_seen
			ORDER BY `item`.`created` DESC LIMIT %d, %d ",
				intval(local_user()),
				intval($start),
				intval($limit)
		);

		if (dbm::is_result($r))
			$notifs = $this->formatNotifs($r, $ident);

		$arr = array (
			'notifications' => $notifs,
			'ident' => $ident,
			'total' => $total,
		);

		return $arr;
	}

	/**
	 * @brief Total number of introductions 
	 * @param bool $all
	 *	If false only include introductions into the query
	 *	which aren't marked as ignored
	 * @return int Number of introductions
	 */
	private function introTotal($all = false) {
		$sql_extra = "";

		if(!$all)
			$sql_extra = " AND `ignore` = 0 ";

		$r = q("SELECT COUNT(*) AS `total` FROM `intro`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0 ",
				intval($_SESSION['uid'])
		);

		if (dbm::is_result($r))
			return $r[0]['total'];

		return 0;
	}

	/**
	 * @brief Get introductions
	 * 
	 * @param bool $all
	 *	If false only include introductions into the query
	 *	which aren't marked as ignored
	 * @param int $start Start the query at this point
	 * @param int $limit Maximum number of query results
	 * 
	 * @return array with
	 *	string 'ident' => Notification identifier
	 *	int 'total' => Total number of available introductions
	 *	array 'notifications' => Introductions
	 */
	public function introNotifs($all = false, $start = 0, $limit = 80) {
		$ident = 'introductions';
		$total = $this->introTotal($seen);
		$notifs = array();
		$sql_extra = "";

		if(!$all)
			$sql_extra = " AND `ignore` = 0 ";

		/// @todo Fetch contact details by "get_contact_details_by_url" instead of queries to contact, fcontact and gcontact
		$r = q("SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*, `fcontact`.`name` AS `fname`,`fcontact`.`url` AS `furl`,`fcontact`.`photo` AS `fphoto`,`fcontact`.`request` AS `frequest`,
				`gcontact`.`location` AS `glocation`, `gcontact`.`about` AS `gabout`,
				`gcontact`.`keywords` AS `gkeywords`, `gcontact`.`gender` AS `ggender`,
				`gcontact`.`network` AS `gnetwork`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				LEFT JOIN `fcontact` ON `intro`.`fid` = `fcontact`.`id`
			WHERE `intro`.`uid` = %d $sql_extra AND `intro`.`blocked` = 0
			LIMIT %d, %d",
				intval($_SESSION['uid']),
				intval($start),
				intval($limit)
		);

		if (dbm::is_result($r))
			$notifs = $this->formatIntros($r);

		$arr = array (
			'ident' => $ident,
			'total' => $total,
			'notifications' => $notifs,
		);

		return $arr;
	}

	/**
	 * @brief Format the notification query in an usable array
	 * 
	 * @param array $intros The array from the db query
	 * @return array with the introductions
	 */
	private function formatIntros($intros) {
		$knowyou = '';

		foreach($intros as $it) {
			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.

			// Contact suggestions
			if($it['fid']) {

				$return_addr = bin2hex($this->a->user['nickname'] . '@' . $this->a->get_hostname() . (($this->a->path) ? '/' . $this->a->path : ''));

				$intro = array(
					'label' => 'friend_suggestion',
					'notify_type' => t('Friend Suggestion'),
					'intro_id' => $it['intro_id'],
					'madeby' => $it['name'],
					'contact_id' => $it['contact-id'],
					'photo' => ((x($it,'fphoto')) ? proxy_url($it['fphoto'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
					'name' => $it['fname'],
					'url' => zrl($it['furl']),
					'hidden' => $it['hidden'] == 1,
					'post_newfriend' => (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0),

					'knowyou' => $knowyou,
					'note' => $it['note'],
					'request' => $it['frequest'] . '?addr=' . $return_addr,

				);

			// Normal connection requests
			} else {

				// Probe the contact url to get missing data
				$ret = probe_url($it["url"]);

				if ($it['gnetwork'] == "")
					$it['gnetwork'] = $ret["network"];

				// Don't show these data until you are connected. Diaspora is doing the same.
				if($it['gnetwork'] === NETWORK_DIASPORA) {
					$it['glocation'] = "";
					$it['gabout'] = "";
					$it['ggender'] = "";
				}
				$intro = array(
					'label' => (($it['network'] !== NETWORK_OSTATUS) ? 'friend_request' : 'follower'),
					'notify_type' => (($it['network'] !== NETWORK_OSTATUS) ? t('Friend/Connect Request') : t('New Follower')),
					'dfrn_id' => $it['issued-id'],
					'uid' => $_SESSION['uid'],
					'intro_id' => $it['intro_id'],
					'contact_id' => $it['contact-id'],
					'photo' => ((x($it,'photo')) ? proxy_url($it['photo'], false, PROXY_SIZE_SMALL) : "images/person-175.jpg"),
					'name' => $it['name'],
					'location' => bbcode($it['glocation'], false, false),
					'about' => bbcode($it['gabout'], false, false),
					'keywords' => $it['gkeywords'],
					'gender' => $it['ggender'],
					'hidden' => $it['hidden'] == 1,
					'post_newfriend' => (intval(get_pconfig(local_user(),'system','post_newfriend')) ? '1' : 0),
					'url' => $it['url'],
					'zrl' => zrl($it['url']),
					'addr' => $ret['addr'],
					'network' => $it['gnetwork'],
					'knowyou' => $it['knowyou'],
					'note' => $it['note'],
				);
			}

			$arr[] = $intro;
		}

		return $arr;
	}
}
