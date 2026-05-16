<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;

/**
 * Admin Inspect Queue Page
 *
 * Generates a page for the admin to have a look into the current queue of
 * worker jobs. Shown are the parameters for the job and its priority.
 *
 * @return string
 */
class Queue extends BaseAdmin
{
	protected function content(array $request = []): string
	{
		parent::content();

		$status = $this->parameters['status'] ?? '';

		// get jobs from the workerqueue table
		if ($status == 'deferred') {
			$condition = ["NOT `done` AND `retrial` > ?", 0];
			$sub_title = DI::l10n()->t('Inspect Deferred Worker Queue');
			$info      = DI::l10n()->t("This page lists the deferred worker jobs. This are jobs that couldn't be executed at the first time.");
		} else {
			$condition = ["NOT `done` AND `retrial` = ?", 0];
			$sub_title = DI::l10n()->t('Inspect Worker Queue');
			$info      = DI::l10n()->t('This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.');
		}

		// @TODO Move to Model\WorkerQueue::getEntries()
		$entries = DBA::select('workerqueue', ['id', 'parameter', 'created', 'next_try', 'priority', 'command'], $condition, ['limit' => 999, 'order' => ['created']]);

		$r = [];
		while ($entry = DBA::fetch($entries)) {
			// fix GH-5469. ref: src/Core/Worker.php:217
			$param = Arrays::recursiveImplode(json_decode($entry['parameter'], true), ': ');
			// Truncate long parameters to prevent text overflow
			if (strlen($param) > 300) {
				$param = substr($param, 0, 300) . '...';
			}
			$entry['parameter'] = $param;
			$entry['created']   = DateTimeFormat::local($entry['created']);
			$entry['next_try']  = DateTimeFormat::local($entry['next_try']);
			$r[]                = $entry;
		}
		DBA::close($entries);

		$t = Renderer::getMarkupTemplate('admin/queue.tpl');
		return Renderer::replaceMacros($t, [
			'$title'           => DI::l10n()->t('Administration'),
			'$page'            => $sub_title,
			'$count'           => count($r),
			'$id_header'       => DI::l10n()->t('ID'),
			'$command_header'  => DI::l10n()->t('Command'),
			'$param_header'    => DI::l10n()->t('Job Parameters'),
			'$created_header'  => DI::l10n()->t('Created'),
			'$next_try_header' => DI::l10n()->t('Next Try'),
			'$prio_header'     => DI::l10n()->t('Priority'),
			'$info'            => $info,
			'$status'          => $status,
			'$entries'         => $r,
		]);
	}
}
