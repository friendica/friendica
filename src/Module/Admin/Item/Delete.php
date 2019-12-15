<?php

namespace Friendica\Module\Admin\Item;

use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;

class Delete extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		if (empty($_POST['page_deleteitem_submit'])) {
			return;
		}

		parent::checkFormSecurityTokenRedirectOnError('/admin/item/delete', 'admin_deleteitem');

		if (!empty($_POST['page_deleteitem_submit'])) {
			$guid = trim(Strings::escapeTags($_POST['deleteitemguid']));
			// The GUID should not include a "/", so if there is one, we got an URL
			// and the last part of it is most likely the GUID.
			if (strpos($guid, '/')) {
				$guid = substr($guid, strrpos($guid, '/') + 1);
			}
			// Now that we have the GUID, drop those items, which will also delete the
			// associated threads.
			Item::delete(['guid' => $guid]);
		}

		info(L10n::t('Item marked for deletion.') . EOL);
		DI::baseUrl()->redirect('admin/item/delete');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$t = Renderer::getMarkupTemplate('admin/item/delete.tpl');

		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Delete Item'),
			'$submit' => L10n::t('Delete this Item'),
			'$intro1' => L10n::t('On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'),
			'$intro2' => L10n::t('You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'),
			'$deleteitemguid' => ['deleteitemguid', L10n::t("GUID"), '', L10n::t("The GUID of the item you want to delete."), 'required', 'autofocus'],
			'$form_security_token' => parent::getFormSecurityToken("admin_deleteitem")
		]);
	}
}
