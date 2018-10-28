<?php
/**
 * @file mod/tagger.php
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Item;

require_once 'include/items.php';

function tagger_content(App $a) {

	if (!local_user() && !remote_user()) {
		return;
	}

	$term = Text::noTags(trim($_GET['term']));
	// no commas allowed
	$term = str_replace([',',' '],['','_'],$term);

	if (!$term) {
		return;
	}

	$item_id = (($a->argc > 1) ? Text::noTags(trim($a->argv[1])) : 0);

	Text::logger('tagger: tag ' . $term . ' item ' . $item_id);


	$item = Item::selectFirst([], ['id' => $item_id]);

	if (!$item_id || !DBA::isResult($item)) {
		Text::logger('tagger: no item ' . $item_id);
		return;
	}

	$owner_uid = $item['uid'];
	$owner_nick = '';
	$blocktags = 0;

	$r = q("select `nickname`,`blocktags` from user where uid = %d limit 1",
		intval($owner_uid)
	);
	if (DBA::isResult($r)) {
		$owner_nick = $r[0]['nickname'];
		$blocktags = $r[0]['blocktags'];
	}

	if (local_user() != $owner_uid) {
		return;
	}

	$r = q("select * from contact where self = 1 and uid = %d limit 1",
		intval(local_user())
	);
	if (DBA::isResult($r)) {
			$contact = $r[0];
	} else {
		Text::logger('tagger: no contact_id');
		return;
	}

	$uri = Item::newURI($owner_uid);
	$xterm = Text::xmlify($term);
	$post_type = (($item['resource-id']) ? L10n::t('photo') : L10n::t('status'));
	$targettype = (($item['resource-id']) ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE );

	if ($owner_nick) {
		$href = System::baseUrl() . '/display/' . $owner_nick . '/' . $item['id'];
	} else {
		$href = System::baseUrl() . '/display/' . $item['guid'];
	}

	$link = Text::xmlify('<link rel="alternate" type="text/html" href="'. $href . '" />' . "\n") ;

	$body = Text::xmlify($item['body']);

	$target = <<< EOT
	<target>
		<type>$targettype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</target>
EOT;

	$tagid = System::baseUrl() . '/search?tag=' . $term;
	$objtype = ACTIVITY_OBJ_TAGTERM;

	$obj = <<< EOT
	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>$tagid</id>
		<link>$tagid</link>
		<title>$xterm</title>
		<content>$xterm</content>
	</object>
EOT;

	$bodyverb = L10n::t('%1$s tagged %2$s\'s %3$s with %4$s');

	if (!isset($bodyverb)) {
		return;
	}

	$termlink = html_entity_decode('&#x2317;') . '[url=' . System::baseUrl() . '/search?tag=' . urlencode($term) . ']'. $term . '[/url]';

	$arr = [];

	$arr['guid'] = System::createUUID();
	$arr['uri'] = $uri;
	$arr['uid'] = $owner_uid;
	$arr['contact-id'] = $contact['id'];
	$arr['wall'] = $item['wall'];
	$arr['gravity'] = GRAVITY_COMMENT;
	$arr['parent'] = $item['id'];
	$arr['parent-uri'] = $item['uri'];
	$arr['owner-name'] = $item['author-name'];
	$arr['owner-link'] = $item['author-link'];
	$arr['owner-avatar'] = $item['author-avatar'];
	$arr['author-name'] = $contact['name'];
	$arr['author-link'] = $contact['url'];
	$arr['author-avatar'] = $contact['thumb'];

	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
	$plink = '[url=' . $item['plink'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink, $termlink );

	$arr['verb'] = ACTIVITY_TAG;
	$arr['target-type'] = $targettype;
	$arr['target'] = $target;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['private'] = $item['private'];
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['origin'] = 1;

	$post_id = Item::insert($arr);

	if (!$item['visible']) {
		Item::update(['visible' => true], ['id' => $item['id']]);
	}

	$term_objtype = ($item['resource-id'] ? TERM_OBJ_PHOTO : TERM_OBJ_POST);

	$t = q("SELECT count(tid) as tcount FROM term WHERE oid=%d AND term='%s'",
		intval($item['id']),
		DBA::escape($term)
	);

	if (!$blocktags && $t[0]['tcount'] == 0) {
		q("INSERT INTO term (oid, otype, type, term, url, uid) VALUE (%d, %d, %d, '%s', '%s', %d)",
		   intval($item['id']),
		   $term_objtype,
		   TERM_HASHTAG,
		   DBA::escape($term),
		   DBA::escape(System::baseUrl() . '/search?tag=' . $term),
		   intval($owner_uid)
		);
	}

	// if the original post is on this site, update it.
	$original_item = Item::selectFirst(['tag', 'id', 'uid'], ['origin' => true, 'uri' => $item['uri']]);
	if (DBA::isResult($original_item)) {
		$x = q("SELECT `blocktags` FROM `user` WHERE `uid`=%d LIMIT 1",
			intval($original_item['uid'])
		);
		$t = q("SELECT COUNT(`tid`) AS `tcount` FROM `term` WHERE `oid`=%d AND `term`='%s'",
			intval($original_item['id']),
			DBA::escape($term)
		);

		if (DBA::isResult($x) && !$x[0]['blocktags'] && $t[0]['tcount'] == 0){
			q("INSERT INTO term (`oid`, `otype`, `type`, `term`, `url`, `uid`) VALUE (%d, %d, %d, '%s', '%s', %d)",
				intval($original_item['id']),
				$term_objtype,
				TERM_HASHTAG,
				DBA::escape($term),
				DBA::escape(System::baseUrl() . '/search?tag=' . $term),
				intval($owner_uid)
			);
		}
	}


	$arr['id'] = $post_id;

	Addon::callHooks('post_local_end', $arr);

	Worker::add(PRIORITY_HIGH, "Notifier", "tag", $post_id);

	killme();

	return; // NOTREACHED
}
