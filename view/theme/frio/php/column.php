<?php
/*
 * Copyright (C) 2010-2026, the Friendica project
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The site template for pages embedded as a column of the deck (/deck)
 *
 * It is selected with the "mode=column" query parameter and outputs the page content
 * without navigation bar, aside and footer widgets.
 */

use Friendica\App;
use Friendica\DI;

require_once 'view/theme/frio/theme.php';
require_once 'view/theme/frio/php/scheme.php';

$show_action_labels = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'show_action_labels', DI::config()->get('frio', 'show_action_labels', true)) ? 'show-action-labels' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo DI::l10n()->getCurrentLang(); ?>">
	<head>
		<title><?php echo $page['title'] ?? ''; ?></title>
		<script type="text/javascript">var baseurl = "<?php echo (string) DI::baseUrl(); ?>";</script>
		<script type="text/javascript">var frio = "view/theme/frio";</script>
<?php
if (!empty($page['htmlhead'])) {
	echo $page['htmlhead'];
}
?>
		<link rel="stylesheet" href="view/theme/frio/css/deck.css?v=<?php echo App::VERSION; ?>" type="text/css" media="screen" />
	</head>

	<body id="top" class="column-view mobile-view mod-<?php echo $page['module'] . ' ' . $show_action_labels; ?>">
		<main>
			<div class="container">
				<div class="row">
					<div class="col-xs-12" id="content" tabindex="0">
						<section class="sectiontop <?php echo $page['section'] ?? ''; ?>-content-wrapper">
							<?php echo $page['content']                           ?? ''; ?>
							<div id="pause"></div>
						</section>
					</div>
				</div>
			</div>
		</main>
		<div id="page-footer">
			<?php echo $page['footer'] ?? ''; ?>
		</div>
		<script type="text/javascript" src="view/theme/frio/js/column-frame.js"></script>
	</body>
</html>
