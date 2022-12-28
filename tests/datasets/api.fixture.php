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
 */

use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Notification;

return [
	// Empty these tables
	'profile_field',
	'permissionset',
	'cache',
	'conversation',
	'pconfig',
	'photo',
	'workerqueue',
	'mail',
	'post-delivery-data',
	// Base test config to avoid notice messages
	'user' => [
		[
			'uid'      => 42,
			'username' => 'Test user',
			'nickname' => 'selfcontact',
			'verified' => 1,
			'prvkey'   => "-----BEGIN RSA PRIVATE KEY-----\nMIICXgIBAAKBgQDVqxF9kIgtgRL0+q+jTi578FA1r1+crEmlYc0pdxcbmmrhjuRc\nrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/KHVks7eQ4yr4MxTRlsxo5qf2o\nnbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjsQPX240vGNXIkfLiAWwIDAQAB\nAoGBAIwuiPIdggqAtWQ+mD8HCx5LQwSFw6/xpPu5F7ZNqL52aAsGCbL3o2QoIG4c\na1qf9Ot16BNgNBqxQF3hzRTkBMrKYlmNTUkwJXun/zjQJq2JvOlcrSuXlIucUjs4\nXekVN25aYPHrX9m2FEIUwZTb4UYXbR80KbIDI53BkQ6EwSbpAkEA7aO49CR2Hf1Y\n1d2GaUI/Z0wvbj//+t0Kg0bPt16ca8KVjEQQA5ylsDaiw510jDz9NBQxSOk6If23\nUeRixc1RDQJBAOYtN4YnPM1Zfp6IxXlqMCc+xUWRTPEPFt+WpG+v79koNamAeA6o\nZzTl92hl58IqSdbgojeE2zXWQRvlimFMLQcCQQCV6jND0byyLqFcSeQBg0l8YROK\n+dUC7W80YfeoNod3c8nkMwvnO2tLPyxvO2XLEq6prBNra7bAus5rWyj0oBIBAkEA\n1EvUMFm0TLpEfLgtWuTD8Q6GKLnxO0ztjd+FXrXpBGN/ywyArxRHzJRmctW6wmz6\nmcOqGobhIHCysKYv0bnOtQJAc2M5RwlASHH4jGJzXgt3nboyiJfufM0RV9iry3ho\nCXQRWAONKoLqnsfC6qNP8OzY8FMJcwmPWj7Q/6z6yLBFTA==\n-----END RSA PRIVATE KEY-----",
			'pubkey'   => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",			'password' => '$2y$10$DLRNTRmJgKe1cSrFJ5Jb0edCqvXlA9sh/RHdSnfxjbR.04yZRm4Qm',
			'theme'    => 'frio',
		],
	],
	'item-uri' => [
		[
			'id'   => 1,
			'uri'  => '1',
			'guid' => '1',
		],
		[
			'id'   => 2,
			'uri'  => '2',
			'guid' => '2',
		],
		[
			'id'   => 3,
			'uri'  => '3',
			'guid' => '3',
		],
		[
			'id'   => 4,
			'uri'  => '4',
			'guid' => '4',
		],
		[
			'id'   => 5,
			'uri'  => '5',
			'guid' => '5',
		],
		[
			'id'   => 6,
			'uri'  => '6',
			'guid' => '6',
		],
		[
			'id'   => 7,
			'uri'  => '7',
			'guid' => '7',
		],
		[
			'id'   => 42,
			'uri'  => 'http://localhost/profile/selfcontact',
			'guid' => '42',
		],
		[
			'id'   => 43,
			'uri'  => 'http://localhost/profile/othercontact',
			'guid' => '43',
		],
		[
			'id'   => 44,
			'uri'  => 'http://localhost/profile/friendcontact',
			'guid' => '44',
		],
		[
			'id'   => 46,
			'uri'  => 'http://localhost/profile/mutualcontact',
			'guid' => '46',
		],
	],
	'contact' => [
		[
			'id'       => 42,
			'uid'      => 42,
			'uri-id'   => 42,
			'name'     => 'Self contact',
			'nick'     => 'selfcontact',
			'self'     => 1,
			'nurl'     => 'http://localhost/profile/selfcontact',
			'url'      => 'http://localhost/profile/selfcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'about'    => 'User used in tests',
			'prvkey'   => "-----BEGIN RSA PRIVATE KEY-----\nMIICXgIBAAKBgQDVqxF9kIgtgRL0+q+jTi578FA1r1+crEmlYc0pdxcbmmrhjuRc\nrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/KHVks7eQ4yr4MxTRlsxo5qf2o\nnbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjsQPX240vGNXIkfLiAWwIDAQAB\nAoGBAIwuiPIdggqAtWQ+mD8HCx5LQwSFw6/xpPu5F7ZNqL52aAsGCbL3o2QoIG4c\na1qf9Ot16BNgNBqxQF3hzRTkBMrKYlmNTUkwJXun/zjQJq2JvOlcrSuXlIucUjs4\nXekVN25aYPHrX9m2FEIUwZTb4UYXbR80KbIDI53BkQ6EwSbpAkEA7aO49CR2Hf1Y\n1d2GaUI/Z0wvbj//+t0Kg0bPt16ca8KVjEQQA5ylsDaiw510jDz9NBQxSOk6If23\nUeRixc1RDQJBAOYtN4YnPM1Zfp6IxXlqMCc+xUWRTPEPFt+WpG+v79koNamAeA6o\nZzTl92hl58IqSdbgojeE2zXWQRvlimFMLQcCQQCV6jND0byyLqFcSeQBg0l8YROK\n+dUC7W80YfeoNod3c8nkMwvnO2tLPyxvO2XLEq6prBNra7bAus5rWyj0oBIBAkEA\n1EvUMFm0TLpEfLgtWuTD8Q6GKLnxO0ztjd+FXrXpBGN/ywyArxRHzJRmctW6wmz6\nmcOqGobhIHCysKYv0bnOtQJAc2M5RwlASHH4jGJzXgt3nboyiJfufM0RV9iry3ho\nCXQRWAONKoLqnsfC6qNP8OzY8FMJcwmPWj7Q/6z6yLBFTA==\n-----END RSA PRIVATE KEY-----",
			'pubkey'   => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		// Having the same name and nick allows us to test
		// the fallback to api_get_nick() in api_get_user()
		[
			'id'       => 43,
			'uid'      => 0,
			'uri-id'   => 43,
			'name'     => 'othercontact',
			'nick'     => 'othercontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/othercontact',
			'url'      => 'http://localhost/profile/othercontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::NOTHING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 44,
			'uid'      => 42,
			'uri-id'   => 44,
			'name'     => 'Friend contact',
			'nick'     => 'friendcontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/friendcontact',
			'url'      => 'http://localhost/profile/friendcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 45,
			'uid'      => 0,
			'uri-id'   => 44,
			'name'     => 'Friend contact',
			'nick'     => 'friendcontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/friendcontact',
			'url'      => 'http://localhost/profile/friendcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 46,
			'uid'      => 42,
			'uri-id'   => 46,
			'name'     => 'Mutual contact',
			'nick'     => 'mutualcontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/mutualcontact',
			'url'      => 'http://localhost/profile/mutualcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FRIEND,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 47,
			'uid'      => 0,
			'uri-id'   => 46,
			'name'     => 'Mutual contact',
			'nick'     => 'mutualcontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/mutualcontact',
			'url'      => 'http://localhost/profile/mutualcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::SHARING,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
		[
			'id'       => 48,
			'uid'      => 0,
			'uri-id'   => 42,
			'name'     => 'Self contact',
			'nick'     => 'selfcontact',
			'self'     => 0,
			'nurl'     => 'http://localhost/profile/selfcontact',
			'url'      => 'http://localhost/profile/selfcontact',
			'notify'   => 'http://localhost/friendica/inbox',
			'about'    => 'User used in tests',
			'pending'  => 0,
			'blocked'  => 0,
			'rel'      => Contact::FOLLOWER,
			'network'  => Protocol::DFRN,
			'location' => 'DFRN',
		],
	],
	'apcontact' => [
		[
			"url"              => "http://localhost/profile/selfcontact",
			"uri-id"           => 1,
			"uuid"             => "42",
			"type"             => "Person",
			"following"        => "http://localhost/following/selfcontact",
			"followers"        => "http://localhost/followers/selfcontact",
			"inbox"            => "http://localhost/inbox/selfcontact",
			"outbox"           => "http://localhost/outbox/selfcontact",
			"sharedinbox"      => "http://localhost/inbox",
			"manually-approve" => 1,
			"discoverable"     => 0,
			"nick"             => "selfcontact",
			"name"             => "Self contact",
			"about"            => "User used in tests",
			"xmpp"             => null,
			"matrix"           => null,
			"photo"            => "http://localhost/photo/profile/admin.jpeg",
			"header"           => null,
			"addr"             => "selfcontact@localhost",
			"alias"            => null,
			"pubkey"           => "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVqxF9kIgtgRL0+q+jTi578FA1\nr1+crEmlYc0pdxcbmmrhjuRcrK1gX3r0mnP25fkHzG+6CAjgbDBRFM1/RXBCyp/K\nHVks7eQ4yr4MxTRlsxo5qf2onbyNzM7Q+LZhFhe/yIoGN/fuEjlqBE98IfPOrUjs\nQPX240vGNXIkfLiAWwIDAQAB\n-----END PUBLIC KEY-----",
			"subscribe"        => "/follow?url={uri}",
			"baseurl"          => null,
			"gsid"             => null,
			"generator"        => "Friendica 'Siberian Iris' 2021.12-dev-1443",
			"following_count"  => 0,
			"followers_count"  => 0,
			"statuses_count"   => 0,
			"updated"          => "2021-11-19 19:17:59",
		],
	],
	'verb' => [
		[
			'id'   => 0,
			'name' => '',
		],
		[
			'id'   => 1,
			'name' => 'http://activitystrea.ms/schema/1.0/like',
		],
		[
			'id'   => 2,
			'name' => 'http://purl.org/macgirvin/dfrn/1.0/dislike',
		],
		[
			'id'   => 3,
			'name' => 'http://purl.org/zot/activity/attendyes',
		],
		[
			'id'   => 4,
			'name' => 'http://purl.org/zot/activity/attendno',
		],
		[
			'id'   => 5,
			'name' => 'http://purl.org/zot/activity/attendmaybe',
		],
		[
			'id'   => 6,
			'name' => 'http://activitystrea.ms/schema/1.0/follow',
		],
		[
			'id'   => 7,
			'name' => 'https://www.w3.org/ns/activitystreams#Announce',
		],
		[
			'id'   => 8,
			'name' => 'http://activitystrea.ms/schema/1.0/post',
		],
	],
	'post-content' => [
		[
			'uri-id' => 1,
			'body'   => 'Parent status',
			'plink'  => 'http://localhost/display/1',
		],
		[
			'uri-id' => 2,
			'body'   => 'Reply',
			'plink'  => 'http://localhost/display/2',
		],
		[
			'uri-id' => 3,
			'body'   => 'Other user status',
			'plink'  => 'http://localhost/display/3',
		],
		[
			'uri-id' => 4,
			'body'   => 'Friend user reply',
			'plink'  => 'http://localhost/display/4',
		],
		[
			'uri-id' => 5,
			'body'   => '[share]Shared status[/share]',
			'plink'  => 'http://localhost/display/5',
		],
		[
			'uri-id' => 6,
			'body'   => 'Friend user status',
			'plink'  => 'http://localhost/display/6',
		],
		[
			'uri-id' => 7,
			'title'  => 'item_title',
			'body'   => 'perspiciatis impedit voluptatem quis molestiae ea qui ' .
						'reiciendis dolorum aut ducimus sunt consequatur inventore dolor ' .
						'officiis pariatur doloremque nemo culpa aut quidem qui dolore ' .
						'laudantium atque commodi alias voluptatem non possimus aperiam ' .
						'ipsum rerum consequuntur aut amet fugit quia aliquid praesentium ' .
						'repellendus quibusdam et et inventore mollitia rerum sit autem ' .
						'pariatur maiores ipsum accusantium perferendis vel sit possimus ' .
						'veritatis nihil distinctio qui eum repellat officia illum quos ' .
						'impedit quam iste esse unde qui suscipit aut facilis ut inventore ' .
						'omnis exercitationem quo magnam consequatur maxime aut illum ' .
						'soluta quaerat natus unde aspernatur et sed beatae nihil ullam ' .
						'temporibus corporis ratione blanditiis perspiciatis impedit ' .
						'voluptatem quis molestiae ea qui reiciendis dolorum aut ducimus ' .
						'sunt consequatur inventore dolor officiis pariatur doloremque ' .
						'nemo culpa aut quidem qui dolore laudantium atque commodi alias ' .
						'voluptatem non possimus aperiam ipsum rerum consequuntur aut ' .
						'amet fugit quia aliquid praesentium repellendus quibusdam et et ' .
						'inventore mollitia rerum sit autem pariatur maiores ipsum accusantium ' .
						'perferendis vel sit possimus veritatis nihil distinctio qui eum ' .
						'repellat officia illum quos impedit quam iste esse unde qui ' .
						'suscipit aut facilis ut inventore omnis exercitationem quo magnam ' .
						'consequatur maxime aut illum soluta quaerat natus unde aspernatur ' .
						'et sed beatae nihil ullam temporibus corporis ratione blanditiis',
			'plink'  => 'http://localhost/display/6',
		],
	],
	'post' => [
		[
			'uri-id'        => 1,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 2,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 3,
			'parent-uri-id' => 3,
			'thr-parent-id' => 3,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 43,
			'causer-id'     => 43,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 4,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 5,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 6,
			'parent-uri-id' => 6,
			'thr-parent-id' => 6,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
		[
			'uri-id'        => 7,
			'parent-uri-id' => 7,
			'thr-parent-id' => 7,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
		],
	],
	'post-user' => [
		[
			'id'            => 1,
			'uri-id'        => 1,
			'visible'       => 1,
			'contact-id'    => 42,
			'author-id'     => 42,
			'owner-id'      => 42,
			'causer-id'     => 42,
			'uid'           => 42,
			'vid'           => 8,
			'unseen'        => 1,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'wall'          => 1,
			'origin'        => 1,
		],
		[
			'id'            => 2,
			'uri-id'        => 2,
			'uid'           => 42,
			'contact-id'    => 42,
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 3,
			'uri-id'        => 3,
			'uid'           => 42,
			'contact-id'    => 43,
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 3,
			'thr-parent-id' => 3,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 43,
			'causer-id'     => 43,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 4,
			'uri-id'        => 4,
			'uid'           => 42,
			'contact-id'    => 44,
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 5,
			'uri-id'        => 5,
			'uid'           => 42,
			'contact-id'    => 42,
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 6,
			'uri-id'        => 6,
			'uid'           => 42,
			'contact-id'    => 44,
			'unseen'        => 0,
			'origin'        => 1,
			'parent-uri-id' => 6,
			'thr-parent-id' => 6,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 1,
		],
		[
			'id'            => 7,
			'uri-id'        => 1,
			'uid'           => 0,
			'contact-id'    => 42,
			'unseen'        => 1,
			'origin'        => 0,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 8,
			'uri-id'        => 2,
			'uid'           => 0,
			'contact-id'    => 42,
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 9,
			'uri-id'        => 3,
			'uid'           => 0,
			'contact-id'    => 43,
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 3,
			'thr-parent-id' => 3,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 43,
			'causer-id'     => 43,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 10,
			'uri-id'        => 4,
			'uid'           => 0,
			'contact-id'    => 44,
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 44,
			'causer-id'     => 44,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 11,
			'uri-id'        => 5,
			'uid'           => 0,
			'contact-id'    => 42,
			'unseen'        => 0,
			'origin'        => 0,
			'parent-uri-id' => 1,
			'thr-parent-id' => 1,
			'gravity'       => Item::GRAVITY_COMMENT,
			'network'       => Protocol::DFRN,
			'owner-id'      => 42,
			'author-id'     => 42,
			'causer-id'     => 42,
			'vid'           => 8,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'visible'       => 1,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 12,
			'uri-id'        => 6,
			'visible'       => 1,
			'contact-id'    => 44,
			'author-id'     => 44,
			'owner-id'      => 42,
			'causer-id'     => 44,
			'uid'           => 0,
			'vid'           => 8,
			'unseen'        => 0,
			'parent-uri-id' => 6,
			'thr-parent-id' => 6,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'origin'        => 0,
			'deleted'       => 0,
			'wall'          => 0,
		],
		[
			'id'            => 13,
			'uri-id'        => 7,
			'visible'       => 1,
			'contact-id'    => 44,
			'author-id'     => 44,
			'owner-id'      => 42,
			'causer-id'     => 44,
			'uid'           => 0,
			'vid'           => 8,
			'unseen'        => 0,
			'parent-uri-id' => 7,
			'thr-parent-id' => 7,
			'private'       => Item::PUBLIC,
			'global'        => true,
			'gravity'       => Item::GRAVITY_PARENT,
			'network'       => Protocol::DFRN,
			'origin'        => 0,
			'deleted'       => 0,
			'wall'          => 0,
		],
	],
	'post-thread' => [
		[
			'uri-id'    => 1,
			'author-id' => 42,
			'owner-id'  => 42,
			'causer-id' => 42,
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 3,
			'author-id' => 43,
			'owner-id'  => 43,
			'causer-id' => 43,
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 6,
			'author-id' => 44,
			'owner-id'  => 44,
			'causer-id' => 44,
			'network'   => Protocol::DFRN,
		],
		[
			'uri-id'    => 7,
			'author-id' => 44,
			'owner-id'  => 44,
			'causer-id' => 44,
			'network'   => Protocol::DFRN,
		],
	],
	'post-thread-user' => [
		[
			'uri-id'       => 1,
			'uid'          => 42,
			'wall'         => 1,
			'post-user-id' => 1,
			'author-id'    => 42,
			'owner-id'     => 42,
			'causer-id'    => 42,
			'contact-id'   => 42,
			'network'      => Protocol::DFRN,
			'starred'      => 1,
			'origin'       => 1,
		],
		[
			'uri-id'       => 3,
			'uid'          => 42,
			'wall'         => 1,
			'post-user-id' => 3,
			'author-id'    => 43,
			'owner-id'     => 43,
			'causer-id'    => 43,
			'contact-id'   => 43,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 6,
			'uid'          => 42,
			'wall'         => 1,
			'post-user-id' => 6,
			'author-id'    => 44,
			'owner-id'     => 44,
			'causer-id'    => 44,
			'contact-id'   => 44,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 1,
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 7,
			'author-id'    => 42,
			'owner-id'     => 42,
			'causer-id'    => 42,
			'contact-id'   => 42,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 3,
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 9,
			'author-id'    => 43,
			'owner-id'     => 43,
			'causer-id'    => 43,
			'contact-id'   => 43,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 6,
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 12,
			'author-id'    => 44,
			'owner-id'     => 44,
			'causer-id'    => 44,
			'contact-id'   => 44,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
		[
			'uri-id'       => 7,
			'uid'          => 42,
			'wall'         => 1,
			'post-user-id' => 7,
			'author-id'    => 44,
			'owner-id'     => 44,
			'causer-id'    => 44,
			'contact-id'   => 44,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 1,
		],
		[
			'uri-id'       => 7,
			'uid'          => 0,
			'wall'         => 0,
			'post-user-id' => 12,
			'author-id'    => 44,
			'owner-id'     => 44,
			'causer-id'    => 44,
			'contact-id'   => 44,
			'network'      => Protocol::DFRN,
			'starred'      => 0,
			'origin'       => 0,
		],
	],
	'notify' => [
		[
			'id'         => 1,
			'type'       => 8,
			'name'       => 'Friend contact',
			'url'        => 'http://localhost/profile/friendcontact',
			'photo'      => 'http://localhost/',
			'date'       => '2020-01-01 12:12:02',
			'msg'        => 'A test reply from an item',
			'uid'        => 42,
			'link'       => 'http://localhost/display/1',
			'iid'        => 4,
			'seen'       => 0,
			'verb'       => \Friendica\Protocol\Activity::POST,
			'otype'      => Notification\ObjectType::ITEM,
			'name_cache' => 'Friend contact',
			'msg_cache'  => 'A test reply from an item',
		],
	],
	'profile' => [
		[
			'id'  => 1,
			'uid' => 42,
		],
	],
	'group' => [
		[
			'id'      => 1,
			'uid'     => 42,
			'visible' => 1,
			'name'    => 'Visible list',
		],
		[
			'id'      => 2,
			'uid'     => 42,
			'visible' => 0,
			'name'    => 'Private list',
		],
	],
	'group_member' => [
		[
			'id' => 1,
			'gid' => 1,
			'contact-id' => 42,
		],
		[
			'id' => 2,
			'gid' => 1,
			'contact-id' => 42,
		],
		[
			'id' => 3,
			'gid' => 2,
			'contact-id' => 43,
		],
	],
	'search' => [
		[
			'id'   => 1,
			'term' => 'Saved search',
			'uid'  => 42,
		],
	],
];
