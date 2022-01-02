<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Util\Strings;

function wall_attach_post(App $a) {

	$r_json = (!empty($_GET['response']) && $_GET['response']=='json');

	if (DI::args()->getArgc() > 1) {
		$nick = DI::args()->getArgv()[1];
		$owner = User::getOwnerDataByNick($nick);
		if (!DBA::isResult($owner)) {
			if ($r_json) {
				echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
				exit();
			}
			return;
		}
	} else {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
			exit();
		}

		return;
	}

	$can_post  = false;

	$page_owner_uid = $owner['uid'];
	$page_owner_cid = $owner['id'];
	$community_page = $owner['page-flags'] == User::PAGE_FLAGS_COMMUNITY;

	if (local_user() && (local_user() == $page_owner_uid)) {
		$can_post = true;
	} elseif ($community_page && !empty(Session::getRemoteContactID($page_owner_uid))) {
		$contact_id = Session::getRemoteContactID($page_owner_uid);
		$can_post = DBA::exists('contact', ['blocked' => false, 'pending' => false, 'id' => $contact_id, 'uid' => $page_owner_uid]);
	}

	if (!$can_post) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Permission denied.')]);
			exit();
		}
		notice(DI::l10n()->t('Permission denied.') . EOL );
		exit();
	}

	if (empty($_FILES['userfile'])) {
		if ($r_json) {
			echo json_encode(['error' => DI::l10n()->t('Invalid request.')]);
		}
		exit();
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);

	$maxfilesize = DI::config()->get('system','maxfilesize');

	/* Found html code written in text field of form,
	 * when trying to upload a file with filesize
	 * greater than upload_max_filesize. Cause is unknown.
	 * Then Filesize gets <= 0.
	 */

	if ($filesize <= 0) {
		$msg = DI::l10n()->t('Sorry, maybe your upload is bigger than the PHP configuration allows') . EOL .(DI::l10n()->t('Or - did you try to upload an empty file?'));
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			notice($msg);
		}
		@unlink($src);
		exit();
	}

	if ($maxfilesize && $filesize > $maxfilesize) {
		$msg = DI::l10n()->t('File exceeds size limit of %s', Strings::formatBytes($maxfilesize));
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo $msg . EOL;
		}
		@unlink($src);
		exit();
	}

	$newid = Attach::storeFile($src, $page_owner_uid, $filename, '<' . $page_owner_cid . '>');

	@unlink($src);

	if ($newid === false) {
		$msg =  DI::l10n()->t('File upload failed.');
		if ($r_json) {
			echo json_encode(['error' => $msg]);
		} else {
			echo $msg . EOL;
		}
		exit();
	}

	if ($r_json) {
		echo json_encode(['ok' => true, 'id' => $newid]);
		exit();
	}

	$lf = "\n";

	echo  $lf . $lf . '[attachment]' . $newid . '[/attachment]' . $lf;

	exit();
	// NOTREACHED
}
