<?php

// See here for a documentation for portable contacts:
// https://web.archive.org/web/20160405005550/http://portablecontacts.net/draft-spec.html


use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Protocol\PortableContact;
use Friendica\Registry\Core;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\XML;

function poco_init(App $a) {
	$system_mode = false;

	if (intval(Config::get('system', 'block_public')) || (Config::get('system', 'block_local_dir'))) {
		throw new \Friendica\Network\HTTPException\ForbiddenException();
	}

	if ($a->argc > 1) {
		$nickname = Strings::escapeTags(trim($a->argv[1]));
	}
	if (empty($nickname)) {
		$c = q("SELECT * FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1");
		if (!DBA::isResult($c)) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}
		$system_mode = true;
	}

	$format = ($_GET['format'] ?? '') ?: 'json';

	$justme = false;
	$global = false;

	if ($a->argc > 1 && $a->argv[1] === '@server') {
		// List of all servers that this server knows
		$ret = PortableContact::serverlist();
		header('Content-type: application/json');
		echo json_encode($ret);
		exit();
	}

	if ($a->argc > 1 && $a->argv[1] === '@global') {
		// List of all profiles that this server recently had data from
		$global = true;
		$update_limit = date(DateTimeFormat::MYSQL, time() - 30 * 86400);
	}
	if ($a->argc > 2 && $a->argv[2] === '@me') {
		$justme = true;
	}
	if ($a->argc > 3 && $a->argv[3] === '@all') {
		$justme = false;
	}
	if ($a->argc > 3 && $a->argv[3] === '@self') {
		$justme = true;
	}
	if ($a->argc > 4 && intval($a->argv[4]) && $justme == false) {
		$cid = intval($a->argv[4]);
	}

	if (! $system_mode && ! $global) {
		$users = q("SELECT `user`.*,`profile`.`hide-friends` from user left join profile on `user`.`uid` = `profile`.`uid`
			where `user`.`nickname` = '%s' and `profile`.`is-default` = 1 limit 1",
			DBA::escape($nickname)
		);
		if (! DBA::isResult($users) || $users[0]['hidewall'] || $users[0]['hide-friends']) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$user = $users[0];
	}

	if ($justme) {
		$sql_extra = " AND `contact`.`self` = 1 ";
	} else {
		$sql_extra = "";
	}

	if (!empty($cid)) {
		$sql_extra = sprintf(" AND `contact`.`id` = %d ", intval($cid));
	}
	if (!empty($_GET['updatedSince'])) {
		$update_limit = date(DateTimeFormat::MYSQL, strtotime($_GET['updatedSince']));
	}
	if ($global) {
		$contacts = q("SELECT count(*) AS `total` FROM `gcontact` WHERE `updated` >= '%s' AND `updated` >= `last_failure` AND NOT `hide` AND `network` IN ('%s', '%s', '%s')",
			DBA::escape($update_limit),
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS)
		);
	} elseif ($system_mode) {
		$contacts = q("SELECT count(*) AS `total` FROM `contact` WHERE `self` = 1
			AND `uid` IN (SELECT `uid` FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1) ");
	} else {
		$contacts = q("SELECT count(*) AS `total` FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0
			AND (`success_update` >= `failure_update` OR `last-item` >= `failure_update`)
			AND `network` IN ('%s', '%s', '%s', '%s') $sql_extra",
			intval($user['uid']),
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS),
			DBA::escape(Protocol::STATUSNET)
		);
	}
	if (DBA::isResult($contacts)) {
		$totalResults = intval($contacts[0]['total']);
	} else {
		$totalResults = 0;
	}
	if (!empty($_GET['startIndex'])) {
		$startIndex = intval($_GET['startIndex']);
	} else {
		$startIndex = 0;
	}
	$itemsPerPage = ((!empty($_GET['count'])) ? intval($_GET['count']) : $totalResults);

	if ($global) {
		Logger::log("Start global query", Logger::DEBUG);
		$contacts = q("SELECT * FROM `gcontact` WHERE `updated` > '%s' AND NOT `hide` AND `network` IN ('%s', '%s', '%s') AND `updated` > `last_failure`
			ORDER BY `updated` DESC LIMIT %d, %d",
			DBA::escape($update_limit),
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS),
			intval($startIndex),
			intval($itemsPerPage)
		);
	} elseif ($system_mode) {
		Logger::log("Start system mode query", Logger::DEBUG);
		$contacts = q("SELECT `contact`.*, `profile`.`about` AS `pabout`, `profile`.`locality` AS `plocation`, `profile`.`pub_keywords`,
				`profile`.`gender` AS `pgender`, `profile`.`address` AS `paddress`, `profile`.`region` AS `pregion`,
				`profile`.`postal-code` AS `ppostalcode`, `profile`.`country-name` AS `pcountry`, `user`.`account-type`
			FROM `contact` INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
				INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `self` = 1 AND `profile`.`is-default`
			AND `contact`.`uid` IN (SELECT `uid` FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1) LIMIT %d, %d",
			intval($startIndex),
			intval($itemsPerPage)
		);
	} else {
		Logger::log("Start query for user " . $user['nickname'], Logger::DEBUG);
		$contacts = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0
			AND (`success_update` >= `failure_update` OR `last-item` >= `failure_update`)
			AND `network` IN ('%s', '%s', '%s', '%s') $sql_extra LIMIT %d, %d",
			intval($user['uid']),
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS),
			DBA::escape(Protocol::STATUSNET),
			intval($startIndex),
			intval($itemsPerPage)
		);
	}
	Logger::log("Query done", Logger::DEBUG);

	$ret = [];
	if (!empty($_GET['sorted'])) {
		$ret['sorted'] = false;
	}
	if (!empty($_GET['filtered'])) {
		$ret['filtered'] = false;
	}
	if (!empty($_GET['updatedSince']) && ! $global) {
		$ret['updatedSince'] = false;
	}
	$ret['startIndex']   = (int) $startIndex;
	$ret['itemsPerPage'] = (int) $itemsPerPage;
	$ret['totalResults'] = (int) $totalResults;
	$ret['entry']        = [];


	$fields_ret = [
		'id' => false,
		'displayName' => false,
		'urls' => false,
		'updated' => false,
		'preferredUsername' => false,
		'photos' => false,
		'aboutMe' => false,
		'currentLocation' => false,
		'network' => false,
		'gender' => false,
		'tags' => false,
		'address' => false,
		'contactType' => false,
		'generation' => false
	];

	if (empty($_GET['fields']) || ($_GET['fields'] === '@all')) {
		foreach ($fields_ret as $k => $v) {
			$fields_ret[$k] = true;
		}
	} else {
		$fields_req = explode(',', $_GET['fields']);
		foreach ($fields_req as $f) {
			$fields_ret[trim($f)] = true;
		}
	}

	if (is_array($contacts)) {
		if (DBA::isResult($contacts)) {
			foreach ($contacts as $contact) {
				if (!isset($contact['updated'])) {
					$contact['updated'] = '';
				}

				if (! isset($contact['generation'])) {
					if ($global) {
						$contact['generation'] = 3;
					} elseif ($system_mode) {
						$contact['generation'] = 1;
					} else {
						$contact['generation'] = 2;
					}
				}

				if (($contact['about'] == "") && isset($contact['pabout'])) {
					$contact['about'] = $contact['pabout'];
				}
				if ($contact['location'] == "") {
					if (isset($contact['plocation'])) {
						$contact['location'] = $contact['plocation'];
					}
					if (isset($contact['pregion']) && ( $contact['pregion'] != "")) {
						if ($contact['location'] != "") {
							$contact['location'] .= ", ";
						}
						$contact['location'] .= $contact['pregion'];
					}

					if (isset($contact['pcountry']) && ( $contact['pcountry'] != "")) {
						if ($contact['location'] != "") {
							$contact['location'] .= ", ";
						}
						$contact['location'] .= $contact['pcountry'];
					}
				}

				if (($contact['gender'] == "") && isset($contact['pgender'])) {
					$contact['gender'] = $contact['pgender'];
				}
				if (($contact['keywords'] == "") && isset($contact['pub_keywords'])) {
					$contact['keywords'] = $contact['pub_keywords'];
				}
				if (isset($contact['account-type'])) {
					$contact['contact-type'] = $contact['account-type'];
				}
				$about = Core::cache()->get("about:" . $contact['updated'] . ":" . $contact['nurl']);
				if (is_null($about)) {
					$about = BBCode::convert($contact['about'], false);
					Core::cache()->set("about:" . $contact['updated'] . ":" . $contact['nurl'], $about);
				}

				// Non connected persons can only see the keywords of a Diaspora account
				if ($contact['network'] == Protocol::DIASPORA) {
					$contact['location'] = "";
					$about = "";
					$contact['gender'] = "";
				}

				$entry = [];
				if ($fields_ret['id']) {
					$entry['id'] = (int)$contact['id'];
				}
				if ($fields_ret['displayName']) {
					$entry['displayName'] = $contact['name'];
				}
				if ($fields_ret['aboutMe']) {
					$entry['aboutMe'] = $about;
				}
				if ($fields_ret['currentLocation']) {
					$entry['currentLocation'] = $contact['location'];
				}
				if ($fields_ret['gender']) {
					$entry['gender'] = $contact['gender'];
				}
				if ($fields_ret['generation']) {
					$entry['generation'] = (int)$contact['generation'];
				}
				if ($fields_ret['urls']) {
					$entry['urls'] = [['value' => $contact['url'], 'type' => 'profile']];
					if ($contact['addr'] && ($contact['network'] !== Protocol::MAIL)) {
						$entry['urls'][] = ['value' => 'acct:' . $contact['addr'], 'type' => 'webfinger'];
					}
				}
				if ($fields_ret['preferredUsername']) {
					$entry['preferredUsername'] = $contact['nick'];
				}
				if ($fields_ret['updated']) {
					if (! $global) {
						$entry['updated'] = $contact['success_update'];

						if ($contact['name-date'] > $entry['updated']) {
							$entry['updated'] = $contact['name-date'];
						}
						if ($contact['uri-date'] > $entry['updated']) {
							$entry['updated'] = $contact['uri-date'];
						}
						if ($contact['avatar-date'] > $entry['updated']) {
							$entry['updated'] = $contact['avatar-date'];
						}
					} else {
						$entry['updated'] = $contact['updated'];
					}
					$entry['updated'] = date("c", strtotime($entry['updated']));
				}
				if ($fields_ret['photos']) {
					$entry['photos'] = [['value' => $contact['photo'], 'type' => 'profile']];
				}
				if ($fields_ret['network']) {
					$entry['network'] = $contact['network'];
					if ($entry['network'] == Protocol::STATUSNET) {
						$entry['network'] = Protocol::OSTATUS;
					}
					if (($entry['network'] == "") && ($contact['self'])) {
						$entry['network'] = Protocol::DFRN;
					}
				}
				if ($fields_ret['tags']) {
					$tags = str_replace(",", " ", $contact['keywords']);
					$tags = explode(" ", $tags);

					$cleaned = [];
					foreach ($tags as $tag) {
						$tag = trim(strtolower($tag));
						if ($tag != "") {
							$cleaned[] = $tag;
						}
					}

					$entry['tags'] = [$cleaned];
				}
				if ($fields_ret['address']) {
					$entry['address'] = [];

					// Deactivated. It just reveals too much data. (Although its from the default profile)
					//if (isset($rr['paddress']))
					//	 $entry['address']['streetAddress'] = $rr['paddress'];

					if (isset($contact['plocation'])) {
						$entry['address']['locality'] = $contact['plocation'];
					}
					if (isset($contact['pregion'])) {
						$entry['address']['region'] = $contact['pregion'];
					}
					// See above
					//if (isset($rr['ppostalcode']))
					//	 $entry['address']['postalCode'] = $rr['ppostalcode'];

					if (isset($contact['pcountry'])) {
						$entry['address']['country'] = $contact['pcountry'];
					}
				}

				if ($fields_ret['contactType']) {
					$entry['contactType'] = intval($contact['contact-type']);
				}
				$ret['entry'][] = $entry;
			}
		} else {
			$ret['entry'][] = [];
		}
	} else {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	Logger::log("End of poco", Logger::DEBUG);

	if ($format === 'xml') {
		header('Content-type: text/xml');
		echo Renderer::replaceMacros(Renderer::getMarkupTemplate('poco_xml.tpl'), XML::arrayEscape(['$response' => $ret]));
		exit();
	}
	if ($format === 'json') {
		header('Content-type: application/json');
		echo json_encode($ret);
		exit();
	} else {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}
}
