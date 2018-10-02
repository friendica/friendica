<div class="generic-page-wrapper">
	<div class="pull-left">
		<h3 id="photo-album-title">{{$album}}</h3>
	</div>

	<div class="photo-album-actions pull-right">
		{{if $can_post}}
		<a class="photos-upload-link page-action faded-icon" href="{{$upload.1}}" title="{{$upload.0}}" data-toggle="tooltip">
			<i class="fa fa-plus"></i>
		</a>
		{{/if}}

		{{if $edit}}
		<span class="icon-padding"> </span>
		<button id="album-edit-link" class="btn-link page-action faded-icon" type="button" data-modal-url="{{$edit.1}}" title="{{$edit.0}}" data-toggle="tooltip">
			<i class="fa fa-pencil"></i>
		</button>
		{{/if}}

		{{if ! $noorder}}
		<span class="icon-padding"> </span>
		<a class="photos-order-link page-action faded-icon" href="{{$order.1}}" title="{{$order.0}}" data-toggle="tooltip">
			{{if $order.2 == "newest"}}
			<i class="fa fa-sort-numeric-desc"></i>
			{{else}}
			<i class="fa fa-sort-numeric-asc"></i>
			{{/if}}
		</a>
		{{/if}}
	</div>
	<div class="clear"></div>

	<div class="photo-album-wrapper" id="photo-album-contents">
		{{foreach $photos as $photo}}
			{{include file="photo_top.tpl"}}
		{{/foreach}}
	</div>
	<div class="photo-album-end"></div>

	{{$paginate}}
</div>

<script type="text/javascript">$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
