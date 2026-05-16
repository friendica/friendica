<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Blocklist\Contact;

use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Network\HTTPException;

class Import extends \Friendica\Module\BaseModeration
{
	/** @var array of contacts to import */
	private $contactlist = [];

	/** @var array of notices/errors to display */
	private $notices = [];

	/**
	 * @param array $request
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 */
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		if (!isset($request['page_contactblock_upload']) && !isset($request['page_contactblock_import'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/contact/import', 'moderation_contactblock_import');

		if (isset($request['page_contactblock_upload'])) {
			try {
				$this->contactlist = $this->extractFromCSVFile($_FILES['listfile']['tmp_name']);
				if (empty($this->contactlist)) {
					$this->notices[] = $this->t('No valid contacts found in CSV file.');
				}
				return;
			} catch (\Throwable $e) {
				$this->notices[] = $this->t('Error importing contact file: %s', $e->getMessage());
				return;
			}
		} elseif (isset($request['page_contactblock_import'])) {
			$this->contactlist = json_decode($request['contactlist'], true);
			if ($this->contactlist === null) {
				$this->notices[] = $this->t('Error parsing contact data.');
				return;
			}
		}

		$queued  = 0;
		$skipped = 0;
		$purge   = !empty($request['purge']);

		foreach ($this->contactlist as $item) {
			$contact_url  = $item['url']    ?? '';
			$block_reason = $item['reason'] ?? '';

			if (empty($contact_url)) {
				$skipped++;
				continue;
			}

			Worker::add(Worker::PRIORITY_LOW, 'Contact\BlockByUrl', $contact_url, $block_reason, $purge);
			$queued++;
		}

		if ($queued > 0) {
			$this->notices[] = $this->tt('%d contact blocking was queued.', '%d contact blockings were queued.', $queued);
		}
		if ($skipped > 0) {
			$this->notices[] = $this->tt('%d contact was skipped (empty URL).', '%d contacts were skipped (empty URL).', $skipped);
		}

		$this->contactlist = [];
	}

	/**
	 * @param array $request
	 * @return string
	 * @throws HTTPException\ServiceUnavailableException
	 */
	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('moderation/blocklist/contact/import.tpl');
		return Renderer::replaceMacros($t, [
			'$notices' => $this->notices,
			'$l10n'    => [
				'return_list'   => $this->t('← Return to the list'),
				'title'         => $this->t('Moderation'),
				'page'          => $this->t('Import a Contact Blocklist'),
				'download'      => $this->t('<p>Upload a CSV file with contact URLs and reasons for blocking.</p>'),
				'upload'        => $this->t('Upload file'),
				'contacts'      => $this->t('Contacts to import'),
				'contact_url'   => $this->t('Contact URL'),
				'block_reason'  => $this->t('Block Reason'),
				'import'        => $this->t('Import Contacts'),
				'contact_count' => $this->tt('%d total contact', '%d total contacts', count($this->contactlist)),
				'purge'         => $this->t('Also purge contacts'),
				'purge_desc'    => $this->t('Removes all content related to these contacts from the node. Keeps the contact records. This action cannot be undone.'),
			],
			'$listfile'            => ['listfile', $this->t('Contact blocklist CSV file'), '', '', $this->t('Required'), '', 'file'],
			'$purge'               => ['purge', $this->t('Also purge contacts'), false, $this->t('Removes all content related to these contacts from the node. Keeps the contact records. This action cannot be undone.')],
			'$contactlist'         => $this->contactlist,
			'$form_security_token' => self::getFormSecurityToken('moderation_contactblock_import')
		]);
	}

	/**
	 * Extracts a contact block list from the provided CSV file name.
	 *
	 * @param string $filename
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function extractFromCSVFile(string $filename): array
	{
		$fp = fopen($filename, 'r');
		if ($fp === false) {
			throw new \Exception($this->l10n->t('The file "%s" could not be opened for importing', $filename));
		}

		$contactlist = [];
		while (($data = fgetcsv($fp, 1000)) !== false) {
			$item = [
				'url'    => $data[0] ?? '',
				'reason' => $data[1] ?? '',
			];

			if (!empty($item['url']) && !in_array($item, $contactlist)) {
				$contactlist[] = $item;
			}
		}

		fclose($fp);
		return $contactlist;
	}
}
