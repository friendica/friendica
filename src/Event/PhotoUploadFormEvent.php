<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the photo upload form is being built.
 *
 * Can be used by addons to modify the upload form data, e.g. by adding an uploader widget.
 */
final class PhotoUploadFormEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.photo_upload_form';

	/**
	 * @internal
	 *
	 * @param array{post_url: string, addon_text: string, default_upload: bool} $form
	 */
	public function __construct(
		private array $form,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array{post_url: string, addon_text: string, default_upload: bool} */
	public function getFormArray(): array
	{
		return $this->form;
	}

	/** @param array{post_url: string, addon_text: string, default_upload: bool} $form */
	public function setFormArray(array $form): void
	{
		$this->form = $form;
	}
}
