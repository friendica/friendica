

<div id="profile-jot-wrapper" >
	<div id="profile-jot-banner-wrapper">
		<div id="profile-jot-desc" >&nbsp;</div>
		<div id="character-counter" class="grey"></div>
	</div>
	<div id="profile-jot-banner-end"></div>

	<form id="profile-jot-form" action="{{$action}}" method="post" >
		<input type="hidden" name="type" value="{{$ptyp}}" />
		<input type="hidden" name="profile_uid" value="{{$profile_uid}}" />
		<input type="hidden" name="return" value="{{$return_path}}" />
		<input type="hidden" name="location" id="jot-location" value="{{$defloc}}" />
		<input type="hidden" name="coord" id="jot-coord" value="" />
		<input type="hidden" name="post_id" value="{{$post_id}}" />
		<input type="hidden" name="preview" id="jot-preview" value="0" />
		<input type="hidden" name="post_id_random" value="{{$rand_num}}" />
		{{if $notes_cid}}
		<input type="hidden" name="contact_allow[]" value="<{{$notes_cid}}>" />
		{{/if}}
		<div id="jot-title-wrap"><input name="title" id="jot-title" type="text" placeholder="{{$placeholdertitle}}" value="{{$title}}" class="jothidden" style="display:none"></div>
		{{if $placeholdercategory}}
		<div id="jot-category-wrap"><input name="category" id="jot-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="jothidden" style="display:none" /></div>
		{{/if}}
		<div id="jot-text-wrap">
		<img id="profile-jot-text-loading" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
		<textarea rows="5" cols="64" class="profile-jot-text" id="profile-jot-text" name="body" placeholder="{{$share}}">{{if $content}}{{$content}}{{/if}}</textarea>
		</div>

<div id="profile-jot-submit-wrapper" class="jothidden">
	<input type="submit" id="profile-jot-submit" name="submit" value="{{$share}}" />

	<div id="profile-rotator-wrapper" style="display: {{$visitor}};" >
		<img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
	</div>

	<div id="profile-upload-wrapper" style="display: {{$visitor}};" >
		<div id="wall-image-upload-div" ><a href="#" onclick="return false;" id="wall-image-upload" class="icon camera" title="{{$upload}}"></a></div>
	</div>
	<div id="profile-attach-wrapper" style="display: {{$visitor}};" >
		<div id="wall-file-upload-div" ><a href="#" onclick="return false;" id="wall-file-upload" class="icon attach" title="{{$attach}}"></a></div>
	</div>

	{{*<!--<div id="profile-link-wrapper" style="display: {{$visitor}};" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >-->*}}
	<div id="profile-link-wrapper" style="display: {{$visitor}};" >
		<a id="profile-link" class="icon link" title="{{$weblink}}" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;"></a>
	</div>
	<div id="profile-video-wrapper" style="display: {{$visitor}};" >
		<a id="profile-video" class="icon video" title="{{$video}}" onclick="jotVideoURL();return false;"></a>
	</div>
	<div id="profile-audio-wrapper" style="display: {{$visitor}};" >
		<a id="profile-audio" class="icon audio" title="{{$audio}}" onclick="jotAudioURL();return false;"></a>
	</div>
	<div id="profile-location-wrapper" style="display: {{$visitor}};" >
		<a id="profile-location" class="icon globe" title="{{$setloc}}" onclick="jotGetLocation();return false;"></a>
	</div>
	<div id="profile-nolocation-wrapper" style="display: none;" >
		<a id="profile-nolocation" class="icon noglobe" title="{{$noloc}}" onclick="jotClearLocation();return false;"></a>
	</div>

	<div id="profile-jot-perms" class="profile-jot-perms" style="display: {{$pvisit}};" >
		<a href="#profile-jot-acl-wrapper" id="jot-perms-icon" class="icon {{$lockstate}}"  title="{{$permset}}" ></a>{{$bang}}
	</div>

	<span onclick="preview_post();" id="jot-preview-link" class="fakelink">{{$preview}}</span>

	<div id="profile-jot-perms-end"></div>


	<div id="profile-jot-plugin-wrapper">
  	{{$jotplugins}}
	</div>

{{*<!--	<span id="jot-display-location" style="display: none;"></span>-->*}}

	<div id="jot-preview-content" style="display:none;"></div>

	<div style="display: none;">
		<div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
			{{$acl}}
		</div>
	</div>


</div>

<div id="profile-jot-end"></div>
</form>
</div>
		{{if $content}}<script>window.jotInit = true;</script>{{/if}}
