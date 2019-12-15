<?php

namespace Friendica\Module\Admin\Blocklist;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Strings;

class Server extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		if (empty($_POST['page_blocklist_save']) && empty($_POST['page_blocklist_edit'])) {
			return;
		}

		parent::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server', 'admin_blocklist');

		if (!empty($_POST['page_blocklist_save'])) {
			//  Add new item to blocklist
			$blocklist = Config::get('system', 'blocklist');
			$blocklist[] = [
				'domain' => Strings::escapeTags(trim($_POST['newentry_domain'])),
				'reason' => Strings::escapeTags(trim($_POST['newentry_reason']))
			];
			Config::set('system', 'blocklist', $blocklist);
			info(L10n::t('Server domain pattern added to blocklist.') . EOL);
		} else {
			// Edit the entries from blocklist
			$blocklist = [];
			foreach ($_POST['domain'] as $id => $domain) {
				// Trimming whitespaces as well as any lingering slashes
				$domain = Strings::escapeTags(trim($domain, "\x00..\x1F/"));
				$reason = Strings::escapeTags(trim($_POST['reason'][$id]));
				if (empty($_POST['delete'][$id])) {
					$blocklist[] = [
						'domain' => $domain,
						'reason' => $reason
					];
				}
			}
			Config::set('system', 'blocklist', $blocklist);
			info(L10n::t('Site blocklist updated.') . EOL);
		}

		DI::app()->internalRedirect('admin/blocklist/server');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$blocklist = Config::get('system', 'blocklist');
		$blocklistform = [];
		if (is_array($blocklist)) {
			foreach ($blocklist as $id => $b) {
				$blocklistform[] = [
					'domain' => ["domain[$id]", L10n::t('Blocked server domain pattern'), $b['domain'], '', 'required', '', ''],
					'reason' => ["reason[$id]", L10n::t("Reason for the block"), $b['reason'], '', 'required', '', ''],
					'delete' => ["delete[$id]", L10n::t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, L10n::t("Check to delete this entry from the blocklist")]
				];
			}
		}

		$t = Renderer::getMarkupTemplate('admin/blocklist/server.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Server Domain Pattern Blocklist'),
			'$intro' => L10n::t('This page can be used to define a blacklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'),
			'$public' => L10n::t('The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'),
			'$syntax' => L10n::t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
	<li><code>[&lt;char1&gt;&lt;char2&gt;...]</code>: char1 or char2</li>
</ul>'),
			'$addtitle' => L10n::t('Add new entry to block list'),
			'$newdomain' => ['newentry_domain', L10n::t('Server Domain Pattern'), '', L10n::t('The domain pattern of the new server to add to the block list. Do not include the protocol.'), 'required', '', ''],
			'$newreason' => ['newentry_reason', L10n::t('Block reason'), '', L10n::t('The reason why you blocked this server domain pattern.'), 'required', '', ''],
			'$submit' => L10n::t('Add Entry'),
			'$savechanges' => L10n::t('Save changes to the blocklist'),
			'$currenttitle' => L10n::t('Current Entries in the Blocklist'),
			'$thurl' => L10n::t('Blocked server domain pattern'),
			'$threason' => L10n::t('Reason for the block'),
			'$delentry' => L10n::t('Delete entry from blocklist'),
			'$entries' => $blocklistform,
			'$baseurl' => $a->getBaseURL(true),
			'$confirm_delete' => L10n::t('Delete entry from blocklist?'),
			'$form_security_token' => parent::getFormSecurityToken("admin_blocklist")
		]);
	}
}
