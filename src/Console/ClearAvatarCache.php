<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Console;

use Friendica\App\BaseURL;
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

	/**
	 * @var $dba Friendica\Database\Database
	 */
	private $dba;

	/**
	 * @var $baseurl Friendica\App\BaseURL
	 */
	private $baseUrl;

	/**
	 * @var L10n
	 */
	private $l10n;

	/**
	 * @var IManageConfigValues
	 */
	private $config;

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

	public function __construct(\Friendica\Database\Database $dba, BaseURL $baseUrl, L10n $l10n, IManageConfigValues $config, array $argv = null)
	{
		parent::__construct($argv);

		$this->dba     = $dba;
		$this->baseUrl = $baseUrl;
		$this->l10n    = $l10n;
		$this->config  = $config;
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
		if (!empty($contact['photo']) && strpos($contact['photo'], Avatar::baseUrl()) === 0) {
			return true;
		}
		if (!empty($contact['thumb']) && strpos($contact['thumb'], Avatar::baseUrl()) === 0) {
			return true;
		}
		if (!empty($contact['micro']) && strpos($contact['micro'], Avatar::baseUrl()) === 0) {
			return true;
		}
		return false;
	}
}
