{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!--
	This is the template used by mod/fbrowser.php
-->
<script type="text/javascript" src="view/js/ajaxupload.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="view/js/module/media/browser.js?v={{$VERSION}}"></script>
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
		<div class="upload">
			<button id="upload-{{$type}}"><img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" /> {{$upload}}</button>
		</div>
		{{foreach $files as $f}}
		<div class="photo-album-image-wrapper">
			<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}" data-alt="{{$f.3}}">
				<img src="{{$f.2}}" alt="{{$f.3}}" {{if $f.3}}class="has-alt-description" title="{{$f.3}}"{{else}}class="empty-description"{{/if}}/>
				<p>{{$f.1}}</p>
			</a>
		</div>
		{{/foreach}}
	</div>
</div>


	</body>

</html>
