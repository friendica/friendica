<?php
/**
 * @file mod/search.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\RenderedPager;
use Friendica\Content\Text\HTML;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Module\BaseSearchModule;
use Friendica\Util\Strings;

function search_saved_searches() {

	$o = '';
	$search = (!empty($_GET['search']) ? Strings::escapeTags(trim(rawurldecode($_GET['search']))) : '');

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


		$tpl = Renderer::getMarkupTemplate("saved_searches_aside.tpl");

		$o .= Renderer::replaceMacros($tpl, [
			'$title'	=> L10n::t('Saved Searches'),
			'$add'		=> '',
			'$searchbox'	=> '',
			'$saved' 	=> $saved,
		]);
	}

	return $o;

}


function search_init(App $a) {

	$search = (!empty($_GET['search']) ? Strings::escapeTags(trim(rawurldecode($_GET['search']))) : '');

	if (local_user()) {
		if (!empty($_GET['save']) && $search) {
			$r = q("SELECT * FROM `search` WHERE `uid` = %d AND `term` = '%s' LIMIT 1",
				intval(local_user()),
				DBA::escape($search)
			);
			if (!DBA::isResult($r)) {
				DBA::insert('search', ['uid' => local_user(), 'term' => $search]);
			}
		}
		if (!empty($_GET['remove']) && $search) {
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

function search_content(App $a) {

	if (Config::get('system','block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	if (Config::get('system','local_search') && !local_user() && !remote_user()) {
		$e = new \Friendica\Network\HTTPException\ForbiddenException(L10n::t("Only logged in users are permitted to perform a search."));
		$e->httpdesc = L10n::t("Public access denied.");
		throw $e;
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
				throw new \Friendica\Network\HTTPException\TooManyRequestsException(L10n::t("Only one search per minute is permitted for not logged in users."));
			}
			Cache::set("remote_search:".$remote, json_encode(["time" => time(), "accesses" => $resultdata->accesses + 1]), Cache::HOUR);
		} else
			Cache::set("remote_search:".$remote, json_encode(["time" => time(), "accesses" => 1]), Cache::HOUR);
	}

	Nav::setSelected('search');

	$search = (!empty($_REQUEST['search']) ? Strings::escapeTags(trim(rawurldecode($_REQUEST['search']))) : '');

	$tag = false;
	if (!empty($_GET['tag'])) {
		$tag = true;
		$search = (!empty($_GET['tag']) ? '#' . Strings::escapeTags(trim(rawurldecode($_GET['tag']))) : '');
	}

	// contruct a wrapper for the search header
	$o = Renderer::replaceMacros(Renderer::getMarkupTemplate("content_wrapper.tpl"),[
		'name' => "search-header",
		'$title' => L10n::t("Search"),
		'$title_size' => 3,
		'$content' => HTML::search($search,'search-box','search', false)
	]);

	if (strpos($search,'#') === 0) {
		$tag = true;
		$search = substr($search,1);
	}
	if (strpos($search,'@') === 0) {
		return BaseSearchModule::performSearch();
	}
	if (strpos($search,'!') === 0) {
		return BaseSearchModule::performSearch();
	}

	if (!empty($_GET['search-option']))
		switch($_GET['search-option']) {
			case 'fulltext':
				break;
			case 'tags':
				$tag = true;
				break;
			case 'contacts':
				return BaseSearchModule::performSearch('@');
			case 'forums':
				return BaseSearchModule::performSearch('!');
		}

	if (!$search)
		return $o;

	if (Config::get('system','only_tag_search'))
		$tag = true;

	// Here is the way permissions work in the search module...
	// Only public posts can be shown
	// OR your own posts if you are a logged in member
	// No items will be shown if the member has a blocked profile wall.

	$pager = new RenderedPager($a->query_string, $a->page['page']);

	if ($tag) {
		Logger::log("Start tag search for '".$search."'", Logger::DEBUG);

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
		Logger::log("Start fulltext search for '".$search."'", Logger::DEBUG);

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

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate("section_title.tpl"),[
		'$title' => $title
	]);

	Logger::log("Start Conversation for '".$search."'", Logger::DEBUG);
	$o .= conversation($a, $r, $pager, 'search', false, false, 'commented', local_user());

	$o .= $pager->renderMinimal(count($r));

	Logger::log("Done '".$search."'", Logger::DEBUG);

	return $o;
}
