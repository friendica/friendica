<?php

namespace Friendica\App;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core;
use Friendica\LegacyModule;
use Friendica\Module\Home;
use Friendica\Module\PageNotFound;
use Psr\Log\LoggerInterface;

/**
 * Holds the common context of the current, loaded module
 */
class Module
{
	const DEFAULT = 'home';
	const DEFAULT_CLASS = Home::class;

	/**
	 * @var string The module name
	 */
	private $module;

	/**
	 * @var BaseObject The module class
	 */
	private $module_class;

	/**
	 * @var bool true, if the module is a backend module
	 */
	private $isBackend;

	/**
	 * @var bool true, if the loaded addon is private, so we have to print out not allowed
	 */
	private $printNotAllowedAddon;

	/**
	 * A list of modules, which are backend methods
	 *
	 * @var array
	 */
	const BACKEND_MODULES = [
		'_well_known',
		'api',
		'dfrn_notify',
		'feed',
		'fetch',
		'followers',
		'following',
		'hcard',
		'hostxrd',
		'inbox',
		'manifest',
		'nodeinfo',
		'noscrape',
		'objects',
		'outbox',
		'poco',
		'post',
		'proxy',
		'pubsub',
		'pubsubhubbub',
		'receive',
		'rsd_xml',
		'salmon',
		'statistics_json',
		'xrd',
	];

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->module;
	}

	/**
	 * @return string The base class name
	 */
	public function getClassName()
	{
		return $this->module_class;
	}

	/**
	 * @return bool
	 */
	public function isBackend()
	{
		return $this->isBackend;
	}

	public function __construct(string $module = self::DEFAULT, string $moduleClass = self::DEFAULT_CLASS, bool $isBackend = false, bool $printNotAllowedAddon = false)
	{
		$this->module       = $module;
		$this->module_class = $moduleClass;
		$this->isBackend    = $isBackend;
		$this->printNotAllowedAddon = $printNotAllowedAddon;
	}

	/**
	 * Determines the current module based on the App arguments and the server variable
	 *
	 * @param Arguments $args   The Friendica arguments
	 * @param array     $server The $_SERVER variable
	 *
	 * @return Module The module with the determined module
	 */
	public function determineModule(Arguments $args, array $server)
	{
		if ($args->getArgc() > 0) {
			$module = str_replace('.', '_', $args->get(0));
			$module = str_replace('-', '_', $module);
		} else {
			$module = self::DEFAULT;
		}

		// Compatibility with the Firefox App
		if (($module == "users") && ($args->getCommand() == "users/sign_in")) {
			$module = "login";
		}

		$isBackend = $this->checkBackend($module, $server);

		return new Module($module, $this->module_class, $isBackend, $this->printNotAllowedAddon);
	}

	/**
	 * Determine the class of the current module
	 *
	 * @param Arguments                 $args   The Friendica execution arguments
	 * @param Router                    $router The Friendica routing instance
	 * @param Core\Config\Configuration $config The Friendica Configuration
	 *
	 * @return Module The determined module of this call
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function determineClass(Arguments $args, Router $router, Core\Config\Configuration $config)
	{
		$printNotAllowedAddon = false;

		/**
		 * ROUTING
		 *
		 * From the request URL, routing consists of obtaining the name of a BaseModule-extending class of which the
		 * post() and/or content() static methods can be respectively called to produce a data change or an output.
		 **/

		// First we try explicit routes defined in App\Router
		$router->collectRoutes();

		$data = $router->getRouteCollector();
		Core\Hook::callAll('route_collection', $data);

		$module_class = $router->getModuleClass($args->getCommand());

		// Then we try addon-provided modules that we wrap in the LegacyModule class
		if (!$module_class && Core\Addon::isEnabled($this->module) && file_exists("addon/{$this->module}/{$this->module}.php")) {
			//Check if module is an app and if public access to apps is allowed or not
			$privateapps = $config->get('config', 'private_addons', false);
			if ((!local_user()) && Core\Hook::isAddonApp($this->module) && $privateapps) {
				$printNotAllowedAddon = true;
			} else {
				include_once "addon/{$this->module}/{$this->module}.php";
				if (function_exists($this->module . '_module')) {
					LegacyModule::setModuleFile("addon/{$this->module}/{$this->module}.php");
					$module_class = LegacyModule::class;
				}
			}
		}

		/* Finally, we look for a 'standard' program module in the 'mod' directory
		 * We emulate a Module class through the LegacyModule class
		 */
		if (!$module_class && file_exists("mod/{$this->module}.php")) {
			LegacyModule::setModuleFile("mod/{$this->module}.php");
			$module_class = LegacyModule::class;
		}

		$module_class = !isset($module_class) ? PageNotFound::class : $module_class;

		return new Module($this->module, $module_class, $this->isBackend, $printNotAllowedAddon);
	}

	/**
	 * Run the determined module class and calls all hooks applied to
	 *
	 * @param Core\L10n\L10n $l10n         The L10n instance
	 * @param App            $app          The whole Friendica app (for method arguments)
	 * @param LoggerInterface           $logger The Friendica logger
	 * @param string         $currentTheme The chosen theme
	 * @param array          $server       The $_SERVER variable
	 * @param array          $post         The $_POST variables
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function run(Core\L10n\L10n $l10n, App $app,  LoggerInterface $logger, string $currentTheme, array $server, array $post)
	{
		if ($this->printNotAllowedAddon) {
			info($l10n->t("You must be logged in to use addons. "));
		}

		/* The URL provided does not resolve to a valid module.
		 *
		 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'.
		 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic -
		 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
		 * this will often succeed and eventually do the right thing.
		 *
		 * Otherwise we are going to emit a 404 not found.
		 */
		if ($this->module_class === PageNotFound::class) {
			$queryString = $server['QUERY_STRING'];
			// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
			if (!empty($queryString) && preg_match('/{[0-9]}/', $queryString) !== 0) {
				exit();
			}

			if (!empty($queryString) && ($queryString === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
				$logger->info('index.php: dreamhost_error_hack invoked.', ['Original URI' => $server['REQUEST_URI']]);
				$app->internalRedirect($server['REQUEST_URI']);
			}

			$logger->debug('index.php: page not found.', ['request_uri' => $server['REQUEST_URI'], 'address' => $server['REMOTE_ADDR'], 'query' => $server['QUERY_STRING']]);
		}

		$placeholder = '';

		Core\Hook::callAll($this->module . '_mod_init', $placeholder);

		call_user_func([$this->module_class, 'init']);

		// "rawContent" is especially meant for technical endpoints.
		// This endpoint doesn't need any theme initialization or other comparable stuff.
		call_user_func([$this->module_class, 'rawContent']);

		// Load current theme info after module has been initialized as theme could have been set in module
		$theme_info_file = 'view/theme/' . $currentTheme . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		if (function_exists(str_replace('-', '_', $currentTheme) . '_init')) {
			$func = str_replace('-', '_', $currentTheme) . '_init';
			$func($app);
		}

		if ($server['REQUEST_METHOD'] === 'POST') {
			Core\Hook::callAll($this->module . '_mod_post', $post);
			call_user_func([$this->module_class, 'post']);
		}

		Core\Hook::callAll($this->module . '_mod_afterpost', $placeholder);
		call_user_func([$this->module_class, 'afterpost']);
	}

	/**
	 * @brief Checks if the site is called via a backend process
	 *
	 * This isn't a perfect solution. But we need this check very early.
	 * So we cannot wait until the modules are loaded.
	 *
	 * @param string $module The determined module
	 * @param array  $server The $_SERVER variable
	 *
	 * @return bool True, if the current module is called at backend
	 */
	private function checkBackend($module, array $server)
	{
		// Check if current module is in backend or backend flag is set
		return basename(($server['PHP_SELF'] ?? ''), '.php') !== 'index' &&
		       in_array($module, Module::BACKEND_MODULES);
	}
}
