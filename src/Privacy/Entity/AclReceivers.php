<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Privacy\Entity;

use Friendica\BaseEntity;

class AclReceivers extends BaseEntity
{
	protected array $allowContacts = [];
	protected array $allowCircles  = [];
	protected array $denyContacts  = [];
	protected array $denyCircles   = [];

	public function __construct(array $allowContacts = [], array $allowCircles = [], array $denyContacts = [], array $denyCircles = [])
	{
		$this->allowContacts = $allowContacts;
		$this->allowCircles  = $allowCircles;
		$this->denyContacts  = $denyContacts;
		$this->denyCircles   = $denyCircles;
	}

	public function isEmpty(): bool
	{
		return empty($this->allowContacts) && empty($this->allowCircles) && empty($this->denyContacts) && empty($this->denyCircles);
	}
}
