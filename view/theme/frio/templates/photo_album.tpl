{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<div style="display: inline-block">
		<h3 id="photo-album-title">{{$album}}</h3>
	</div>

	<div class="photo-album-actions pull-right">
		{{if $can_post}}
		<a class="btn btn-primary photos-upload-link page-action" href="{{$upload.1}}">
			<i class="fa fa-plus"></i>
			{{$upload.0}}
		</a>
		{{/if}}

		{{if $edit}}
		<button id="album-edit-link" class="btn btn-primary page-action" type="button" data-modal-url="{{$edit.1}}">
			<i class="fa fa-pencil"></i>
			{{$edit.0}}
		</button>
		{{/if}}
		{{if $drop}}
		<button id="album-drop-link" class="btn btn-primary page-action" type="button" data-modal-url="{{$drop.1}}">
			<i class="fa fa-trash"></i>
			{{$drop.0}}
		</button>
		{{/if}}

		{{if ! $noorder}}
		<a class="photos-order-link page-action" href="{{$order.1}}" title="{{$order.0}}" data-toggle="tooltip">
			{{if $order.2 == "newest"}}
			<i class="fa fa-sort-numeric-desc"></i>
			{{else}}
			<i class="fa fa-sort-numeric-asc"></i>
			{{/if}}
		</a>
		{{/if}}
	</div>

	<div class="photo-album-wrapper" id="photo-album-contents">
		{{foreach $photos as $photo}}
			{{include file="photo_top.tpl"}}
		{{/foreach}}
	</div>
	<div class="photo-album-end"></div>

	{{$paginate nofilter}}
</div>

<script type="text/javascript">$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
