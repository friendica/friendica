<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The default site template
 */

?>
<!DOCTYPE html>
<?php

use Friendica\DI;
use Friendica\Model\Profile;

require_once 'view/theme/frio/theme.php';
require_once 'view/theme/frio/php/frio_boot.php';
require_once 'view/theme/frio/php/scheme.php';

//	$minimal = is_modal();
if (!isset($minimal)) {
	$minimal = false;
}

$basepath            = DI::baseUrl()->getPath() ? "/" . DI::baseUrl()->getPath() . "/" : "/";
$frio                = "view/theme/frio";
$view_mode_class     = (DI::mode()->isMobile() || DI::mode()->isMobile()) ? 'mobile-view' : 'desktop-view';
$is_singleuser       = DI::config()->get('system', 'singleuser');
$is_singleuser_class = $is_singleuser ? "is-singleuser" : "is-not-singleuser";
?>
<html>
	<head>
		<title><?php if (!empty($page['title'])) {
			echo $page['title'];
		} ?></title>
		<meta request="<?php echo htmlspecialchars($_REQUEST['pagename'] ?? '') ?>">
		<script  type="text/javascript">var baseurl = "<?php echo (string)DI::baseUrl(); ?>";</script>
		<script type="text/javascript">var frio = "<?php echo 'view/theme/frio'; ?>";</script>
<?php
		// Because we use minimal for modals the header and the included js stuff should be only loaded
		// if the page is an standard page (so we don't have it twice for modals)
		//
		/// @todo Think about to move js stuff in the footer
		if (!$minimal && !empty($page['htmlhead'])) {
			echo $page['htmlhead'];
		}

// Add the theme color meta
// It makes mobile Chrome UI match Frio's top bar color.
$uid    = Profile::getThemeUid($a);
$scheme = frio_scheme_get_current_for_user($uid);
if ($scheme != FRIO_CUSTOM_SCHEME) {
	if (file_exists('view/theme/frio/scheme/' . $scheme . '.php')) {
		$schemefile    = 'view/theme/frio/scheme/' . $scheme . '.php';
		$scheme_accent = DI::pConfig()->get($uid, 'frio', 'scheme_accent') ?:
				DI::config()->get('frio', 'scheme_accent') ?: FRIO_SCHEME_ACCENT_BLUE;

		require_once $schemefile;
	}
}

$nav_bg = $nav_bg ?? DI::pConfig()->get($uid, 'frio', 'nav_bg', DI::config()->get('frio', 'nav_bg', '#708fa0'));

echo '<meta name="theme-color" content="' . $nav_bg . '" />';
?>
	</head>

	<body id="top" class="mod-<?php echo $page['module'] . " " . $is_singleuser_class . " " . $view_mode_class;?>">
		<a href="<?php echo DI::args()->getCommand(); ?>/#content" class="sr-only sr-only-focusable"><?php echo DI::l10n()->t('Skip to main content'); ?></a>
<?php
	if (!empty($page['nav']) && !$minimal) {
		echo str_replace(
			"~config.sitename~",
			DI::config()->get('config', 'sitename'),
			str_replace(
				"~system.banner~",
				DI::config()->get('system', 'banner'),
				$page['nav']
			)
		);
	};

// special minimal style for modal dialogs
if ($minimal) {
	?>
		<!-- <?php echo __FILE__ ?> -->
		<section class="minimal">
			<?php if (!empty($page['content'])) {
				echo $page['content'];
			} ?>
			<div id="page-footer"></div>
		</section>
<?php
} else {
	// the style for all other pages
	?>
		<main>
			<div class="container">
				<div class="row">
<?php
					if ((empty($_REQUEST['pagename']) || $_REQUEST['pagename'] != "lostpass") && ($_SERVER['REQUEST_URI'] != $basepath)) {
						echo '
					<aside class="col-lg-3 col-md-3 offcanvas-sm offcanvas-xs">';

						if (!empty($page['aside'])) {
							echo $page['aside'];
						}

						if (!empty($page['right_aside'])) {
							echo $page['right_aside'];
						}

						echo '
					</aside>

					<div class="col-lg-7 col-md-7 col-sm-12 col-xs-12" id="content">
						<section class="sectiontop ';
						echo $page['section'] ?? '';
						echo '-content-wrapper">';
						if (!empty($page['content'])) {
							echo $page['content'];
						}
						echo '
							<div id="pause"></div> <!-- The pause/resume Ajax indicator -->
						</section>
					</div>
						';
					} else {
						echo '
					<section class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="content" style="margin-top:50px;">';
						if (!empty($page['content'])) {
							echo $page['content'];
						}
						echo '
					</section>
					';
					}
	?>
				</div><!--row-->
			</div><!-- container -->

			<div id="back-to-top" title="<?php echo DI::l10n()->t('Back to top')?>">⇧</div>
		</main>

		<footer>
			<?php echo $page['footer'] ?? ''; ?>
		</footer>
<?php } ?> <!-- End of condition if $minimal else the rest -->
	</body>
