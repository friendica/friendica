<?php

require_once('include/socgraph.php');

function common_content(&$a) {

	$o = '';
	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc > 1)
		$cid = intval($a->argv[1]);
	if(! $cid)
		return;

	$c = q("select name, url, photo from contact where id = %d and uid = %d limit 1",
		intval($cid),
		intval(local_user())
	);

	$a->page['aside'] .= '<div class="vcard">' 
		. '<div class="fn label">' . $c[0]['name'] . '</div>' 
		. '<div id="profile-photo-wrapper">'
		. '<a href="/contacts/' . $cid . '"><img class="photo" width="175" height="175" 
		src="' . $c[0]['photo'] . '" alt="' . $c[0]['name'] . '" /></div>'
		. '</div>';
	

	if(! count($c))
		return;

	$o .= '<h2>' . t('Common Friends') . '</h2>';

//	$o .= '<h3>' . sprintf( t('You and %s'),$c[0]['name']) . '</h3>';


	$r = common_friends(local_user(),$cid);

	if(! count($r)) {
		$o .= t('No friends in common.');
		return $o;
	}

	$tpl = get_markup_template('common_friends.tpl');

	foreach($r as $rr) {
			
		$o .= replace_macros($tpl,array(
			'$url' => $rr['url'],
			'$name' => $rr['name'],
			'$photo' => $rr['photo'],
			'$tags' => ''
		));
	}

	$o .= cleardiv();
//	$o .= paginate($a);
	return $o;
}
