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

namespace Friendica\Core;

use DOMDocument;
use Exception;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Util\Images;
use Friendica\Util\Strings;

/**
 * Contains methods for installation purpose of Friendica
 */
class Installer
{
	// Default values for the install page
	const DEFAULT_LANG = 'en';
	const DEFAULT_TZ   = 'America/Los_Angeles';
	const DEFAULT_HOST = 'localhost';

	/**
	 * @var array the check outcomes
	 */
	private $checks;

	/**
	 * @var string The path to the PHP binary
	 */
	private $phppath = null;

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
	 * Returns the PHP path
	 *
	 * @return string the PHP Path
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getPHPPath()
	{
		// if not set, determine the PHP path
		if (!isset($this->phppath)) {
			$this->checkPHP();
			$this->resetChecks();
		}

		return $this->phppath;
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
	 * @param string $baseurl The baseurl of Friendica
	 * @param string $phpath  Optional path to the PHP binary
	 *
	 * @return bool if the check succeed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function checkEnvironment($baseurl, $phpath = null)
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

		if (!$this->checkTLS()) {
			$returnVal = false;
		}

		if (!$this->checkKeys()) {
			$returnVal = false;
		}

		/// @TODO This check should not block installations because of containerization issues
		/// @see https://github.com/friendica/docker/issues/134
		$this->checkHtAccess($baseurl);

		return $returnVal;
	}

	/**
	 * Executes the installation of Friendica in the given environment.
	 * - Creates `config/local.config.php`
	 * - Installs Database Structure
	 *
	 * @param Cache $configCache The config cache with all config relevant information
	 *
	 * @return bool true if the config was created, otherwise false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function createConfig(Cache $configCache)
	{
		$basepath = $configCache->get('system', 'basepath');

		$tpl = Renderer::getMarkupTemplate('install/local.config.tpl');
		$txt = Renderer::replaceMacros($tpl, [
			'$dbhost'     => $configCache->get('database', 'hostname'),
			'$dbuser'     => $configCache->get('database', 'username'),
			'$dbpass'     => $configCache->get('database', 'password'),
			'$dbdata'     => $configCache->get('database', 'database'),

			'$phpath'     => $configCache->get('config', 'php_path'),
			'$adminmail'  => $configCache->get('config', 'admin_email'),

			'$system_url' => $configCache->get('system', 'url'),
			'$basepath'   => $basepath,
			'$timezone'   => $configCache->get('system', 'default_timezone'),
			'$language'   => $configCache->get('system', 'language'),
		]);

		$result = file_put_contents($basepath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.config.php', $txt);

		if (!$result) {
			$this->addCheck(DI::l10n()->t('The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'), false, false, htmlentities($txt, ENT_COMPAT, 'UTF-8'));
		}

		return $result;
	}

	/***
	 * Installs the DB-Scheme for Friendica
	 *
	 * @return bool true if the installation was successful, otherwise false
	 * @throws Exception
	 */
	public function installDatabase(): bool
	{
		$result = DBStructure::install();

		if ($result) {
			$txt = DI::l10n()->t('You may need to import the file "database.sql" manually using phpmyadmin or mysql.') . '<br />';
			$txt .= DI::l10n()->t('Please see the file "doc/INSTALL.md".');

			$this->addCheck($txt, false, true, htmlentities($result, ENT_COMPAT, 'UTF-8'));

			return false;
		}

		return true;
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
	 * @param string $phppath  Optional. The Path to the PHP-Binary
	 * @param bool   $required Optional. If set to true, the PHP-Binary has to exist (Default false)
	 *
	 * @return bool false if something required failed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function checkPHP($phppath = null, $required = false)
	{
		$passed3 = false;

		if (!isset($phppath)) {
			$phppath = 'php';
		}

		$passed = file_exists($phppath);
		if (!$passed) {
			$phppath = trim(shell_exec('which ' . $phppath));
			$passed = strlen($phppath);
		}

		$help = "";
		if (!$passed) {
			$help .= DI::l10n()->t('Could not find a command line version of PHP in the web server PATH.') . '<br />';
			$help .= DI::l10n()->t("If you don't have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href='https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker'>'Setup the worker'</a>") . '<br />';
			$help .= '<br /><br />';
			$tpl = Renderer::getMarkupTemplate('field_input.tpl');
			/// @todo Separate backend Installer class and presentation layer/view
			$help .= Renderer::replaceMacros($tpl, [
				'$field' => ['config-php_path', DI::l10n()->t('PHP executable path'), $phppath, DI::l10n()->t('Enter full path to php executable. You can leave this blank to continue the installation.')],
			]);
			$phppath = "";
		}

		$this->addCheck(DI::l10n()->t('Command line PHP') . ($passed ? " (<tt>$phppath</tt>)" : ""), $passed, false, $help);

		if ($passed) {
			$cmd = "$phppath -v";
			$result = trim(shell_exec($cmd));
			$passed2 = (strpos($result, "(cli)") !== false);
			[$result] = explode("\n", $result);
			$help = "";
			if (!$passed2) {
				$help .= DI::l10n()->t("PHP executable is not the php cli binary \x28could be cgi-fgci version\x29") . '<br />';
				$help .= DI::l10n()->t('Found PHP version: ') . "<tt>$result</tt>";
			}
			$this->addCheck(DI::l10n()->t('PHP cli binary'), $passed2, true, $help);
		} else {
			// return if it was required
			return !$required;
		}

		if ($passed2) {
			$str = Strings::getRandomName(8);
			$cmd = "$phppath bin/testargs.php $str";
			$result = trim(shell_exec($cmd));
			$passed3 = $result == $str;
			$help = "";
			if (!$passed3) {
				$help .= DI::l10n()->t('The command line version of PHP on your system does not have "register_argc_argv" enabled.') . '<br />';
				$help .= DI::l10n()->t('This is required for message delivery to work.');
			} else {
				$this->phppath = $phppath;
			}

			$this->addCheck(DI::l10n()->t('PHP register_argc_argv'), $passed3, true, $help);
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
			$help .= DI::l10n()->t('Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys') . '<br />';
			$help .= DI::l10n()->t('If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".');
			$status = false;
		}
		$this->addCheck(DI::l10n()->t('Generate encryption keys'), $res, true, $help);

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
	 * - fileinfo
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
				$help = DI::l10n()->t('Error: Apache webserver mod-rewrite module is required but not installed.');
				$status = false;
				$returnVal = false;
			}
		}
		$this->addCheck(DI::l10n()->t('Apache mod_rewrite module'), $status, true, $help);

