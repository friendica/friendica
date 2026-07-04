<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Friendica;

use Friendica\BaseDataTransferObject;

class Circle extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $user;
	/** @var string */
	protected $mode;

	/**
	 * @param array $circle Circle row array
	 * @param array $user
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $circle, array $user)
	{
		$this->name   = $circle['name'];
		$this->id     = $circle['id'];
		$this->id_str = (string) $circle['id'];
		$this->user   = $user;
		$this->mode   = !empty($circle['public']) ? 'public' : 'private';
	}
}
