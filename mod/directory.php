<?php
/**
 * @file mod/directory.php
 */
use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

function directory_init(App $a) {
	$a->set_pager_itemspage(60);

	if(local_user()) {
		$a->page['aside'] .= Widget::findPeople();

		$a->page['aside'] .= Widget::follow();
	} else {
		unset($_SESSION['theme']);
		unset($_SESSION['mobile-theme']);
	}
}

function directory_post(App $a) {
	if(x($_POST,'search'))
		$a->data['search'] = $_POST['search'];
}

function directory_content(App $a) {
	require_once("mod/proxy.php");

	if((Config::get('system','block_public')) && (! local_user()) && (! remote_user()) ||
		(Config::get('system','block_local_dir')) && (! local_user()) && (! remote_user())) {
		notice( t('Public access denied.') . EOL);
		return;
	}

	$o = '';
	Nav::setSelected('directory');

	if(x($a->data,'search'))
		$search = notags(trim($a->data['search']));
	else
		$search = ((x($_GET,'search')) ? notags(trim(rawurldecode($_GET['search']))) : '');

	$gdirpath = '';
	$dirurl = Config::get('system','directory');
	if(strlen($dirurl)) {
		$gdirpath = Profile::zrl($dirurl,true);
	}

	if($search) {
		$search = dbesc($search);

		$sql_extra = " AND ((`profile`.`name` LIKE '%$search%') OR
				(`user`.`nickname` LIKE '%$search%') OR
				(`profile`.`pdesc` LIKE '%$search%') OR
				(`profile`.`locality` LIKE '%$search%') OR
				(`profile`.`region` LIKE '%$search%') OR
				(`profile`.`country-name` LIKE '%$search%') OR
				(`profile`.`gender` LIKE '%$search%') OR
				(`profile`.`marital` LIKE '%$search%') OR
				(`profile`.`sexual` LIKE '%$search%') OR
				(`profile`.`about` LIKE '%$search%') OR
				(`profile`.`romance` LIKE '%$search%') OR
				(`profile`.`work` LIKE '%$search%') OR
				(`profile`.`education` LIKE '%$search%') OR
				(`profile`.`pub_keywords` LIKE '%$search%') OR
				(`profile`.`prv_keywords` LIKE '%$search%'))";
	}

	$publish = ((Config::get('system','publish_all')) ? '' : " AND `publish` = 1 " );


	$r = q("SELECT COUNT(*) AS `total` FROM `profile`
			LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
			WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra ");
	if (DBM::isResult($r))
		$a->set_pager_total($r[0]['total']);

	$order = " ORDER BY `name` ASC ";

	$limit = intval($a->pager['start']).",".intval($a->pager['itemspage']);

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`, `user`.`timezone` , `user`.`page-flags`,
			`contact`.`addr`, `contact`.`url` AS profile_url FROM `profile`
			LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
			LEFT JOIN `contact` ON `contact`.`uid` = `user`.`uid`
			WHERE `is-default` $publish AND `user`.`blocked` = 0 AND `contact`.`self` $sql_extra $order LIMIT ".$limit);
	if (DBM::isResult($r)) {

		if (in_array('small', $a->argv)) {
			$photo = 'thumb';
		}
		else {
			$photo = 'photo';
		}

		foreach ($r as $rr) {

			$itemurl= '';

			$itemurl = (($rr['addr'] != "") ? $rr['addr'] : $rr['profile_url']);

			$profile_link = 'profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);

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
//			if(strlen($rr['dob'])) {
//				if(($years = age($rr['dob'],$rr['timezone'],'')) != 0)
//					$details .= '<br />' . t('Age: ') . $years ;
//			}
//			if(strlen($rr['gender']))
//				$details .= '<br />' . t('Gender: ') . $rr['gender'];

			$profile = $rr;

			if((x($profile,'address') == 1)
				|| (x($profile,'locality') == 1)
				|| (x($profile,'region') == 1)
				|| (x($profile,'postal-code') == 1)
				|| (x($profile,'country-name') == 1))
			$location = t('Location:');

			$gender = ((x($profile,'gender') == 1) ? t('Gender:') : False);

			$marital = ((x($profile,'marital') == 1) ?  t('Status:') : False);

			$homepage = ((x($profile,'homepage') == 1) ?  t('Homepage:') : False);

			$about = ((x($profile,'about') == 1) ?  t('About:') : False);

			$location_e = $location;

			$photo_menu = [
				'profile' => [t("View Profile"), Profile::zrl($profile_link)]
			];

			$entry = [
				'id' => $rr['id'],
				'url' => $profile_link,
				'itemurl' => $itemurl,
				'thumb' => proxy_url($rr[$photo], false, PROXY_SIZE_THUMB),
				'img_hover' => $rr['name'],
				'name' => $rr['name'],
				'details' => $details,
				'account_type' => Contact::getAccountType($rr),
				'profile' => $profile,
				'location' => $location_e,
				'tags' => $rr['pub_keywords'],
				'gender'   => $gender,
				'pdesc'	=> $pdesc,
				'marital'  => $marital,
				'homepage' => $homepage,
				'about' => $about,
				'photo_menu' => $photo_menu,

			];

			$arr = ['contact' => $rr, 'entry' => $entry];

			Addon::callHooks('directory_item', $arr);

			unset($profile);
			unset($location);

			if(! $arr['entry'])
				continue;

			$entries[] = $arr['entry'];

		}

		$tpl = get_markup_template('directory_header.tpl');

		$o .= replace_macros($tpl, [
			'$search' => $search,
			'$globaldir' => t('Global Directory'),
			'$gdirpath' => $gdirpath,
			'$desc' => t('Find on this site'),
			'$contacts' => $entries,
			'$finding' => t('Results for:'),
			'$findterm' => (strlen($search) ? $search : ""),
			'$title' => t('Site Directory'),
			'$submit' => t('Find'),
			'$paginate' => paginate($a),
		]);

	}
	else
		info( t("No entries \x28some entries may be hidden\x29.") . EOL);

	return $o;
}
