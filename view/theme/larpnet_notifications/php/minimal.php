<?php
/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Friendica\DI;

?>
<!DOCTYPE html>
<html lang="<?php echo DI::l10n()->getCurrentLang(); ?>">
<head>
	<title><?php if(!empty($page['title'])) {
		echo $page['title'];
	} ?></title>
	<script>var baseurl="<?php echo Friendica\DI::baseUrl() ?>";</script>
	<?php if(!empty($page['htmlhead'])) {
		echo $page['htmlhead'];
	} ?>
</head>
<body class="minimal">
	<section>
		<div  class="generic-page-wrapper">
			<?php if(!empty($page['content'])) {
				echo $page['content'];
			} ?>
		</div>
		<div id="page-footer">
			<?php echo $page['footer'] ?? ''; ?>
		</div>
	</section>
	<!-- Modal  -->
	<div id="modal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-full-screen">
			<div class="modal-content">
				<div id="modal-header" class="modal-header">
					<button id="modal-close" type="button" class="close" data-dismiss="modal" title="<?php echo DI::l10n()->t('Close'); ?>">
						&times;
					</button>
					<h4 id="modal-title" class="modal-title"></h4>
				</div>
				<div id="modal-body" class="modal-body">
					<!-- /# content goes here -->
				</div>
			</div>
		</div>
	</div>
</body>
</html>

