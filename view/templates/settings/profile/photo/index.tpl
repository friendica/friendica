<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>

	<h2>{{$current_picture}}</h2>
	<p><img src="{{$avatar}}" alt="{{$current_picture}}"/></p>
	<h2>{{$upload_picture}}</h2>
	<form enctype="multipart/form-data" action="settings/profile/photo" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div id="profile-photo-upload-wrapper" class="form-group field input">
			<label id="profile-photo-upload-label" for="profile-photo-upload">{{$lbl_upfile}} </label>
			<input class="form-control" name="userfile" type="file" id="profile-photo-upload" size="48">
			<div class="clear"></div>
		</div>

		<div id="profile-photo-submit-wrapper" class="pull-right settings-submit-wrapper">
			<button type="submit" name="submit" id="profile-photo-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
		</div>
		<div class="clear"></div>
	</form>

	<p id="profile-photo-link-select-wrapper">
	{{$select nofilter}}
	</p>
</div>