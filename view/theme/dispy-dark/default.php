<!DOCTYPE html>
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title']; ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead']; ?>
</head>
<body>
	<header>
		<?php if(x($page, 'header')) echo $page['header']; ?>
	</header>
	<article id="articlemain">
		<header id="articleheader">
			<?php if(x($page, 'articleheader')) echo $page['articleheader']; ?>
		</header>
		<?php if(x($page,'nav')) echo $page['nav']; ?>
		<aside id="asideleft">
			<?php if(x($page,'aside_left')) echo $page['aside_left']; ?>
			<?php if(x($page,'aside_left_bottom')) echo $page['aside_left_bottom']; ?>
		</aside>
		<section id="sectionmain">
			<?php if(x($page,'content')) echo $page['content']; ?>
			<footer id="sectionfooter">
			</footer>
		</section>
		<aside id="asidemain">
			<?php if(x($page,'aside')) echo $page['aside']; ?>
			<?php if(x($page,'aside_bottom')) echo $page['aside_bottom']; ?>
		</aside>
		<aside id="asideright">
			<?php if(x($page,'aside_right')) echo $page['aside_right']; ?>
			<?php if(x($page,'aside_right_bottom')) echo $page['aside_right_bottom']; ?>
		</aside>
		<footer id="articlefooter">
			<?php if(x($page, 'articlefooter')) echo $page['articlefooter']; ?>
		</footer>
	</article>
	<footer id="footer">
		<?php if(x($page, 'footer')) echo $page['footer']; ?>
	</footer>
	<?php if (x($page, 'bottom')) echo $page['bottom']; ?>
</body>
</html>

