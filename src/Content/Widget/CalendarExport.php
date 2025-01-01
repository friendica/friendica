<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

		$tpl    = Renderer::getMarkupTemplate('widget/events.tpl');
		$return = Renderer::replaceMacros($tpl, [
			'$etitle'      => DI::l10n()->t('Export'),
			'$export_ical' => DI::l10n()->t('Export calendar as ical'),
			'$export_csv'  => DI::l10n()->t('Export calendar as csv'),
			'$user'        => $user['nickname']
		]);

		return $return;
	}
}
