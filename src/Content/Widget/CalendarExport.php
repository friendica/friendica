<?php

/*
 * @file src/Content/Widget/CalendarExport.php
 */

namespace Friendica\Content\Widget;

use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Registry\DI;

/**
 * TagCloud widget
 *
 * @author Rabuzarus
 */
class CalendarExport
{
	/**
	 * @brief Get the events widget.
	 *
	 * @return string Formated HTML of the calendar widget.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getHTML() {
		$a = DI::app();

		if (empty($a->data['user'])) {
			return;
		}

		$owner_uid = intval($a->data['user']['uid']);

		// The permission testing is a little bit tricky because we have to respect many cases.

		// It's not the private events page (we don't get the $owner_uid for /events).
		if (!local_user() && !$owner_uid) {
			return;
		}

		/*
		 * If it's a kind of profile page (intval($owner_uid)) return if the user not logged in and
		 * export feature isn't enabled.
		 */
		/*
		 * Cal logged in user (test permission at foreign profile page).
		 * If the $owner uid is available we know it is part of one of the profile pages (like /cal).
		 * So we have to test if if it's the own profile page of the logged in user
		 * or a foreign one. For foreign profile pages we need to check if the feature
		 * for exporting the cal is enabled (otherwise the widget would appear for logged in users
		 * on foreigen profile pages even if the widget is disabled).
		 */
		if (local_user() != $owner_uid && !Feature::isEnabled($owner_uid, "export_calendar")) {
			return;
		}

		// $a->data is only available if the profile page is visited. If the visited page is not part
		// of the profile page it should be the personal /events page. So we can use $a->user.
		$user = ($a->data['user']['nickname'] ?? '') ?: $a->user['nickname'];

		$tpl = Renderer::getMarkupTemplate("widget/events.tpl");
		$return = Renderer::replaceMacros($tpl, [
			'$etitle'      => L10n::t("Export"),
			'$export_ical' => L10n::t("Export calendar as ical"),
			'$export_csv'  => L10n::t("Export calendar as csv"),
			'$user'        => $user
		]);

		return $return;
	}
}
