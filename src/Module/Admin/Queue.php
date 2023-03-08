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
			$info = DI::l10n()->t("This page lists the deferred worker jobs. This are jobs that couldn't be executed at the first time.");
		} else {
			$condition = ["NOT `done` AND `retrial` = ?", 0];
			$sub_title = DI::l10n()->t('Inspect Worker Queue');
			$info = DI::l10n()->t('This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.');
		}

		// @TODO Move to Model\WorkerQueue::getEntries()
		$entries = DBA::select('workerqueue', ['id', 'parameter', 'created', 'priority', 'command'], $condition, ['limit' => 999, 'order' => ['created']]);

		$r = [];
		while ($entry = DBA::fetch($entries)) {
			// fix GH-5469. ref: src/Core/Worker.php:217
			$entry['parameter'] = Arrays::recursiveImplode(json_decode($entry['parameter'], true), ': ');
			$entry['created'] = DateTimeFormat::local($entry['created']);
			$r[] = $entry;
		}
		DBA::close($entries);

		$t = Renderer::getMarkupTemplate('admin/queue.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => $sub_title,
			'$count' => count($r),
			'$id_header' => DI::l10n()->t('ID'),
			'$command_header' => DI::l10n()->t('Command'),
			'$param_header' => DI::l10n()->t('Job Parameters'),
			'$created_header' => DI::l10n()->t('Created'),
			'$prio_header' => DI::l10n()->t('Priority'),
			'$info' => $info,
			'$entries' => $r,
		]);
	}
}
