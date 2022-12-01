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

namespace Friendica\Module\Profile;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;

class Media extends BaseProfile
{
	protected function content(array $request = []): string
	{
		$a = DI::app();

		$profile = ProfileModel::load($a, $this->parameters['nickname']);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!$profile['net-publish']) {
			DI::page()['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$is_owner = DI::userSession()->getLocalUserId() == $profile['uid'];

		$o = self::getTabsHTML('media', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$o .= Contact::getPostsFromUrl($profile['url'], false, 0, 0, true);

		return $o;
	}
}
