
<h3>{{$pagename}}</h3>

<div id="photos-usage-message">{{$usage}}</div>

<form action="photos/{{$nickname}}" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form" >
	<div id="photos-upload-new-wrapper" >
		<div id="photos-upload-newalbum-div">
			<label id="photos-upload-newalbum-text" for="photos-upload-newalbum" >{{$newalbum}}</label>
		</div>
		<input id="photos-upload-newalbum" type="text" name="newalbum" />
	</div>
	<div id="photos-upload-new-end"></div>
	<div id="photos-upload-exist-wrapper">
		<div id="photos-upload-existing-album-text">{{$existalbumtext}}</div>
		<select id="photos-upload-album-select" name="album" size="4">
		{{$albumselect}}
		</select>
	</div>
	<div id="photos-upload-exist-end"></div>

	<div id="photos-upload-noshare-div" class="photos-upload-noshare-div" >
		<input id="photos-upload-noshare" type="checkbox" name="not_visible" value="1" checked/>
		<label id="photos-upload-noshare-text" for="photos-upload-noshare" >{{$nosharetext}}</label>
	</div>


	<div id="photos-upload-perms" class="photos-upload-perms" >
		<a href="#photos-upload-permissions-wrapper" id="photos-upload-perms-menu" class="button popupbox" />
		<span id="jot-perms-icon" class="icon {{$lockstate}}" ></span>{{$permissions}}
		</a>
	</div>
	<div id="photos-upload-perms-end"></div>

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

