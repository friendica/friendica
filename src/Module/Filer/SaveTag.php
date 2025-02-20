<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
			'$field'  => ['term', $this->t("Folder:"), '', '', $filetags, $this->t('- select -')],
			'$submit' => $this->t('Save'),
		]);

		exit;
	}
}
