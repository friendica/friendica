<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * The default site template
 */

use Friendica\DI;

$frio = 'view/theme/frio';

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php if(!empty($page['title'])) {
		echo $page['title'];
	} ?></title>
	<meta name="viewport" content="initial-scale=1.0">
	<meta request="<?php echo htmlspecialchars($_REQUEST['pagename']) ?>">
	<script type="text/javascript">var baseurl="<?php echo DI::baseUrl() ?>";</script>
	<script type="text/javascript">var frio="<?php echo $frio; ?>";</script>
	<?php if(!empty($page['htmlhead'])) {
		echo $page['htmlhead'];
	} ?>
</head>
<body id="top">
<?php if($_SERVER['REQUEST_URI'] == '/') {
	header('Location: /login');
} ?>
<a href="<?php echo DI::args()->getCommand(); ?>/#content" class="sr-only sr-only-focusable"><?php echo DI::l10n()->t('Skip to main content'); ?></a>
<?php
	if(!empty($page['nav'])) {
		echo	str_replace(
			'~config.sitename~',
			DI::config()->get('config', 'sitename'),
			str_replace(
				'~system.banner~',
				DI::config()->get('system', 'banner'),
				$page['nav']
			)
		);
	};
?>
	<main>

		<div class="container">
			<div class="row">
<?php
					echo '
					<aside class="col-lg-3 col-md-3 hidden-sm hidden-xs">
						';
if(!empty($page['aside'])) {
	echo $page['aside'];
} echo'
						';
if(!empty($page['right_aside'])) {
	echo $page['right_aside'];
} echo'
						';
include('includes/photo_side.php');
echo'
					</aside>

					<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12" id="content">
						<section class="sectiontop">
								<div class="panel ' . DI::args()->get(0, 'generic') . '-content-wrapper">
									<div class="panel-body">';
if(!empty($page['content'])) {
	echo $page['content'];
} echo'
										<div id="pause"></div> <!-- The pause/resume Ajax indicator -->
									</div>
								</div>
						</section>
					</div>
						';
?>
			</div><!--row-->
		</div><!-- container -->

		<div id="back-to-top" title="<?php echo DI::l10n()->t('Back to top')?>">⇧</div>
	</main>

<footer>
<script>
	$('#menu-toggle').click(function(e) {
		e.preventDefault();
		$('#wrapper').toggleClass('toggled');
	});
</script>
<script type="text/javascript">
	$.fn.enterKey = function (fnc, mod) {
		return this.each(function () {
			$(this).keypress(function (ev) {
				var keycode = (ev.keyCode ? ev.keyCode : ev.which);
				if ((keycode == '13' || keycode == '10') && (!mod || ev[mod + 'Key'])) {
					fnc.call(this, ev);
				}
			})
		})
	}

	$('textarea').enterKey(function() {$(this).closest('form').submit(); }, 'ctrl')
	$('input').enterKey(function() {$(this).closest('form').submit(); }, 'ctrl')
</script>

<script>
var pagetitle = null;
$('nav').bind('nav-update', function(e,data)
{
	if (pagetitle==null) pagetitle = document.title;
	var count = $(data).find('notif').attr('count');
	if (count>0)
	{
		document.title = '('+count+') '+pagetitle;
	}
	else
	{
		document.title = pagetitle;
	}
});
</script>
<script src="<?=$frio?>/js/theme.js"></script>
<script src="<?=$frio?>/frameworks/bootstrap/js/bootstrap.min.js"></script>
<script src="<?=$frio?>/frameworks/jasny/js/jasny-bootstrap.min.js"></script>
<script src="<?=$frio?>/frameworks/bootstrap-select/js/bootstrap-select.min.js"></script>
<script src="<?=$frio?>/frameworks/ekko-lightbox/ekko-lightbox.min.js"></script>
<script src="<?=$frio?>/frameworks/justifiedGallery/jquery.justifiedGallery.min.js"></script>

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
</footer>
</body>
