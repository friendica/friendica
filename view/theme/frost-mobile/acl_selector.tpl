<div id="acl-wrapper">
	<input id="acl-search">
	<a href="#" id="acl-showall">$showall</a>
	<div id="acl-list">
		<div id="acl-list-content">
		</div>
	</div>
	<span id="acl-fields"></span>
</div>

<div class="acl-list-item" rel="acl-template" style="display:none">
	<img data-src="{0}"><p>{1}</p>
	<a href="#" class='acl-button-show'>$show</a>
	<a href="#" class='acl-button-hide'>$hide</a>
</div>

<script>
	window.allowCID = $allowcid;
	window.allowGID = $allowgid;
	window.denyCID = $denycid;
	window.denyGID = $denygid;
	window.aclInit = "true";
</script>
