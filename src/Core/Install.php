<?php
/**
 * @file src/Core/Install.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\App;
use Friendica\Database\DBStructure;
use Friendica\Object\Image;
use Friendica\Util\Network;

use Exception;
use DOMDocument;

/**
 * Contains methods for installation purpose of Friendica
 */
class Install extends BaseObject
{
	/**
	 * Sets the install-mode for further methods
	 */
	public static function setInstallMode()
	{
		self::getApp()->mode = App::MODE_INSTALL;
	}

	/**
	 * Checks the current installation environment. There are optional and mandatory checks.
	 *
	 * @param string $phpath Optional path to the PHP binary (Default is 'php')
	 *
	 * @return array First element is a list of all checks and their results,
	 *               the second element is a list of passed checks
	 */
	public static function check($phpath = 'php')
	{
		$checks = [];

		self::checkFunctions($checks);

		self::checkImagick($checks);

		self::checkLocalIni($checks);

		self::checkSmarty3($checks);

		self::checkKeys($checks);

		self::checkPHP($phpath, $checks);

		self::checkHtAccess($checks);

		$checkspassed = array_reduce($checks,
			function ($v, $c) {
				if (!empty($c['require'])) {
					$v = $v && $c['status'];
				}
				return $v;
			},
			true);

		return array($checks, $checkspassed);
	}

	/**
	 * Executes the installation of Friendica in the given environment.
	 * - Creates `config/local.ini.php`
	 * - Installs Database Structure
	 *
	 * @param string 	$urlpath 	Path based on the URL of Friendica (e.g. '/friendica')
	 * @param string 	$dbhost 	Hostname/IP of the Friendica Database
	 * @param string 	$dbuser 	Username of the Database connection credentials
	 * @param string 	$dbpass 	Password of the Database connection credentials
	 * @param string 	$dbdata 	Name of the Database
	 * @param string 	$phpath 	Path to the PHP-Binary (e.g. 'php' or '/usr/bin/php')
	 * @param string 	$timezone 	Timezone of the Friendica Installaton (e.g. 'Europe/Berlin')
	 * @param string 	$language 	2-letter ISO 639-1 code (eg. 'en')
	 * @param string 	$adminmail 	Mail-Adress of the administrator
	 * @param int 		$rino		Rino-enabled (1 = true, 0 = false)
	 */
	public static function install($urlpath, $dbhost, $dbuser, $dbpass, $dbdata, $phpath, $timezone, $language, $adminmail)
	{
		$tpl = get_markup_template('local.ini.tpl');
		$txt = replace_macros($tpl,[
			'$dbhost' => $dbhost,
			'$dbuser' => $dbuser,
			'$dbpass' => $dbpass,
			'$dbdata' => $dbdata,
			'$timezone' => $timezone,
			'$language' => $language,
			'$urlpath' => $urlpath,
			'$phpath' => $phpath,
			'$adminmail' => $adminmail,
		]);

		$result = file_put_contents('config/local.ini.php', $txt);
		if (! $result) {
			self::getApp()->data['txt'] = $txt;
		}

		$errors = self::installDatabaseStructure();

		if ($errors) {
			self::getApp()->data['db_failed'] = $errors;
		} else {
			self::getApp()->data['db_installed'] = true;
		}
	}

	/**
	 * Adds new checks to the array $checks
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 * @param string $title The title of the current check
	 * @param bool $status 1 = check passed, 0 = check not passed
	 * @param bool $required 1 = check is mandatory, 0 = check is optional
	 * @param string $help A help-string for the current check
	 * @param string $error_msg Optional. A error message, if the current check failed
	 */
	private static function addCheck(&$checks, $title, $status, $required, $help, $error_msg = "")
	{
		$checks[] = [
			'title' => $title,
			'status' => $status,
			'required' => $required,
			'help' => $help,
			'error_msg' => $error_msg,
		];
	}

