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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\JsonLD;

class ActivityPubConversion extends BaseModule
{
	protected function content(array $request = []): string
	{
		function visible_whitespace($s)
		{
			return '<pre>' . htmlspecialchars($s) . '</pre>';
		}

		$results = [];
		if (!empty($_REQUEST['source'])) {
			try {
				$source = json_decode($_REQUEST['source'], true);
				$trust_source = true;
				$uid = DI::userSession()->getLocalUserId();
				$push = false;

				if (!$source) {
					throw new \Exception('Failed to decode source JSON');
				}

				$formatted = json_encode($source, JSON_PRETTY_PRINT);
				$results[] = [
					'title'   => DI::l10n()->t('Formatted'),
					'content' => visible_whitespace(trim(var_export($formatted, true), "'")),
				];
				$results[] = [
					'title'   => DI::l10n()->t('Source'),
					'content' => visible_whitespace(var_export($source, true))
				];
				$activity = JsonLD::compact($source);
				if (!$activity) {
					throw new \Exception('Failed to compact JSON');
				}
				$results[] = [
					'title'   => DI::l10n()->t('Activity'),
					'content' => visible_whitespace(var_export($activity, true))
				];

				$type = JsonLD::fetchElement($activity, '@type');

				if (!$type) {
					throw new \Exception('Empty type');
				}

				if (!JsonLD::fetchElement($activity, 'as:object', '@id')) {
					throw new \Exception('Empty object');
				}

				if (!JsonLD::fetchElement($activity, 'as:actor', '@id')) {
					throw new \Exception('Empty actor');
				}

				// Don't trust the source if "actor" differs from "attributedTo". The content could be forged.
				if ($trust_source && ($type == 'as:Create') && is_array($activity['as:object'])) {
					$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
					$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
					$trust_source = ($actor == $attributed_to);
					if (!$trust_source) {
						throw new \Exception('Not trusting actor: ' . $actor . '. It differs from attributedTo: ' . $attributed_to);
					}
				}

				// $trust_source is called by reference and is set to true if the content was retrieved successfully
				$object_data = ActivityPub\Receiver::prepareObjectData($activity, $uid, $push, $trust_source);
				if (empty($object_data)) {
					throw new \Exception('No object data found');
				}

				if (!$trust_source) {
					throw new \Exception('No trust for activity type "' . $type . '", so we quit now.');
				}

				if (!empty($body) && empty($object_data['raw'])) {
					$object_data['raw'] = $body;
				}

				// Internal flag for thread completion. See Processor.php
				if (!empty($activity['thread-completion'])) {
					$object_data['thread-completion'] = $activity['thread-completion'];
				}

				if (!empty($activity['completion-mode'])) {
					$object_data['completion-mode'] = $activity['completion-mode'];
				}

				$results[] = [
					'title'   => DI::l10n()->t('Object data'),
					'content' => visible_whitespace(var_export($object_data, true))
				];

				$item = ActivityPub\Processor::createItem($object_data, true);

				$results[] = [
					'title'   => DI::l10n()->t('Result Item'),
					'content' => visible_whitespace(var_export($item, true))
				];
			} catch (\Throwable $e) {
				$results[] = [
					'title'   => DI::l10n()->tt('Error', 'Errors', 1),
					'content' => $e->getMessage(),
				];
			}
		}

		$tpl = Renderer::getMarkupTemplate('debug/activitypubconversion.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title'   => DI::l10n()->t('ActivityPub Conversion'),
			'$source'  => ['source', DI::l10n()->t('Source activity'), $_REQUEST['source'] ?? '', ''],
			'$results' => $results,
			'$submit' => DI::l10n()->t('Submit'),
		]);

		return $o;
	}
}
