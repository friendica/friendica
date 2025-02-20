<?php
/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The site template for pure content (e.g. (modals)
 *
 * This template is used e.g for bs modals. So outputs
 * only the pure content
 */
?>

<!DOCTYPE html>
<html lang="<?php echo Friendica\DI::l10n()->getCurrentLang(); ?>">
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
	<section><?php if(!empty($page['content'])) {
		echo $page['content'];
	} ?>
		<div id="page-footer">
			<?php echo $page['footer'] ?? ''; ?>
		</div>
	</section>
</body>
</html>
