{{* Template for singele photo view *}}

{{* "live-photos" is needed for js autoupdate *}}
<div id="live-photos"></div>

<div id="photo-view-{{$id}}" class="generic-page-wrapper">
	<h3><a href="{{$album.0}}">{{$album.1}}</a></h3>

	<div id="photo-edit-link-wrap">
		{{if $tools}}
		<a id="photo-edit-link" href="{{$tools.edit.0}}">{{$tools.edit.1}}</a>
		|
		<a id="photo-toprofile-link" href="{{$tools.profile.0}}">{{$tools.profile.1}}</a>
		{{/if}}
		{{if $lock}} | <img src="images/lock_icon.gif" class="lockview" alt="{{$lock}}" onclick="lockview(event,'photo/{{$id}}');" /> {{/if}}
	</div>

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
		<div id="photo-tags">{{$tags.1}}</div>
		{{/if}}

		{{if $tags.2}}
		<div id="tag-remove">
			<a href="{{$tags.2}}">{{$tags.3}}</a>
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
