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

namespace Friendica\Module\Moderation\Item;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Source extends BaseModeration
{
	/** @var IManageConfigValues */
	private $config;

	public function __construct(IManageConfigValues $config, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$guid = basename($request['guid'] ?? $this->parameters['guid'] ?? '');

		$item_uri = '';
		$item_id = '';
		$terms = [];
		$source = '';
		if (!empty($guid)) {
			$item = Model\Post::selectFirst(['id', 'uri-id', 'guid', 'uri'], ['guid' => $guid]);

			if ($item) {
				$item_id = $item['id'];
				$item_uri = $item['uri'];
				$terms = Model\Tag::getByURIId($item['uri-id'], [Model\Tag::HASHTAG, Model\Tag::MENTION, Model\Tag::IMPLICIT_MENTION]);

				$activity = Model\Post\Activity::getByURIId($item['uri-id']);
				if (!empty($activity)) {
					$source = $activity['activity'];
				}
			}
		}

		$tpl = Renderer::getMarkupTemplate('moderation/item/source.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Item Source'),
				'itemidlbl'   => $this->t('Item Id'),
				'itemurilbl'  => $this->t('Item URI'),
				'submit'      => $this->t('Submit'),
				'termslbl'    => $this->t('Terms'),
				'taglbl'      => $this->t('Tag'),
				'typelbl'     => $this->t('Type'),
				'termlbl'     => $this->t('Term'),
				'urllbl'      => $this->t('URL'),
				'mentionlbl'  => $this->t('Mention'),
				'implicitlbl' => $this->t('Implicit Mention'),
				'error'       => $this->t('Error'),
				'notfound'    => $this->t('Item not found'),
				'nosource'    => $this->t('No source recorded'),
				'noconfig'    => !$this->config->get('debug', 'store_source') ? $this->t('Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.') : '',
			],
			'$guid_field' => ['guid', $this->t('Item Guid'), $guid, ''],
			'$guid'       => $guid,
			'$item_uri'   => $item_uri,
			'$item_id'    => $item_id,
			'$terms'      => $terms,
			'$source'     => $source,
		]);
	}
}