	/**
	 * PHP Check
	 *
	 * Checks the PHP environment.
	 *
	 * - Checks if a PHP binary is available
	 * - Checks if it is the CLI version
	 * - Checks if "register_argc_argv" is enabled
 	 *
	 * @param string $phpath Optional. The Path to the PHP-Binary
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkPHP(&$phpath, &$checks)
	{
		$passed = $passed2 = $passed3 = false;
		if (strlen($phpath)) {
			$passed = file_exists($phpath);
		} else {
			$phpath = trim(shell_exec('which php'));
			$passed = strlen($phpath);
		}
		$help = "";
		if (!$passed) {
			$help .= L10n::t('Could not find a command line version of PHP in the web server PATH.') . EOL;
			$help .= L10n::t("If you don't have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href='https://github.com/friendica/friendica/blob/master/doc/Install.md#set-up-the-worker'>'Setup the worker'</a>") . EOL;
			$help .= EOL . EOL;
			$tpl = get_markup_template('field_input.tpl');
			$help .= replace_macros($tpl, [
				'$field' => ['phpath', L10n::t('PHP executable path'), $phpath, L10n::t('Enter full path to php executable. You can leave this blank to continue the installation.')],
			]);
			$phpath = "";
		}

		self::addCheck($checks, L10n::t('Command line PHP').($passed?" (<tt>$phpath</tt>)":""), $passed, false, $help);

		if ($passed) {
			$cmd = "$phpath -v";
			$result = trim(shell_exec($cmd));
			$passed2 = (strpos($result, "(cli)") !== false);
			list($result) = explode("\n", $result);
			$help = "";
			if (!$passed2) {
				$help .= L10n::t("PHP executable is not the php cli binary \x28could be cgi-fgci version\x29") . EOL;
				$help .= L10n::t('Found PHP version: ') . "<tt>$result</tt>";
			}
			self::addCheck($checks, L10n::t('PHP cli binary'), $passed2, true, $help);
		}

		if ($passed2) {
			$str = autoname(8);
			$cmd = "$phpath testargs.php $str";
			$result = trim(shell_exec($cmd));
			$passed3 = $result == $str;
			$help = "";
			if (!$passed3) {
				$help .= L10n::t('The command line version of PHP on your system does not have "register_argc_argv" enabled.') . EOL;
				$help .= L10n::t('This is required for message delivery to work.');
			}
			self::addCheck($checks, L10n::t('PHP register_argc_argv'), $passed3, true, $help);
		}
	}

	/**
	 * OpenSSL Check
	 *
	 * Checks the OpenSSL Environment
	 *
	 * - Checks, if the command "openssl_pkey_new" is available
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkKeys(&$checks)
	{
		$help = '';
		$res = false;

		if (function_exists('openssl_pkey_new')) {
			$res = openssl_pkey_new([
				'digest_alg' => 'sha1',
				'private_key_bits' => 4096,
				'encrypt_key' => false
			]);
		}

		// Get private key
		if (!$res) {
			$help .= L10n::t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys') . EOL;
			$help .= L10n::t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".');
		}
		self::addCheck($checks, L10n::t('Generate encryption keys'), $res, true, $help);
	}

	/**
	 * PHP functions Check
	 *
	 * Checks the following PHP functions
	 * - libCurl
	 * - GD Graphics
	 * - OpenSSL
	 * - PDO or MySQLi
	 * - mb_string
	 * - XML
	 * - iconv
	 * - POSIX
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkFunctions(&$checks)
	{
		$ck_funcs = [];
		self::addCheck($ck_funcs, L10n::t('libCurl PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('GD graphics PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('OpenSSL PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('PDO or MySQLi PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('mb_string PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('XML PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('iconv PHP module'), true, true, "");
		self::addCheck($ck_funcs, L10n::t('POSIX PHP module'), true, true, "");

		if (function_exists('apache_get_modules')) {
			if (! in_array('mod_rewrite',apache_get_modules())) {
				self::addCheck($ck_funcs, L10n::t('Apache mod_rewrite module'), false, true, L10n::t('Error: Apache webserver mod-rewrite module is required but not installed.'));
			} else {
				self::addCheck($ck_funcs, L10n::t('Apache mod_rewrite module'), true, true, "");
			}
		}

		if (!function_exists('curl_init')) {
			$ck_funcs[0]['status'] = false;
			$ck_funcs[0]['help'] = L10n::t('Error: libCURL PHP module required but not installed.');
		}
		if (!function_exists('imagecreatefromjpeg')) {
			$ck_funcs[1]['status'] = false;
			$ck_funcs[1]['help'] = L10n::t('Error: GD graphics PHP module with JPEG support required but not installed.');
		}
		if (!function_exists('openssl_public_encrypt')) {
			$ck_funcs[2]['status'] = false;
			$ck_funcs[2]['help'] = L10n::t('Error: openssl PHP module required but not installed.');
		}
		if (!function_exists('mysqli_connect') && !class_exists('pdo')) {
			$ck_funcs[3]['status'] = false;
			$ck_funcs[3]['help'] = L10n::t('Error: PDO or MySQLi PHP module required but not installed.');
		}
		if (!function_exists('mysqli_connect') && class_exists('pdo') && !in_array('mysql', \PDO::getAvailableDrivers())) {
			$ck_funcs[3]['status'] = false;
			$ck_funcs[3]['help'] = L10n::t('Error: The MySQL driver for PDO is not installed.');
		}
		if (!function_exists('mb_strlen')) {
			$ck_funcs[4]['status'] = false;
			$ck_funcs[4]['help'] = L10n::t('Error: mb_string PHP module required but not installed.');
		}
		if (!function_exists('iconv_strlen')) {
			$ck_funcs[6]['status'] = false;
			$ck_funcs[6]['help'] = L10n::t('Error: iconv PHP module required but not installed.');
		}
		if (!function_exists('posix_kill')) {
			$ck_funcs[7]['status'] = false;
			$ck_funcs[7]['help'] = L10n::t('Error: POSIX PHP module required but not installed.');
		}

		$checks = array_merge($checks, $ck_funcs);

		// check for XML DOM Documents being able to be generated
		try {
			$xml = new DOMDocument();
		} catch (Exception $e) {
			$ck_funcs[5]['status'] = false;
			$ck_funcs[5]['help'] = L10n::t('Error, XML PHP module required but not installed.');
		}
	}

	/**
	 * "config/local.ini.php" - Check
	 *
	 * Checks if it's possible to create the "config/local.ini.php"
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkLocalIni(&$checks)
	{
		$status = true;
		$help = "";
		if ((file_exists('config/local.ini.php') && !is_writable('config/local.ini.php')) ||
			(!file_exists('config/local.ini.php') && !is_writable('.'))) {

			$status = false;
			$help = L10n::t('The web installer needs to be able to create a file called "local.ini.php" in the "config" folder of your web server and it is unable to do so.') . EOL;
			$help .= L10n::t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.') . EOL;
			$help .= L10n::t('At the end of this procedure, we will give you a text to save in a file named local.ini.php in your Friendica "config" folder.') . EOL;
			$help .= L10n::t('You can alternatively skip this procedure and perform a manual installation. Please see the file "INSTALL.txt" for instructions.') . EOL;
		}

		self::addCheck($checks, L10n::t('config/local.ini.php is writable'), $status, false, $help);

	}

	/**
	 * Smarty3 Template Check
	 *
	 * Checks, if the directory of Smarty3 is writable
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkSmarty3(&$checks)
	{
		$status = true;
		$help = "";
		if (!is_writable('view/smarty3')) {

			$status = false;
			$help = L10n::t('Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.') . EOL;
			$help .= L10n::t('In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.') . EOL;
			$help .= L10n::t("Please ensure that the user that your web server runs as \x28e.g. www-data\x29 has write access to this folder.") . EOL;
			$help .= L10n::t("Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files \x28.tpl\x29 that it contains.") . EOL;
		}

		self::addCheck($checks, L10n::t('view/smarty3 is writable'), $status, true, $help);
	}

	/**
	 * ".htaccess" - Check
	 *
	 * Checks, if "url_rewrite" is enabled in the ".htaccess" file
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkHtAccess(&$checks)
	{
		$status = true;
		$help = "";
		$error_msg = "";
		if (function_exists('curl_init')) {
			$test = Network::fetchUrlFull(System::baseUrl() . "/install/testrewrite");

			$url = normalise_link(System::baseUrl() . "/install/testrewrite");
			if ($test['body'] != "ok") {
				$test = Network::fetchUrlFull($url);
			}

			if ($test['body'] != "ok") {
				$status = false;
				$help = L10n::t('Url rewrite in .htaccess is not working. Check your server configuration.');
				$error_msg = [];
				$error_msg['head'] = L10n::t('Error message from Curl when fetching');
				$error_msg['url'] = $test['redirect_url'];
				$error_msg['msg'] = defaults($test, 'error', '');
			}
			self::addCheck($checks, L10n::t('Url rewrite is working'), $status, true, $help, $error_msg);
		} else {
			// cannot check modrewrite if libcurl is not installed
			/// @TODO Maybe issue warning here?
		}
	}

	/**
	 * Imagick Check
	 *
	 * Checks, if the imagick module is available
	 *
	 * @param array $checks The list of all checks (by-ref parameter!)
	 */
	public static function checkImagick(&$checks)
	{
		$imagick = false;
		$gif = false;

		if (class_exists('Imagick')) {
			$imagick = true;
			$supported = Image::supportedTypes();
			if (array_key_exists('image/gif', $supported)) {
				$gif = true;
			}
		}
		if ($imagick == false) {
			self::addCheck($checks, L10n::t('ImageMagick PHP extension is not installed'), $imagick, false, "");
		} else {
			self::addCheck($checks, L10n::t('ImageMagick PHP extension is installed'), $imagick, false, "");
			if ($imagick) {
				self::addCheck($checks, L10n::t('ImageMagick supports GIF'), $gif, false, "");
			}
		}
	}

	/**
	 * Installs the Database structure
	 *
	 * @return string A possible error
	 */
	public static function installDatabaseStructure()
	{
		$errors = DBStructure::update(false, true, true);

		return $errors;
	}
}
