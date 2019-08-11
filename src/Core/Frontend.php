<?php

namespace Friendica\Core;

use DOMDocument;
use DOMXPath;
use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Config\PConfiguration;
use Friendica\Model\Profile;
use Friendica\Module\Login;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\BaseURL;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * The Frontend class which acts as controller for the frontend related process
 * 1) Initializes the environment of the Frontend
 * 2) Load the Frontend page
 *
 * Additional, all frontend related properties are saved or altered here
 *
 * Currently, the Frontend class acts like an array for the page-settings to be compatible
 * with the `$a->page[]` calls.
 */
final class Frontend implements \ArrayAccess
{
	/**
	 * @var array Additional stylesheets to load during the call
	 */
	private $stylesheets = [];
	/**
	 * @var array Additional footer scripts to load during the call
	 */
	private $footerScripts = [];
	/**
	 * @var array The raw content of the page,
	 * split into different parts
	 */
	private $page = [];
	/**
	 * @var string The basepath of Friendica
	 */
	private $basePath;

	public function __construct(string $basepath)
	{
		$this->basePath = $basepath;
	}

	/**
	 * Initialize the Frontend with all components, sessions and processes
	 * 1) Check if the frontend is callable
	 * 2) Start the Session/Hooks/...
	 * 3) Check the login state
	 * 4) Check if the URL should get directly redirected
	 *
	 * @param App\Arguments   $args     The Friendica call arguments
	 * @param App\Mode        $mode     The Friendica mode of the exection
	 * @param App\Module      $module   The current used Friendica module
	 * @param L10n\L10n       $l10n     The Language environment
	 * @param Process         $process  The process environment
	 * @param LoggerInterface $logger   The Friendica Logger instance
	 * @param Profiler        $profiler The Friendica Profiler instance
	 * @param BaseURL         $baseURL  The Friendica BaseURL
	 *
	 * @return Frontend $this Returns itself
	 *
	 * @throws \ImagickException in case imagick isn't available
	 */
	public function init(App\Arguments $args, App\Mode $mode, App\Module $module, L10n\L10n $l10n, Process $process, LoggerInterface $logger, Profiler $profiler, BaseURL $baseURL)
	{
		$moduleName = $module->getName();

		try {
			// Missing DB connection: ERROR
			if ($mode->has(App\Mode::LOCALCONFIGPRESENT) && !$mode->has(App\Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException('Apologies but the website is unavailable at the moment.');
			}

			// Max Load Average reached: ERROR
			if ($process->isMaxProcessesReached() || $process->isMaxLoadReached()) {
				header('Retry-After: 120');
				header('Refresh: 120; url=' . $baseURL->get() . "/" . $args->getQueryString());

				throw new HTTPException\ServiceUnavailableException('The node is currently overloaded. Please try again later.');
			}

			if (!$mode->isInstall()) {
				// Force SSL redirection
				if ($baseURL->checkRedirectHttps()) {
					System::externalRedirect($baseURL->get() . '/' . $args->getQueryString());
				}

				Session::init();
				Hook::callAll('init_1');
			}

			// Exclude the backend processes from the session management
			if (!$module->isBackend()) {
				$stamp1 = microtime(true);
				session_start();
				$profiler->saveTimestamp($stamp1, 'parser', System::callstack());
				$l10n->setSessionVariable();
				$l10n->setLangFromSession();
			} else {
				$_SESSION = [];
				Worker::executeIfIdle();
			}

			if ($mode->isNormal()) {
				$requester = HTTPSignature::getSigner('', $_SERVER);
				if (!empty($requester)) {
					Profile::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($_GET['zrl']) && $mode->isNormal()) {
				if (!local_user()) {
					// Only continue when the given profile link seems valid
					// Valid profile links contain a path with "/profile/" and no query parameters
					if ((parse_url($_GET['zrl'], PHP_URL_QUERY) == "") &&
					    strstr(parse_url($_GET['zrl'], PHP_URL_PATH), "/profile/")) {
						if (Session::get('visitor_home') != $_GET["zrl"]) {
							Session::set('my_url', $_GET['zrl']);
							Session::set('authenticated', 0);
						}

						Profile::zrlInit($args, $baseURL);
					} else {
						// Someone came with an invalid parameter, maybe as a DDoS attempt
						// We simply stop processing here
						$logger->debug("Invalid ZRL parameter.", ['zrl' => $_GET['zrl']]);
						throw new HTTPException\ForbiddenException();
					}
				}
			}

			if (!empty($_GET['owt']) && $mode->isNormal()) {
				$token = $_GET['owt'];
				Profile::openWebAuthInit($token);
			}

			Login::sessionAuth();

			if (empty($_SESSION['authenticated'])) {
				header('X-Account-Management-Status: none');
			}

			$_SESSION['sysmsg']       = Session::get('sysmsg', []);
			$_SESSION['sysmsg_info']  = Session::get('sysmsg_info', []);
			$_SESSION['last_updated'] = Session::get('last_updated', []);

			/*
			 * check_config() is responsible for running update scripts. These automatically
			 * update the DB schema whenever we push a new one out. It also checks to see if
			 * any addons have been added or removed and reacts accordingly.
			 */

			// in install mode, any url loads install module
			// but we need "view" module for stylesheet
			if ($mode->isInstall() && $moduleName !== 'install') {
				$baseURL->redirect('install');
			} elseif (!$mode->isInstall() && !$mode->has(App\Mode::MAINTENANCEDISABLED) && $moduleName !== 'maintenance') {
				$baseURL->redirect('maintenance');
			} else {
				$baseURL->check();
				Update::check($this->basePath, false, $mode);
				Addon::loadAddons();
				Hook::loadHooks();
			}

			$this->page = [
				'aside'       => '',
				'bottom'      => '',
				'content'     => '',
				'footer'      => '',
				'htmlhead'    => '',
				'nav'         => '',
				'page_title'  => '',
				'right_aside' => '',
				'template'    => '',
				'title'       => ''
			];

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$baseURL->redirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$baseURL->redirect('message');
			}

			if ($moduleName == 'commented') {
				$baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$baseURL->redirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($args->getCommand() == 'status_messages/new')) {
				$baseURL->redirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($args->getCommand() == 'user/edit')) {
				$baseURL->redirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($args->getCommand() == 'tag_followings/manage')) {
				$baseURL->redirect('search');
			}

		} catch (HTTPException $e) {
			ModuleHTTPException::rawContent($e);
		}

		return $this;
	}

	/**
	 * Shows the initialized frontend to the user
	 * 1) Determine the module class
	 * 2) Runs the module class with all hooks (init, post, get, ...)
	 * 3) Initialize header/footer/nav-bar
	 * 4) Render the whole page (minimized or normal)
	 *
	 * @param App             $app     The Friendica Application instance for Hook-calls
	 * @param App\Arguments   $args    The Friendica call arguments
	 * @param App\Mode        $mode    The Friendica mode of the exection
	 * @param App\Module      $module  The current used Friendica module
	 * @param App\Router      $router  The Friendica router to determine the used module class
	 * @param L10n\L10n       $l10n    The Language environment
	 * @param Configuration   $config  The Friendica configuration
	 * @param PConfiguration  $pconfig The Friendica configuration for users
	 * @param LoggerInterface $logger  The Friendica Logger instance
	 * @param BaseURL         $baseURL The Friendica BaseURL
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function show(App $app, App\Arguments $args, App\Mode $mode, App\Module $module, App\Router $router, L10n\L10n $l10n, Configuration $config, PConfiguration $pconfig, LoggerInterface $logger, BaseURL $baseURL)
	{
		$moduleName = $module->getName();
		$content    = '';

		try {
			// Initialize module that can set the current theme in the init() method, either directly or via App->profile_uid
			$this->page['page_title'] = $moduleName;

			// determine the module class and save it to the module instance
			// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
			$module = $module->determineClass($args, $router, $config);

			// Let the module run it's internal process (init, get, post, ...)
			$module->run($l10n, $app, $logger, $app->getCurrentTheme(), $_SERVER, $_POST);

			$moduleClass = $module->getClassName();

			$arr = ['content' => $content];
			Hook::callAll($moduleClass . '_mod_content', $arr);
			$content = $arr['content'];
			$arr     = ['content' => call_user_func([$moduleClass, 'content'])];
			Hook::callAll($moduleClass . '_mod_aftercontent', $arr);
			$content .= $arr['content'];
		} catch (HTTPException $e) {
			$content = ModuleHTTPException::content($e);
		}

		// initialise content region
		if ($mode->isNormal()) {
			Hook::callAll('page_content_top', $this->page['content']);
		}

		$this->page['content'] .= $content;

		/* Create the page head after setting the language
		 * and getting any auth credentials.
		 *
		 * Moved initHead() and initFooter() to after
		 * all the module functions have executed so that all
		 * theme choices made by the modules can take effect.
		 */
		$this->initHead($app, $module, $pconfig, $config, $l10n);

		/* Build the page ending -- this is stuff that goes right before
		 * the closing </body> tag
		 */
		$this->initFooter($app, $l10n);

		if (!$app->isAjax()) {
			Hook::callAll('page_end', $this->page['content']);
		}

		// Add the navigation (menu) template
		if ($moduleName != 'install' && $moduleName != 'maintenance') {
			$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('nav_head.tpl'), []);
			$this->page['nav']      = Nav::build($app);
		}

