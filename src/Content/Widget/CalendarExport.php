<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Content\Widget;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;

/**
 * TagCloud widget
 *
 * @author Rabuzarus
 */
class CalendarExport
{
	/**
	 * Get the events widget.
	 *
	 * @param int $uid
	 *
	 * @return string Formated HTML of the calendar widget.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getHTML(int $uid = 0): string
	{
		if (empty($uid)) {
			return '';
		}

		$user = User::getById($uid, ['nickname']);
		if (empty($user['nickname'])) {
			return '';
		}

		$tpl = Renderer::getMarkupTemplate('widget/events.tpl');
		$return = Renderer::replaceMacros($tpl, [
			'$etitle'      => DI::l10n()->t('Export'),
			'$export_ical' => DI::l10n()->t('Export calendar as ical'),
			'$export_csv'  => DI::l10n()->t('Export calendar as csv'),
			'$user'        => $user['nickname']
		]);

		return $return;
	}
}
