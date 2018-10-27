<?php
/**
 * @file mod/search.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;

require_once 'include/conversation.php';
require_once 'mod/dirfind.php';

function search_saved_searches() {

	$o = '';
	$search = ((x($_GET,'search')) ? Text::noTags(trim(rawurldecode($_GET['search']))) : '');

	if (!Feature::isEnabled(local_user(),'savedsearch'))
		return $o;

	$r = q("SELECT `id`,`term` FROM `search` WHERE `uid` = %d",
		intval(local_user())
	);

	if (DBA::isResult($r)) {
		$saved = [];
		foreach ($r as $rr) {
			$saved[] = [
				'id'		=> $rr['id'],
				'term'		=> $rr['term'],
				'encodedterm'	=> urlencode($rr['term']),
				'delete'	=> L10n::t('Remove term'),
				'selected'	=> ($search==$rr['term']),
			];
		}


		$tpl = get_markup_template("saved_searches_aside.tpl");

		$o .= Text::replaceMacros($tpl, [
			'$title'	=> L10n::t('Saved Searches'),
			'$add'		=> '',
			'$searchbox'	=> '',
			'$saved' 	=> $saved,
		]);
	}

	return $o;

}


function search_init(App $a) {

	$search = ((x($_GET,'search')) ? Text::noTags(trim(rawurldecode($_GET['search']))) : '');

	if (local_user()) {
		if (x($_GET,'save') && $search) {
			$r = q("SELECT * FROM `search` WHERE `uid` = %d AND `term` = '%s' LIMIT 1",
				intval(local_user()),
				DBA::escape($search)
			);
			if (!DBA::isResult($r)) {
				DBA::insert('search', ['uid' => local_user(), 'term' => $search]);
			}
		}
		if (x($_GET,'remove') && $search) {
			DBA::delete('search', ['uid' => local_user(), 'term' => $search]);
		}

		/// @todo Check if there is a case at all that "aside" is prefilled here
		if (!isset($a->page['aside'])) {
			$a->page['aside'] = '';
		}

		$a->page['aside'] .= search_saved_searches();

	} else {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}



}



function search_post(App $a) {
	if (x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}


function search_content(App $a) {

	if (Config::get('system','block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	if (Config::get('system','local_search') && !local_user() && !remote_user()) {
		System::httpExit(403,
				["title" => L10n::t("Public access denied."),
					"description" => L10n::t("Only logged in users are permitted to perform a search.")]);
		killme();
		//notice(L10n::t('Public access denied.').EOL);
		//return;
	}

	if (Config::get('system','permit_crawling') && !local_user() && !remote_user()) {
		// Default values:
		// 10 requests are "free", after the 11th only a call per minute is allowed

		$free_crawls = intval(Config::get('system','free_crawls'));
		if ($free_crawls == 0)
			$free_crawls = 10;

		$crawl_permit_period = intval(Config::get('system','crawl_permit_period'));
		if ($crawl_permit_period == 0)
			$crawl_permit_period = 10;

		$remote = $_SERVER["REMOTE_ADDR"];
		$result = Cache::get("remote_search:".$remote);
		if (!is_null($result)) {
			$resultdata = json_decode($result);
			if (($resultdata->time > (time() - $crawl_permit_period)) && ($resultdata->accesses > $free_crawls)) {
				System::httpExit(429,
						["title" => L10n::t("Too Many Requests"),
							"description" => L10n::t("Only one search per minute is permitted for not logged in users.")]);
				killme();
			}
			Cache::set("remote_search:".$remote, json_encode(["time" => time(), "accesses" => $resultdata->accesses + 1]), Cache::HOUR);
		} else
			Cache::set("remote_search:".$remote, json_encode(["time" => time(), "accesses" => 1]), Cache::HOUR);
	}

	Nav::setSelected('search');

	$search = '';
	if (x($a->data,'search'))
		$search = Text::noTags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? Text::noTags(trim(rawurldecode($_GET['search']))) : '');

	$tag = false;
	if (x($_GET,'tag')) {
		$tag = true;
		$search = (x($_GET,'tag') ? '#' . Text::noTags(trim(rawurldecode($_GET['tag']))) : '');
	}

	// contruct a wrapper for the search header
	$o = Text::replaceMacros(get_markup_template("content_wrapper.tpl"),[
		'name' => "search-header",
		'$title' => L10n::t("Search"),
		'$title_size' => 3,
		'$content' => search($search,'search-box','search',((local_user()) ? true : false), false)
	]);

	if (strpos($search,'#') === 0) {
		$tag = true;
		$search = substr($search,1);
	}
	if (strpos($search,'@') === 0) {
		return dirfind_content($a);
	}
	if (strpos($search,'!') === 0) {
		return dirfind_content($a);
	}

	if (x($_GET,'search-option'))
		switch($_GET['search-option']) {
			case 'fulltext':
				break;
			case 'tags':
				$tag = true;
				break;
			case 'contacts':
				return dirfind_content($a, "@");
				break;
			case 'forums':
				return dirfind_content($a, "!");
				break;
		}

	if (!$search)
		return $o;

	if (Config::get('system','only_tag_search'))
		$tag = true;

	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall.

	$pager = new Pager($a->query_string);

	if ($tag) {
		logger("Start tag search for '".$search."'", LOGGER_DEBUG);

		$condition = ["(`uid` = 0 OR (`uid` = ? AND NOT `global`))
			AND `otype` = ? AND `type` = ? AND `term` = ?",
			local_user(), TERM_OBJ_POST, TERM_HASHTAG, $search];
		$params = ['order' => ['created' => true],
			'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$terms = DBA::select('term', ['oid'], $condition, $params);

		$itemids = [];
		while ($term = DBA::fetch($terms)) {
			$itemids[] = $term['oid'];
		}
		DBA::close($terms);

		if (!empty($itemids)) {
			$params = ['order' => ['id' => true]];
			$items = Item::selectForUser(local_user(), [], ['id' => $itemids], $params);
			$r = Item::inArray($items);
		} else {
			$r = [];
		}
	} else {
		logger("Start fulltext search for '".$search."'", LOGGER_DEBUG);

		$condition = ["(`uid` = 0 OR (`uid` = ? AND NOT `global`))
			AND `body` LIKE CONCAT('%',?,'%')",
			local_user(), $search];
		$params = ['order' => ['id' => true],
			'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$items = Item::selectForUser(local_user(), [], $condition, $params);
		$r = Item::inArray($items);
	}

	if (!DBA::isResult($r)) {
		info(L10n::t('No results.') . EOL);
		return $o;
	}


	if ($tag) {
		$title = L10n::t('Items tagged with: %s', $search);
	} else {
		$title = L10n::t('Results for: %s', $search);
	}

	$o .= Text::replaceMacros(get_markup_template("section_title.tpl"),[
		'$title' => $title
	]);

	logger("Start Conversation for '".$search."'", LOGGER_DEBUG);
	$o .= conversation($a, $r, $pager, 'search', false, false, 'commented', local_user());

	$o .= $pager->renderMinimal(count($r));

	logger("Done '".$search."'", LOGGER_DEBUG);

	return $o;
}