		// Build the page - now that we have all the components
		if (isset($_GET["mode"]) && (($_GET["mode"] == "raw") || ($_GET["mode"] == "minimal"))) {
			$doc = new DOMDocument();

			$target = new DOMDocument();
			$target->loadXML("<root></root>");

			$content = mb_convert_encoding($this->page["content"], 'HTML-ENTITIES', "UTF-8");

			/// @TODO one day, kill those error-surpressing @ stuff, or PHP should ban it
			@$doc->loadHTML($content);

			$xpath = new DOMXPath($doc);

			$list = $xpath->query("//*[contains(@id,'tread-wrapper-')]");  /* */

			foreach ($list as $item) {
				$item = $target->importNode($item, true);

				// And then append it to the target
				$target->documentElement->appendChild($item);
			}

			if ($_GET["mode"] == "raw") {
				header("Content-type: text/html; charset=utf-8");

				echo substr($target->saveHTML(), 6, -8);

				exit();
			}
		}

		$page    = $this->page;
		$profile = $app->profile;

		header("X-Friendica-Version: " . FRIENDICA_VERSION);
		header("Content-type: text/html; charset=utf-8");

		if ($config->get('system', 'hsts') && ($baseURL->getSSLPolicy() == BaseUrl::SSL_POLICY_FULL)) {
			header("Strict-Transport-Security: max-age=31536000");
		}

