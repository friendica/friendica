<?php
/**
 * @file src/Core/Install.php
 */
namespace Friendica\Core;

use DOMDocument;
use Exception;
use Friendica\Content\Text;
use Friendica\Object\Image;
use Friendica\Util\Network;

/**
 * Contains methods for installation purpose of Friendica
 */
class Install
{
	/**
	 * @var array the check outcomes
	 */
	private $checks;

	/**
	 * Returns all checks made
	 *
	 * @return array the checks
	 */
	public function getChecks()
	{
		return $this->checks;
	}

	/**
	 * Resets all checks
	 */
	public function resetChecks()
	{
		$this->checks = [];
	}

	/**
	 * Install constructor.
	 *
	 */
	public function __construct()
	{
		$this->checks = [];
	}

	/**
	 * Checks the current installation environment. There are optional and mandatory checks.
	 *
	 * @param string $basepath    The basepath of Friendica
	 * @param string $baseurl     The baseurl of Friendica
	 * @param string $phpath      Optional path to the PHP binary
	 *
	 * @return bool if the check succeed
	 */
	public function checkAll($basepath, $baseurl, $phpath = null)
	{
		$returnVal = true;

		if (isset($phpath)) {
			if (!$this->checkPHP($phpath)) {
				$returnVal = false;
			}
		}

		if (!$this->checkFunctions()) {
			$returnVal = false;
		}

		if (!$this->checkImagick()) {
			$returnVal = false;
		}

		if (!$this->checkLocalIni()) {
			$returnVal = false;
		}

		if (!$this->checkSmarty3()) {
			$returnVal = false;
		}

		if (!$this->checkKeys()) {
			$returnVal = false;
		}

		if (!$this->checkHtAccess($basepath, $baseurl)) {
			$returnVal = false;
		}

		return $returnVal;
	}

