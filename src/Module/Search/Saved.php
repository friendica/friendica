<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Search;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Saved extends BaseModule
{
	/** @var Database */
	protected $dba;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba = $dba;
	}

	protected function rawContent(array $request = [])
	{
		$action = $this->args->get(2, 'none');
		$search = trim(rawurldecode($_GET['term'] ?? ''));

		if (!empty($_GET['return_url']) && Strings::isHex($_GET['return_url'])) {
			$return_url = hex2bin($_GET['return_url']);
		} else {
			$return_url = Search::getSearchPath($search);
		}

		if (DI::userSession()->getLocalUserId() && $search) {
			switch ($action) {
				case 'add':
					$fields = ['uid' => DI::userSession()->getLocalUserId(), 'term' => $search];
					if (!$this->dba->exists('search', $fields)) {
						if (!$this->dba->insert('search', $fields)) {
							DI::sysmsg()->addNotice($this->t('Search term was not saved.'));
						}
					} else {
						DI::sysmsg()->addNotice($this->t('Search term already saved.'));
					}
					break;

				case 'remove':
					if (!$this->dba->delete('search', ['uid' => DI::userSession()->getLocalUserId(), 'term' => $search])) {
						DI::sysmsg()->addNotice($this->t('Search term was not removed.'));
					}
					break;
			}
		}

		$this->baseUrl->redirect($return_url);
	}
}