		// Some security stuff
		header('X-Content-Type-Options: nosniff');
		header('X-XSS-Protection: 1; mode=block');
		header('X-Permitted-Cross-Domain-Policies: none');
		header('X-Frame-Options: sameorigin');

		// Things like embedded OSM maps don't work, when this is enabled
		// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' https: data:; media-src 'self' https:; child-src 'self' https:; object-src 'none'");

		/* We use $_GET["mode"] for special page templates. So we will check if we have
		 * to load another page template than the default one.
		 * The page templates are located in /view/php/ or in the theme directory.
		 */
		if (isset($_GET["mode"])) {
			$template = Theme::getPathForFile($_GET["mode"] . '.php');
		}

		// If there is no page template use the default page template
		if (empty($template)) {
			$template = Theme::getPathForFile("default.php");
		}

		// Theme templates expect $a as an App instance
		$a = $app;

		// Used as is in view/php/default.php
		$lang = $l10n->getCurrentLang();

		/// @TODO Looks unsafe (remote-inclusion), is maybe not but Core\Theme::getPathForFile() uses file_exists() but does not escape anything
		require_once $template;
	}

	/**
	 * Initializes App->page['htmlhead'].
	 *
	 * Includes:
	 * - Page title
	 * - Favicons
	 * - Registered stylesheets (through App->registerStylesheet())
	 * - Infinite scroll data
	 * - head.tpl template
	 */
	private function initHead(App $app, App\Module $module, PConfiguration $pconfig, Configuration $config, L10n\L10n $l10n)
	{
		$interval = ((local_user()) ? $pconfig->get(local_user(), 'system', 'update_interval') : 40000);

		// If the update is 'deactivated' set it to the highest integer number (~24 days)
		if ($interval < 0) {
			$interval = 2147483647;
		}

		if ($interval < 10000) {
			$interval = 40000;
		}

		// Default title: current module called
		if (empty($this->page['title']) && $module->getName()) {
			$this->page['title'] = ucfirst($module->getName());
		}

		// Prepend the sitename to the page title
		$this->page['title'] = $config->get('config', 'sitename', '') . (!empty($this->page['title']) ? ' | ' . $this->page['title'] : '');

		if (!empty(Renderer::$theme['stylesheet'])) {
			$stylesheet = Renderer::$theme['stylesheet'];
		} else {
			$stylesheet = $app->getCurrentThemeStylesheetPath();
		}

		$this->registerStylesheet($stylesheet);

		$shortcut_icon = $config->get('system', 'shortcut_icon');
		if ($shortcut_icon == '') {
			$shortcut_icon = 'images/friendica-32.png';
		}

		$touch_icon = $config->get('system', 'touch_icon');
		if ($touch_icon == '') {
			$touch_icon = 'images/friendica-128.png';
		}

		Hook::callAll('head', $this->page['htmlhead']);

		$tpl = Renderer::getMarkupTemplate('head.tpl');
		/* put the head template at the beginning of page['htmlhead']
		 * since the code added by the modules frequently depends on it
		 * being first
		 */
		$this->page['htmlhead'] = Renderer::replaceMacros($tpl, [
				'$local_user'      => local_user(),
				'$generator'       => 'Friendica' . ' ' . FRIENDICA_VERSION,
				'$delitem'         => $l10n->t('Delete this item?'),
				'$update_interval' => $interval,
				'$shortcut_icon'   => $shortcut_icon,
				'$touch_icon'      => $touch_icon,
				'$block_public'    => intval($config->get('system', 'block_public')),
				'$stylesheets'     => $this->stylesheets,
			]) . $this->page['htmlhead'];
	}

	/**
	 * Initializes App->page['footer'].
	 *
	 * Includes:
	 * - Javascript homebase
	 * - Mobile toggle link
	 * - Registered footer scripts (through App->registerFooterScript())
	 * - footer.tpl template
	 */
	private function initFooter(App $app, L10n\L10n $l10n)
	{
		// If you're just visiting, let javascript take you home
		if (!empty($_SESSION['visitor_home'])) {
			$homebase = $_SESSION['visitor_home'];
		} elseif (local_user()) {
			$homebase = 'profile/' . $app->user['nickname'];
		}

		if (isset($homebase)) {
			$this->page['footer'] .= '<script>var homebase="' . $homebase . '";</script>' . "\n";
		}

		/*
		 * Add a "toggle mobile" link if we're using a mobile device
		 */
		if ($app->is_mobile || $app->is_tablet) {
			if (isset($_SESSION['show-mobile']) && !$_SESSION['show-mobile']) {
				$link = 'toggle_mobile?address=' . urlencode(curPageURL());
			} else {
				$link = 'toggle_mobile?off=1&address=' . urlencode(curPageURL());
			}
			$this->page['footer'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate("toggle_mobile_footer.tpl"), [
				'$toggle_link' => $link,
				'$toggle_text' => $l10n->t('toggle mobile')
			]);
		}

		Hook::callAll('footer', $this->page['footer']);

		$tpl                  = Renderer::getMarkupTemplate('footer.tpl');
		$this->page['footer'] = Renderer::replaceMacros($tpl, [
				'$footerScripts' => $this->footerScripts,
			]) . $this->page['footer'];
	}

	/**
	 * Register a stylesheet file path to be included in the <head> tag of every page.
	 * Inclusion is done in App->initHead().
	 * The path can be absolute or relative to the Friendica installation base folder.
	 *
	 * @param string $path
	 *
	 * @see initHead()
	 *
	 */
	public function registerStylesheet($path)
	{
		if (mb_strpos($path, $this->basePath . DIRECTORY_SEPARATOR) === 0) {
			$path = mb_substr($path, mb_strlen($this->basePath . DIRECTORY_SEPARATOR));
		}

		$this->stylesheets[] = trim($path, '/');
	}

	/**
	 * Register a javascript file path to be included in the <footer> tag of every page.
	 * Inclusion is done in App->initFooter().
	 * The path can be absolute or relative to the Friendica installation base folder.
	 *
	 * @param string $path
	 *
	 * @see initFooter()
	 *
	 */
	public function registerFooterScript($path)
	{
		$url = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $path);

		$this->footerScripts[] = trim($url, '/');
	}

	/**
	 * Whether a offset exists
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 *                      An offset to check for.
	 *                      </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset)
	{
		return isset($this->page[$offset]);
	}

	/**
	 * Offset to retrieve
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to retrieve.
	 *                      </p>
	 *
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset)
	{
		return $this->page[$offset] ?? null;
	}

	/**
	 * Offset to set
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to assign the value to.
	 *                      </p>
	 * @param mixed $value  <p>
	 *                      The value to set.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value)
	{
		$this->page[$offset] = $value;
	}

	/**
	 * Offset to unset
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to unset.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset)
	{
		unset($this->page[$offset]);
	}
}
