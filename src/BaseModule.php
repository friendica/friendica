<?php

namespace Friendica;

use Friendica\Core\L10n;
use Friendica\Core\Logger;

/**
 * All modules in Friendica should extend BaseModule, although not all modules
 * need to extend all the methods described here
 *
 * The filename of the module in src/Module needs to match the class name
 * exactly to make the module available.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
abstract class BaseModule
{
	/**
	 * @brief Initialization method common to both content() and post()
	 *
	 * Extend this method if you need to do any shared processing before both
	 * content() or post()
	 */
	public static function init(array $parameters = [])
	{
	}

	/**
	 * @brief Module GET method to display raw content from technical endpoints
	 *
	 * Extend this method if the module is supposed to return communication data,
	 * e.g. from protocol implementations.
	 */
	public static function rawContent(array $parameters = [])
	{
		// echo '';
		// exit;
	}

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
		$o = '';

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
		// $a = self::getApp();
		// $a->internalRedirect('module');
	}

	/**
	 * @brief Called after post()
	 *
	 * Unknown purpose
	 */
	public static function afterpost(array $parameters = [])
	{
	}

	/*
	 * Functions used to protect against Cross-Site Request Forgery
	 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
	 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
	 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amout of time (3hours).
	 * The "typename" seperates the security tokens of different types of forms. This could be relevant in the following case:
	 *    A security token is used to protekt a link from CSRF (e.g. the "delete this profile"-link).
	 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
	 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but somethimes they still are,
	 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
	 */
	public static function getFormSecurityToken($typename = '')
	{
		$a = \get_app();

		$timestamp = time();
		$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $timestamp . $typename);

		return $timestamp . '.' . $sec_hash;
	}

	public static function checkFormSecurityToken($typename = '', $formname = 'form_security_token')
	{
		$hash = null;

		if (!empty($_REQUEST[$formname])) {
			/// @TODO Careful, not secured!
			$hash = $_REQUEST[$formname];
		}

		if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			/// @TODO Careful, not secured!
			$hash = $_SERVER['HTTP_X_CSRF_TOKEN'];
		}

		if (empty($hash)) {
			return false;
		}

		$max_livetime = 10800; // 3 hours

		$a = \get_app();

		$x = explode('.', $hash);
		if (time() > (intval($x[0]) + $max_livetime)) {
			return false;
		}

		$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $x[0] . $typename);

		return ($sec_hash == $x[1]);
	}

	public static function getFormSecurityStandardErrorMessage()
	{
		return L10n::t("The form security token was not correct. This probably happened because the form has been opened for too long \x28>3 hours\x29 before submitting it.") . EOL;
	}

	public static function checkFormSecurityTokenRedirectOnError($err_redirect, $typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			$a = \get_app();
			Logger::log('checkFormSecurityToken failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
			Logger::log('checkFormSecurityToken failed: _REQUEST data: ' . print_r($_REQUEST, true), Logger::DATA);
			notice(self::getFormSecurityStandardErrorMessage());
			$a->internalRedirect($err_redirect);
		}
	}

	public static function checkFormSecurityTokenForbiddenOnError($typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			$a = \get_app();
			Logger::log('checkFormSecurityToken failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
			Logger::log('checkFormSecurityToken failed: _REQUEST data: ' . print_r($_REQUEST, true), Logger::DATA);

			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}
	}
}
