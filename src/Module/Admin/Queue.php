<?php

namespace Friendica\Module\Admin;

use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;

/**
 * @brief Admin Inspect Queue Page
 *
 * Generates a page for the admin to have a look into the current queue of
 * worker jobs. Shown are the parameters for the job and its priority.
 *
 * @return string
 */
class Queue extends BaseAdminModule
{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		// @TODO: Replace with parameter from router
		$deferred = $a->argc > 2 && $a->argv[2] == 'deferred';

		// get jobs from the workerqueue table
		if ($deferred) {
			$condition = ["NOT `done` AND `retrial` > ?", 0];
			$sub_title = L10n::t('Inspect Deferred Worker Queue');
			$info = L10n::t("This page lists the deferred worker jobs. This are jobs that couldn't be executed at the first time.");
		} else {
			$condition = ["NOT `done` AND `retrial` = ?", 0];
			$sub_title = L10n::t('Inspect Worker Queue');
			$info = L10n::t('This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.');
		}

		// @TODO Move to Model\WorkerQueue::getEntries()
		$entries = DBA::select('workerqueue', ['id', 'parameter', 'created', 'priority'], $condition, ['limit' => 999, 'order' => ['created']]);

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
			'$title' => L10n::t('Administration'),
			'$page' => $sub_title,
			'$count' => count($r),
			'$id_header' => L10n::t('ID'),
			'$param_header' => L10n::t('Job Parameters'),
			'$created_header' => L10n::t('Created'),
			'$prio_header' => L10n::t('Priority'),
			'$info' => $info,
			'$entries' => $r,
		]);
	}
}
