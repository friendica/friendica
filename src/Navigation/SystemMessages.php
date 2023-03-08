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
 * Friendica is a communications platform for integrated social communications
 * utilising decentralised communications and linkage to several indie social
 * projects - as well as popular mainstream providers.
 *
 * Our mission is to free our friends and families from the clutches of
 * data-harvesting corporations, and pave the way to a future where social
 * communications are free and open and flow between alternate providers as
 * easily as email does today.
 */

namespace Friendica\Navigation;

use Friendica\Core\Session\Capability\IHandleSessions;

class SystemMessages
{
	/**
	 * @var IHandleSessions
	 */
	private $session;

	public function __construct(IHandleSessions $session)
	{
		$this->session = $session;
	}

	public function addNotice(string $message)
	{
		$sysmsg = $this->getNotices();

		$sysmsg[] = $message;

		$this->session->set('sysmsg', $sysmsg);
	}

	public function getNotices(): array
	{
		return $this->session->get('sysmsg', []);
	}

	public function flushNotices(): array
	{
		$notices = $this->getNotices();
		$this->session->remove('sysmsg');
		return $notices;
	}

	public function addInfo(string $message)
	{
		$sysmsg = $this->getNotices();

		$sysmsg[] = $message;

		$this->session->set('sysmsg_info', $sysmsg);
	}

	public function getInfos(): array
	{
		return $this->session->get('sysmsg_info', []);
	}

	public function flushInfos(): array
	{
		$notices = $this->getInfos();
		$this->session->remove('sysmsg_info');
		return $notices;
	}
}
