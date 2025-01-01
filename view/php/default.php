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
<html itemscope itemtype="http://schema.org/Blog" lang="<?php echo $lang; ?>">
<head>
  <title><?php if(!empty($page['title'])) {
  	echo $page['title'];
  } ?></title>
  <script>var baseurl="<?php echo Friendica\DI::baseUrl() ?>";</script>
  <?php if(!empty($page['htmlhead'])) {
  	echo $page['htmlhead'];
  } ?>
</head>
<body>
	<?php if(!empty($page['nav'])) {
		echo $page['nav'];
	} ?>
	<aside><?php if(!empty($page['aside'])) {
		echo $page['aside'];
	} ?></aside>
	<section>
		<?php if(!empty($page['content'])) {
			echo $page['content'];
		} ?>
		<div id="pause"></div> <!-- The pause/resume Ajax indicator -->
		<div id="page-footer"></div>
	</section>
	<right_aside><?php if(!empty($page['right_aside'])) {
		echo $page['right_aside'];
	} ?></right_aside>
	<footer><?php if(!empty($page['footer'])) {
		echo $page['footer'];
	} ?></footer>
</body>
</html>
