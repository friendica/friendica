<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php if(x($page,'title')) echo $page['title'] ?></title>
		<?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
	</head>
	<body>
		<header id="header">
			<?php if(x($page,'nav')) echo $page['nav']; ?>
		</header>
		<aside id="left_aside">
			<?php if(x($page,'aside')) echo $page['aside']; ?>
		</aside>
		<section id="content">
			<?php if(x($page,'content')) echo $page['content']; ?>
			<footer id="content_footer"></footer>
		</section>
		<aside id="right_aside">
			<?php if(x($page,'right_aside')) echo $page['right_aside']; ?>
		</aside>
		<footer id="footer">
			<?php if(x($page,'footer')) echo $page['footer']; ?>
		</footer>
		<?php if(x($page,'end')) echo $page['end']; ?>
	</body>
</html>