	/**
	 * Executes the installation of Friendica in the given environment.
	 * - Creates `config/local.ini.php`
	 * - Installs Database Structure
	 *
	 * @param string 	$phppath 	Path to the PHP-Binary (optional, if not set e.g. 'php' or '/usr/bin/php')
	 * @param string 	$urlpath 	Path based on the URL of Friendica (e.g. '/friendica')
	 * @param string 	$dbhost 	Hostname/IP of the Friendica Database
	 * @param string 	$dbuser 	Username of the Database connection credentials
	 * @param string 	$dbpass 	Password of the Database connection credentials
	 * @param string 	$dbdata 	Name of the Database
	 * @param string 	$timezone 	Timezone of the Friendica Installaton (e.g. 'Europe/Berlin')
	 * @param string 	$language 	2-letter ISO 639-1 code (eg. 'en')
	 * @param string 	$adminmail 	Mail-Adress of the administrator
	 * @param string 	$basepath   The basepath of Friendica
	 *
	 * @return bool|string true if the config was created, the text if something went wrong
	 */
	public function createConfig($phppath, $urlpath, $dbhost, $dbuser, $dbpass, $dbdata, $timezone, $language, $adminmail, $basepath)
	{
		$tpl = Text::getMarkupTemplate('local.ini.tpl');
		$txt = App::replaceMacros($tpl,[
			'$phpath' => $phppath,
			'$dbhost' => $dbhost,
			'$dbuser' => $dbuser,
			'$dbpass' => $dbpass,
			'$dbdata' => $dbdata,
			'$timezone' => $timezone,
			'$language' => $language,
			'$urlpath' => $urlpath,
			'$adminmail' => $adminmail,
		]);

		$result = file_put_contents($basepath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.ini.php', $txt);

		if (!$result) {
			return $txt;
		} else {
			return true;
		}
	}

	/**
	 * Adds new checks to the array $checks
	 *
	 * @param string $title The title of the current check
	 * @param bool $status 1 = check passed, 0 = check not passed
	 * @param bool $required 1 = check is mandatory, 0 = check is optional
	 * @param string $help A help-string for the current check
	 * @param string $error_msg Optional. A error message, if the current check failed
	 */
	private function addCheck($title, $status, $required, $help, $error_msg = "")
	{
		array_push($this->checks, [
			'title' => $title,
			'status' => $status,
			'required' => $required,
			'help' => $help,
			'error_msg' => $error_msg,
		]);
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
	 * @param string $phppath Optional. The Path to the PHP-Binary
	 * @param bool   $required Optional. If set to true, the PHP-Binary has to exist (Default false)
	 *
	 * @return bool false if something required failed
	 */
	public function checkPHP($phppath = null, $required = false)
	{
		$passed = $passed2 = $passed3 = false;
		if (isset($phppath)) {
			$passed = file_exists($phppath);
		} else {
			$phppath = trim(shell_exec('which php'));
			$passed = strlen($phppath);
		}

		$help = "";
		if (!$passed) {
			$help .= L10n::t('Could not find a command line version of PHP in the web server PATH.') . EOL;
			$help .= L10n::t("If you don't have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href='https://github.com/friendica/friendica/blob/master/doc/Install.md#set-up-the-worker'>'Setup the worker'</a>") . EOL;
			$help .= EOL . EOL;
			$tpl = Text::getMarkupTemplate('field_input.tpl');
			$help .= App::replaceMacros($tpl, [
				'$field' => ['phpath', L10n::t('PHP executable path'), $phppath, L10n::t('Enter full path to php executable. You can leave this blank to continue the installation.')],
			]);
			$phppath = "";
		}

		$this->addCheck(L10n::t('Command line PHP') . ($passed ? " (<tt>$phppath</tt>)" : ""), $passed, false, $help);

		if ($passed) {
			$cmd = "$phppath -v";
			$result = trim(shell_exec($cmd));
			$passed2 = (strpos($result, "(cli)") !== false);
			list($result) = explode("\n", $result);
			$help = "";
			if (!$passed2) {
				$help .= L10n::t("PHP executable is not the php cli binary \x28could be cgi-fgci version\x29") . EOL;
				$help .= L10n::t('Found PHP version: ') . "<tt>$result</tt>";
			}
			$this->addCheck(L10n::t('PHP cli binary'), $passed2, true, $help);
		} else {
			// return if it was required
			return $required;
		}

		if ($passed2) {
			$str = Text::autoName(8);
			$cmd = "$phppath testargs.php $str";
			$result = trim(shell_exec($cmd));
			$passed3 = $result == $str;
			$help = "";
			if (!$passed3) {
				$help .= L10n::t('The command line version of PHP on your system does not have "register_argc_argv" enabled.') . EOL;
				$help .= L10n::t('This is required for message delivery to work.');
			} else {
				$this->phppath = $phppath;
			}

			$this->addCheck(L10n::t('PHP register_argc_argv'), $passed3, true, $help);
		}

		// passed2 & passed3 are required if first check passed
		return $passed2 && $passed3;
	}

	/**
	 * OpenSSL Check
	 *
	 * Checks the OpenSSL Environment
	 *
	 * - Checks, if the command "openssl_pkey_new" is available
	 *
	 * @return bool false if something required failed
	 */
	public function checkKeys()
	{
		$help = '';
		$res = false;
		$status = true;

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
			$status = false;
		}
		$this->addCheck(L10n::t('Generate encryption keys'), $res, true, $help);

		return $status;
	}

	/**
	 * PHP basic function check
	 *
	 * @param string $name The name of the function
	 * @param string $title The (localized) title of the function
	 * @param string $help The (localized) help of the function
	 * @param boolean $required If true, this check is required
	 *
	 * @return bool false, if the check failed
	 */
	private function checkFunction($name, $title, $help, $required)
	{
		$currHelp = '';
		$status = true;
		if (!function_exists($name)) {
			$currHelp = $help;
			$status = false;
		}
		$this->addCheck($title, $status, $required, $currHelp);

		return $status || (!$status && !$required);
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
	 * @return bool false if something required failed
	 */
	public function checkFunctions()
	{
		$returnVal = true;

		$help = '';
		$status = true;
		if (function_exists('apache_get_modules')) {
			if (!in_array('mod_rewrite', apache_get_modules())) {
				$help = L10n::t('Error: Apache webserver mod-rewrite module is required but not installed.');
				$status = false;
				$returnVal = false;
			}
		}
		$this->addCheck(L10n::t('Apache mod_rewrite module'), $status, true, $help);

		$help = '';
		$status = true;
		if (!function_exists('mysqli_connect') && !class_exists('pdo')) {
			$status = false;
			$help = L10n::t('Error: PDO or MySQLi PHP module required but not installed.');
			$returnVal = false;
		} else {
			if (!function_exists('mysqli_connect') && class_exists('pdo') && !in_array('mysql', \PDO::getAvailableDrivers())) {
				$status = false;
				$help = L10n::t('Error: The MySQL driver for PDO is not installed.');
				$returnVal = false;
			}
		}
		$this->addCheck(L10n::t('PDO or MySQLi PHP module'), $status, true, $help);

		// check for XML DOM Documents being able to be generated
		$help = '';
		$status = true;
		try {
			$xml = new DOMDocument();
		} catch (Exception $e) {
			$help = L10n::t('Error, XML PHP module required but not installed.');
			$status = false;
			$returnVal = false;
		}
		$this->addCheck(L10n::t('XML PHP module'), $status, true, $help);

		$status = $this->checkFunction('curl_init',
			L10n::t('libCurl PHP module'),
			L10n::t('Error: libCURL PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('imagecreatefromjpeg',
			L10n::t('GD graphics PHP module'),
			L10n::t('Error: GD graphics PHP module with JPEG support required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('openssl_public_encrypt',
			L10n::t('OpenSSL PHP module'),
			L10n::t('Error: openssl PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('mb_strlen',
			L10n::t('mb_string PHP module'),
			L10n::t('Error: mb_string PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('iconv_strlen',
			L10n::t('iconv PHP module'),
			L10n::t('Error: iconv PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('posix_kill',
			L10n::t('POSIX PHP module'),
			L10n::t('Error: POSIX PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		return $returnVal;
	}

	/**
	 * "config/local.ini.php" - Check
	 *
	 * Checks if it's possible to create the "config/local.ini.php"
	 *
	 * @return bool false if something required failed
	 */
	public function checkLocalIni()
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

		$this->addCheck(L10n::t('config/local.ini.php is writable'), $status, false, $help);

		// Local INI File is not required
		return true;
	}

	/**
	 * Smarty3 Template Check
	 *
	 * Checks, if the directory of Smarty3 is writable
	 *
	 * @return bool false if something required failed
	 */
	public function checkSmarty3()
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

		$this->addCheck(L10n::t('view/smarty3 is writable'), $status, true, $help);

		return $status;
	}

	/**
	 * ".htaccess" - Check
	 *
	 * Checks, if "url_rewrite" is enabled in the ".htaccess" file
	 *
	 * @param string $basepath   The basepath of the app
	 * @param string $baseurl    The baseurl of the app
	 * @return bool false if something required failed
	 */
	public function checkHtAccess($basepath, $baseurl)
	{
		$status = true;
		$help = "";
		$error_msg = "";
		if (function_exists('curl_init')) {
			$fetchResult = Network::fetchUrlFull($basepath . "/install/testrewrite");

			$url = Text::normaliseLink($baseurl . "/install/testrewrite");
			if ($fetchResult->getBody() != "ok") {
				$fetchResult = Network::fetchUrlFull($url);
			}

			if ($fetchResult->getBody() != "ok") {
				$status = false;
				$help = L10n::t('Url rewrite in .htaccess is not working. Check your server configuration.');
				$error_msg = [];
				$error_msg['head'] = L10n::t('Error message from Curl when fetching');
				$error_msg['url'] = $fetchResult->getRedirectUrl();
				$error_msg['msg'] = $fetchResult->getError();
			}

			$this->addCheck(L10n::t('Url rewrite is working'), $status, true, $help, $error_msg);
		} else {
			// cannot check modrewrite if libcurl is not installed
			/// @TODO Maybe issue warning here?
		}

		return $status;
	}

	/**
	 * Imagick Check
	 *
	 * Checks, if the imagick module is available
	 *
	 * @return bool false if something required failed
	 */
	public function checkImagick()
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
		if (!$imagick) {
			$this->addCheck(L10n::t('ImageMagick PHP extension is not installed'), $imagick, false, "");
		} else {
			$this->addCheck(L10n::t('ImageMagick PHP extension is installed'), $imagick, false, "");
			if ($imagick) {
				$this->addCheck(L10n::t('ImageMagick supports GIF'), $gif, false, "");
			}
		}

		// Imagick is not required
		return true;
	}
}
