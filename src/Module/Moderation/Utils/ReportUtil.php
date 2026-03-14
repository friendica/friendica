<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Utils;

use Friendica\Core\L10n;
use Friendica\Moderation\Entity\Report;

final class ReportUtil
{
	private L10n $l10n;

	public function __construct(L10n $l10n)
	{
		$this->l10n = $l10n;
	}

	public function getReportCategoryName(int $category): string
	{
		switch ($category) {
			case Report::CATEGORY_SPAM:
				return $this->l10n->t('Spam');
			case Report::CATEGORY_ILLEGAL:
				return $this->l10n->t('Illegal Content');
			case Report::CATEGORY_SAFETY:
				return $this->l10n->t('Community Safety');
			case Report::CATEGORY_UNWANTED:
				return $this->l10n->t('Unwanted Content/Behavior');
			case Report::CATEGORY_VIOLATION:
				return $this->l10n->t('Rules Violation');
			case Report::CATEGORY_OTHER:
				return $this->l10n->t('Other');
			default:
				return "";
		};
	}
}
