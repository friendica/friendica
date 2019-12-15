<?php

namespace Friendica\Module\Admin\Blocklist;

use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Model;

class Contact extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		$contact_url  = $_POST['contact_url'] ?? '';
		$block_reason = $_POST['contact_block_reason'] ?? '';
		$contacts     = $_POST['contacts'] ?? [];

		parent::checkFormSecurityTokenRedirectOnError('/admin/blocklist/contact', 'admin_contactblock');

		if (!empty($_POST['page_contactblock_block'])) {
			$contact_id = Model\Contact::getIdForURL($contact_url);
			if ($contact_id) {
				Model\Contact::block($contact_id, $block_reason);
				notice(L10n::t('The contact has been blocked from the node'));
			} else {
				notice(L10n::t('Could not find any contact entry for this URL (%s)', $contact_url));
			}
		}

		if (!empty($_POST['page_contactblock_unblock'])) {
			foreach ($contacts as $uid) {
				Model\Contact::unblock($uid);
			}
			notice(L10n::tt('%s contact unblocked', '%s contacts unblocked', count($contacts)));
		}

		DI::baseUrl()->redirect('admin/blocklist/contact');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$condition = ['uid' => 0, 'blocked' => true];

		$total = DBA::count('contact', $condition);

		$pager = new Pager($a->query_string, 30);

		$contacts = Model\Contact::selectToArray([], $condition, ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);

		$t = Renderer::getMarkupTemplate('admin/blocklist/contact.tpl');
		$o = Renderer::replaceMacros($t, [
			// strings //
			'$title'       => L10n::t('Administration'),
			'$page'        => L10n::t('Remote Contact Blocklist'),
			'$description' => L10n::t('This page allows you to prevent any message from a remote contact to reach your node.'),
			'$submit'      => L10n::t('Block Remote Contact'),
			'$select_all'  => L10n::t('select all'),
			'$select_none' => L10n::t('select none'),
			'$block'       => L10n::t('Block'),
			'$unblock'     => L10n::t('Unblock'),
			'$no_data'     => L10n::t('No remote contact is blocked from this node.'),

			'$h_contacts'  => L10n::t('Blocked Remote Contacts'),
			'$h_newblock'  => L10n::t('Block New Remote Contact'),
			'$th_contacts' => [L10n::t('Photo'), L10n::t('Name'), L10n::t('Reason')],

			'$form_security_token' => parent::getFormSecurityToken('admin_contactblock'),

			// values //
			'$baseurl'    => $a->getBaseURL(true),

			'$contacts'   => $contacts,
			'$total_contacts' => L10n::tt('%s total blocked contact', '%s total blocked contacts', $total),
			'$paginate'   => $pager->renderFull($total),
			'$contacturl' => ['contact_url', L10n::t('Profile URL'), '', L10n::t('URL of the remote contact to block.')],
			'$contact_block_reason' => ['contact_block_reason', L10n::t('Block Reason')],
		]);
		return $o;
	}
}
