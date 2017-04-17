<?php
/**
 * @file view/theme/frio/php/modes/default.php
 * @brief The default site template
 */
?>
<!DOCTYPE html >
<?php
	require_once('view/theme/frio/php/frio_boot.php');

//	$minimal = is_modal();

?>
<html>
<head>
	<title><?php if(x($page,'title')) echo $page['title'] ?></title>
	<meta request="<?php echo htmlspecialchars($_REQUEST['pagename']) ?>">
	<script>var baseurl="<?php echo App::get_baseurl() ?>";</script>
	<script>var frio="<?php echo "view/theme/frio"; ?>";</script>
	<?php $baseurl = App::get_baseurl(); ?>
	<?php $frio = "view/theme/frio"; ?>
	<?php 
		// Because we use minimal for modals the header and the included js stuff should be only loaded
		// if the page is an standard page (so we don't have it twice for modals)
		// 
		/// @todo Think about to move js stuff in the footer
		if(!$minimal) {
			if(x($page,'htmlhead')) echo $page['htmlhead'];
		}
	?>
	<?php
		// Add the theme color meta
		// It makes mobile Chrome UI match Frio's top bar color.
		$uid = $a->profile_uid;
		if (is_null($uid)) {
			$uid = get_theme_uid();
		}
		$schema = get_pconfig($uid, 'frio', 'schema');
		if (($schema) && ($schema != '---')) {
			if (file_exists('view/theme/frio/schema/'.$schema.'.php')) {
				$schemefile = 'view/theme/frio/schema/'.$schema.'.php';
				require_once($schemefile);
			}
		} else {
			$nav_bg = get_pconfig($uid, 'frio', 'nav_bg');
		}
		if (!$nav_bg) {
			$nav_bg = "#708fa0";
		}
		echo '<meta name="theme-color" content="'.$nav_bg.'" />';
	?>

</head>
<?php
if(($_SERVER['REQUEST_URI'] != "/register") && ($_SERVER['REQUEST_URI'] != "/lostpass") && ($_SERVER['REQUEST_URI'] != "/login"))
{
	echo"<body id=\"top\">";
}
else
{
	echo"<body id=\"top\">";
}
?>
<a href="#content" class="sr-only sr-only-focusable">Skip to main content</a>
<?php
	if(x($page,'nav') && (!$minimal)){
	echo	str_replace("~config.sitename~",get_config('config','sitename'),
			str_replace("~system.banner~",get_config('system','banner'),
			$page['nav']
	));};

	// special minimal style for modal dialogs
	if($minimal) {
?>
		<section class="minimal" style="margin:0px!important; padding:0px!important; float:none!important;display:block!important;"><?php if(x($page,'content')) echo $page['content']; ?>
			<div id="page-footer"></div>
		</section>
<?php	}
	// the style for all other pages
	else {
?>	<main>
		<div class="container">
			<div class="row">
<?php
				if(($_REQUEST['pagename'] != "register") && ($_REQUEST['pagename'] != "lostpass") && ($_REQUEST['pagename'] != "login") && ($_SERVER['REQUEST_URI'] != "/"))
				{
					echo"
					<aside class=\"col-lg-3 col-md-3 offcanvas-sm offcanvas-xs\">
						"; if(x($page,'aside')) echo $page['aside']; echo"
						"; if(x($page,'right_aside')) echo $page['right_aside']; echo"
					</aside>

					<div class=\"col-lg-7 col-md-7 col-sm-12 col-xs-12\" id=\"content\">
						<section class=\"sectiontop "; echo $a->argv[0]; echo "-content-wrapper\">";
								if(x($page,'content')) echo $page['content']; echo"
								<div id=\"pause\"></div> <!-- The pause/resume Ajax indicator -->
						</section>
					</div>
						";
				}
				else
				{
					echo"
					<div class=\"col-lg-12 col-md-12 col-sm-12 col-xs-12\" id=\"content\" style=\"margin-top:50px;\" >
						"; if(x($page,'content')) echo $page['content']; echo"
					</div>
					";
				}
?>
			</div><!--row-->
		</div><!-- container -->

		<div id="back-to-top" title="back to top">&#8679;</div>
	</main>

<footer>
<?php if(x($page,'footer')) echo $page['footer']; ?>
<!-- Modal  -->
<div id="modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="plan-info" aria-hidden="true">
	<div class="modal-dialog modal-full-screen">
		<div class="modal-content">
			<div id="modal-header" class="modal-header">
				<button id="modal-cloase" type="button" class="close" data-dismiss="modal" aria-hidden="true">
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

<!-- Dummy div to append other div's when needed (e.g. used for js function editpost() -->
<div id="cache-container"></div>

</footer>
<?php } ?> <!-- End of condition if $minal else the rest -->
</body>
