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

namespace Friendica\Module\Admin\Blocklist;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Network;

class Contact extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/blocklist/contact', 'admin_contactblock');

		$contact_url  = $_POST['contact_url'] ?? '';
		$block_reason = $_POST['contact_block_reason'] ?? '';
		$block_purge  = $_POST['contact_block_purge'] ?? false;
		$contacts     = $_POST['contacts'] ?? [];

		if (!empty($_POST['page_contactblock_block'])) {
			$contact = Model\Contact::getByURL($contact_url, null, ['id', 'nurl']);
			if (empty($contact)) {
				notice(DI::l10n()->t('Could not find any contact entry for this URL (%s)', $contact_url));
				DI::baseUrl()->redirect('admin/blocklist/contact');
			}

			if (Network::isLocalLink($contact['nurl'])) {
				notice(DI::l10n()->t('You can\'t block a local contact, please block the user instead'));
				DI::baseUrl()->redirect('admin/blocklist/contact');
			}

			Model\Contact::block($contact['id'], $block_reason);

			if ($block_purge) {
				foreach (Model\Contact::selectToArray(['id'], ['nurl' => $contact['nurl']]) as $contact) {
					Worker::add(PRIORITY_LOW, 'Contact\RemoveContent', $contact['id']);
				}
			}

			info(DI::l10n()->t('The contact has been blocked from the node'));
		}

		if (!empty($_POST['page_contactblock_unblock'])) {
			foreach ($contacts as $uid) {
				Model\Contact::unblock($uid);
			}
			info(DI::l10n()->tt('%s contact unblocked', '%s contacts unblocked', count($contacts)));
		}

		DI::baseUrl()->redirect('admin/blocklist/contact');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$condition = ['uid' => 0, 'blocked' => true];

		$total = DBA::count('contact', $condition);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 30);

		$contacts = Model\Contact::selectToArray([], $condition, ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);

		$t = Renderer::getMarkupTemplate('admin/blocklist/contact.tpl');
		$o = Renderer::replaceMacros($t, [
			// strings //
			'$title'       => DI::l10n()->t('Administration'),
			'$page'        => DI::l10n()->t('Remote Contact Blocklist'),
			'$description' => DI::l10n()->t('This page allows you to prevent any message from a remote contact to reach your node.'),
			'$submit'      => DI::l10n()->t('Block Remote Contact'),
			'$select_all'  => DI::l10n()->t('select all'),
			'$select_none' => DI::l10n()->t('select none'),
			'$block'       => DI::l10n()->t('Block'),
			'$unblock'     => DI::l10n()->t('Unblock'),
			'$no_data'     => DI::l10n()->t('No remote contact is blocked from this node.'),

			'$h_contacts'  => DI::l10n()->t('Blocked Remote Contacts'),
			'$h_newblock'  => DI::l10n()->t('Block New Remote Contact'),
			'$th_contacts' => [DI::l10n()->t('Photo'), DI::l10n()->t('Name'), DI::l10n()->t('Reason')],

			'$form_security_token' => self::getFormSecurityToken('admin_contactblock'),

			// values //
			'$baseurl'    => DI::baseUrl()->get(true),

			'$contacts'   => $contacts,
			'$total_contacts' => DI::l10n()->tt('%s total blocked contact', '%s total blocked contacts', $total),
			'$paginate'   => $pager->renderFull($total),
			'$contacturl' => ['contact_url', DI::l10n()->t('Profile URL'), '', DI::l10n()->t('URL of the remote contact to block.')],
			'$contact_block_purge'  => ['contact_block_purge', DI::l10n()->t('Also purge contact'), false, DI::l10n()->t('Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.')],
			'$contact_block_reason' => ['contact_block_reason', DI::l10n()->t('Block Reason')],
		]);
		return $o;
	}
}
