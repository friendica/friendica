	<!--
		This is the template used by mod/fbrowser.php
	-->
<style>
	#buglink_wrapper{display:none;} /* hide buglink. only in this page */
</style>
{{*<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>*}}
{{*<script type="text/javascript" src="view/theme/frio/js/filebrowser.js"></script>*}}

<div class="fbrowser {{$type}}">
	<div class="fbrowser-content">
		<input id="fb-nickname" type="hidden" name="type" value="{{$nickname}}" />
		<input id="fb-type" type="hidden" name="type" value="{{$type}}" />

		<div class="error hidden">
			<span></span> <button type="button" class="btn btn-link" class="close" aria-label="Close">X</a>
		</div>

		{{* The breadcrumb navigation *}}
		<ol class="path breadcrumb" aria-label="Breadcrumb" role="navigation">
			{{foreach $path as $p}}<li role="presentation"><a href="#" data-folder="{{$p.0}}">{{$p.1}}</a></li>{{/foreach}}

			{{* Switch between image and file mode *}}
			<div class="fbswitcher btn-group btn-group-xs pull-right" aria-label="Switch between image and file mode">
				<button type="button" class="btn btn-default" data-mode="image" aria-label="Image Mode"><i class="fa fa-picture-o" aria-hidden="true"></i></button>
				<button type="button" class="btn btn-default" data-mode="file" aria-label="File Mode"><i class="fa fa-file-o" aria-hidden="true"></i></button>
			</div>
		</ol>

		<div class="media">

			{{* List of photo albums *}}
			{{if $folders }}
			<div class="folders media-left" role="navigation" aria-label="Album Navigation">
				<ul role="menu">
					{{foreach $folders as $f}}
					<li role="presentation">
						<a href="#" data-folder="{{$f.0}}" role="menuitem">{{$f.1}}</a>
					</li>
					{{/foreach}}
				</ul>
			</div>
			{{/if}}

			{{* The main content (images or files) *}}
			<div class="list {{$type}} media-body" role="main" aria-label="Browser Content">
				<div class="fbrowser-content-container">
					{{foreach $files as $f}}
					<div class="photo-album-image-wrapper">
						<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}">
							<img src="{{$f.2}}" alt="{{$f.1}}">
							<p>{{$f.1}}</p>
						</a>
					</div>
					{{/foreach}}
				</div>
			</div>
		</div>

		<div class="upload">
			<button id="upload-{{$type}}">{{"Upload"|t}}</button>
		</div>
	</div>

	{{* This part contains the conent loader icon which is visible when new conent is loaded *}}
	<div class="profile-rotator-wrapper" aria-hidden="true" style="display: none;">
		<i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>
	</div>
</div>
