{{* Template for singele photo view *}}

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
		<span class="icon-padding"> </span>
		<a id="photo-edit-link" href="{{$tools.edit.0}}" title="{{$tools.edit.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-pencil"></i>
		</a>
		<span class="icon-padding"> </span>
		<a id="photo-toprofile-link" href="{{$tools.profile.0}}" title="{{$tools.profile.1}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-user"></i>
		</a>
		{{/if}}
		{{if $lock}}
		<span class="icon-padding"> </span>
		<a id="photo-lock-link" onclick="lockview(event,'photo/{{$id}}');" title="{{$lock}}" data-toggle="tooltip">
			<i class="page-action faded-icon fa fa-lock"></i>
		</a>
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
			<span class="p-category">{{$t.name}}</span>
			{{if $t.removeurl}} (<a href="{{$t.removeurl}}">x</a>) {{/if}}
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
		{{if $edit}}{{$edit}}{{/if}}

		{{if $likebuttons}}
		<div id="photo-like-div">
			{{$likebuttons}}
			{{$like}}
			{{$dislike}}
		</div>
		{{/if}}
		<hr>
	</div>

	{{* Insert the comments *}}
	<div id="photo-comment-wrapper-{{$id}}" class="photo-comment-wrapper">
		{{$comments}}
	</div>

	{{$paginate}}
</div>
