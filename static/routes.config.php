<?php

use Friendica\App\Router as R;
use Friendica\Module;

/**
* Configuration for the default routes in Friendica
*
* The syntax is either
* - 'route' => [ Module::class , [ HTTPMethod(s) ] ]
* - 'group' => [ 'route' => [ Module::class, [ HTTPMethod(s) ] ]
*
* It's possible to create recursive groups
*/
return [
	'/' => [Module\Home::class, [R::GET]],

	'/.well-known' => [
		'/host-meta'      => [Module\WellKnown\HostMeta::class,     [R::GET]],
		'/nodeinfo'       => [Module\WellKnown\NodeInfo::class,     [R::GET]],
		'/webfinger'      => [Module\Xrd::class,                    [R::GET]],
		'/x-social-relay' => [Module\WellKnown\XSocialRelay::class, [R::GET]],
	],

	'/2fa' => [
		'[/]'       => [Module\Security\TwoFactor\Verify::class,   [R::GET, R::POST]],
		'/recovery' => [Module\Security\TwoFactor\Recovery::class, [R::GET, R::POST]],
	],

	'/api' => [
		'/v1' => [
			'/follow_requests'                   => [Module\Api\Mastodon\FollowRequests::class, [R::GET         ]],
			'/follow_requests/{id:\d+}/{action}' => [Module\Api\Mastodon\FollowRequests::class, [        R::POST]],
			'/instance'                          => [Module\Api\Mastodon\Instance::class, [R::GET]],
			'/instance/peers'                    => [Module\Api\Mastodon\Instance\Peers::class, [R::GET]],
		],
	],

	'/admin'               => [
		'[/]' => [Module\Admin\Summary::class, [R::GET]],

		'/addons'         => [Module\Admin\Addons\Index::class,   [R::GET, R::POST]],
		'/addons/{addon}' => [Module\Admin\Addons\Details::class, [R::GET, R::POST]],


		'/blocklist/contact' => [Module\Admin\Blocklist\Contact::class, [R::GET, R::POST]],
		'/blocklist/server'  => [Module\Admin\Blocklist\Server::class,  [R::GET, R::POST]],

		'/dbsync[/check]'           => [Module\Admin\DBSync::class, [R::GET]],
		'/dbsync/{update:\d+}'      => [Module\Admin\DBSync::class, [R::GET]],
		'/dbsync/mark/{update:\d+}' => [Module\Admin\DBSync::class, [R::GET]],

		'/features'   => [Module\Admin\Features::class,   [R::GET, R::POST]],
		'/federation' => [Module\Admin\Federation::class, [R::GET]],

		'/item/delete'          => [Module\Admin\Item\Delete::class, [R::GET, R::POST]],
		'/item/source[/{guid}]' => [Module\Admin\Item\Source::class, [R::GET, R::POST]],

		'/logs/view' => [Module\Admin\Logs\View::class,     [R::GET]],
		'/logs'      => [Module\Admin\Logs\Settings::class, [R::GET, R::POST]],

		'/phpinfo' => [Module\Admin\PhpInfo::class, [R::GET]],

		'/queue[/deferred]' => [Module\Admin\Queue::class, [R::GET]],

		'/site' => [Module\Admin\Site::class, [R::GET, R::POST]],

		'/themes'               => [Module\Admin\Themes\Index::class,   [R::GET, R::POST]],
		'/themes/{theme}'       => [Module\Admin\Themes\Details::class, [R::GET, R::POST]],
		'/themes/{theme}/embed' => [Module\Admin\Themes\Embed::class,   [R::GET, R::POST]],

		'/tos' => [Module\Admin\Tos::class, [R::GET, R::POST]],

		'/users[/{action}/{uid}]' => [Module\Admin\Users::class, [R::GET, R::POST]],
	],
	'/amcd'                => [Module\AccountManagementControlDocument::class, [R::GET]],
	'/acctlink'            => [Module\Acctlink::class,     [R::GET]],
	'/allfriends/{id:\d+}' => [Module\AllFriends::class,   [R::GET]],
	'/apps'                => [Module\Apps::class,         [R::GET]],
	'/attach/{item:\d+}'   => [Module\Attach::class,       [R::GET]],
	'/babel'               => [Module\Debug\Babel::class,  [R::GET, R::POST]],
	'/bookmarklet'         => [Module\Bookmarklet::class,  [R::GET]],
	'/compose[/{type}]'    => [Module\Item\Compose::class, [R::GET, R::POST]],

	'/contact'   => [
		'[/]'                     => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}[/]'            => [Module\Contact::class,           [R::GET, R::POST]],
		'/{id:\d+}/archive'       => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/block'         => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/conversations' => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/drop'          => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/ignore'        => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/posts'         => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/update'        => [Module\Contact::class,           [R::GET]],
		'/{id:\d+}/updateprofile' => [Module\Contact::class,           [R::GET]],
		'/archived'               => [Module\Contact::class,           [R::GET]],
		'/batch'                  => [Module\Contact::class,           [R::GET, R::POST]],
		'/pending'                => [Module\Contact::class,           [R::GET]],
		'/blocked'                => [Module\Contact::class,           [R::GET]],
		'/hidden'                 => [Module\Contact::class,           [R::GET]],
		'/ignored'                => [Module\Contact::class,           [R::GET]],
		'/hovercard'              => [Module\Contact\Hovercard::class, [R::GET]],
	],

	'/credits'   => [Module\Credits::class,          [R::GET]],
	'/delegation'=> [Module\Delegation::class,       [R::GET, R::POST]],
	'/dirfind'   => [Module\Search\Directory::class, [R::GET]],
	'/directory' => [Module\Directory::class,        [R::GET]],

	'/feed'     => [
		'/{nickname}'          => [Module\Feed::class, [R::GET]],
		'/{nickname}/posts'    => [Module\Feed::class, [R::GET]],
		'/{nickname}/comments' => [Module\Feed::class, [R::GET]],
		'/{nickname}/replies'  => [Module\Feed::class, [R::GET]],
		'/{nickname}/activity' => [Module\Feed::class, [R::GET]],
	],
	'/feedtest' => [Module\Debug\Feed::class, [R::GET]],

	'/fetch'             => [
		'/post/{guid}'           => [Module\Diaspora\Fetch::class, [R::GET]],
		'/status_message/{guid}' => [Module\Diaspora\Fetch::class, [R::GET]],
		'/reshare/{guid}'        => [Module\Diaspora\Fetch::class, [R::GET]],
	],
	'/filer[/{id:\d+}]'  => [Module\Filer\SaveTag::class,   [R::GET]],
	'/filerm/{id:\d+}'   => [Module\Filer\RemoveTag::class, [R::GET]],
	'/follow_confirm'    => [Module\FollowConfirm::class,   [R::GET, R::POST]],
	'/followers/{owner}' => [Module\Followers::class,       [R::GET]],
	'/following/{owner}' => [Module\Following::class,       [R::GET]],
	'/friendica[/json]'  => [Module\Friendica::class,       [R::GET]],

	'/group'              => [
		'[/]'                        => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}'               => [Module\Group::class, [R::GET, R::POST]],
		'/none'                      => [Module\Group::class, [R::GET, R::POST]],
		'/new'                       => [Module\Group::class, [R::GET, R::POST]],
		'/drop/{group:\d+}'          => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/{contact:\d+}' => [Module\Group::class, [R::GET, R::POST]],

		'/{group:\d+}/add/{contact:\d+}'    => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/remove/{contact:\d+}' => [Module\Group::class, [R::GET, R::POST]],
	],
	'/hashtag'                    => [Module\Hashtag::class,   [R::GET]],
	'/help[/{doc:.+}]'            => [Module\Help::class,      [R::GET]],
	'/home'                       => [Module\Home::class,      [R::GET]],
	'/hcard/{profile}[/{action}]' => [Module\HoverCard::class, [R::GET]],
	'/inbox[/{nickname}]'         => [Module\Inbox::class,     [R::GET, R::POST]],
	'/invite'                     => [Module\Invite::class,    [R::GET, R::POST]],

	'/install'         => [
		'[/]'                    => [Module\Install::class, [R::GET, R::POST]],
		'/testrewrite'           => [Module\Install::class, [R::GET]],
	],

	'/item'            => [
		'/ignore/{id}' => [Module\Item\Ignore::class, [R::GET]],
	],

	'/like/{item:\d+}'    => [Module\Like::class,            [R::GET]],
	'/localtime'          => [Module\Debug\Localtime::class, [R::GET, R::POST]],
	'/login'              => [Module\Security\Login::class,  [R::GET, R::POST]],
	'/logout'             => [Module\Security\Logout::class, [R::GET, R::POST]],
	'/magic'              => [Module\Magic::class,           [R::GET]],
	'/maintenance'        => [Module\Maintenance::class,     [R::GET]],
	'/manifest'           => [Module\Manifest::class,        [R::GET]],
	'/modexp/{nick}'      => [Module\PublicRSAKey::class,    [R::GET]],
	'/newmember'          => [Module\Welcome::class,         [R::GET]],
	'/nodeinfo/{version}' => [Module\NodeInfo::class,        [R::GET]],
	'/nogroup'            => [Module\Group::class,           [R::GET]],

	'/notify'         => [
		'[/]'            => [Module\Notifications\Notify::class, [R::GET]],
		'/view/{id:\d+}' => [Module\Notifications\Notify::class, [R::GET]],
		'/mark/all'      => [Module\Notifications\Notify::class, [R::GET]],
	],
	'/objects/{guid}' => [Module\Objects::class, [R::GET]],

	'/oembed'         => [
		'/b2h'    => [Module\Oembed::class, [R::GET]],
		'/h2b'    => [Module\Oembed::class, [R::GET]],
		'/{hash}' => [Module\Oembed::class, [R::GET]],
	],
	'/outbox/{owner}' => [Module\Outbox::class,          [R::GET]],
	'/owa'            => [Module\Owa::class,             [R::GET]],
	'/openid'         => [Module\Security\OpenID::class, [R::GET]],
	'/opensearch'     => [Module\OpenSearch::class,      [R::GET]],

	'/photo' => [
		'/{name}'                    => [Module\Photo::class, [R::GET]],
		'/{type}/{name}'             => [Module\Photo::class, [R::GET]],
		'/{type}/{customize}/{name}' => [Module\Photo::class, [R::GET]],
	],

	'/pinned/{item:\d+}' => [Module\Pinned::class,       [R::GET]],
	'/pretheme'          => [Module\ThemeDetails::class, [R::GET]],
	'/probe'             => [Module\Debug\Probe::class,  [R::GET]],

	'/profile' => [
		'/{nickname}'                                                 => [Module\Profile::class,          [R::GET]],
		'/{nickname}/{to:\d{4}-\d{2}-\d{2}}/{from:\d{4}-\d{2}-\d{2}}' => [Module\Profile::class,          [R::GET]],
		'/{nickname}/contacts[/{type}]'                               => [Module\Profile\Contacts::class, [R::GET]],
		'/{profile:\d+}/view'                                         => [Module\Profile::class,          [R::GET]],
	],

	'/proxy' => [
		'[/]'                  => [Module\Proxy::class, [R::GET]],
		'/{url}'               => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{url}'        => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{sub2}/{url}' => [Module\Proxy::class, [R::GET]],
	],

	'/search' => [
		'[/]'                  => [Module\Search\Index::class, [R::GET]],
		'/acl'                 => [Module\Search\Acl::class,   [R::GET, R::POST]],
		'/saved/add'           => [Module\Search\Saved::class, [R::GET]],
		'/saved/remove'        => [Module\Search\Saved::class, [R::GET]],
	],

	'/receive' => [
		'/public'       => [Module\Diaspora\Receive::class, [R::POST]],
		'/users/{guid}' => [Module\Diaspora\Receive::class, [R::POST]],
	],

	'/settings' => [
		'/2fa' => [
			'[/]'           => [Module\Settings\TwoFactor\Index::class,       [R::GET, R::POST]],
			'/recovery'     => [Module\Settings\TwoFactor\Recovery::class,    [R::GET, R::POST]],
			'/app_specific' => [Module\Settings\TwoFactor\AppSpecific::class, [R::GET, R::POST]],
			'/verify'       => [Module\Settings\TwoFactor\Verify::class,      [R::GET, R::POST]],
		],
		'/delegation[/{action}/{user_id}]' => [Module\Settings\Delegation::class,       [R::GET, R::POST]],
		'/profile' => [
			'/photo[/new]'         => [Module\Settings\Profile\Photo\Index::class, [R::GET, R::POST]],
			'/photo/crop/{guid}'   => [Module\Settings\Profile\Photo\Crop::class,  [R::GET, R::POST]],
		],
		'/userexport[/{action}]' => [Module\Settings\UserExport::class,             [R::GET, R::POST]],
	],

	'/randprof'                      => [Module\RandomProfile::class,         [R::GET]],
	'/register'                      => [Module\Register::class,              [R::GET, R::POST]],
	'/robots.txt'                    => [Module\RobotsTxt::class,             [R::GET]],
	'/rsd.xml'                       => [Module\ReallySimpleDiscovery::class, [R::GET]],
	'/smilies[/json]'                => [Module\Smilies::class,               [R::GET]],
	'/statistics.json'               => [Module\Statistics::class,            [R::GET]],
	'/starred/{item:\d+}'            => [Module\Starred::class,               [R::GET]],
	'/toggle_mobile'                 => [Module\ToggleMobile::class,          [R::GET]],
	'/tos'                           => [Module\Tos::class,                   [R::GET]],
	'/view/theme/{theme}/style.pcss' => [Module\Theme::class,                 [R::GET]],
	'/viewsrc/{item:\d+}'            => [Module\Debug\ItemBody::class,        [R::GET]],
	'/webfinger'                     => [Module\Debug\WebFinger::class,       [R::GET]],
	'/xrd'                           => [Module\Xrd::class,                   [R::GET]],
];
