<div class="generic-page-wrapper">

	<h3>{{$pagename}}</h3>

	<div id="photos-usage-message">{{$usage}}</div>

	<form action="photos/{{$nickname}}" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form">
		<div id="photos-upload-div" class="form-group">
			<label id="photos-upload-text" for="photos-upload-newalbum" >{{$newalbum}}</label>

			<input id="photos-upload-album-select" class="form-control" placeholder="{{$existalbumtext}}" list="dl-photo-upload" type="text" name="album" size="4">
			<datalist id="dl-photo-upload">{{$albumselect}}</datalist>
		</div>
		<div id="photos-upload-end" class="clearfix"></div>

		<div id="photos-upload-noshare-div" class="photos-upload-noshare-div pull-left" >
			<input id="photos-upload-noshare" type="checkbox" name="not_visible" value="1" checked/>
			<label id="photos-upload-noshare-text" for="photos-upload-noshare" >{{$nosharetext}}</label>
		</div>

		<div id="photos-upload-perms" class="photos-upload-perms pull-right" >
			<a href="#photos-upload-permissions-wrapper" id="photos-upload-perms-menu" class="button popupbox" />
				<span id="jot-perms-icon" class="icon {{$lockstate}}" ></span>{{$permissions}}
			</a>
		</div>
		<div id="photos-upload-perms-end" class="clear"></div>

		<div style="display: none;">
			<div id="photos-upload-permissions-wrapper">
				{{$aclselect}}
			</div>
		</div>

		<div id="photos-upload-spacer"></div>

		{{$alt_uploader}}

		{{$default_upload_box}}
		{{$default_upload_submit}}

		<div class="photos-upload-end" ></div>
	</form>
</div>
