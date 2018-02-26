<?php
/**
 * @file include/acl_selectors.php
 */

use Friendica\App;
use Friendica\Core\Acl;
use Friendica\Model\Contact;

require_once "mod/proxy.php";

function contact_select($selname, $selclass, $preselected = false, $size = 4, $privmail = false, $celeb = false, $privatenet = false, $tabindex = null) {
	return Acl::getMessageContactSelectHTML($selname, $selclass, defaults($preselected, []), $size, $privmail, $celeb, $privatenet, $tabindex);
}

function prune_deadguys($arr) {
	Contact::pruneUnavailable($arr);
	return $arr;
}

function get_acl_permissions($user = null) {
	return Acl::getDefaultUserPermissions($user);
}

function populate_acl($user = null, $show_jotnets = false) {
	return Acl::getFullSelectorHTML($user, $show_jotnets);
}

function navbar_complete(App $a) {
	$search = notags(trim($_REQUEST['search']));
	$mode = $_REQUEST['smode'];

	return Acl::contactAutocomplete($search, $mode);
}
