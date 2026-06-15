<?php
/**
 * Name: Larpnet WIFI
 * Description: Zarządza dostępem do sieci WIFI. Przy rejestracji i na żądanie użytkownika
 *   zapisuje rekord do kolejki, który zewnętrzny skrypt odbiera i konfiguruje konto
 *   w MikroTik RouterOS User Manager.
 * Version: 1.0
 * Author: larpnet admin <https://larpnet.pl>
 */

use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;

const LARPNET_WIFI_SPOOL_DIR = '/var/spool/portalprov';

function larpnet_wifi_install()
{
	Hook::register('register_account',    __FILE__, 'larpnet_wifi_register_account');
	Hook::register('addon_settings',      __FILE__, 'larpnet_wifi_settings');
	Hook::register('addon_settings_post', __FILE__, 'larpnet_wifi_settings_post');
}

function larpnet_wifi_uninstall()
{
	Hook::unregister('register_account',    __FILE__, 'larpnet_wifi_register_account');
	Hook::unregister('addon_settings',      __FILE__, 'larpnet_wifi_settings');
	Hook::unregister('addon_settings_post', __FILE__, 'larpnet_wifi_settings_post');
}

function larpnet_wifi_write(int $uid): bool
{
	$user = DI::dba()->selectFirst('user', ['uid', 'username', 'email', 'nickname'], ['uid' => $uid]);
	if (!DBA::isResult($user) || empty($user['email'])) {
		return false;
	}

	$dir = LARPNET_WIFI_SPOOL_DIR;
	if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
		DI::logger()->warning('larpnet_wifi: could not create spool dir', ['dir' => $dir]);
		return false;
	}

	$record = json_encode([
		'uid'          => $uid,
		'portal_user'  => $user['nickname'],
		'email'        => $user['email'],
		'realname'     => $user['username'] ?: $user['nickname'],
		'requested_at' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('c'),
	]);

	$name  = sprintf('%d-%s', $uid, bin2hex(random_bytes(6)));
	$tmp   = "$dir/$name.json.tmp";
	$final = "$dir/$name.json";

	if (file_put_contents($tmp, $record) === false) {
		DI::logger()->warning('larpnet_wifi: could not write spool file', ['tmp' => $tmp]);
		return false;
	}

	chmod($tmp, 0600);
	rename($tmp, $final);

	DI::logger()->info('larpnet_wifi: spool record written', ['uid' => $uid, 'file' => $final]);
	return true;
}

function larpnet_wifi_register_account(int &$uid)
{
	larpnet_wifi_write((int) $uid);
}

function larpnet_wifi_settings(array &$data)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$html = '<p>Kliknij przycisk, aby zresetować hasło do sieci WIFI. '
		. 'Nowe hasło zostanie wysłane na Twój adres email w ciągu kilku minut.</p>';

	$data = [
		'addon'  => 'larpnet_wifi',
		'title'  => 'Larpnet WIFI',
		'html'   => $html,
		'submit' => 'Zresetuj hasło WIFI',
	];
}

function larpnet_wifi_settings_post(array &$b)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid || empty($_POST['larpnet_wifi-submit'])) {
		return;
	}

	if (larpnet_wifi_write($uid)) {
		DI::sysmsg()->addInfo(DI::l10n()->t('Żądanie resetu hasła WIFI zostało przyjęte. Nowe hasło otrzymasz emailem.'));
	} else {
		DI::sysmsg()->addNotice(DI::l10n()->t('Nie udało się zapisać żądania. Spróbuj ponownie lub skontaktuj się z administratorem.'));
	}
}
