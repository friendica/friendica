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
	<!-- Modal  -->
	<div id="modal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-full-screen">
			<div class="modal-content">
				<div id="modal-header" class="modal-header">
					<button id="modal-cloase" type="button" class="close" data-dismiss="modal">
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
</body>
</html>

