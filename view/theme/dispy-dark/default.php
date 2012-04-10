<!DOCTYPE html>
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo $a->get_baseurl() ?>";</script>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
	<?php if(x($page,'nav')) echo $page['nav']; ?>
	<aside id="asideleft">
		<?php if(x($page,'aside')) echo $page['aside']; ?>
		<?php if(x($page,'aside_bottom')) echo $page['aside_bottom']; ?>
	</aside>
	<section>
		<?php if(x($page,'content')) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
	<aside id="asideright">
		<?php if(x($page,'aside_right')) echo $page['aside_right']; ?>
		<?php if(x($page,'aside_right_bottom')) echo $page['aside_right_bottom']; ?>
	</aside>
	<footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
</body>
</html>

