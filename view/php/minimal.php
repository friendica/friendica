<!DOCTYPE html >
<html>
<head>
  <title><?php use Friendica\Registry\App;

	  if(!empty($page['title'])) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo App::baseUrl() ?>";</script>
  <?php if(!empty($page['htmlhead'])) echo $page['htmlhead'] ?>
</head>
<body class="minimal">
	<section><?php if(!empty($page['content'])) echo $page['content']; ?>
		<div id="page-footer"></div>
	</section>
</body>
</html>
