<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Utils;

use Friendica\Core\L10n;
use Friendica\Moderation\Entity\Report;

final readonly class ReportUtil
{
	public function __construct(private L10n $l10n) {}

	public function getReportCategoryName(int $category): string
	{
		return match ($category) {
			Report::CATEGORY_SPAM      => $this->l10n->t('Spam'),
			Report::CATEGORY_ILLEGAL   => $this->l10n->t('Illegal Content'),
			Report::CATEGORY_SAFETY    => $this->l10n->t('Community Safety'),
			Report::CATEGORY_UNWANTED  => $this->l10n->t('Unwanted Content/Behavior'),
			Report::CATEGORY_VIOLATION => $this->l10n->t('Rules Violation'),
			Report::CATEGORY_OTHER     => $this->l10n->t('Other'),
			default                    => "",
		};
		;
	}
}
