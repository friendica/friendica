<div class="pull-left">
<h3 id="photo-album-title">{{$album}}</h3>
</div>

<div class="photo-album-actions pull-right">
	{{if $can_post}}
	<a class="photos-upload-link" href="{{$upload.1}}" title="{{$upload.0}}" data-toggle="tooltip">
	<i class="faded-icon fa fa-plus"></i></a>
	{{/if}}
	{{if $edit}}
	<span class="icon-padding"> </span>
	<a id="album-edit-link" href="{{$edit.1}}" title="{{$edit.0}}" data-toggle="tooltip">
	<i class="faded-icon fa fa-pencil"></i></a>
	{{/if}}
	{{if ! $noorder}}
	<span class="icon-padding"> </span>
	<a class="photos-order-link" href="{{$order.1}}" title="{{$order.0}}" data-toggle="tooltip">
	{{if $order.2 == "newest"}}
	<i class="faded-icon fa fa-sort-numeric-desc"></i>
	{{else}}
	<i class="faded-icon fa fa-sort-numeric-asc"></i>
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

<script type="text/javascript">$(document).ready(function() { loadingPage = false; justifyPhotos(); });</script>
