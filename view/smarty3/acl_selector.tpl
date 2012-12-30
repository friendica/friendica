<div id="acl-wrapper">
	<input id="acl-search">
	<a href="#" id="acl-showall">{{$showall}}</a>
	<div id="acl-list">
		<div id="acl-list-content">
		</div>
	</div>
	<span id="acl-fields"></span>
</div>

<div class="acl-list-item" rel="acl-template" style="display:none">
	<img data-src="{0}"><p>{1}</p>
	<a href="#" class='acl-button-show'>{{$show}}</a>
	<a href="#" class='acl-button-hide'>{{$hide}}</a>
</div>

<script>
$(document).ready(function() {
	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl",
			[ {{$allowcid}},{{$allowgid}},{{$denycid}},{{$denygid}} ]
		);
	}
});
</script>
