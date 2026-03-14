<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$sysmsg = $this->getInfos();

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
