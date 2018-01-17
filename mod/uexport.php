<?php
/**
 * @file mod/uexport.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\System;
use Friendica\Database\DBM;

function uexport_init(App $a) {
	if (!local_user()) {
		killme();
	}

	require_once("mod/settings.php");
	settings_init($a);
}

function uexport_content(App $a) {

	if ($a->argc > 1) {
		header("Content-type: application/json");
		header('Content-Disposition: attachment; filename="' . $a->user['nickname'] . '.' . $a->argv[1] . '"');
		switch ($a->argv[1]) {
			case "backup":
				uexport_all($a);
				killme();
				break;
			case "account":
				uexport_account($a);
				killme();
				break;
			default:
				killme();
		}
	}

	/**
	 * options shown on "Export personal data" page
	 * list of array( 'link url', 'link text', 'help text' )
	 */
	$options = [
		['uexport/account', t('Export account'), t('Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.')],
		['uexport/backup', t('Export all'), t('Export your accout info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)')],
	];
	Addon::callHooks('uexport_options', $options);

	$tpl = get_markup_template("uexport.tpl");
	return replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$title' => t('Export personal data'),
		'$options' => $options
	]);
}

function _uexport_multirow($query) {
	$result = [];
	$r = q($query);
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$p = [];
			foreach ($rr as $k => $v) {
				$p[$k] = $v;
			}
			$result[] = $p;
		}
	}
	return $result;
}

function _uexport_row($query) {
	$result = [];
	$r = q($query);
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			foreach ($rr as $k => $v) {
				$result[$k] = $v;
			}
		}
	}
	return $result;
}

function uexport_account($a) {

	$user = _uexport_row(
		sprintf("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", intval(local_user()))
	);

	$contact = _uexport_multirow(
		sprintf("SELECT * FROM `contact` WHERE `uid` = %d ", intval(local_user()))
	);


	$profile = _uexport_multirow(
		sprintf("SELECT * FROM `profile` WHERE `uid` = %d ", intval(local_user()))
	);

	$photo = _uexport_multirow(
		sprintf("SELECT * FROM `photo` WHERE uid = %d AND profile = 1", intval(local_user()))
	);
	foreach ($photo as &$p) {
		$p['data'] = bin2hex($p['data']);
	}

	$pconfig = _uexport_multirow(
		sprintf("SELECT * FROM `pconfig` WHERE uid = %d", intval(local_user()))
	);

	$group = _uexport_multirow(
		sprintf("SELECT * FROM `group` WHERE uid = %d", intval(local_user()))
	);

	$group_member = _uexport_multirow(
		sprintf("SELECT `group_member`.`gid`, `group_member`.`contact-id` FROM `group_member` INNER JOIN `group` ON `group`.`id` = `group_member`.`gid` WHERE `group`.`uid` = %d", intval(local_user()))
	);

	$output = [
		'version' => FRIENDICA_VERSION,
		'schema' => DB_UPDATE_VERSION,
		'baseurl' => System::baseUrl(),
		'user' => $user,
		'contact' => $contact,
		'profile' => $profile,
		'photo' => $photo,
		'pconfig' => $pconfig,
		'group' => $group,
		'group_member' => $group_member,
	];

	//echo "<pre>"; var_dump(json_encode($output)); killme();
	echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR);
}

/**
 * echoes account data and items as separated json, one per line
 */
function uexport_all(App $a) {

	uexport_account($a);
	echo "\n";

	$r = q("SELECT count(*) as `total` FROM `item` WHERE `uid` = %d ",
		intval(local_user())
	);
	if (DBM::is_result($r)) {
		$total = $r[0]['total'];
	}
	// chunk the output to avoid exhausting memory

	for ($x = 0; $x < $total; $x += 500) {
		$item = [];
		$r = q("SELECT * FROM `item` WHERE `uid` = %d LIMIT %d, %d",
			intval(local_user()),
			intval($x),
			intval(500)
		);

		$output = ['item' => $r];
		echo json_encode($output, JSON_PARTIAL_OUTPUT_ON_ERROR). "\n";
	}
}
