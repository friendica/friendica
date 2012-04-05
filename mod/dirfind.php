<?php

function dirfind_init(&$a) {

	require_once('include/contact_widgets.php');

	if(! x($a->page,'aside'))
		$a->page['aside'] = '';

	$a->page['aside'] .= follow_widget();

	$a->page['aside'] .= findpeople_widget();
}



function dirfind_content(&$a) {

	$search = notags(trim($_REQUEST['search']));
	
	$o = '';

	$o .= '<h2>' . t('People Search') . ' - ' . $search . '</h2>';
	
	if($search) {

		$p = (($a->pager['page'] != 1) ? '&p=' . $a->pager['page'] : '');
			
		if(strlen(get_config('system','directory_submit_url')))
			$x = fetch_url('http://dir.friendica.com/lsearch?f=' . $p .  '&search=' . urlencode($search));

//TODO fallback local search if global dir not available.
//		else
//			$x = post_url($a->get_baseurl() . '/lsearch', $params);

		$j = json_decode($x);

		if($j->total) {
			$a->set_pager_total($j->total);
			$a->set_pager_itemspage($j->items_page);
		}

		if(count($j->results)) {
			
			$tpl = get_markup_template('match.tpl');
			foreach($j->results as $jj) {
				
				$o .= replace_macros($tpl,array(
					'$url' => zrl($jj->url),
					'$name' => $jj->name,
					'$photo' => $jj->photo,
					'$tags' => $jj->tags
				));
			}
		}
		else {
			info( t('No matches') . EOL);
		}		

	}

	$o .= '<div class="clear"></div>';
	$o .= paginate($a);
	return $o;
}
