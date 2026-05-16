<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\ValueObject;

use Friendica\BaseDataTransferObject;

/**
 * A view-only object for printing item notifications to the frontend
 *
 * @deprecated 2022.05 Use \Friendica\Navigation\Notifications\ValueObject\FormattedNotification instead
 */
class FormattedNotify extends BaseDataTransferObject
{
	public const SYSTEM   = 'system';
	public const PERSONAL = 'personal';
	public const NETWORK  = 'network';
	public const INTRO    = 'intro';
	public const HOME     = 'home';

	/** @var string */
	protected $label = '';
	/** @var string */
	protected $link = '';
	/** @var string */
	protected $image = '';
	/** @var string */
	protected $url = '';
	/** @var string */
	protected $text = '';
	/** @var string */
	protected $when = '';
	/** @var string */
	protected $ago = '';
	/** @var boolean */
	protected $seen = false;

	public function __construct(string $label, string $link, string $image, string $url, string $text, string $when, string $ago, bool $seen)
	{
		$this->label = $label;
		$this->link  = $link;
		$this->image = $image;
		$this->url   = $url;
		$this->text  = $text;
		$this->when  = $when;
		$this->ago   = $ago;
		$this->seen  = $seen;
	}
}
