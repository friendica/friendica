<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Blocklist\Contact;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Model;
use Friendica\Module\BaseModeration;

/**
 * Export blocked contacts to CSV file
 */
class Export extends BaseModeration
{
	/**
	 * @param array $request
	 * @return void
	 */
	protected function rawContent(array $request = []): void
	{
		$this->checkModerationAccess();

		$condition = ['uid' => 0, 'blocked' => true, 'network' => Protocol::FEDERATED];
		$contacts  = Model\Contact::selectToArray(['url', 'addr', 'alias', 'block_reason'], $condition);

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Transfer-Encoding: Binary');
		header('Content-disposition: attachment; filename="blocked_contacts_' . date('Y-m-d') . '.csv"');

		$output = fopen('php://output', 'w');

		foreach ($contacts as $contact) {
			// Prefer addr (user@domain), then alias, then url as fallback
			$identifier = $contact['url'];

			if (!empty($contact['addr']) && strpos($contact['addr'], '@') !== false) {
				$identifier = $contact['addr'];
			} elseif (!empty($contact['alias'])) {
				$identifier = $contact['alias'];
			}

			fputcsv($output, [$identifier, $contact['block_reason'] ?? '']);
		}

		fclose($output);
		System::exit();
	}
}
