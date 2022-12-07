<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
 * Configuration for the default routes in Friendica
 *
 * The syntax is either
 * - 'route' => [ Module::class , [ HTTPMethod(s) ] ]
 * - 'group' => [ 'route' => [ Module::class, [ HTTPMethod(s) ] ]
 *
 * It's possible to create recursive groups
 *
 */

use Friendica\App\Router as R;
use Friendica\Module;

$profileRoutes = [
	''                                         => [Module\Profile\Index::class,        [R::GET]],
	'/contacts/common'                         => [Module\Profile\Common::class,       [R::GET]],
	'/contacts[/{type}]'                       => [Module\Profile\Contacts::class,     [R::GET]],
	'/media'                                   => [Module\Profile\Media::class,        [R::GET]],
	'/photos'                                  => [Module\Profile\Photos::class,       [R::GET, R::POST]],
	'/profile'                                 => [Module\Profile\Profile::class,      [R::GET]],
	'/remote_follow'                           => [Module\Profile\RemoteFollow::class, [R::GET, R::POST]],
	'/restricted'                              => [Module\Profile\Restricted::class,   [R::GET         ]],
	'/schedule'                                => [Module\Profile\Schedule::class,     [R::GET, R::POST]],
	'/status[/{category}[/{date1}[/{date2}]]]' => [Module\Profile\Status::class,       [R::GET]],
	'/unkmail'                                 => [Module\Profile\UnkMail::class,      [R::GET, R::POST]],
];

$apiRoutes = [
	'/account' => [
		'/verify_credentials[.{extension:json|xml|rss|atom}]'      => [Module\Api\Twitter\Account\VerifyCredentials::class,  [R::GET         ]],
		'/rate_limit_status[.{extension:json|xml|rss|atom}]'       => [Module\Api\Twitter\Account\RateLimitStatus::class,    [R::GET         ]],
		'/update_profile[.{extension:json|xml|rss|atom}]'          => [Module\Api\Twitter\Account\UpdateProfile ::class,     [        R::POST]],
		'/update_profile_image[.{extension:json|xml|rss|atom}]'    => [Module\Api\Twitter\Account\UpdateProfileImage::class, [        R::POST]],
	],

	'/blocks/ids[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Blocks\Ids::class,               [R::GET         ]],
	'/blocks/list[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Blocks\Lists::class,             [R::GET         ]],
	'/conversation/show[.{extension:json|xml|rss|atom}]'           => [Module\Api\GNUSocial\Statusnet\Conversation::class, [R::GET         ]],
	'/conversation/show/{id:\d+}[.{extension:json|xml|rss|atom}]'  => [Module\Api\GNUSocial\Statusnet\Conversation::class, [R::GET         ]],
	'/direct_messages' => [
		'/all[.{extension:json|xml|rss|atom}]'                     => [Module\Api\Twitter\DirectMessages\All::class,          [R::GET         ]],
		'/conversation[.{extension:json|xml|rss|atom}]'            => [Module\Api\Twitter\DirectMessages\Conversation::class, [R::GET         ]],
		'/destroy[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\DirectMessages\Destroy::class,      [        R::POST]],
		'/new[.{extension:json|xml|rss|atom}]'                     => [Module\Api\Twitter\DirectMessages\NewDM::class,        [        R::POST]],
		'/sent[.{extension:json|xml|rss|atom}]'                    => [Module\Api\Twitter\DirectMessages\Sent::class,         [R::GET         ]],
	],
	'/direct_messages[.{extension:json|xml|rss|atom}]'             => [Module\Api\Twitter\DirectMessages\Inbox::class,     [R::GET, R::POST]],

	'/externalprofile/show[.{extension:json|xml|rss|atom}]'        => [Module\Api\Twitter\Users\Show::class,               [R::GET         ]],
	'/favorites/create[.{extension:json|xml|rss|atom}]'            => [Module\Api\Twitter\Favorites\Create::class,         [        R::POST]],
	'/favorites/destroy[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Favorites\Destroy::class,        [        R::POST]],
	'/favorites[.{extension:json|xml|rss|atom}]'                   => [Module\Api\Twitter\Favorites::class,                [R::GET         ]],
	'/followers/ids[.{extension:json|xml|rss|atom}]'               => [Module\Api\Twitter\Followers\Ids::class,            [R::GET         ]],
	'/followers/list[.{extension:json|xml|rss|atom}]'              => [Module\Api\Twitter\Followers\Lists::class,          [R::GET         ]],
	'/friends/ids[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Friends\Ids::class,              [R::GET         ]],
	'/friends/list[.{extension:json|xml|rss|atom}]'                => [Module\Api\Twitter\Friends\Lists::class,            [R::GET         ]],
	'/friendships/destroy[.{extension:json|xml|rss|atom}]'         => [Module\Api\Twitter\Friendships\Destroy::class,      [        R::POST]],
	'/friendships/incoming[.{extension:json|xml|rss|atom}]'        => [Module\Api\Twitter\Friendships\Incoming::class,     [R::GET         ]],
	'/friendships/show[.{extension:json|xml|rss|atom}]'            => [Module\Api\Twitter\Friendships\Show::class,         [R::GET         ]],

	'/friendica' => [
		'/activity/{verb:attendmaybe|attendno|attendyes|dislike|like|unattendmaybe|unattendno|unattendyes|undislike|unlike}[.{extension:json|xml|rss|atom}]'
			=> [Module\Api\Friendica\Activity::class, [        R::POST]],
		'/notification/seen[.{extension:json|xml|rss|atom}]'       => [Module\Api\Friendica\Notification\Seen::class,      [        R::POST]],
		'/notification[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Notification::class,           [R::GET         ]],
		'/notifications[.{extension:json|xml|rss|atom}]'           => [Module\Api\Friendica\Notification::class,           [R::GET         ]],
		'/direct_messages_setseen[.{extension:json|xml|rss|atom}]' => [Module\Api\Friendica\DirectMessages\Setseen::class, [        R::POST]],
		'/direct_messages_search[.{extension:json|xml|rss|atom}]'  => [Module\Api\Friendica\DirectMessages\Search ::class, [R::GET         ]],
		'/events[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Friendica\Events\Index::class,           [R::GET         ]],
                '/event_create[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Events\Create::class,          [        R::POST]],
                '/event_delete[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Events\Delete::class,          [        R::POST]],
		'/group_show[.{extension:json|xml|rss|atom}]'              => [Module\Api\Friendica\Group\Show::class,             [R::GET         ]],
		'/group_create[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Group\Create::class,           [        R::POST]],
		'/group_delete[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Group\Delete::class,           [        R::POST]],
		'/group_update[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Group\Update::class,           [        R::POST]],
		'/profile/show[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Profile\Show::class,           [R::GET         ]],
		'/photoalbum/delete[.{extension:json|xml|rss|atom}]'       => [Module\Api\Friendica\Photoalbum\Delete::class,      [        R::POST]],
		'/photoalbum/update[.{extension:json|xml|rss|atom}]'       => [Module\Api\Friendica\Photoalbum\Update::class,      [        R::POST]],
		'/photos/list[.{extension:json|xml|rss|atom}]'             => [Module\Api\Friendica\Photo\Lists::class,            [R::GET         ]],
		'/photo/create[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Photo\Create::class,           [        R::POST]],
		'/photo/delete[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Photo\Delete::class,           [        R::POST]],
		'/photo/update[.{extension:json|xml|rss|atom}]'            => [Module\Api\Friendica\Photo\Update::class,           [        R::POST]],
		'/photo[.{extension:json|xml|rss|atom}]'                   => [Module\Api\Friendica\Photo::class,                  [R::GET         ]],
	],

	'/gnusocial/config[.{extension:json|xml|rss|atom}]'            => [Module\Api\GNUSocial\GNUSocial\Config::class,  [R::GET         ]],
	'/gnusocial/version[.{extension:json|xml|rss|atom}]'           => [Module\Api\GNUSocial\GNUSocial\Version::class, [R::GET         ]],
	'/help/test[.{extension:json|xml|rss|atom}]'                   => [Module\Api\GNUSocial\Help\Test::class,         [R::GET         ]],

	'/lists' => [
		'/create[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Lists\Create::class,    [        R::POST]],
		'/destroy[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Lists\Destroy::class,   [        R::POST]],
		'/list[.{extension:json|xml|rss|atom}]'                    => [Module\Api\Twitter\Lists\Lists::class,     [R::GET         ]],
		'/ownerships[.{extension:json|xml|rss|atom}]'              => [Module\Api\Twitter\Lists\Ownership::class, [R::GET         ]],
		'/statuses[.{extension:json|xml|rss|atom}]'                => [Module\Api\Twitter\Lists\Statuses::class,  [R::GET         ]],
		'/subscriptions[.{extension:json|xml|rss|atom}]'           => [Module\Api\Friendica\Lists\Lists::class,   [R::GET         ]],
		'/update[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Lists\Update::class,    [        R::POST]],
	],

	'/media/upload[.{extension:json|xml|rss|atom}]'                    => [Module\Api\Twitter\Media\Upload::class,             [        R::POST]],
	'/media/metadata/create[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Media\Metadata\Create::class,    [        R::POST]],
	'/saved_searches/list[.{extension:json|xml|rss|atom}]'             => [Module\Api\Twitter\SavedSearches::class,            [R::GET         ]],
	'/search/tweets[.{extension:json|xml|rss|atom}]'                   => [Module\Api\Twitter\Search\Tweets::class,            [R::GET         ]],
	'/search[.{extension:json|xml|rss|atom}]'                          => [Module\Api\Twitter\Search\Tweets::class,            [R::GET         ]],
	'/statusnet/config[.{extension:json|xml|rss|atom}]'                => [Module\Api\GNUSocial\GNUSocial\Config::class,       [R::GET         ]],
	'/statusnet/conversation[.{extension:json|xml|rss|atom}]'          => [Module\Api\GNUSocial\Statusnet\Conversation::class, [R::GET         ]],
	'/statusnet/conversation/{id:\d+}[.{extension:json|xml|rss|atom}]' => [Module\Api\GNUSocial\Statusnet\Conversation::class, [R::GET         ]],
	'/statusnet/version[.{extension:json|xml|rss|atom}]'               => [Module\Api\GNUSocial\GNUSocial\Version::class,      [R::GET         ]],

	'/statuses' => [
		'/destroy[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Statuses\Destroy::class,               [        R::POST]],
		'/destroy/{id:\d+}[.{extension:json|xml|rss|atom}]'        => [Module\Api\Twitter\Statuses\Destroy::class,               [        R::POST]],
		'/followers[.{extension:json|xml|rss|atom}]'               => [Module\Api\Twitter\Followers\Lists::class,                [R::GET         ]],
		'/friends[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Friends\Lists::class,                  [R::GET         ]],
		'/friends_timeline[.{extension:json|xml|rss|atom}]'        => [Module\Api\Twitter\Statuses\HomeTimeline::class,          [R::GET         ]],
		'/home_timeline[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Statuses\HomeTimeline::class,          [R::GET         ]],
		'/mediap[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Statuses\Update::class,                [        R::POST]],
		'/mentions[.{extension:json|xml|rss|atom}]'                => [Module\Api\Twitter\Statuses\Mentions::class,              [R::GET         ]],
		'/mentions_timeline[.{extension:json|xml|rss|atom}]'       => [Module\Api\Twitter\Statuses\Mentions::class,              [R::GET         ]],
		'/networkpublic_timeline[.{extension:json|xml|rss|atom}]'  => [Module\Api\Twitter\Statuses\NetworkPublicTimeline::class, [R::GET         ]],
		'/public_timeline[.{extension:json|xml|rss|atom}]'         => [Module\Api\Twitter\Statuses\PublicTimeline::class,        [R::GET         ]],
		'/replies[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Statuses\Mentions::class,              [R::GET         ]],
		'/retweet[.{extension:json|xml|rss|atom}]'                 => [Module\Api\Twitter\Statuses\Retweet::class,               [        R::POST]],
		'/retweet/{id:\d+}[.{extension:json|xml|rss|atom}]'        => [Module\Api\Twitter\Statuses\Retweet::class,               [        R::POST]],
		'/show[.{extension:json|xml|rss|atom}]'                    => [Module\Api\Twitter\Statuses\Show::class,                  [R::GET         ]],
		'/show/{id:\d+}[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Statuses\Show::class,                  [R::GET         ]],
		'/update[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Statuses\Update::class,                [        R::POST]],
		'/update_with_media[.{extension:json|xml|rss|atom}]'       => [Module\Api\Twitter\Statuses\Update::class,                [        R::POST]],
		'/user_timeline[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Statuses\UserTimeline::class,          [R::GET         ]],
	],

	'/users' => [
		'/lookup[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Users\Lookup::class, [R::GET         ]],
		'/search[.{extension:json|xml|rss|atom}]'                  => [Module\Api\Twitter\Users\Search::class, [R::GET         ]],
		'/show[.{extension:json|xml|rss|atom}]'                    => [Module\Api\Twitter\Users\Show::class,   [R::GET         ]],
		'/show/{id:\d+}[.{extension:json|xml|rss|atom}]'           => [Module\Api\Twitter\Users\Show::class,   [R::GET         ]],
	],
];

return [
	'/' => [Module\Home::class, [R::GET]],

	'/.well-known' => [
		'/host-meta'      => [Module\WellKnown\HostMeta::class,     [R::GET]],
		'/nodeinfo'       => [Module\WellKnown\NodeInfo::class,     [R::GET]],
		'/security.txt'   => [Module\WellKnown\SecurityTxt::class,  [R::GET]],
		'/webfinger'      => [Module\Xrd::class,                    [R::GET]],
		'/x-nodeinfo2'    => [Module\NodeInfo210::class,            [R::GET]],
		'/x-social-relay' => [Module\WellKnown\XSocialRelay::class, [R::GET]],
	],

	'/2fa' => [
		'[/]'       => [Module\Security\TwoFactor\Verify::class,   [R::GET, R::POST]],
		'/recovery' => [Module\Security\TwoFactor\Recovery::class, [R::GET, R::POST]],
		'/trust'    => [Module\Security\TwoFactor\Trust::class,    [R::GET, R::POST]],
		'/signout'  => [Module\Security\TwoFactor\SignOut::class,  [R::GET, R::POST]],
	],

	'/api' => [
		''     => $apiRoutes,
		'/1.1' => $apiRoutes,
		'/v1' => [
			'/accounts'                          => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/accounts/{id:\d+}'                 => [Module\Api\Mastodon\Accounts::class,                 [R::GET         ]],
			'/accounts/{id:\d+}/statuses'        => [Module\Api\Mastodon\Accounts\Statuses::class,        [R::GET         ]],
			'/accounts/{id:\d+}/featured_tags'   => [Module\Api\Mastodon\Accounts\FeaturedTags::class,    [R::GET         ]], // Dummy, not supported
			'/accounts/{id:\d+}/followers'       => [Module\Api\Mastodon\Accounts\Followers::class,       [R::GET         ]],
			'/accounts/{id:\d+}/following'       => [Module\Api\Mastodon\Accounts\Following::class,       [R::GET         ]],
			'/accounts/{id:\d+}/lists'           => [Module\Api\Mastodon\Accounts\Lists::class,           [R::GET         ]],
			'/accounts/{id:\d+}/identity_proofs' => [Module\Api\Mastodon\Accounts\IdentityProofs::class,  [R::GET         ]], // Dummy, not supported
			'/accounts/{id:\d+}/follow'          => [Module\Api\Mastodon\Accounts\Follow::class,          [        R::POST]],
			'/accounts/{id:\d+}/unfollow'        => [Module\Api\Mastodon\Accounts\Unfollow::class,        [        R::POST]],
			'/accounts/{id:\d+}/block'           => [Module\Api\Mastodon\Accounts\Block::class,           [        R::POST]],
			'/accounts/{id:\d+}/unblock'         => [Module\Api\Mastodon\Accounts\Unblock::class,         [        R::POST]],
			'/accounts/{id:\d+}/mute'            => [Module\Api\Mastodon\Accounts\Mute::class,            [        R::POST]],
			'/accounts/{id:\d+}/unmute'          => [Module\Api\Mastodon\Accounts\Unmute::class,          [        R::POST]],
			'/accounts/{id:\d+}/pin'             => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/accounts/{id:\d+}/unpin'           => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/accounts/{id:\d+}/note'            => [Module\Api\Mastodon\Accounts\Note::class,            [        R::POST]],
			'/accounts/{id:\d+}/remove_from_followers' => [Module\Api\Mastodon\Unimplemented::class,      [        R::POST]], // not supported
			'/accounts/familiar_followers'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/accounts/lookup'                   => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/accounts/relationships'            => [Module\Api\Mastodon\Accounts\Relationships::class,   [R::GET         ]],
			'/accounts/search'                   => [Module\Api\Mastodon\Accounts\Search::class,          [R::GET         ]],
			'/accounts/update_credentials'       => [Module\Api\Mastodon\Accounts\UpdateCredentials::class, [R::PATCH     ]],
			'/accounts/verify_credentials'       => [Module\Api\Mastodon\Accounts\VerifyCredentials::class, [R::GET       ]],
			'/accounts/{name}'                   => [Module\Api\Mastodon\Accounts::class,                 [R::GET         ]],
			'/admin/accounts/{id:\d+}'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/accounts/{id:\d+}/{action}'  => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/admin/dimensions'                  => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/admin/measures'                    => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/admin/retention'                   => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/admin/trends/links'                => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/trends/statuses'             => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/trends/tags'                 => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/reports'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/reports/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/admin/reports/{id:\d+}/{action}'   => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/announcements'                     => [Module\Api\Mastodon\Announcements::class,            [R::GET         ]], // Dummy, not supported
			'/announcements/{id:\d+}/dismiss'    => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/announcements/{id:\d+}/reactions/{name}' => [Module\Api\Mastodon\Unimplemented::class,      [R::PUT, R::DELETE]], // not supported
			'/apps'                              => [Module\Api\Mastodon\Apps::class,                     [        R::POST]],
			'/apps/verify_credentials'           => [Module\Api\Mastodon\Apps\VerifyCredentials::class,   [R::GET         ]],
			'/blocks'                            => [Module\Api\Mastodon\Blocks::class,                   [R::GET         ]],
			'/bookmarks'                         => [Module\Api\Mastodon\Bookmarks::class,                [R::GET         ]],
			'/conversations'                     => [Module\Api\Mastodon\Conversations::class,            [R::GET         ]],
			'/conversations/{id:\d+}'            => [Module\Api\Mastodon\Conversations::class,            [R::DELETE      ]],
			'/conversations/{id:\d+}/read'       => [Module\Api\Mastodon\Conversations\Read::class,       [R::POST        ]],
			'/custom_emojis'                     => [Module\Api\Mastodon\CustomEmojis::class,             [R::GET         ]],
			'/domain_blocks'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST, R::DELETE]], // not supported
			'/directory'                         => [Module\Api\Mastodon\Directory::class,                [R::GET         ]],
			'/emails/confirmations'              => [Module\Api\Mastodon\Unimplemented::class,            [R::POST        ]], // not supported
			'/endorsements'                      => [Module\Api\Mastodon\Endorsements::class,             [R::GET         ]], // Dummy, not supported
			'/favourites'                        => [Module\Api\Mastodon\Favourited::class,               [R::GET         ]],
			'/featured_tags'                     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST]], // not supported
			'/featured_tags/{id:\d+}'            => [Module\Api\Mastodon\Unimplemented::class,            [R::DELETE      ]], // not supported
			'/featured_tags/suggestions'         => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/filters/{id:\d+}'                  => [Module\Api\Mastodon\Unimplemented::class,            [R::GET, R::POST, R::PUT, R::DELETE]], // not supported
			'/follow_requests'                   => [Module\Api\Mastodon\FollowRequests::class,           [R::GET         ]],
			'/follow_requests/{id:\d+}/{action}' => [Module\Api\Mastodon\FollowRequests::class,           [        R::POST]],
			'/followed_tags'                     => [Module\Api\Mastodon\FollowedTags::class,             [R::GET         ]],
			'/instance'                          => [Module\Api\Mastodon\Instance::class,                 [R::GET         ]],
			'/instance/activity'                 => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // @todo
			'/instance/domain_blocks'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // @todo
			'/instance/extended_description'     => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // @todo
			'/instance/peers'                    => [Module\Api\Mastodon\Instance\Peers::class,           [R::GET         ]],
			'/instance/rules'                    => [Module\Api\Mastodon\Instance\Rules::class,           [R::GET         ]],
			'/lists'                             => [Module\Api\Mastodon\Lists::class,                    [R::GET, R::POST]],
			'/lists/{id:\d+}'                    => [Module\Api\Mastodon\Lists::class,                    [R::GET, R::PUT, R::DELETE]],
			'/lists/{id:\d+}/accounts'           => [Module\Api\Mastodon\Lists\Accounts::class,           [R::GET, R::POST, R::DELETE]],
			'/markers'                           => [Module\Api\Mastodon\Markers::class,                  [R::GET, R::POST]],
			'/media/{id:\d+}'                    => [Module\Api\Mastodon\Media::class,                    [R::GET, R::PUT ]],
			'/mutes'                             => [Module\Api\Mastodon\Mutes::class,                    [R::GET         ]],
			'/notifications'                     => [Module\Api\Mastodon\Notifications::class,            [R::GET         ]],
			'/notifications/{id:\d+}'            => [Module\Api\Mastodon\Notifications::class,            [R::GET         ]],
			'/notifications/clear'               => [Module\Api\Mastodon\Notifications\Clear::class,      [        R::POST]],
			'/notifications/{id:\d+}/dismiss'    => [Module\Api\Mastodon\Notifications\Dismiss::class,    [        R::POST]],
			'/polls/{id:\d+}'                    => [Module\Api\Mastodon\Polls::class,                    [R::GET         ]],
			'/polls/{id:\d+}/votes'              => [Module\Api\Mastodon\Unimplemented::class,            [        R::POST]], // not supported
			'/preferences'                       => [Module\Api\Mastodon\Preferences::class,              [R::GET         ]],
			'/push/subscription'                 => [Module\Api\Mastodon\PushSubscription::class,         [R::GET, R::POST, R::PUT, R::DELETE]],
			'/reports'                           => [Module\Api\Mastodon\Reports::class,                  [        R::POST]],
			'/scheduled_statuses'                => [Module\Api\Mastodon\ScheduledStatuses::class,        [R::GET         ]],
			'/scheduled_statuses/{id:\d+}'       => [Module\Api\Mastodon\ScheduledStatuses::class,        [R::GET, R::PUT, R::DELETE]],
			'/statuses'                          => [Module\Api\Mastodon\Statuses::class,                 [        R::POST]],
			'/statuses/{id:\d+}'                 => [Module\Api\Mastodon\Statuses::class,                 [R::GET, R::PUT, R::DELETE]],
			'/statuses/{id:\d+}/card'            => [Module\Api\Mastodon\Statuses\Card::class,            [R::GET         ]],
			'/statuses/{id:\d+}/context'         => [Module\Api\Mastodon\Statuses\Context::class,         [R::GET         ]],
			'/statuses/{id:\d+}/reblogged_by'    => [Module\Api\Mastodon\Statuses\RebloggedBy::class,     [R::GET         ]],
			'/statuses/{id:\d+}/favourited_by'   => [Module\Api\Mastodon\Statuses\FavouritedBy::class,    [R::GET         ]],
			'/statuses/{id:\d+}/favourite'       => [Module\Api\Mastodon\Statuses\Favourite::class,       [        R::POST]],
			'/statuses/{id:\d+}/unfavourite'     => [Module\Api\Mastodon\Statuses\Unfavourite::class,     [        R::POST]],
			'/statuses/{id:\d+}/reblog'          => [Module\Api\Mastodon\Statuses\Reblog::class,          [        R::POST]],
			'/statuses/{id:\d+}/unreblog'        => [Module\Api\Mastodon\Statuses\Unreblog::class,        [        R::POST]],
			'/statuses/{id:\d+}/bookmark'        => [Module\Api\Mastodon\Statuses\Bookmark::class,        [        R::POST]],
			'/statuses/{id:\d+}/unbookmark'      => [Module\Api\Mastodon\Statuses\Unbookmark::class,      [        R::POST]],
			'/statuses/{id:\d+}/mute'            => [Module\Api\Mastodon\Statuses\Mute::class,            [        R::POST]],
			'/statuses/{id:\d+}/unmute'          => [Module\Api\Mastodon\Statuses\Unmute::class,          [        R::POST]],
			'/statuses/{id:\d+}/pin'             => [Module\Api\Mastodon\Statuses\Pin::class,             [        R::POST]],
			'/statuses/{id:\d+}/unpin'           => [Module\Api\Mastodon\Statuses\Unpin::class,           [        R::POST]],
			'/statuses/{id:\d+}/history'         => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/statuses/{id:\d+}/source'          => [Module\Api\Mastodon\Statuses\Source::class,          [R::GET         ]],
			'/streaming/direct'                  => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/hashtag'                 => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/hashtag/local'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/health'                  => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/list'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/public'                  => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/public/local'            => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/public/remote'           => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/user'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/streaming/user/notification'       => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not implemented
			'/suggestions/{id:\d+}'              => [Module\Api\Mastodon\Unimplemented::class,            [R::DELETE      ]], // not implemented
			'/tags/{hashtag}'                    => [Module\Api\Mastodon\Tags::class,                     [R::GET         ]],
			'/tags/{hashtag}/follow'             => [Module\Api\Mastodon\Tags\Follow::class,              [        R::POST]],
			'/tags/{hashtag}/unfollow'           => [Module\Api\Mastodon\Tags\Unfollow::class,            [        R::POST]],
			'/timelines/direct'                  => [Module\Api\Mastodon\Timelines\Direct::class,         [R::GET         ]],
			'/timelines/home'                    => [Module\Api\Mastodon\Timelines\Home::class,           [R::GET         ]],
			'/timelines/list/{id:\d+}'           => [Module\Api\Mastodon\Timelines\ListTimeline::class,   [R::GET         ]],
			'/timelines/public'                  => [Module\Api\Mastodon\Timelines\PublicTimeline::class, [R::GET         ]],
			'/timelines/tag/{hashtag}'           => [Module\Api\Mastodon\Timelines\Tag::class,            [R::GET         ]],
			'/trends'                            => [Module\Api\Mastodon\Trends\Tags::class,              [R::GET         ]],
			'/trends/links'                      => [Module\Api\Mastodon\Trends\Links::class,             [R::GET         ]],
			'/trends/statuses'                   => [Module\Api\Mastodon\Trends\Statuses::class,          [R::GET         ]],
			'/trends/tags'                       => [Module\Api\Mastodon\Trends\Tags::class,              [R::GET         ]],
		],
		'/v2' => [
			'/instance'                          => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
		],
		'/v{version:\d+}' => [
			'/admin/accounts'                    => [Module\Api\Mastodon\Unimplemented::class,            [R::GET         ]], // not supported
			'/filters'                           => [Module\Api\Mastodon\Filters::class,                  [R::GET         ]], // Dummy, not supported
			'/media'                             => [Module\Api\Mastodon\Media::class,                    [        R::POST]],
			'/search'                            => [Module\Api\Mastodon\Search::class,                   [R::GET         ]],
			'/suggestions'                       => [Module\Api\Mastodon\Suggestions::class,              [R::GET         ]],
		],
		'/meta'                                  => [Module\Api\Mastodon\Unimplemented::class, [R::POST        ]], // not supported
		'/oembed'                                => [Module\Api\Mastodon\Unimplemented::class, [R::GET         ]],
		'/proofs'                                => [Module\Api\Mastodon\Proofs::class,        [R::GET         ]], // Dummy, not supported
	],

	'/about[/more]'                              => [Module\Friendica::class, [R::GET]],

	'/admin'               => [
		'[/]' => [Module\Admin\Summary::class, [R::GET]],

		'/addons'         => [Module\Admin\Addons\Index::class,   [R::GET, R::POST]],
		'/addons/{addon}' => [Module\Admin\Addons\Details::class, [R::GET, R::POST]],

		'/dbsync[/{action}[/{update:\d+}]]' => [Module\Admin\DBSync::class, [R::GET]],

		'/features'   => [Module\Admin\Features::class,   [R::GET, R::POST]],
		'/federation' => [Module\Admin\Federation::class, [R::GET]],

		'/logs/view' => [Module\Admin\Logs\View::class,     [R::GET]],
		'/logs'      => [Module\Admin\Logs\Settings::class, [R::GET, R::POST]],

		'/phpinfo' => [Module\Admin\PhpInfo::class, [R::GET]],

		'/queue[/{status}]' => [Module\Admin\Queue::class, [R::GET]],

		'/site' => [Module\Admin\Site::class, [R::GET, R::POST]],

		'/storage'        => [Module\Admin\Storage::class, [R::GET, R::POST]],
		'/storage/{name}' => [Module\Admin\Storage::class, [        R::POST]],

		'/themes'               => [Module\Admin\Themes\Index::class,   [R::GET, R::POST]],
		'/themes/{theme}'       => [Module\Admin\Themes\Details::class, [R::GET, R::POST]],
		'/themes/{theme}/embed' => [Module\Admin\Themes\Embed::class,   [R::GET, R::POST]],

		'/tos' => [Module\Admin\Tos::class, [R::GET, R::POST]],
	],
	'/amcd'                => [Module\AccountManagementControlDocument::class, [R::GET]],
	'/acctlink'            => [Module\Acctlink::class,     [R::GET]],
	'/apps'                => [Module\Apps::class,         [R::GET]],
	'/attach/{item:\d+}'   => [Module\Attach::class,       [R::GET]],

	// Mastodon route used by Fedifind to follow people who set their Webfinger address in their Twitter bio
	'/authorize_interaction' => [Module\Contact\Follow::class, [R::GET, R::POST]],

	'/babel'               => [Module\Debug\Babel::class,  [R::GET, R::POST]],
	'/debug/ap'            => [Module\Debug\ActivityPubConversion::class,  [R::GET, R::POST]],

	'/blocklist/domain/download' => [Module\Blocklist\Domain\Download::class, [R::GET]],

	'/bookmarklet'         => [Module\Bookmarklet::class,  [R::GET]],

	'/calendar' => [
		'[/]'                                           => [Module\Calendar\Show::class,       [R::GET         ]],
		'/show/{nickname}'                              => [Module\Calendar\Show::class,       [R::GET         ]],
		'/export/{nickname}[/{format:csv|ical}]'        => [Module\Calendar\Export::class,     [R::GET         ]],
		'/api/{action:ignore|unignore|delete}/{id:\d+}' => [Module\Calendar\Event\API::class,  [R::GET         ]],
		'/api/{action:create}'                          => [Module\Calendar\Event\API::class,  [        R::POST]],
		'/api/get[/{nickname}]'                         => [Module\Calendar\Event\Get::class,  [R::GET         ]],
		'/event/show/{id:\d+}'                          => [Module\Calendar\Event\Show::class, [R::GET         ]],
		'/event/show/{nickname}/{id:\d+}'               => [Module\Calendar\Event\Show::class, [R::GET         ]],
		'/event/{mode:new}'                             => [Module\Calendar\Event\Form::class, [R::GET         ]],
		'/event/{mode:edit|copy}/{id:\d+}'              => [Module\Calendar\Event\Form::class, [R::GET         ]],
	],

	'/community[/{content}]' => [Module\Conversation\Community::class, [R::GET]],

	'/compose[/{type}]'    => [Module\Item\Compose::class, [R::GET, R::POST]],

	'/contact'   => [
		'[/]'                         => [Module\Contact::class,                [R::GET]],
		'/{id:\d+}[/]'                => [Module\Contact\Profile::class,        [R::GET, R::POST]],
		'/{id:\d+}/{action:block|ignore|update|updateprofile}'
		                              => [Module\Contact\Profile::class,        [R::GET]],
		'/{id:\d+}/advanced'          => [Module\Contact\Advanced::class,       [R::GET, R::POST]],
		'/{id:\d+}/conversations'     => [Module\Contact\Conversations::class,  [R::GET]],
		'/{id:\d+}/contacts[/{type}]' => [Module\Contact\Contacts::class,       [R::GET]],
		'/{id:\d+}/media'             => [Module\Contact\Media::class,          [R::GET]],
		'/{id:\d+}/posts'             => [Module\Contact\Posts::class,          [R::GET]],
		'/{id:\d+}/revoke'            => [Module\Contact\Revoke::class,         [R::GET, R::POST]],
		'/archived'                   => [Module\Contact::class,                [R::GET]],
		'/batch'                      => [Module\Contact::class,                [R::GET, R::POST]],
		'/blocked'                    => [Module\Contact::class,                [R::GET]],
		'/follow'                     => [Module\Contact\Follow::class,         [R::GET, R::POST]],
		'/hidden'                     => [Module\Contact::class,                [R::GET]],
		'/hovercard'                  => [Module\Contact\Hovercard::class,      [R::GET]],
		'/ignored'                    => [Module\Contact::class,                [R::GET]],
		'/match'                      => [Module\Contact\MatchInterests::class, [R::GET]],
		'/pending'                    => [Module\Contact::class,                [R::GET]],
		'/redir/{id:\d+}'             => [Module\Contact\Redir::class,          [R::GET]],
		'/suggestions'                => [Module\Contact\Suggestions::class,    [R::GET]],
		'/unfollow'                   => [Module\Contact\Unfollow::class,       [R::GET, R::POST]],
	],

	'/credits'                  => [Module\Credits::class,          [R::GET]],
	'/delegation'               => [Module\Delegation::class,       [R::GET, R::POST]],
	'/dfrn_notify[/{nickname}]' => [Module\DFRN\Notify::class,      [R::POST]],
	'/dfrn_poll/{nickname}'     => [Module\DFRN\Poll::class,        [R::GET]],
	'/dirfind'                  => [Module\Search\Directory::class, [R::GET]],
	'/directory'                => [Module\Directory::class,        [R::GET]],

	'/display/{guid}'                                        => [Module\Item\Display::class, [R::GET]],
	'/display/feed-item/{uri-id}[.atom]'                     => [Module\Item\Feed::class,    [R::GET]],
	'/display/feed-item/{uri-id}/{mode:conversation}[.atom]' => [Module\Item\Feed::class,    [R::GET]],

	'/featured/{nickname}'      => [Module\ActivityPub\Featured::class, [R::GET]],

	'/feed/{nickname}[/{type:posts|comments|replies|activity}]' => [Module\Feed::class, [R::GET]],

	'/feedtest' => [Module\Debug\Feed::class, [R::GET]],

	'/fetch'             => [
		'/post/{guid}'           => [Module\Diaspora\Fetch::class, [R::GET]],
		'/status_message/{guid}' => [Module\Diaspora\Fetch::class, [R::GET]],
		'/reshare/{guid}'        => [Module\Diaspora\Fetch::class, [R::GET]],
	],
	'/filed'                => [Module\Search\Filed::class,          [R::GET]],
	'/filer[/{id:\d+}]'     => [Module\Filer\SaveTag::class,         [R::GET]],
	'/filerm/{id:\d+}'      => [Module\Filer\RemoveTag::class,       [R::GET, R::POST]],
	'/follow_confirm'       => [Module\FollowConfirm::class,         [R::GET, R::POST]],
	'/followers/{nickname}' => [Module\ActivityPub\Followers::class, [R::GET]],
	'/following/{nickname}' => [Module\ActivityPub\Following::class, [R::GET]],
	'/friendica[/json]'     => [Module\Friendica::class,             [R::GET]],
	'/friendica/inbox'      => [Module\ActivityPub\Inbox::class,     [R::GET, R::POST]],

	'/fsuggest/{contact:\d+}' => [Module\FriendSuggest::class,  [R::GET, R::POST]],

	'/group'              => [
		'[/]'                        => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}'               => [Module\Group::class, [R::GET, R::POST]],
		'/none'                      => [Module\Group::class, [R::GET, R::POST]],
		'/new'                       => [Module\Group::class, [R::GET, R::POST]],
		'/drop/{group:\d+}'          => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/{contact:\d+}' => [Module\Group::class, [R::GET, R::POST]],
		'/{group:\d+}/{command:add|remove}/{contact:\d+}'    => [Module\Group::class, [R::GET, R::POST]],
	],
	'/hashtag'                    => [Module\Hashtag::class,           [R::GET]],
	'/help[/{doc:.+}]'            => [Module\Help::class,              [R::GET]],
	'/home'                       => [Module\Home::class,              [R::GET]],
	'/hcard/{profile}[/{action}]' => [Module\HCard::class,             [R::GET]],
	'/inbox[/{nickname}]'         => [Module\ActivityPub\Inbox::class, [R::GET, R::POST]],
	'/invite'                     => [Module\Invite::class,            [R::GET, R::POST]],

	'/install'         => [
		'[/]'                    => [Module\Install::class, [R::GET, R::POST]],
		'/testrewrite'           => [Module\Install::class, [R::GET]],
	],

	'/item/{id:\d+}'            => [
		'/activity/{verb}' => [Module\Item\Activity::class,    [        R::POST]],
		'/follow'          => [Module\Item\Follow::class,      [        R::POST]],
		'/ignore'          => [Module\Item\Ignore::class,      [        R::POST]],
		'/pin'             => [Module\Item\Pin::class,         [        R::POST]],
		'/star'            => [Module\Item\Star::class,        [        R::POST]],
	],

	'/localtime'          => [Module\Debug\Localtime::class, [R::GET, R::POST]],
	'/login'              => [Module\Security\Login::class,  [R::GET, R::POST]],
	'/logout'             => [Module\Security\Logout::class, [R::GET, R::POST]],
	'/magic'              => [Module\Magic::class,           [R::GET]],
	'/manifest'           => [Module\Manifest::class,        [R::GET]],
	'/friendica.webmanifest'  => [Module\Manifest::class,    [R::GET]],

	'/media' => [
		'/attachment/browser'      => [Module\Media\Attachment\Browser::class, [R::GET]],
		'/attachment/upload'       => [Module\Media\Attachment\Upload::class,  [       R::POST]],
		'/photo/browser[/{album}]' => [Module\Media\Photo\Browser::class,      [R::GET]],
		'/photo/upload'            => [Module\Media\Photo\Upload::class,       [       R::POST]],
	],

	'/moderation'               => [
		'[/]' => [Module\Moderation\Summary::class, [R::GET]],

		'/blocklist/contact'       => [Module\Moderation\Blocklist\Contact::class,       [R::GET, R::POST]],
		'/blocklist/server'        => [Module\Moderation\Blocklist\Server\Index::class,  [R::GET, R::POST]],
		'/blocklist/server/add'    => [Module\Moderation\Blocklist\Server\Add::class,    [R::GET, R::POST]],
		'/blocklist/server/import' => [Module\Moderation\Blocklist\Server\Import::class, [R::GET, R::POST]],

		'/item/delete'          => [Module\Moderation\Item\Delete::class, [R::GET, R::POST]],
		'/item/source[/{guid}]' => [Module\Moderation\Item\Source::class, [R::GET, R::POST]],

		'/users[/{action}/{uid}]'         => [Module\Moderation\Users\Index::class,   [R::GET, R::POST]],
		'/users/active[/{action}/{uid}]'  => [Module\Moderation\Users\Active::class,  [R::GET, R::POST]],
		'/users/pending[/{action}/{uid}]' => [Module\Moderation\Users\Pending::class, [R::GET, R::POST]],
		'/users/blocked[/{action}/{uid}]' => [Module\Moderation\Users\Blocked::class, [R::GET, R::POST]],
		'/users/deleted'                  => [Module\Moderation\Users\Deleted::class, [R::GET         ]],
		'/users/create'                   => [Module\Moderation\Users\Create::class,  [R::GET, R::POST]],
	],
	'/modexp/{nick}'      => [Module\PublicRSAKey::class,    [R::GET]],
	'/newmember'          => [Module\Welcome::class,         [R::GET]],
	'/nodeinfo/1.0'       => [Module\NodeInfo110::class,     [R::GET]],
	'/nodeinfo/2.0'       => [Module\NodeInfo120::class,     [R::GET]],
	'/nogroup'            => [Module\Group::class,           [R::GET]],

	'/noscrape' => [
		'/{nick}'         => [Module\NoScrape::class, [R::GET]],
		'/{profile}/view' => [Module\NoScrape::class, [R::GET]],
	],

	'/notifications' => [
		'/network[/json]'    => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/system[/json]'     => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/personal[/json]'   => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/home[/json]'       => [Module\Notifications\Notifications::class, [R::GET, R::POST]],
		'/intros[/json]'     => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
		'/intros/all[/json]' => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
		'/intros/{contact:\d+}[/json]' => [Module\Notifications\Introductions::class, [R::GET, R::POST]],
	],

	'/notification'         => [
		'[/]'       => [Module\Notifications\Notification::class, [R::GET]],
		'/mark/all' => [Module\Notifications\Notification::class, [R::GET]],
		'/{id:\d+}' => [Module\Notifications\Notification::class, [R::GET, R::POST]],
	],

	'/notify/{notify_id:\d+}' => [Module\Notifications\Notification::class, [R::GET]],

	'/oauth' => [
		'/acknowledge' => [Module\OAuth\Acknowledge::class, [R::GET, R::POST]],
		'/authorize'   => [Module\OAuth\Authorize::class,   [R::GET]],
		'/revoke'      => [Module\OAuth\Revoke::class,      [R::POST]],
		'/token'       => [Module\OAuth\Token::class,       [R::POST]],
	],

	'/objects/{guid}[/{activity}]' => [Module\ActivityPub\Objects::class, [R::GET]],

	'/oembed'         => [
		'/b2h'    => [Module\Oembed::class, [R::GET]],
		'/h2b'    => [Module\Oembed::class, [R::GET]],
		'/{hash}' => [Module\Oembed::class, [R::GET]],
	],
	'/outbox/{nickname}' => [Module\ActivityPub\Outbox::class, [R::GET]],
	'/owa'               => [Module\Owa::class,                [R::GET]],
	'/openid'            => [Module\Security\OpenID::class,    [R::GET]],
	'/opensearch'        => [Module\OpenSearch::class,         [R::GET]],

	'/parseurl'                           => [Module\ParseUrl::class,          [R::GET]],
	'/permission/tooltip/{type}/{id:\d+}' => [Module\PermissionTooltip::class, [R::GET]],

	'/photo' => [
		'/{name}'                                                  => [Module\Photo::class, [R::GET]],
		'/{type}/{id:\d+}'                                         => [Module\Photo::class, [R::GET]],
		'/{type:contact|header}/{guid}'                            => [Module\Photo::class, [R::GET]],
		// User Id Fallback, to remove after version 2021.12
		'/{type}/{uid_ext:\d+\..*}'                                => [Module\Photo::class, [R::GET]],
		'/{type}/{nickname_ext}'                                   => [Module\Photo::class, [R::GET]],
		// Contact Id Fallback, to remove after version 2021.12
		'/{type:contact|header}/{customsize:\d+}/{contact_id:\d+}' => [Module\Photo::class, [R::GET]],
		'/{type:contact|header}/{customsize:\d+}/{guid}'           => [Module\Photo::class, [R::GET]],
		'/{type}/{customsize:\d+}/{id:\d+}'                        => [Module\Photo::class, [R::GET]],
		// User Id Fallback, to remove after version 2021.12
		'/{type}/{customsize:\d+}/{uid_ext:\d+\..*}'               => [Module\Photo::class, [R::GET]],
		'/{type}/{customsize:\d+}/{nickname_ext}'                  => [Module\Photo::class, [R::GET]],
	],

	// Kept for backwards-compatibility
	// @TODO remove by version 2023.12
	'/photos/{nickname}' => [Module\Profile\Photos::class, [R::GET]],

	'/ping'              => [Module\Notifications\Ping::class, [R::GET]],

	'/post' => [
		'/{post_id}/edit'                                          => [Module\Post\Edit::class,       [R::GET         ]],
		'/{post_id}/share'                                         => [Module\Post\Share::class,      [R::GET         ]],
		'/{item_id}/tag/add'                                       => [Module\Post\Tag\Add::class,    [        R::POST]],
		'/{item_id}/tag/remove[/{tag_name}]'                       => [Module\Post\Tag\Remove::class, [R::GET, R::POST]],
	],

	'/pretheme'          => [Module\ThemeDetails::class, [R::GET]],
	'/probe'             => [Module\Debug\Probe::class,  [R::GET]],

	'/profile/{nickname}' => $profileRoutes,
	'/u/{nickname}'       => $profileRoutes,
	'/~{nickname}'        => $profileRoutes,

	'/proxy' => [
		'[/]'                  => [Module\Proxy::class, [R::GET]],
		'/{url}'               => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{url}'        => [Module\Proxy::class, [R::GET]],
		'/{sub1}/{sub2}/{url}' => [Module\Proxy::class, [R::GET]],
	],

	// OStatus stack modules
	'/ostatus/repair'              => [Module\OStatus\Repair::class,           [R::GET         ]],
	'/ostatus/subscribe'           => [Module\OStatus\Subscribe::class,        [R::GET         ]],
	'/poco'                        => [Module\User\PortableContacts::class,    [R::GET         ]],
	'/pubsub/{nickname}/{cid:\d+}' => [Module\OStatus\PubSub::class,           [R::GET, R::POST]],
	'/pubsubhubbub/{nickname}'     => [Module\OStatus\PubSubHubBub::class,     [        R::POST]],
	'/salmon/{nickname}'           => [Module\OStatus\Salmon::class,           [        R::POST]],

	'/search' => [
		'[/]'                  => [Module\Search\Index::class, [R::GET         ]],
		'/acl'                 => [Module\Search\Acl::class,   [R::GET, R::POST]],
		'/saved/add'           => [Module\Search\Saved::class, [R::GET         ]],
		'/saved/remove'        => [Module\Search\Saved::class, [R::GET         ]],
		'/user/tags'           => [Module\Search\Tags::class,  [        R::POST]],
	],

	'/receive' => [
		'/{type:public}'       => [Module\Diaspora\Receive::class, [        R::POST]],
		'/{type:users}/{guid}' => [Module\Diaspora\Receive::class, [        R::POST]],
	],

	'/security' => [
		'/password_too_long' => [Module\Security\PasswordTooLong::class, [R::GET, R::POST]],
	],

	'/settings' => [
		'[/]'         => [Module\Settings\Account::class,               [R::GET, R::POST]],
		'/account' => [
			'[/]'     => [Module\Settings\Account::class,               [R::GET, R::POST]],
			'/{open}' => [Module\Settings\Account::class,               [R::GET, R::POST]],
		],
		'/addons[/{addon}]'                => [Module\Settings\Addons::class,           [R::GET, R::POST]],
		'/connectors[/{connector}]'        => [Module\Settings\Connectors::class,       [R::GET, R::POST]],
		'/delegation[/{action}/{user_id}]' => [Module\Settings\Delegation::class,       [R::GET, R::POST]],
		'/display'                         => [Module\Settings\Display::class,          [R::GET, R::POST]],
		'/features'                        => [Module\Settings\Features::class,         [R::GET, R::POST]],
		'/oauth'                           => [Module\Settings\OAuth::class,            [R::GET, R::POST]],
		'/profile' => [
			'[/]'                  => [Module\Settings\Profile\Index::class,       [R::GET, R::POST]],
			'/photo[/new]'         => [Module\Settings\Profile\Photo\Index::class, [R::GET, R::POST]],
			'/photo/crop/{guid}'   => [Module\Settings\Profile\Photo\Crop::class,  [R::GET, R::POST]],
		],
		'/removeme'              => [Module\Settings\RemoveMe::class,              [R::GET, R::POST]],
		'/userexport[/{action}]' => [Module\Settings\UserExport::class,            [R::GET         ]],
		'/2fa' => [
			'[/]'           => [Module\Settings\TwoFactor\Index::class,       [R::GET, R::POST]],
			'/recovery'     => [Module\Settings\TwoFactor\Recovery::class,    [R::GET, R::POST]],
			'/app_specific' => [Module\Settings\TwoFactor\AppSpecific::class, [R::GET, R::POST]],
			'/verify'       => [Module\Settings\TwoFactor\Verify::class,      [R::GET, R::POST]],
			'/trusted'      => [Module\Settings\TwoFactor\Trusted::class,     [R::GET, R::POST]],
		],
	],

	'/network' => [
		'[/]'                         => [Module\Conversation\Network::class, [R::GET]],
		'/archive/{from:\d\d\d\d-\d\d-\d\d}[/{to:\d\d\d\d-\d\d-\d\d}]' => [Module\Conversation\Network::class, [R::GET]],
		'/forum/{contact_id:\d+}'     => [Module\Conversation\Network::class, [R::GET]],
		'/group/{group_id:\d+}'       => [Module\Conversation\Network::class, [R::GET]],
	],

	'/randprof'                      => [Module\RandomProfile::class,         [R::GET]],
	'/register'                      => [Module\Register::class,              [R::GET, R::POST]],
	'/robots.txt'                    => [Module\RobotsTxt::class,             [R::GET]],
	'/rsd.xml'                       => [Module\ReallySimpleDiscovery::class, [R::GET]],
	'/smilies[/json]'                => [Module\Smilies::class,               [R::GET]],
	'/statistics.json'               => [Module\Statistics::class,            [R::GET]],
	'/toggle_mobile'                 => [Module\ToggleMobile::class,          [R::GET]],
	'/tos'                           => [Module\Tos::class,                   [R::GET]],

	'/update_community[/{content}]'  => [Module\Update\Community::class,      [R::GET]],

	'/update_display'                => [Module\Update\Display::class, [R::GET]],

	'/update_network' => [
		'[/]'                        => [Module\Update\Network::class, [R::GET]],
		'/archive/{from:\d\d\d\d-\d\d-\d\d}[/{to:\d\d\d\d-\d\d-\d\d}]' => [Module\Update\Network::class, [R::GET]],
		'/forum/{contact_id:\d+}'    => [Module\Update\Network::class, [R::GET]],
		'/group/{group_id:\d+}'      => [Module\Update\Network::class, [R::GET]],
	],

	'/update_profile'                => [Module\Update\Profile::class,        [R::GET]],

	'/user/import'                   => [Module\User\Import::class,           [R::GET, R::POST]],

	'/view/theme/{theme}/style.pcss' => [Module\Theme::class,                 [R::GET]],
	'/viewsrc/{item:\d+}'            => [Module\Debug\ItemBody::class,        [R::GET]],
	'/webfinger'                     => [Module\Debug\WebFinger::class,       [R::GET]],
	'/xrd'                           => [Module\Xrd::class,                   [R::GET]],
];
