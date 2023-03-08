{{* Template for single photo view *}}

{{* "live-photos" is needed for js autoupdate *}}
<div id="live-photos"></div>

<div id="photo-view-{{$id}}" class="generic-page-wrapper">
	<div class="pull-left" id="photo-edit-link-wrap">
		<a class="page-action faded-icon" id="photo-album-link" href="{{$album.0}}" title="{{$album.1}}" data-toggle="tooltip">
			<i class="fa fa-folder-open"></i>&nbsp;{{$album.1}}
		</a>
	</div>
	<div class="pull-right" id="photo-edit-link-wrap">
{{if $tools}}
	{{if $tools.view}}
		<span class="icon-padding"> </span>
		<a id="photo-edit-link" href="{{$tools.view.0}}" title="{{$tools.view.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-image"></i>
		</a>
	{{/if}}
	{{if $tools.edit}}
		<span class="icon-padding"> </span>
		<a id="photo-edit-link" href="{{$tools.edit.0}}" title="{{$tools.edit.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-pencil"></i>
		</a>
	{{/if}}
	{{if $tools.delete}}
		<span class="icon-padding"> </span>
		<a id="photo-edit-link" href="{{$tools.delete.0}}" title="{{$tools.delete.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-trash"></i>
		</a>
	{{/if}}
	{{if $tools.profile}}
		<span class="icon-padding"> </span>
		<a id="photo-toprofile-link" href="{{$tools.profile.0}}" title="{{$tools.profile.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-user"></i>
		</a>
	{{/if}}
	{{if $tools.lock}}
		<span class="icon-padding"> </span>
		<a id="photo-lock-link" onclick="lockview(event, 'photo', {{$id}});" title="{{$tools.lock}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-lock"></i>
		</a>
	{{/if}}
{{/if}}
	</div>
	<div class="clear"></div>

	<div id="photo-view-wrapper">
		<div id="photo-photo">
			{{* The photo *}}
			<div class="photo-container">
				<a href="{{$photo.href}}" title="{{$photo.title}}"><img src="{{$photo.src}}" alt="{{$photo.filename}}"/></a>
			</div>

			{{* Overlay buttons for previous and next photo *}}
			{{if $prevlink}}
			<a class="photo-prev-link" href="{{$prevlink.0}}"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
			{{/if}}
			{{if $nextlink}}
			<a class="photo-next-link" href="{{$nextlink.0}}"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
			{{/if}}
		</div>

		<div id="photo-photo-end"></div>
		{{* The photo description *}}
		<div id="photo-caption">{{$desc}}</div>

		{{* Tags and mentions *}}
		{{if $tags}}
		<div id="photo-tags">{{$tags.title}}
			{{foreach $tags.tags as $t}}
			<span class="category label btn-success sm">
				<span class="p-category">{{$t.name nofilter}}</span>
				{{if $t.removeurl}} <a href="{{$t.removeurl}}">(X)</a> {{/if}}
			</span>
			{{/foreach}}
		</div>
		{{/if}}

		{{if $tags.removeanyurl}}
		<div id="tag-remove">
			<a href="{{$tags.removeanyurl}}">{{$tags.removetitle}}</a>
		</div>
		{{/if}}

		{{* The part for editing the photo - only available for the edit subpage *}}
		{{if $edit}}{{$edit nofilter}}{{/if}}

		{{if $likebuttons}}
		<div id="photo-like-div">
			{{$likebuttons nofilter}}
			{{$like nofilter}}
			{{$dislike nofilter}}
		</div>
		{{/if}}
		<hr>
	</div>

{{if !$edit}}
	{{* Insert the comments *}}
	<div id="photo-comment-wrapper-{{$id}}" class="photo-comment-wrapper">
		{{$comments nofilter}}
	</div>

	{{$paginate nofilter}}
{{/if}}
</div>
