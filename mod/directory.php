<?php

function directory_init(&$a) {
	$a->set_pager_itemspage(60);

	if(local_user()) {
		require_once('include/contact_widgets.php');

		$a->page['aside'] .= findpeople_widget();

	}
	else
		unset($_SESSION['theme']);


}


function directory_post(&$a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}



function directory_content(&$a) {

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$o = '';
	nav_set_selected('directory');

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$tpl = get_markup_template('directory_header.tpl');

	$globaldir = '';
	$gdirpath = dirname(get_config('system','directory_submit_url'));
	if(strlen($gdirpath)) {
		$globaldir = '<ul><li><div id="global-directory-link"><a href="'
		. zrl($gdirpath,true) . '">' . t('Global Directory') . '</a></div></li></ul>';
	}

	$admin = '';

	$o .= replace_macros($tpl, array(
		'$search' => $search,
		'$globaldir' => $globaldir,
		'$desc' => t('Find on this site'),
		'$admin' => $admin,
		'$finding' => (strlen($search) ? '<h4>' . t('Finding: ') . "'" . $search . "'" . '</h4>' : ""),
		'$sitedir' => t('Site Directory'),
		'$submit' => t('Find')
	));

	if($search)
		$search = dbesc($search);
	$sql_extra = ((strlen($search)) ? " AND MATCH (`profile`.`name`, `user`.`nickname`, `pdesc`, `locality`,`region`,`country-name`,`gender`,`marital`,`sexual`,`about`,`romance`,`work`,`education`,`pub_keywords`,`prv_keywords` ) AGAINST ('$search' IN BOOLEAN MODE) " : "");

	$publish = ((get_config('system','publish_all')) ? '' : " AND `publish` = 1 " );


	$r = q("SELECT COUNT(*) AS `total` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra ");
	if(count($r))
		$a->set_pager_total($r[0]['total']);

	$order = " ORDER BY `name` ASC "; 


	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`, `user`.`timezone` FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra $order LIMIT %d , %d ",
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if(count($r)) {

		$tpl = get_markup_template('directory_item.tpl');

		if(in_array('small', $a->argv))
			$photo = 'thumb';
		else
			$photo = 'photo';

		foreach($r as $rr) {


			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);
		
			$pdesc = (($rr['pdesc']) ? $rr['pdesc'] . '<br />' : '');

			$details = '';
			if(strlen($rr['locality']))
				$details .= $rr['locality'];
			if(strlen($rr['region'])) {
				if(strlen($rr['locality']))
					$details .= ', ';
				$details .= $rr['region'];
			}
			if(strlen($rr['country-name'])) {
				if(strlen($details))
					$details .= ', ';
				$details .= $rr['country-name'];
			}
			if(strlen($rr['dob'])) {
				if(($years = age($rr['dob'],$rr['timezone'],'')) != 0)
					$details .= '<br />' . t('Age: ') . $years ; 
			}
			if(strlen($rr['gender']))
				$details .= '<br />' . t('Gender: ') . $rr['gender'];

			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $profile_link,
				'$photo' => $rr[$photo],
				'$alt-text' => $rr['name'],
				'$name' => $rr['name'],
				'$details' => $pdesc . $details  


			));

			$arr = array('contact' => $rr, 'entry' => $entry);

			call_hooks('directory_item', $arr);

			$o .= $entry;

		}

		$o .= "<div class=\"directory-end\" ></div>\r\n";
		$o .= paginate($a);

	}
	else
		info( t("No entries \x28some entries may be hidden\x29.") . EOL);

	return $o;
}
