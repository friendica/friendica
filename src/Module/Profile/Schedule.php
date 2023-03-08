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

namespace Friendica\Module\Profile;

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

class Schedule extends BaseProfile
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		if (empty($_REQUEST['delete'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!DBA::exists('delayed-post', ['id' => $_REQUEST['delete'], 'uid' => DI::userSession()->getLocalUserId()])) {
			throw new HTTPException\NotFoundException();
		}

		Post\Delayed::deleteById($_REQUEST['delete']);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		$o = self::getTabsHTML('schedule', true, $a->getLoggedInUserNickname(), false);

		$schedule = [];
		$delayed = DBA::select('delayed-post', [], ['uid' => DI::userSession()->getLocalUserId()]);
		while ($row = DBA::fetch($delayed)) {
			$parameter = Post\Delayed::getParametersForid($row['id']);
			if (empty($parameter)) {
				continue;
			}
			$schedule[] = [
				'id'           => $row['id'],
				'scheduled_at' => DateTimeFormat::local($row['delayed']),
				'content'      => BBCode::toPlaintext($parameter['item']['body'], false)
			];
		}
		DBA::close($delayed);

		$tpl = Renderer::getMarkupTemplate('profile/schedule.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("profile_schedule"),
			'$title'               => DI::l10n()->t('Scheduled Posts'),
			'$nickname'            => $this->parameters['nickname'] ?? '',
			'$scheduled_at'        => DI::l10n()->t('Scheduled'),
			'$content'             => DI::l10n()->t('Content'),
			'$delete'              => DI::l10n()->t('Remove post'),
			'$schedule'            => $schedule,
		]);

		return $o;
	}
}
