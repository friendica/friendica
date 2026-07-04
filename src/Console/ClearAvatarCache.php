<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Friendica\Contact\Avatar;
use Friendica\Core\L10n;
use Friendica\Model\Contact;
use Friendica\Core\Config\Capability\IManageConfigValues;

/**
 * tool to clear the avatar file cache.
 */
class ClearAvatarCache extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console clearavatarcache - Clear the file based avatar cache
Synopsis
	bin/console clearavatarcache

Description
	bin/console clearavatarcache
		Clear the file based avatar cache

Options
	-h|--help|-? Show help information
HELP;
		return $help;
	}

	public function __construct(
		private readonly \Friendica\Database\Database $dba,
		private readonly L10n $l10n,
		private readonly IManageConfigValues $config,
		?array $argv = null,
	) {
		parent::__construct($argv);
	}

	protected function doExecute(): int
	{
		if ($this->config->get('system', 'avatar_cache')) {
			$this->err($this->l10n->t('The avatar cache needs to be disabled in local.config.php to use this command.'));
			return 2;
		}

		// Contacts (but not self contacts) with cached avatars.
		$condition = ["NOT `self` AND (`photo` != ? OR `thumb` != ? OR `micro` != ?)", '', '', ''];
		$total     = $this->dba->count('contact', $condition);
		$count     = 0;
		$contacts  = $this->dba->select('contact', ['id', 'uri-id', 'url', 'uid', 'photo', 'thumb', 'micro'], $condition);
		while ($contact = $this->dba->fetch($contacts)) {
			if (Avatar::deleteCache($contact) || $this->isAvatarCache($contact)) {
				Contact::update(['photo' => '', 'thumb' => '', 'micro' => ''], ['id' => $contact['id']]);
			}
			$this->out(++$count . '/' . $total . "\t" . $contact['id'] . "\t" . $contact['url'] . "\t" . $contact['photo']);
		}
		$this->dba->close($contacts);
		return 0;
	}

	private function isAvatarCache(array $contact): bool
	{
		if (!empty($contact['photo']) && str_starts_with((string) $contact['photo'], Avatar::baseUrl())) {
			return true;
		}
		if (!empty($contact['thumb']) && str_starts_with((string) $contact['thumb'], Avatar::baseUrl())) {
			return true;
		}
		if (!empty($contact['micro']) && str_starts_with((string) $contact['micro'], Avatar::baseUrl())) {
			return true;
		}
		return false;
	}
}
