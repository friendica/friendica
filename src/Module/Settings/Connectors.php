<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Protocol\Email;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Connectors extends BaseSettings
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IManagePersonalConfigValues */
	private $pconfig;
	/** @var Database */
	private $database;
	/** @var SystemMessages */
	private $systemMessages;

	public function __construct(SystemMessages $systemMessages, Database $database, IManagePersonalConfigValues $pconfig, IManageConfigValues $config, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config         = $config;
		$this->pconfig        = $pconfig;
		$this->database       = $database;
		$this->systemMessages = $systemMessages;
	}

	protected function post(array $request = [])
	{
		BaseSettings::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'settings_connectors');

		$user = User::getById($this->session->getLocalUserId());

		if (!empty($request['general-submit'])) {
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'accept_only_sharer', intval($request['accept_only_sharer']));
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'disable_cw', !intval($request['enable_cw']));
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'no_intelligent_shortening', !intval($request['enable_smart_shortening']));
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'simple_shortening', intval($request['simple_shortening']));
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'attach_link_title', intval($request['attach_link_title']));
			$this->pconfig->set($this->session->getLocalUserId(), 'system', 'api_spoiler_title', intval($request['api_spoiler_title']));
			$this->pconfig->set($this->session->getLocalUserId(), 'ostatus', 'legacy_contact', $request['legacy_contact']);
		} elseif (!empty($request['mail-submit']) && function_exists('imap_open') && !$this->config->get('system', 'imap_disabled')) {
			$mail_server       =                 $request['mail_server'] ?? '';
			$mail_port         =                 $request['mail_port'] ?? '';
			$mail_ssl          = strtolower(trim($request['mail_ssl'] ?? ''));
			$mail_user         =                 $request['mail_user'] ?? '';
			$mail_pass         =            trim($request['mail_pass'] ?? '');
			$mail_action       =            trim($request['mail_action'] ?? '');
			$mail_movetofolder =            trim($request['mail_movetofolder'] ?? '');
			$mail_replyto      =                 $request['mail_replyto'] ?? '';
			$mail_pubmail      =                 $request['mail_pubmail'] ?? '';

			if (!$this->database->exists('mailacct', ['uid' => $this->session->getLocalUserId()])) {
				$this->database->insert('mailacct', ['uid' => $this->session->getLocalUserId()]);
			}

			if (strlen($mail_pass)) {
				$pass = '';
				openssl_public_encrypt($mail_pass, $pass, $user['pubkey']);
				$this->database->update('mailacct', ['pass' => bin2hex($pass)], ['uid' => $this->session->getLocalUserId()]);
			}

			$r = $this->database->update('mailacct', [
				'server'       => $mail_server,
				'port'         => $mail_port,
				'ssltype'      => $mail_ssl,
				'user'         => $mail_user,
				'action'       => $mail_action,
				'movetofolder' => $mail_movetofolder,
				'mailbox'      => 'INBOX',
				'reply_to'     => $mail_replyto,
				'pubmail'      => $mail_pubmail
			], ['uid' => $this->session->getLocalUserId()]);

			$this->logger->debug('updating mailaccount', ['response' => $r]);
			$mailacct = $this->database->selectFirst('mailacct', [], ['uid' => $this->session->getLocalUserId()]);
			if ($this->database->isResult($mailacct)) {
				if (strlen($mailacct['server'])) {
					$dcrpass = '';
					openssl_private_decrypt(hex2bin($mailacct['pass']), $dcrpass, $user['prvkey']);
					$mbox = Email::connect(Email::constructMailboxName($mailacct), $mail_user, $dcrpass);
					unset($dcrpass);
					if (!$mbox) {
						$this->systemMessages->addNotice($this->t('Failed to connect with email account using the settings provided.'));
					}
				}
			}
		}

		Hook::callAll('connector_settings_post', $request);
		$this->baseUrl->redirect($this->args->getQueryString());
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		$accept_only_sharer      =  intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'accept_only_sharer'));
		$enable_cw               = !intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'disable_cw'));
		$enable_smart_shortening = !intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'no_intelligent_shortening'));
		$simple_shortening       =  intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'simple_shortening'));
		$attach_link_title       =  intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'attach_link_title'));
		$api_spoiler_title       =  intval($this->pconfig->get($this->session->getLocalUserId(), 'system', 'api_spoiler_title', true));
		$legacy_contact          =         $this->pconfig->get($this->session->getLocalUserId(), 'ostatus', 'legacy_contact');

		if (!empty($legacy_contact)) {
			$this->baseUrl->redirect('ostatus/subscribe?url=' . urlencode($legacy_contact));
		}

		$connector_settings_forms = [];
		foreach ($this->database->selectToArray('hook', ['file', 'function'], ['hook' => 'connector_settings']) as $hook) {
			$data = [];
			Hook::callSingle('connector_settings', [$hook['file'], $hook['function']], $data);

			$tpl                                          = Renderer::getMarkupTemplate('settings/addons/connector.tpl');
			$connector_settings_forms[$data['connector']] = Renderer::replaceMacros($tpl, [
				'$connector' => $data['connector'],
				'$title'     => $data['title'],
				'$image'     => $data['image'] ?? '',
				'$enabled'   => $data['enabled'] ?? true,
				'$open'      => ($this->parameters['connector'] ?? '') === $data['connector'],
				'$html'      => $data['html'] ?? '',
				'$submit'    => $data['submit'] ?? $this->t('Save Settings'),
			]);
		}

		if ($this->session->isSiteAdmin()) {
			$diasp_enabled = $this->config->get('system', 'diaspora_enabled') ?
				$this->t('Built-in support for %s connectivity is enabled', $this->t('Diaspora (Socialhome, Hubzilla)')) :
				$this->t('Built-in support for %s connectivity is disabled', $this->t('Diaspora (Socialhome, Hubzilla)'));
			$ostat_enabled = $this->config->get('system', 'ostatus_disabled') ?
				$this->t('Built-in support for %s connectivity is disabled', $this->t('OStatus (GNU Social)')) :
				$this->t('Built-in support for %s connectivity is enabled', $this->t('OStatus (GNU Social)'));
		} else {
			$diasp_enabled = '';
			$ostat_enabled = '';
		}

		$mail_enabled = function_exists('imap_open') && !$this->config->get('system', 'imap_disabled');
		if ($mail_enabled) {
			$mail_account  = $this->database->selectFirst('mailacct', [], ['uid' => $this->session->getLocalUserId()]);
			$mail_disabled = '';
		} else {
			$mail_account  = null;
			$mail_disabled = $this->t('Email access is disabled on this site.');
		}

		$mail_server       = $mail_account['server']       ?? '';
		$mail_port         = (!empty($mail_account['port']) && is_numeric($mail_account['port'])) ? (int)$mail_account['port'] : '';
		$mail_ssl          = $mail_account['ssltype']      ?? '';
		$mail_user         = $mail_account['user']         ?? '';
		$mail_replyto      = $mail_account['reply_to']     ?? '';
		$mail_pubmail      = $mail_account['pubmail']      ?? 0;
		$mail_action       = $mail_account['action']       ?? 0;
		$mail_movetofolder = $mail_account['movetofolder'] ?? '';
		$mail_chk          = $mail_account['last_check']   ?? DBA::NULL_DATETIME;

		$ssl_options = ['TLS' => 'TLS', 'SSL' => 'SSL'];
		if ($this->config->get('system', 'insecure_imap')) {
			$ssl_options['notls'] = $this->t('None');
		}

		$tpl = Renderer::getMarkupTemplate('settings/connectors.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseSettings::getFormSecurityToken("settings_connectors"),

			'$title' => $this->t('Social Networks'),

			'$diasp_enabled' => $diasp_enabled,
			'$ostat_enabled' => $ostat_enabled,

			'$general_settings'   => $this->t('General Social Media Settings'),
			'$accept_only_sharer' => [
				'accept_only_sharer',
				$this->t('Followed content scope'),
				$accept_only_sharer,
				$this->t('By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'),
				[
					Item::COMPLETION_NONE    => $this->t('Only conversations my follows started'),
					Item::COMPLETION_COMMENT => $this->t('Conversations my follows started or commented on (default)'),
					Item::COMPLETION_LIKE    => $this->t('Any conversation my follows interacted with, including likes'),
				]
			],
			'$enable_cw'               => ['enable_cw', $this->t('Enable Content Warning'), $enable_cw, $this->t('Users on networks like Mastodon or Pleroma are able to set a content warning field which collapse their post by default. This enables the automatic collapsing instead of setting the content warning as the post title. Doesn\'t affect any other content filtering you eventually set up.')],
			'$enable_smart_shortening' => ['enable_smart_shortening', $this->t('Enable intelligent shortening'), $enable_smart_shortening, $this->t('Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.')],
			'$simple_shortening'       => ['simple_shortening', $this->t('Enable simple text shortening'), $simple_shortening, $this->t('Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.')],
			'$attach_link_title'       => ['attach_link_title', $this->t('Attach the link title'), $attach_link_title, $this->t('When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.')],
			'$api_spoiler_title'       => ['api_spoiler_title', $this->t('API: Use spoiler field as title'), $api_spoiler_title, $this->t('When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.')],
			'$legacy_contact'          => ['legacy_contact', $this->t('Your legacy ActivityPub/GNU Social account'), $legacy_contact, $this->t('If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.')],
			'$repair_ostatus_url'  => 'ostatus/repair',
			'$repair_ostatus_text' => $this->t('Repair OStatus subscriptions'),

			'$connector_settings_forms' => $connector_settings_forms,

			'$h_mail'            => $this->t('Email/Mailbox Setup'),
			'$mail_desc'         => $this->t("If you wish to communicate with email contacts using this service \x28optional\x29, please specify how to connect to your mailbox."),
			'$mail_lastcheck'    => ['mail_lastcheck', $this->t('Last successful email check:'), $mail_chk, ''],
			'$mail_disabled'     => $mail_disabled,
			'$mail_server'       => ['mail_server', $this->t('IMAP server name:'), $mail_server, ''],
			'$mail_port'         => ['mail_port', $this->t('IMAP port:'), $mail_port, ''],
			'$mail_ssl'          => ['mail_ssl', $this->t('Security:'), strtoupper($mail_ssl), '', $ssl_options],
			'$mail_user'         => ['mail_user', $this->t('Email login name:'), $mail_user, ''],
			'$mail_pass'         => ['mail_pass', $this->t('Email password:'), '', ''],
			'$mail_replyto'      => ['mail_replyto', $this->t('Reply-to address:'), $mail_replyto, 'Optional'],
			'$mail_pubmail'      => ['mail_pubmail', $this->t('Send public posts to all email contacts:'), $mail_pubmail, ''],
			'$mail_action'       => ['mail_action', $this->t('Action after import:'), $mail_action, '', [0 => $this->t('None'), 1 => $this->t('Delete'), 2 => $this->t('Mark as seen'), 3 => $this->t('Move to folder')]],
			'$mail_movetofolder' => ['mail_movetofolder', $this->t('Move to folder:'), $mail_movetofolder, ''],
			'$submit'            => $this->t('Save Settings'),
		]);

		return $o;
	}
}
