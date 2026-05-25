<?php
/**
 * Name: Larpnet Banner
 * Description: Baner (header image) profilu dla motywu Larpnet.
 * Version: 1.1
 * Author: larpnet admin <https://larpnet.pl>
 */

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Photo;

function larpnet_banner_install()
{
	Hook::register('addon_settings',      __FILE__, 'larpnet_banner_settings');
	Hook::register('addon_settings_post', __FILE__, 'larpnet_banner_settings_post');
}

function larpnet_banner_settings(array &$data)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$self = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
	$cid  = $self['id'] ?? 0;

	$preview = $cid
		? '<p><img src="/photo/header/' . $cid . '" style="max-width:100%;max-height:200px;border-radius:4px;" /></p>'
		: '';

	// The framework wraps our HTML in a plain <form> with no enctype.
	// We patch it to multipart/form-data so the file input is transmitted.
	$html = $preview . '
<div class="form-group">
	<label for="larpnet-banner-file">Wybierz nowy obraz (JPG/PNG, zalecane min. 1500×500 px):</label>
	<input type="file" id="larpnet-banner-file" name="larpnet_banner" accept="image/*" class="form-control"
		onchange="if(this.form){this.form.enctype=\'multipart/form-data\';}" />
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
	var input = document.getElementById("larpnet-banner-file");
	if (input && input.form) { input.form.enctype = "multipart/form-data"; }
});
</script>';

	$data = [
		'addon'  => 'larpnet_banner',
		'title'  => 'Baner profilu',
		'html'   => $html,
		'submit' => 'Prześlij baner',
	];
}

function larpnet_banner_settings_post(array &$b)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}
	// Framework submit button name is "{addon}-submit" = "larpnet_banner-submit"
	if (empty($_POST['larpnet_banner-submit'])) {
		return;
	}
	if (empty($_FILES['larpnet_banner']['tmp_name'])) {
		return;
	}

	$uid    = DI::userSession()->getLocalUserId();
	$result = Photo::uploadBanner($uid, $_FILES['larpnet_banner']);

	if ($result) {
		DI::sysmsg()->addInfo(DI::l10n()->t('Baner profilu zaktualizowany.'));
	} else {
		DI::sysmsg()->addNotice(DI::l10n()->t('Nie udało się przesłać banera. Sprawdź format i rozmiar pliku.'));
	}
}
