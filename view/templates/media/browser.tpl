<!--
	This is the template used by mod/fbrowser.php
-->
<script type="text/javascript" src="view/js/ajaxupload.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="view/js/module/media/browser.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script>
	$(function() {
		Browser.init("{{$nickname}}", "{{$type}}");
	});
</script>
<div class="fbrowser {{$type}}">
	<div class="error hidden">
		<span></span> <a href="#" class='close'>X</a>
	</div>

	<div class="path">
		{{foreach $path as $folder => $name}}
			<a href="#" data-folder="{{$folder}}">{{$name}}</a>
		{{/foreach}}
	</div>

	{{if $folders }}
	<div class="folders">
		<ul>
		{{foreach $folders as $folder}}
			<li><a href="#" data-folder="{{$folder}}">{{$folder}}</a></li>
		{{/foreach}}
		</ul>
	</div>
	{{/if}}

	<div class="list">
		{{foreach $files as $f}}
		<div class="photo-album-image-wrapper">
			<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}" data-alt="{{$f.3}}">
				<img alt="{{$f.3}}" src="{{$f.2}}">
				<p>{{$f.1}}</p>
			</a>
		</div>
		{{/foreach}}
	</div>

	<div class="upload">
		<button id="upload-{{$type}}"><img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" /> {{$upload}}</button>
	</div>
</div>


	</body>

</html>
