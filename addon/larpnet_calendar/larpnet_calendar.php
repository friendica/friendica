<?php
/**
 * Name: LARPnet Calendar Feed
 * Description: Generuje prywatny URL do subskrypcji kalendarza (iCal) dla aplikacji
 *   takich jak Google Calendar, Apple Calendar czy Thunderbird. Każdy użytkownik
 *   otrzymuje unikalny, tajny link — nie wymaga logowania. Link można znaleźć
 *   w bocznym panelu strony kalendarza lub w Ustawienia → Addony → LARPnet Calendar Feed.
 *   Aby zregenerować link (unieważnić stary), wejdź w ustawienia addonu.
 * Version: 1.0
 * Author: larpnet admin
 */

use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Event;
use Friendica\Model\User;
use Friendica\Util\Strings;

function larpnet_calendar_install()
{
	Hook::register('addon_settings',      __FILE__, 'larpnet_calendar_addon_settings');
	Hook::register('addon_settings_post', __FILE__, 'larpnet_calendar_addon_settings_post');
	Hook::register('page_end',            __FILE__, 'larpnet_calendar_page_end');
	DI::logger()->info('installed addon larpnet_calendar');
}

function larpnet_calendar_module() {}

function larpnet_calendar_get_or_create_token(int $uid): string
{
	$token = DI::pConfig()->get($uid, 'larpnet_calendar', 'token');
	if (empty($token)) {
		$token = Strings::getRandomHex(32);
		DI::pConfig()->set($uid, 'larpnet_calendar', 'token', $token);
	}
	return $token;
}

function larpnet_calendar_init()
{
	$token = $_GET['token'] ?? '';
	if (empty($token)) {
		return;
	}

	$row = DBA::selectFirst('pconfig', ['uid'], [
		'cat' => 'larpnet_calendar',
		'k'   => 'token',
		'v'   => $token,
	]);

	if (!DBA::isResult($row)) {
		header('HTTP/1.1 403 Forbidden');
		header('Content-Type: text/plain');
		echo 'Invalid token.';
		exit;
	}

	$uid    = (int) $row['uid'];
	$result = Event::exportListByUserId($uid, 'ical');

	$content = !empty($result['success'])
		? $result['content']
		: "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//larpnet calendar//1.0//EN\r\nEND:VCALENDAR\r\n";

	header('Content-Type: text/calendar; charset=utf-8');
	header('Content-Disposition: inline; filename="calendar.ics"');
	echo $content;
	exit;
}

function larpnet_calendar_page_end(string &$body)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$command = DI::args()->getCommand();

	if ($command === 'calendar') {
		// User's own calendar page — show widget
	} elseif (strpos($command, 'calendar/show/') === 0) {
		// /calendar/show/{nick} — only show widget on own profile
		$nick = explode('/', substr($command, strlen('calendar/show/')))[0];
		$self = User::getById($uid, ['nickname']);
		if (empty($self) || $self['nickname'] !== $nick) {
			return;
		}
	} else {
		return;
	}

	$token   = larpnet_calendar_get_or_create_token($uid);
	$feedUrl = htmlspecialchars((string) DI::baseUrl() . '/larpnet_calendar?token=' . $token);
	$rawUrl  = (string) DI::baseUrl() . '/larpnet_calendar?token=' . $token;

	$body .= '<script>(function(){'
		. 'var anchor=document.getElementById("sidebar-calendar");'
		. 'if(!anchor)return;'
		. 'var nav=document.createElement("nav");'
		. 'nav.id="sidebar-calendar-feed";'
		. 'nav.className="widget";'
		. 'nav.innerHTML='
		. '"<h3>Subskrypcja kalendarza</h3>"'
		. '+"<ul>"'
		. '+"<li><input id=\"larpnet-cal-url\" class=\"form-control input-sm\" value=\"' . $feedUrl . '\" readonly style=\"width:100%;margin-bottom:4px\">'
		. '<button class=\"btn btn-default btn-sm\" type=\"button\" onclick=\"(function(){var e=document.getElementById(\'larpnet-cal-url\');if(navigator.clipboard){navigator.clipboard.writeText(e.value)}else{e.select();document.execCommand(\'copy\')}})();return false;\">Kopiuj</button></li>"'
		. '+"<li><small>Wklej URL w Google Calendar, Apple Calendar lub Thunderbird jako „Subskrybuj przez URL”.</small></li>"'
		. '+"</ul>";'
		. 'anchor.insertAdjacentElement("afterend",nav);'
		. '})();</script>';
}

function larpnet_calendar_addon_settings(array &$data)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$token   = larpnet_calendar_get_or_create_token($uid);
	$feedUrl = htmlspecialchars((string) DI::baseUrl() . '/larpnet_calendar?token=' . $token);

	$html = '<div class="form-group">'
		. '<label>URL subskrypcji kalendarza</label>'
		. '<div class="input-group">'
		. '<input id="larpnet-cal-settings-url" class="form-control" value="' . $feedUrl . '" readonly>'
		. '<span class="input-group-btn">'
		. '<button class="btn btn-default" type="button" onclick="(function(){var e=document.getElementById(\'larpnet-cal-settings-url\');if(navigator.clipboard){navigator.clipboard.writeText(e.value)}else{e.select();document.execCommand(\'copy\')}})();return false;">Kopiuj</button>'
		. '</span>'
		. '</div>'
		. '<p class="help-block">Wklej ten URL w Google Calendar, Apple Calendar lub Thunderbird jako &bdquo;Subskrybuj przez URL&rdquo;. '
		. 'Link daje dostęp do wszystkich Twoich wydarzeń (publicznych i prywatnych) bez logowania &mdash; traktuj go jak hasło. '
		. 'Aby unieważnić stary link i wygenerować nowy, użyj przycisku poniżej.</p>'
		. '</div>';

	$data = [
		'addon'  => 'larpnet_calendar',
		'title'  => 'LARPnet Calendar Feed',
		'html'   => $html,
		'submit' => 'Wygeneruj nowy token',
	];
}

function larpnet_calendar_addon_settings_post(array &$b)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid || empty($_POST['larpnet_calendar-submit'])) {
		return;
	}

	$token = Strings::getRandomHex(32);
	DI::pConfig()->set($uid, 'larpnet_calendar', 'token', $token);
	DI::sysmsg()->addInfo('Wygenerowano nowy token. Stary link do kalendarza przestał działać.');
}
