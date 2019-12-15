<?php

namespace Friendica\Module;

use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\L10n\L10n as L10nClass;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Util\Strings;

/**
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Register extends BaseModule
{
	const CLOSED  = 0;
	const APPROVE = 1;
	const OPEN    = 2;

	/**
	 * @brief Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @return string
	 */
	public static function content(array $parameters = [])
	{
		// logged in users can register others (people/pages/groups)
		// even with closed registrations, unless specifically prohibited by site policy.
		// 'block_extended_register' blocks all registrations, period.
		$block = Config::get('system', 'block_extended_register');

		if (local_user() && ($block)) {
			notice('Permission denied.' . EOL);
			return '';
		}

		if ((!local_user()) && (intval(Config::get('config', 'register_policy')) === self::CLOSED)) {
			notice('Permission denied.' . EOL);
			return '';
		}

		$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				Logger::log('max daily registrations exceeded.');
				notice(L10n::t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
				return '';
			}
		}

		$username   = $_REQUEST['username']   ?? '';
		$email      = $_REQUEST['email']      ?? '';
		$openid_url = $_REQUEST['openid_url'] ?? '';
		$nickname   = $_REQUEST['nickname']   ?? '';
		$photo      = $_REQUEST['photo']      ?? '';
		$invite_id  = $_REQUEST['invite_id']  ?? '';

		if (Config::get('system', 'no_openid')) {
			$fillwith = '';
			$fillext  = '';
			$oidlabel = '';
		} else {
			$fillwith = L10n::t('You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".');
			$fillext  = L10n::t('If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.');
			$oidlabel = L10n::t('Your OpenID (optional): ');
		}

		if (Config::get('system', 'publish_all')) {
			$profile_publish = '<input type="hidden" name="profile_publish_reg" value="1" />';
		} else {
			$publish_tpl = Renderer::getMarkupTemplate('profile_publish.tpl');
			$profile_publish = Renderer::replaceMacros($publish_tpl, [
				'$instance'     => 'reg',
				'$pubdesc'      => L10n::t('Include your profile in member directory?'),
				'$yes_selected' => '',
				'$no_selected'  => ' checked="checked"',
				'$str_yes'      => L10n::t('Yes'),
				'$str_no'       => L10n::t('No'),
			]);
		}

		$ask_password = ! DBA::count('contact');

		$tpl = Renderer::getMarkupTemplate('register.tpl');

		$arr = ['template' => $tpl];

		Hook::callAll('register_form', $arr);

		$tpl = $arr['template'];

		$tos = new Tos();

		$o = Renderer::replaceMacros($tpl, [
			'$invitations'  => Config::get('system', 'invitation_only'),
			'$permonly'     => intval(Config::get('config', 'register_policy')) === self::APPROVE,
			'$permonlybox'  => ['permonlybox', L10n::t('Note for the admin'), '', L10n::t('Leave a message for the admin, why you want to join this node'), 'required'],
			'$invite_desc'  => L10n::t('Membership on this site is by invitation only.'),
			'$invite_label' => L10n::t('Your invitation code: '),
			'$invite_id'    => $invite_id,
			'$regtitle'     => L10n::t('Registration'),
			'$registertext' => BBCode::convert(Config::get('config', 'register_text', '')),
			'$fillwith'     => $fillwith,
			'$fillext'      => $fillext,
			'$oidlabel'     => $oidlabel,
			'$openid'       => $openid_url,
			'$namelabel'    => L10n::t('Your Full Name (e.g. Joe Smith, real or real-looking): '),
			'$addrlabel'    => L10n::t('Your Email Address: (Initial information will be send there, so this has to be an existing address.)'),
			'$ask_password' => $ask_password,
			'$password1'    => ['password1', L10n::t('New Password:'), '', L10n::t('Leave empty for an auto generated password.')],
			'$password2'    => ['confirm', L10n::t('Confirm:'), '', ''],
			'$nickdesc'     => L10n::t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".', DI::app()->getHostName()),
			'$nicklabel'    => L10n::t('Choose a nickname: '),
			'$photo'        => $photo,
			'$publish'      => $profile_publish,
			'$regbutt'      => L10n::t('Register'),
			'$username'     => $username,
			'$email'        => $email,
			'$nickname'     => $nickname,
			'$sitename'     => DI::app()->getHostName(),
			'$importh'      => L10n::t('Import'),
			'$importt'      => L10n::t('Import your profile to this friendica instance'),
			'$showtoslink'  => Config::get('system', 'tosdisplay'),
			'$tostext'      => L10n::t('Terms of Service'),
			'$showprivstatement' => Config::get('system', 'tosprivstatement'),
			'$privstatement'=> $tos->privacy_complete,
			'$form_security_token' => BaseModule::getFormSecurityToken('register'),
			'$explicit_content' => Config::get('system', 'explicit_content', false),
			'$explicit_content_note' => L10n::t('Note: This node explicitly contains adult content')
		]);

		return $o;
	}

	/**
	 * @brief Module POST method to process submitted data
	 *
	 * Extend this method if the module is supposed to process POST requests.
	 * Doesn't display any content
	 */
	public static function post(array $parameters = [])
	{
		BaseModule::checkFormSecurityTokenRedirectOnError('/register', 'register');

		$a = DI::app();

		$arr = ['post' => $_POST];
		Hook::callAll('register_post', $arr);

		$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				return;
			}
		}

		switch (Config::get('config', 'register_policy')) {
			case self::OPEN:
				$blocked = 0;
				$verified = 1;
				break;

			case self::APPROVE:
				$blocked = 1;
				$verified = 0;
				break;

			case self::CLOSED:
			default:
				if (empty($_SESSION['authenticated']) && empty($_SESSION['administrator'])) {
					\notice(L10n::t('Permission denied.') . EOL);
					return;
				}
				$blocked = 1;
				$verified = 0;
				break;
		}

		$netpublish = !empty($_POST['profile_publish_reg']);

		$arr = $_POST;

		$arr['blocked'] = $blocked;
		$arr['verified'] = $verified;
		$arr['language'] = L10nClass::detectLanguage($_SERVER, $_GET, $a->getConfig()->get('system', 'language'));

		try {
			$result = Model\User::create($arr);
		} catch (\Exception $e) {
			\notice($e->getMessage());
			return;
		}

		$user = $result['user'];

		$base_url = self::getClass(BaseURL::class)->get();

		if ($netpublish && intval(Config::get('config', 'register_policy')) !== self::APPROVE) {
			$url = $base_url . '/profile/' . $user['nickname'];
			Worker::add(PRIORITY_LOW, 'Directory', $url);
		}

		$using_invites = Config::get('system', 'invitation_only');
		$num_invites   = Config::get('system', 'number_invites');
		$invite_id = (!empty($_POST['invite_id']) ? Strings::escapeTags(trim($_POST['invite_id'])) : '');

		if (intval(Config::get('config', 'register_policy')) === self::OPEN) {
			if ($using_invites && $invite_id) {
				Model\Register::deleteByHash($invite_id);
				PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
			}

			// Only send a password mail when the password wasn't manually provided
			if (empty($_POST['password1']) || empty($_POST['confirm'])) {
				$res = Model\User::sendRegisterOpenEmail(
					$user,
					Config::get('config', 'sitename'),
					$base_url,
					$result['password']
				);

				if ($res) {
					\info(L10n::t('Registration successful. Please check your email for further instructions.') . EOL);
					$a->internalRedirect();
				} else {
					\notice(
						L10n::t('Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.',
							$user['email'],
							$result['password'])
					);
				}
			} else {
				\info(L10n::t('Registration successful.') . EOL);
				$a->internalRedirect();
			}
		} elseif (intval(Config::get('config', 'register_policy')) === self::APPROVE) {
			if (!strlen(Config::get('config', 'admin_email'))) {
				\notice(L10n::t('Your registration can not be processed.') . EOL);
				$a->internalRedirect();
			}

			// Check if the note to the admin is actually filled out
			if (empty($_POST['permonlybox'])) {
				\notice(L10n::t('You have to leave a request note for the admin.')
					. L10n::t('Your registration can not be processed.') . EOL);

				$a->internalRedirect('register/');
			}
			// Is there text in the tar pit?
			if (!empty($_POST['registertarpit'])) {
				\notice(L10n::t('You have entered too much information.'));
				$a->internalRedirect('register/');
			}

			Model\Register::createForApproval($user['uid'], Config::get('system', 'language'), $_POST['permonlybox']);

			// invite system
			if ($using_invites && $invite_id) {
				Model\Register::deleteByHash($invite_id);
				PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
			}

			// send email to admins
			$admins_stmt = DBA::select(
				'user',
				['uid', 'language', 'email'],
				['email' => explode(',', str_replace(' ', '', Config::get('config', 'admin_email')))]
			);

			// send notification to admins
			while ($admin = DBA::fetch($admins_stmt)) {
				\notification([
					'type'         => NOTIFY_SYSTEM,
					'event'        => 'SYSTEM_REGISTER_REQUEST',
					'source_name'  => $user['username'],
					'source_mail'  => $user['email'],
					'source_nick'  => $user['nickname'],
					'source_link'  => $base_url . '/admin/users/',
					'link'         => $base_url . '/admin/users/',
					'source_photo' => $base_url . '/photo/avatar/' . $user['uid'] . '.jpg',
					'to_email'     => $admin['email'],
					'uid'          => $admin['uid'],
					'language'     => ($admin['language'] ?? '') ?: 'en',
					'show_in_notification_page' => false
				]);
			}
			DBA::close($admins_stmt);

			// send notification to the user, that the registration is pending
			Model\User::sendRegisterPendingEmail(
				$user,
				Config::get('config', 'sitename'),
				$base_url,
				$result['password']
			);

			\info(L10n::t('Your registration is pending approval by the site owner.') . EOL);
			$a->internalRedirect();
		}

		return;
	}
}
