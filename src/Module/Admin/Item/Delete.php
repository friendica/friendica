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

namespace Friendica\Module\Admin\Item;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Module\BaseAdmin;

class Delete extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_deleteitem_submit'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/item/delete', 'admin_deleteitem');

		if (!empty($_POST['page_deleteitem_submit'])) {
			$guid = trim($_POST['deleteitemguid']);
			// The GUID should not include a "/", so if there is one, we got an URL
			// and the last part of it is most likely the GUID.
			if (strpos($guid, '/')) {
				$guid = substr($guid, strrpos($guid, '/') + 1);
			}
			// Now that we have the GUID, drop those items, which will also delete the
			// associated threads.
			Item::markForDeletion(['guid' => $guid]);
		}

		info(DI::l10n()->t('Item marked for deletion.'));
		DI::baseUrl()->redirect('admin/item/delete');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('admin/item/delete.tpl');

		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Delete Item'),
			'$submit' => DI::l10n()->t('Delete this Item'),
			'$intro1' => DI::l10n()->t('On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'),
			'$intro2' => DI::l10n()->t('You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'),
			'$deleteitemguid' => ['deleteitemguid', DI::l10n()->t("GUID"), '', DI::l10n()->t("The GUID of the item you want to delete."), DI::l10n()->t('Required'), 'autofocus'],
			'$form_security_token' => self::getFormSecurityToken("admin_deleteitem")
		]);
	}
}
