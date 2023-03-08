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

namespace Friendica\Module\Filer;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;

/**
 * Shows a dialog for adding tags to a file
 */
class SaveTag extends BaseModule
{
	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice($this->t('You must be logged in to use this module'));
			$baseUrl->redirect();
		}
	}

	protected function rawContent(array $request = [])
	{
		$term = XML::unescape(trim($_GET['term'] ?? ''));

		$item_id = $this->parameters['id'] ?? 0;

		$this->logger->info('filer', ['tag' => $term, 'item' => $item_id]);

		if ($item_id && strlen($term)) {
			$item = Model\Post::selectFirst(['uri-id'], ['id' => $item_id]);
			if (!DBA::isResult($item)) {
				throw new HTTPException\NotFoundException();
			}
			Model\Post\Category::storeFileByURIId($item['uri-id'], DI::userSession()->getLocalUserId(), Model\Post\Category::FILE, $term);
		}

		// return filer dialog
		$filetags = Model\Post\Category::getArray(DI::userSession()->getLocalUserId(), Model\Post\Category::FILE);

		$tpl = Renderer::getMarkupTemplate("filer_dialog.tpl");
		echo Renderer::replaceMacros($tpl, [
			'$field' => ['term', $this->t("Save to Folder:"), '', '', $filetags, $this->t('- select -')],
			'$submit' => $this->t('Save'),
		]);

		exit;
	}
}
