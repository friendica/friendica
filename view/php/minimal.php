<!DOCTYPE html>
<html>
<head>
  <title><?php if(!empty($page['title'])) echo $page['title'] ?></title>
  <script>var baseurl="<?php echo Friendica\DI::baseUrl() ?>";</script>
  <?php if(!empty($page['htmlhead'])) echo $page['htmlhead'] ?>
</head>
<body class="minimal">
	<section><?php if(!empty($page['content'])) echo $page['content']; ?>
		<div id="page-footer">
			<?php echo $page['footer'] ?? ''; ?>
		</div>
	</section>
</body>
</html>