		$help = '';
		$status = true;
		if (!function_exists('mysqli_connect') && !class_exists('pdo')) {
			$status = false;
			$help = DI::l10n()->t('Error: PDO or MySQLi PHP module required but not installed.');
			$returnVal = false;
		} else {
			if (!function_exists('mysqli_connect') && class_exists('pdo') && !in_array('mysql', \PDO::getAvailableDrivers())) {
				$status = false;
				$help = DI::l10n()->t('Error: The MySQL driver for PDO is not installed.');
				$returnVal = false;
			}
		}
		$this->addCheck(DI::l10n()->t('PDO or MySQLi PHP module'), $status, true, $help);

		// check for XML DOM Documents being able to be generated
		$help = '';
		$status = true;
		try {
			new DOMDocument();
		} catch (Exception $e) {
			$help = DI::l10n()->t('Error, XML PHP module required but not installed.');
			$status = false;
			$returnVal = false;
		}
		$this->addCheck(DI::l10n()->t('XML PHP module'), $status, true, $help);

		$status = $this->checkFunction('curl_init',
			DI::l10n()->t('libCurl PHP module'),
			DI::l10n()->t('Error: libCURL PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('imagecreatefromjpeg',
			DI::l10n()->t('GD graphics PHP module'),
			DI::l10n()->t('Error: GD graphics PHP module with JPEG support required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('openssl_public_encrypt',
			DI::l10n()->t('OpenSSL PHP module'),
			DI::l10n()->t('Error: openssl PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('mb_strlen',
			DI::l10n()->t('mb_string PHP module'),
			DI::l10n()->t('Error: mb_string PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('iconv_strlen',
			DI::l10n()->t('iconv PHP module'),
			DI::l10n()->t('Error: iconv PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('posix_kill',
			DI::l10n()->t('POSIX PHP module'),
			DI::l10n()->t('Error: POSIX PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('proc_open',
			DI::l10n()->t('Program execution functions'),
			DI::l10n()->t('Error: Program execution functions (proc_open) required but not enabled.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('json_encode',
			DI::l10n()->t('JSON PHP module'),
			DI::l10n()->t('Error: JSON PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('finfo_open',
			DI::l10n()->t('File Information PHP module'),
			DI::l10n()->t('Error: File Information PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		$status = $this->checkFunction('gmp_strval',
			DI::l10n()->t('GNU Multiple Precision PHP module'),
			DI::l10n()->t('Error: GNU Multiple Precision PHP module required but not installed.'),
			true
		);
		$returnVal = $returnVal ? $status : false;

		return $returnVal;
	}

	/**
	 * "config/local.config.php" - Check
	 *
	 * Checks if it's possible to create the "config/local.config.php"
	 *
	 * @return bool false if something required failed
	 */
	public function checkLocalIni()
	{
		$status = true;
		$help = "";
		if ((file_exists('config/local.config.php') && !is_writable('config/local.config.php')) ||
			(!file_exists('config/local.config.php') && !is_writable('.'))) {

			$status = false;
			$help = DI::l10n()->t('The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.') . '<br />';
			$help .= DI::l10n()->t('This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.') . '<br />';
			$help .= DI::l10n()->t('At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.') . '<br />';
			$help .= DI::l10n()->t('You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.') . '<br />';
		}

		$this->addCheck(DI::l10n()->t('config/local.config.php is writable'), $status, false, $help);

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
			$help = DI::l10n()->t('Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.') . '<br />';
			$help .= DI::l10n()->t('In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.') . '<br />';
			$help .= DI::l10n()->t("Please ensure that the user that your web server runs as \x28e.g. www-data\x29 has write access to this folder.") . '<br />';
			$help .= DI::l10n()->t("Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files \x28.tpl\x29 that it contains.") . '<br />';
		}

		$this->addCheck(DI::l10n()->t('view/smarty3 is writable'), $status, true, $help);

		return $status;
	}

	/**
	 * ".htaccess" - Check
	 *
	 * Checks, if "url_rewrite" is enabled in the ".htaccess" file
	 *
	 * @param string $baseurl The baseurl of the app
	 * @return bool false if something required failed
	 */
	public function checkHtAccess($baseurl)
	{
		$status = true;
		$help = "";
		$error_msg = "";
		if (function_exists('curl_init')) {
			$fetchResult = DI::httpClient()->fetchFull($baseurl . "/install/testrewrite");

			$url = Strings::normaliseLink($baseurl . "/install/testrewrite");
			if ($fetchResult->getReturnCode() != 204) {
				$fetchResult = DI::httpClient()->fetchFull($url);
			}

			if ($fetchResult->getReturnCode() != 204) {
				$status = false;
				$help = DI::l10n()->t('Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.') . '<br />';
				$help .= DI::l10n()->t('In some circumstances (like running inside containers), you can skip this error.');
				$error_msg = [];
				$error_msg['head'] = DI::l10n()->t('Error message from Curl when fetching');
				$error_msg['url'] = $fetchResult->getRedirectUrl();
				$error_msg['msg'] = $fetchResult->getError();
			}

			/// @TODO Required false because of cURL issues in containers - see https://github.com/friendica/docker/issues/134
			$this->addCheck(DI::l10n()->t('Url rewrite is working'), $status, false, $help, $error_msg);
		} else {
			// cannot check modrewrite if libcurl is not installed
			/// @TODO Maybe issue warning here?
		}

		return $status;
	}

	/**
	 * TLS Check
	 *
	 * Tries to determine whether the connection to the server is secured
	 * by TLS or not. If not the user will be warned that it is higly
	 * encuraged to use TLS.
	 *
	 * @return bool (true) as TLS is not mandatory
	 */
	public function checkTLS()
	{
		$tls = false;

		if (isset($_SERVER['HTTPS'])) {
			if (($_SERVER['HTTPS'] == 1) || ($_SERVER['HTTPS'] == 'on')) {
				$tls = true;
			}
		}

		if (!$tls) {
			$help = DI::l10n()->t('The detection of TLS to secure the communication between the browser and the new Friendica server failed.');
			$help .= ' ' . DI::l10n()->t('It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.');
			$help .= ' ' . DI::l10n()->t('Please ensure that the connection to the server is secure.');
			$this->addCheck(DI::l10n()->t('No TLS detected'), $tls, false, $help);
		} else {
			$this->addCheck(DI::l10n()->t('TLS detected'), $tls, false, '');
		}

		// TLS is not required
		return true;
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
			$supported = Images::supportedTypes();
			if (array_key_exists('image/gif', $supported)) {
				$gif = true;
			}
		}
		if (!$imagick) {
			$this->addCheck(DI::l10n()->t('ImageMagick PHP extension is not installed'), $imagick, false, "");
		} else {
			$this->addCheck(DI::l10n()->t('ImageMagick PHP extension is installed'), $imagick, false, "");
			if ($imagick) {
				$this->addCheck(DI::l10n()->t('ImageMagick supports GIF'), $gif, false, "");
			}
		}

		// Imagick is not required
		return true;
	}

	/**
	 * Checking the Database connection and if it is available for the current installation
	 *
	 * @param Database $dba
	 *
	 * @return bool true if the check was successful, otherwise false
	 * @throws Exception
	 */
	public function checkDB(Database $dba): bool
	{
		$dba->reconnect();

		if ($dba->isConnected()) {
			if (DBStructure::existsTable('user')) {
				$this->addCheck(DI::l10n()->t('Database already in use.'), false, true, '');

				return false;
			}
		} else {
			$this->addCheck(DI::l10n()->t('Could not connect to database.'), false, true, '');

			return false;
		}

		return true;
	}

	/**
	 * Setup the default cache for a new installation
	 *
	 * @param \Friendica\Core\Config\ValueObject\Cache $configCache The configuration cache
	 * @param string                                   $basePath    The determined basepath
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function setUpCache(Cache $configCache, $basePath)
	{
		$configCache->set('config', 'php_path'  , $this->getPHPPath());
		$configCache->set('system', 'basepath'  , $basePath);
	}
}
