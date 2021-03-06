<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\DI;

require_once __DIR__ . '/../include/api.php';

function api_post(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}

	if (count($a->user) && !empty($a->user['uid']) && $a->user['uid'] != local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return;
	}
}

function api_content(App $a)
{
	echo api_call($a);
	exit();
}
