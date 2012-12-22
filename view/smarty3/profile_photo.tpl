<h1>{{$title}}</h1>

<form enctype="multipart/form-data" action="profile_photo" method="post">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<div id="profile-photo-upload-wrapper">
<label id="profile-photo-upload-label" for="profile-photo-upload">{{$lbl_upfile}} </label>
<input name="userfile" type="file" id="profile-photo-upload" size="48" />
</div>

<label id="profile-photo-profiles-label" for="profile-photo-profiles">{{$lbl_profiles}} </label>
<select name="profile" id="profile-photo-profiles" />
{{foreach $profiles as $p}}
<option value="{{$p.id}}" {{if $p.default}}selected="selected"{{/if}}>{{$p.name}}</option>
{{/foreach}}
</select>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="{{$submit}}">
</div>

</form>

<div id="profile-photo-link-select-wrapper">
{{$select}}
</div>