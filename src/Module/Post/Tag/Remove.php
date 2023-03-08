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

namespace Friendica\Module\Post\Tag;

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Remove extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;

	public function __construct(IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect($request['return'] ?? '');
		}


		if (isset($request['cancel'])) {
			$this->baseUrl->redirect($request['return'] ?? '');
		}

		$tags = [];
		foreach ($request['tag'] ?? [] as $tag => $checked) {
			if ($checked) {
				$tags[] = hex2bin(trim($tag));
			}
		}

		$this->removeTagsFromItem($this->parameters['item_id'], $tags);
		$this->baseUrl->redirect($request['return'] ?? '');
	}

	protected function content(array $request = []): string
	{
		$returnUrl = $request['return'] ?? '';

		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect($returnUrl);
		}

		if (isset($this->parameters['tag_name'])) {
			$this->removeTagsFromItem($this->parameters['item_id'], [trim(hex2bin($this->parameters['tag_name']))]);
			$this->baseUrl->redirect($returnUrl);
		}

		$item_id = intval($this->parameters['item_id']);
		if (!$item_id) {
			$this->baseUrl->redirect($returnUrl);
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $this->session->getLocalUserId()]);
		if (!$item) {
			$this->baseUrl->redirect($returnUrl);
		}

		$tag_text = Tag::getCSVByURIId($item['uri-id']);

		$tags = explode(',', $tag_text);
		if (empty($tags)) {
			$this->baseUrl->redirect($returnUrl);
		}

		$tag_checkboxes = array_map(function ($tag_text) {
			return ['tag[' . bin2hex($tag_text) . ']', BBCode::toPlaintext($tag_text)];
		}, $tags);

		$tpl = Renderer::getMarkupTemplate('post/tag/remove.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'header' => $this->t('Remove Item Tag'),
				'desc'   => $this->t('Select a tag to remove: '),
				'remove' => $this->t('Remove'),
				'cancel' => $this->t('Cancel'),
			],

			'$item_id'        => $item_id,
			'$return'         => $returnUrl,
			'$tag_checkboxes' => $tag_checkboxes,
		]);
	}

	/**
	 * @param int   $item_id
	 * @param array $tags
	 * @throws \Exception
	 */
	private function removeTagsFromItem(int $item_id, array $tags)
	{
		if (empty($item_id) || empty($tags)) {
			return;
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $this->session->getLocalUserId()]);
		if (empty($item)) {
			return;
		}

		foreach ($tags as $tag) {
			if (preg_match('~([#@!])\[url=([^\[\]]*)]([^\[\]]*)\[/url]~im', $tag, $results)) {
				Tag::removeByHash($item['uri-id'], $results[1], $results[3], $results[2]);
			}
		}
	}
}
