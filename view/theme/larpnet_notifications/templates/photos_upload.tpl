{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">

	<h3>{{$pagename}}</h3>

	<div id="photos-usage-message">{{$usage}}</div>

	<form action="profile/{{$nickname}}/photos" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form">
		<div id="photos-upload-div" class="form-group">
			<label id="photos-upload-text" for="photos-upload-newalbum">{{$newalbum}}</label>

			<input id="photos-upload-album-select" class="form-control" placeholder="{{$existalbumtext}}" list="dl-photo-upload" type="text" name="album" size="4">
			<datalist id="dl-photo-upload">
				{{foreach $albumselect as $value => $name}}
					<option value="{{$value}}"{{if $selname == $value}} selected{{/if}}>{{$name}}</option>
				{{/foreach}}
			</datalist>
		</div>
		<div id="photos-upload-end" class="clearfix"></div>

		<div id="photos-upload-noshare-div" class="photos-upload-noshare-div checkbox pull-left">
			<input id="photos-upload-noshare" type="checkbox" name="not_visible" value="1" checked/>
			<label id="photos-upload-noshare-text" for="photos-upload-noshare">{{$nosharetext}}</label>
		</div>

		{{if $alt_uploader}}
			<div id="photos-upload-perms" class="pull-right">
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#photo-upload-permission-acl" onclick="return false;">
					<i id="jot-perms-icon" class="fa {{$lockstate}}"></i> {{$permissions}}
				</button>
			</div>
			<div class="clearfix"></div>

			<div id="photos-upload-spacer"></div>

			{{$alt_uploader nofilter}}
		{{/if}}


		{{if $default_upload_submit}}
			<div class="clearfix"></div>

			<div id="photos-upload-spacer"></div>

			<div class="photos-upload-wrapper">
				<div id="photos-upload-perms" class="btn-group pull-right">
					<button class="btn btn-default" data-toggle="modal" data-target="#photo-upload-permission-acl" onclick="return false;">
						<i id="jot-perms-icon" class="fa {{$lockstate}}"></i>
					</button>

					{{$default_upload_submit nofilter}}
				</div>
				{{$default_upload_box nofilter}}
			</div>
			<div class="clearfix"></div>
		{{/if}}

		<div class="photos-upload-end" class="clearfix"></div>

		{{* The modal for advanced-expire *}}
		<div id="photo-upload-permission-acl" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button id="modal-close" type="button" class="close" data-dismiss="modal" aria-hidden="true">
							&times;
						</button>
						<h4 class="modal-title">{{$permissions}}</h4>
					</div>
					<div id="photos-upload-permissions-wrapper" class="modal-body">
						{{$aclselect nofilter}}
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
