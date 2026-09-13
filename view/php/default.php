<?php
/*
 * Copyright (C) 2010-2026, the Friendica project
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
<html itemscope itemtype="http://schema.org/Blog" lang="<?php echo $lang; ?>">
<head>
  <title><?php if (!empty($page['title'])) {
  	echo $page['title'];
  } ?></title>
  <script>var baseurl="<?php echo Friendica\DI::baseUrl() ?>";</script>
  <?php if (!empty($page['htmlhead'])) {
  	echo $page['htmlhead'];
  } ?>
</head>
<body class="mod-<?php echo $page['module'] ?>">
	<?php if (!empty($page['nav'])) {
		echo $page['nav'];
	} ?>
	<aside id="aside-section"><?php if (!empty($page['aside'])) {
		echo $page['aside'];
	} ?></aside>
	<section id="content-section">
		<?php if (!empty($page['content'])) {
			echo $page['content'];
		} ?>
		<div id="pause"></div> <!-- The pause/resume Ajax indicator -->
		<div id="page-footer"></div>
	</section>
	<div id="right-aside-section"><?php if (!empty($page['right_aside'])) {
		echo $page['right_aside'];
	} ?></div>
	<footer><?php if (!empty($page['footer'])) {
		echo $page['footer'];
	} ?></footer>
</body>
</html>
