<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Email;
use Friendica\Util\Strings;

/**
 * Invite people to friendica
 */
class Invite extends BaseModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\ForbiddenException(L10n::t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('/', 'send_invite');

		$app = DI::app();
		$config = $app->getConfig();

		$max_invites = intval($config->get('system', 'max_invites'));
		if (!$max_invites) {
			$max_invites = 50;
		}

		$current_invites = intval(PConfig::get(local_user(), 'system', 'sent_invites'));
		if ($current_invites > $max_invites) {
			throw new HTTPException\ForbiddenException(L10n::t('Total invitation limit exceeded.'));
		}


		$recipients = !empty($_POST['recipients']) ? explode("\n", $_POST['recipients']) : [];
		$message = !empty($_POST['message']) ? Strings::escapeTags(trim($_POST['message'])) : '';

		$total = 0;
		$invitation_only = false;
		$invites_remaining = null;

		if ($config->get('system', 'invitation_only')) {
			$invitation_only = true;
			$invites_remaining = PConfig::get(local_user(), 'system', 'invites_remaining');
			if ((!$invites_remaining) && (!is_site_admin())) {
				throw new HTTPException\ForbiddenException();
			}
		}

		foreach ($recipients as $recipient) {
			$recipient = trim($recipient);

			if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
				notice(L10n::t('%s : Not a valid email address.', $recipient) . EOL);
				continue;
			}

			if ($invitation_only && ($invites_remaining || is_site_admin())) {
				$code = Model\Register::createForInvitation();
				$nmessage = str_replace('$invite_code', $code, $message);

				if (!is_site_admin()) {
					$invites_remaining--;
					if ($invites_remaining >= 0) {
						PConfig::set(local_user(), 'system', 'invites_remaining', $invites_remaining);
					} else {
						return;
					}
				}
			} else {
				$nmessage = $message;
			}

			$additional_headers = 'From: ' . $app->user['email'] . "\n"
				. 'Sender: ' . $app->getSenderEmailAddress() . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit';

			$res = mail(
				$recipient,
				Email::encodeHeader(L10n::t('Please join us on Friendica'), 'UTF-8'),
				$nmessage,
				$additional_headers);

			if ($res) {
				$total++;
				$current_invites++;
				PConfig::set(local_user(), 'system', 'sent_invites', $current_invites);
				if ($current_invites > $max_invites) {
					notice(L10n::t('Invitation limit exceeded. Please contact your site administrator.') . EOL);
					return;
				}
			} else {
				notice(L10n::t('%s : Message delivery failed.', $recipient) . EOL);
			}

		}
		notice(L10n::tt('%d message sent.', '%d messages sent.', $total) . EOL);
	}

	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\ForbiddenException(L10n::t('Permission denied.'));
		}

		$app = DI::app();
		$config = $app->getConfig();

		$inviteOnly = false;

		if ($config->get('system', 'invitation_only')) {
			$inviteOnly = true;
			$x = PConfig::get(local_user(), 'system', 'invites_remaining');
			if ((!$x) && (!is_site_admin())) {
				throw new HTTPException\ForbiddenException(L10n::t('You have no more invitations available'));
			}
		}

		$dirLocation = $config->get('system', 'directory');
		if (strlen($dirLocation)) {
			if ($config->get('config', 'register_policy') === Register::CLOSED) {
				$linkTxt = L10n::t('Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.', $dirLocation . '/servers');
			} else {
				$linkTxt = L10n::t('To accept this invitation, please visit and register at %s or any other public Friendica website.', $app->getBaseURL())
					. "\r\n" . "\r\n" . L10n::t('Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.', $dirLocation . '/servers');
			}
		} else { // there is no global directory URL defined
			if ($config->get('config', 'register_policy') === Register::CLOSED) {
				return L10n::t('Our apologies. This system is not currently configured to connect with other public sites or invite members.');
			} else {
				$linkTxt = L10n::t('To accept this invitation, please visit and register at %s.', $app->getBaseURL()
					. "\r\n" . "\r\n" . L10n::t('Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'));
			}
		}

		$tpl = Renderer::getMarkupTemplate('invite.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => self::getFormSecurityToken('send_invite'),
			'$title'               => L10n::t('Send invitations'),
			'$recipients'          => ['recipients', L10n::t('Enter email addresses, one per line:')],
			'$message'             => [
				'message',
				L10n::t('Your message:'),
				L10n::t('You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.') . "\r\n" . "\r\n"
				. $linkTxt
				. "\r\n" . "\r\n" . (($inviteOnly) ? L10n::t('You will need to supply this invitation code: $invite_code') . "\r\n" . "\r\n" : '') . L10n::t('Once you have registered, please connect with me via my profile page at:')
				. "\r\n" . "\r\n" . $app->getBaseURL() . '/profile/' . $app->user['nickname']
				. "\r\n" . "\r\n" . L10n::t('For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca') . "\r\n" . "\r\n",
			],
			'$submit'              => L10n::t('Submit')
		]);
	}
}
