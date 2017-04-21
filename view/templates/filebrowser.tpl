	<!--
		This is the template used by mod/fbrowser.php
	-->
<style>
	#buglink_wrapper{display:none;} /* hide buglink. only in this page */
</style>
<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>
<script type="text/javascript" src="{{$baseurl}}/js/filebrowser.js"></script>
<script>
	$(function() {
		FileBrowser.init("{{$nickname}}", "{{$type}}");
	});
</script>
<div class="fbrowser {{$type}}">
	<div class="error hidden">
		<span></span> <a href="#" class='close'>X</a>
	</div>

	<div class="path">
		{{foreach $path as $p}}<a href="#" data-folder="{{$p.0}}">{{$p.1}}</a>{{/foreach}}
	</div>

	{{if $folders }}
	<div class="folders">
		<ul>
			{{foreach $folders as $f}}<li><a href="#" data-folder="{{$f.0}}">{{$f.1}}</a></li>{{/foreach}}
		</ul>
	</div>
	{{/if}}

	<div class="list">
		{{foreach $files as $f}}
		<div class="photo-album-image-wrapper">
			<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}">
				<img src="{{$f.2}}">
				<p>{{$f.1}}</p>
			</a>
		</div>
		{{/foreach}}
	</div>

	<div class="upload">
		<button id="upload-{{$type}}"><img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait|escape:'html'}}" style="display: none;" /> {{"Upload"|t}}</button>
	</div>
</div>


	</body>

</html>
