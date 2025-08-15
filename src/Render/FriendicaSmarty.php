<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Render;

use Smarty;
use Friendica\Core\Renderer;

/**
 * Friendica extension of the Smarty3 template engine
 */
class FriendicaSmarty extends Smarty
{
	const SMARTY3_TEMPLATE_FOLDER = 'templates';

	public $filename;

	public function __construct(string $theme, array $theme_info, string $work_dir, bool $use_sub_dirs)
	{
		parent::__construct();

		// setTemplateDir can be set to an array, which Smarty will parse in order.
		// The order is thus very important here
		$template_dirs = ['theme' => "view/theme/$theme/" . self::SMARTY3_TEMPLATE_FOLDER . '/'];
		if (!empty($theme_info['extends'])) {
			$template_dirs = $template_dirs + ['extends' => 'view/theme/' . $theme_info['extends'] . '/' . self::SMARTY3_TEMPLATE_FOLDER . '/'];
		}

		$template_dirs = $template_dirs + ['base' => 'view/' . self::SMARTY3_TEMPLATE_FOLDER . '/'];
		$this->setTemplateDir($template_dirs);

		$work_dir = rtrim($work_dir, '/');

		$this->setCompileDir($work_dir . '/compiled');
		$this->setConfigDir($work_dir . '/');
		$this->setCacheDir($work_dir . '/');

		$this->registerPlugin('modifier', 'is_string', function ($value) {
			return is_string($value);
		});
		$this->registerPlugin("modifier", "str_ends_with", "str_ends_with");

		/*
		 * Enable sub-directory splitting for reducing directory descriptor
		 * size. The default behavior is to put all compiled/cached files into
		 * one single directory. Under Linux and EXT4 (and maybe other FS) this
		 * will increase the descriptor's size (which contains information
		 * about entries inside the described directory. If the descriptor is
		 * getting to big, the system will slow down as it has to read the
		 * whole directory descriptor all over again (unless you have tons of
		 * RAM available + have enabled caching inode tables (aka.
		 * "descriptors"). Still it won't hurt you.
		 */
		$this->setUseSubDirs($use_sub_dirs);

		$this->left_delimiter  = Renderer::getTemplateLeftDelimiter();
		$this->right_delimiter = Renderer::getTemplateRightDelimiter();

		$this->escape_html = true;

		// Don't report errors so verbosely
		$this->error_reporting = E_ALL & ~E_NOTICE;

		$this->muteUndefinedOrNullWarnings();
	}
}
