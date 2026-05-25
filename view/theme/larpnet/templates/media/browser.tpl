{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!--
	This is the template used by mod/fbrowser.php
-->
<div id="filebrowser" class="fbrowser {{$type}}" data-nickname="{{$nickname}}" data-type="{{$type}}">
	<div class="fbrowser-content">
		<div class="error hidden">
			<span></span> <button type="button" class="btn btn-link close" aria-label="Close">X</button>
		</div>

		{{* The breadcrumb navigation *}}
		<ol class="path breadcrumb" aria-label="Breadcrumb" role="menu">
		{{foreach $path as $folder => $name}}
			<li>
				<button type="button" class="btn btn-link" data-folder="{{$folder}}" role="menuitem">{{$name}}</button>
			</li>
		{{/foreach}}

			{{* Switch between image and file mode *}}
			<div class="fbswitcher btn-group btn-group-xs pull-right" aria-label="Switch between photo and attachment mode">
				<button type="button" class="btn btn-default" data-mode="photo" aria-label="Photo Mode"><i class="fa fa-picture-o" aria-hidden="true"></i></button>
				<button type="button" class="btn btn-default" data-mode="attachment" aria-label="Attachment Mode"><i class="fa fa-file-o" aria-hidden="true"></i></button>
			</div>
		</ol>

		<div class="upload">
			<button id="upload-{{$type}}" type="button" class="btn btn-primary">{{$upload}}</button>
		</div>

		<div class="media">

			{{* List of photo albums *}}
			{{if $folders }}
			<nav class="folders media-left" aria-label="Album Navigation">
				<ul role="menu">
					{{foreach $folders as $folder}}
					<li>
						<button type="button" data-folder="{{$folder}}" role="menuitem">{{$folder}}</button>
					</li>
					{{/foreach}}
				</ul>
			</nav>
			{{/if}}

			{{* The main content (images or files) *}}
			<div class="list {{$type}} media-body" role="main" aria-label="Browser Content">
				<div class="fbrowser-content-container">
					{{foreach $files as $f}}
					<div class="photo-album-image-wrapper">
						<a href="#" class="photo-album-photo-link" data-link="{{$f.0}}" data-filename="{{$f.1}}" data-img="{{$f.2}}" data-alt="{{$f.3}}">
							<img src="{{$f.2}}" alt="{{$f.1}}">
							<p>{{$f.1}}</p>
						</a>
					</div>
					{{/foreach}}
				</div>
			</div>
		</div>

	</div>

	{{* This part contains the content loader icon which is visible when new content is loaded *}}
	<div class="profile-rotator-wrapper" aria-hidden="true" style="display: none;">
		<i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>
	</div>
</div>
