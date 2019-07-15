<?php

namespace Friendica\App;


use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module;

/**
 * Wrapper for FastRoute\Router
 *
 * This wrapper only makes use of a subset of the router features, mainly parses a route rule to return the relevant
 * module class.
 *
 * Actual routes are defined in App->collectRoutes.
 *
 * @package Friendica\App
 */
class Router
{
	/** @var RouteCollector */
	protected $routeCollector;

	/**
	 * Static declaration of Friendica routes.
	 *
	 * Supports:
	 * - Route groups
	 * - Variable parts
	 * Disregards:
	 * - HTTP method other than GET
	 * - Named parameters
	 *
	 * Handler must be the name of a class extending Friendica\BaseModule.
	 *
	 * @brief Static declaration of Friendica routes.
	 */
	public function collectRoutes()
	{
		$this->routeCollector->addRoute(['GET'],         '[/]',               Module\Home::class);
		$this->routeCollector->addGroup('/.well-known', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/host-meta'       , Module\WellKnown\HostMeta::class);
			$collector->addRoute(['GET'], '/nodeinfo[/1.0]'  , Module\NodeInfo::class);
			$collector->addRoute(['GET'], '/webfinger'       , Module\Xrd::class);
			$collector->addRoute(['GET'], '/x-social-relay'  , Module\WellKnown\XSocialRelay::class);
		});
		$this->routeCollector->addGroup('/2fa', function (RouteCollector $collector) {
			$collector->addRoute(['GET', 'POST'], '[/]'                     , Module\TwoFactor\Verify::class);
			$collector->addRoute(['GET', 'POST'], '/recovery'               , Module\TwoFactor\Recovery::class);
		});
		$this->routeCollector->addGroup('/admin', function (RouteCollector $collector) {
			$collector->addRoute(['GET']        , '[/]'                     , Module\Admin\Summary::class);

			$collector->addRoute(['GET', 'POST'], '/addons'                 , Module\Admin\Addons\Index::class);
			$collector->addRoute(['GET', 'POST'], '/addons/{addon}'         , Module\Admin\Addons\Details::class);

			$collector->addRoute(['GET', 'POST'], '/blocklist/contact'      , Module\Admin\Blocklist\Contact::class);
			$collector->addRoute(['GET', 'POST'], '/blocklist/server'       , Module\Admin\Blocklist\Server::class);

			$collector->addRoute(['GET']        , '/dbsync[/check]'         , Module\Admin\DBSync::class);
			$collector->addRoute(['GET']        , '/dbsync/{update:\d+}'    , Module\Admin\DBSync::class);
			$collector->addRoute(['GET']        , '/dbsync/mark/{update:\d+}', Module\Admin\DBSync::class);

			$collector->addRoute(['GET', 'POST'], '/features'               , Module\Admin\Features::class);
			$collector->addRoute(['GET']        , '/federation'             , Module\Admin\Federation::class);

			$collector->addRoute(['GET', 'POST'], '/item/delete'            , Module\Admin\Item\Delete::class);
			$collector->addRoute(['GET', 'POST'], '/item/source[/{guid}]'   , Module\Admin\Item\Source::class);

			$collector->addRoute(['GET']        , '/logs/view'              , Module\Admin\Logs\View::class);
			$collector->addRoute(['GET', 'POST'], '/logs'                   , Module\Admin\Logs\Settings::class);

			$collector->addRoute(['GET']        , '/phpinfo'                , Module\Admin\PhpInfo::class);

			$collector->addRoute(['GET']        , '/queue[/deferred]'       , Module\Admin\Queue::class);

			$collector->addRoute(['GET', 'POST'], '/site'                   , Module\Admin\Site::class);

			$collector->addRoute(['GET', 'POST'], '/themes'                 , Module\Admin\Themes\Index::class);
			$collector->addRoute(['GET', 'POST'], '/themes/{theme}'         , Module\Admin\Themes\Details::class);
			$collector->addRoute(['GET', 'POST'], '/themes/{theme}/embed'   , Module\Admin\Themes\Embed::class);

			$collector->addRoute(['GET', 'POST'], '/tos'                    , Module\Admin\Tos::class);

			$collector->addRoute(['GET', 'POST'], '/users[/{action}/{uid}]' , Module\Admin\Users::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/amcd',                Module\AccountManagementControlDocument::class);
		$this->routeCollector->addRoute(['GET'],         '/acctlink',            Module\Acctlink::class);
		$this->routeCollector->addRoute(['GET'],         '/allfriends/{id:\d+}', Module\AllFriends::class);
		$this->routeCollector->addRoute(['GET'],         '/apps',                Module\Apps::class);
		$this->routeCollector->addRoute(['GET'],         '/attach/{item:\d+}',   Module\Attach::class);
		$this->routeCollector->addRoute(['GET'],         '/babel',               Module\Debug\Babel::class);
		$this->routeCollector->addRoute(['GET'],         '/bookmarklet',         Module\Bookmarklet::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/compose[/{type}]',    Module\Item\Compose::class);
		$this->routeCollector->addGroup('/contact', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '[/]',                                 Module\Contact::class);
			$collector->addRoute(['GET', 'POST'], '/{id:\d+}[/]',                Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/archive',                   Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/block',                     Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/conversations',             Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/drop',                      Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/ignore',                    Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/posts',                     Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/update',                    Module\Contact::class);
			$collector->addRoute(['GET'], '/{id:\d+}/updateprofile',             Module\Contact::class);
			$collector->addRoute(['GET'], '/archived',                           Module\Contact::class);
			$collector->addRoute(['GET', 'POST'], '/batch',                      Module\Contact::class);
			$collector->addRoute(['GET'], '/blocked',                            Module\Contact::class);
			$collector->addRoute(['GET'], '/hidden',                             Module\Contact::class);
			$collector->addRoute(['GET'], '/ignored',                            Module\Contact::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/credits',             Module\Credits::class);
		$this->routeCollector->addRoute(['GET'],         '/dirfind',             Module\Search\Directory::class);
		$this->routeCollector->addRoute(['GET'],         '/directory',           Module\Directory::class);
		$this->routeCollector->addGroup('/feed', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/{nickname}',                         Module\Feed::class);
			$collector->addRoute(['GET'], '/{nickname}/posts',                   Module\Feed::class);
			$collector->addRoute(['GET'], '/{nickname}/comments',                Module\Feed::class);
			$collector->addRoute(['GET'], '/{nickname}/replies',                 Module\Feed::class);
			$collector->addRoute(['GET'], '/{nickname}/activity',                Module\Feed::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/feedtest',            Module\Debug\Feed::class);
		$this->routeCollector->addGroup('/fetch', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/post/{guid}',                        Module\Diaspora\Fetch::class);
			$collector->addRoute(['GET'], '/status_message/{guid}',              Module\Diaspora\Fetch::class);
			$collector->addRoute(['GET'], '/reshare/{guid}',                     Module\Diaspora\Fetch::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/filer[/{id:\d+}]',    Module\Filer\SaveTag::class);
		$this->routeCollector->addRoute(['GET'],         '/filerm/{id:\d+}',     Module\Filer\RemoveTag::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/follow_confirm',      Module\FollowConfirm::class);
		$this->routeCollector->addRoute(['GET'],         '/followers/{owner}',   Module\Followers::class);
		$this->routeCollector->addRoute(['GET'],         '/following/{owner}',   Module\Following::class);
		$this->routeCollector->addRoute(['GET'],         '/friendica[/json]',    Module\Friendica::class);
		$this->routeCollector->addGroup('/group', function (RouteCollector $collector) {
			$collector->addRoute(['GET', 'POST'], '[/]',                         Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/{group:\d+}',                Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/none',                       Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/new',                        Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/drop/{group:\d+}',           Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/{group:\d+}/{contact:\d+}',  Module\Group::class);

			$collector->addRoute(['GET', 'POST'], '/{group:\d+}/add/{contact:\d+}',     Module\Group::class);
			$collector->addRoute(['GET', 'POST'], '/{group:\d+}/remove/{contact:\d+}',  Module\Group::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/hashtag',             Module\Hashtag::class);
		$this->routeCollector->addRoute(['GET'],         '/home',                Module\Home::class);
		$this->routeCollector->addRoute(['GET'],         '/help[/{doc:.+}]',     Module\Help::class);
		$this->routeCollector->addRoute(['GET'],         '/inbox[/{nickname}]',  Module\Inbox::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/invite',              Module\Invite::class);
		$this->routeCollector->addGroup('/install', function (RouteCollector $collector) {
			$collector->addRoute(['GET', 'POST'], '[/]',                         Module\Install::class);
			$collector->addRoute(['GET'],         '/testrewrite',                Module\Install::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/like/{item:\d+}',     Module\Like::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/localtime',           Module\Debug\Localtime::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/login',               Module\Login::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/logout',              Module\Logout::class);
		$this->routeCollector->addRoute(['GET'],         '/magic',               Module\Magic::class);
		$this->routeCollector->addRoute(['GET'],         '/maintenance',         Module\Maintenance::class);
		$this->routeCollector->addRoute(['GET'],         '/manifest',            Module\Manifest::class);
		$this->routeCollector->addRoute(['GET'],         '/modexp/{nick}',       Module\PublicRSAKey::class);
		$this->routeCollector->addRoute(['GET'],         '/newmember',           Module\Welcome::class);
		$this->routeCollector->addRoute(['GET'],         '/nodeinfo/1.0',        Module\NodeInfo::class);
		$this->routeCollector->addRoute(['GET'],         '/nogroup',             Module\Group::class);
		$this->routeCollector->addGroup('/notify', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '[/]',                                 Module\Notifications\Notify::class);
			$collector->addRoute(['GET'], '/view/{id:\d+}',                      Module\Notifications\Notify::class);
			$collector->addRoute(['GET'], '/mark/all',                           Module\Notifications\Notify::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/objects/{guid}',      Module\Objects::class);
		$this->routeCollector->addGroup('/oembed', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/b2h',                                Module\Oembed::class);
			$collector->addRoute(['GET'], '/h2b',                                Module\Oembed::class);
			$collector->addRoute(['GET'], '/{hash}',                             Module\Oembed::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/outbox/{owner}',      Module\Outbox::class);
		$this->routeCollector->addRoute(['GET'],         '/owa',                 Module\Owa::class);
		$this->routeCollector->addRoute(['GET'],         '/opensearch',          Module\OpenSearch::class);
		$this->routeCollector->addGroup('/photo', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/{name}',                             Module\Photo::class);
			$collector->addRoute(['GET'], '/{type}/{name}',                      Module\Photo::class);
			$collector->addRoute(['GET'], '/{type}/{customize}/{name}',          Module\Photo::class);
		});
		$this->routeCollector->addRoute(['GET'],         '/pretheme',            Module\ThemeDetails::class);
		$this->routeCollector->addRoute(['GET'],         '/probe',               Module\Debug\Probe::class);
		$this->routeCollector->addGroup('/profile', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/{nickname}',                         Module\Profile::class);
			$collector->addRoute(['GET'], '/{nickname}/{to:\d{4}-\d{2}-\d{2}}/{from:\d{4}-\d{2}-\d{2}}', Module\Profile::class);
			$collector->addRoute(['GET'], '/{nickname}/contacts[/{type}]',       Module\Profile\Contacts::class);
			$collector->addRoute(['GET'], '/{profile:\d+}/view',                 Module\Profile::class);
		});
		$this->routeCollector->addGroup('/proxy', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '[/]'                                , Module\Proxy::class);
			$collector->addRoute(['GET'], '/{url}'                             , Module\Proxy::class);
			$collector->addRoute(['GET'], '/{sub1}/{url}'                      , Module\Proxy::class);
			$collector->addRoute(['GET'], '/{sub1}/{sub2}/{url}'               , Module\Proxy::class);
		});

		$this->routeCollector->addGroup('/settings', function (RouteCollector $collector) {
			$collector->addGroup('/2fa', function (RouteCollector $collector) {
				$collector->addRoute(['GET', 'POST'], '[/]'                    , Module\Settings\TwoFactor\Index::class);
				$collector->addRoute(['GET', 'POST'], '/recovery'              , Module\Settings\TwoFactor\Recovery::class);
				$collector->addRoute(['GET', 'POST'], '/verify'                , Module\Settings\TwoFactor\Verify::class);
			});
		});
		$this->routeCollector->addRoute(['GET'],         '/randprof',            Module\RandomProfile::class);
		$this->routeCollector->addRoute(['GET', 'POST'], '/register',            Module\Register::class);
		$this->routeCollector->addRoute(['GET'],         '/robots.txt',          Module\RobotsTxt::class);
		$this->routeCollector->addRoute(['GET'],         '/rsd.xml',             Module\ReallySimpleDiscovery::class);
		$this->routeCollector->addRoute(['GET'],         '/smilies[/json]',      Module\Smilies::class);
		$this->routeCollector->addRoute(['GET'],         '/statistics.json',     Module\Statistics::class);
		$this->routeCollector->addRoute(['GET'],         '/starred/{item:\d+}',  Module\Starred::class);
		$this->routeCollector->addRoute(['GET'],         '/toggle_mobile',       Module\ToggleMobile::class);
		$this->routeCollector->addRoute(['GET'],         '/tos',                 Module\Tos::class);
		$this->routeCollector->addRoute(['GET'],         '/view/theme/{theme}/style.pcss',  Module\Theme::class);
		$this->routeCollector->addRoute(['GET'],         '/viewsrc/{item:\d+}',  Module\Debug\ItemBody::class);
		$this->routeCollector->addRoute(['GET'],         '/webfinger',           Module\Debug\WebFinger::class);
		$this->routeCollector->addRoute(['GET'],         '/xrd',                 Module\Xrd::class);
	}

	public function __construct(RouteCollector $routeCollector = null)
	{
		if (!$routeCollector) {
			$routeCollector = new RouteCollector(new Std(), new GroupCountBased());
		}

		$this->routeCollector = $routeCollector;
	}

	public function getRouteCollector()
	{
		return $this->routeCollector;
	}

	/**
	 * Returns the relevant module class name for the given page URI or NULL if no route rule matched.
	 *
	 * @param string $cmd The path component of the request URL without the query string
	 * @return string|null A Friendica\BaseModule-extending class name if a route rule matched
	 */
	public function getModuleClass($cmd)
	{
		$cmd = '/' . ltrim($cmd, '/');

		$dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->routeCollector->getData());

		$moduleClass = null;

		// @TODO: Enable method-specific modules
		$httpMethod = 'GET';
		$routeInfo = $dispatcher->dispatch($httpMethod, $cmd);
		if ($routeInfo[0] === Dispatcher::FOUND) {
			$moduleClass = $routeInfo[1];
		}

		return $moduleClass;
	}
}
