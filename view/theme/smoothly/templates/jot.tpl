

<div id="profile-jot-wrapper" >
	<div id="profile-jot-banner-wrapper">
		<div id="profile-jot-desc" >&nbsp;</div>
		<div id="character-counter" class="grey" style="display: none;">0</div>
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
		<div id="jot-title-wrap">
			<input name="title" id="jot-title" type="text" placeholder="{{$placeholdertitle}}" value="{{$title}}" class="jothidden" style="display:none">
		</div>
		{{if $placeholdercategory}}
		<div id="jot-category-wrap">
			<input name="category" id="jot-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="jothidden" style="display:none" />
		</div>
		{{/if}}
		<div id="jot-text-wrap">
                	<img id="profile-jot-text-loading" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" /><br>
                	<textarea rows="5" cols="80" class="profile-jot-text" id="profile-jot-text" name="body" placeholder="{{$share}}">
			{{if $content}}{{$content}}{{/if}}
			</textarea>
		</div>

	<div id="profile-upload-wrapper" class="jot-tool" style="display: none;" >
		<div id="wall-image-upload-div" ><a onclick="return false;" id="wall-image-upload" class="icon border camera" title="{{$upload}}"></a></div>
	</div>
	<div id="profile-attach-wrapper" class="jot-tool" style="display: none;" >
		<div id="wall-file-upload-div" ><a href="#" onclick="return false;" id="wall-file-upload" class="icon border attach" title="{{$attach}}"></a></div>
	</div>
	<div id="profile-link-wrapper" class="jot-tool" style="display: none;" ondragenter="linkdropper(event);" ondragover="linkdropper(event);" ondrop="linkdrop(event);" >
		<a href="#" id="profile-link" class="icon border  link" title="{{$weblink}}" ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;"></a>
	</div>
	<div id="profile-video-wrapper" class="jot-tool" style="display: none;" >
		<a href="#" id="profile-video" class="icon border  video" title="{{$video}}" onclick="jotVideoURL(); return false;"></a>
	</div>
	<div id="profile-audio-wrapper" class="jot-tool" style="display: none;" >
		<a href="#" id="profile-audio" class="icon border  audio" title="{{$audio}}" onclick="jotAudioURL(); return false;"></a>
	</div>
	<div id="profile-location-wrapper" class="jot-tool" style="display: none;" >
		<a href="#" id="profile-location" class="icon border  globe" title="{{$setloc}}" onclick="jotGetLocation(); return false;"></a>
	</div>
	<div id="profile-nolocation-wrapper" class="jot-tool" style="display: none;" >
		<a href="#" id="profile-nolocation" class="icon border  noglobe" title="{{$noloc}}" onclick="jotClearLocation(); return false;"></a>
	</div>

	<span onclick="preview_post();" id="jot-preview-link" class="fakelink" style="display: none;" >{{$preview}}</span>

	<div id="profile-jot-submit-wrapper" style="display:none;padding-left: 400px;">
		<input type="submit" id="profile-jot-submit" name="submit" value="{{$share}}" />
		<div id="profile-jot-perms" class="profile-jot-perms" style="display: {{$pvisit}};" >
            <a href="#profile-jot-acl-wrapper" id="jot-perms-icon" class="icon {{$lockstate}} sharePerms" title="{{$permset}}"></a>{{$bang}}</div>
	</div>

	<div id="profile-jot-plugin-wrapper" style="display: none;">
  	{{$jotplugins}}
	</div>
	<div id="profile-jot-tools-end"></div>

	<div id="jot-preview-content" style="display:none;"></div>

        <div style="display: none;">
            <div id="profile-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;">
                {{$acl}}
                <hr style="clear:both"/>
                <div id="profile-jot-email-label">{{$emailcc}}</div><input type="text" name="emailcc" id="profile-jot-email" title="{{$emtitle}}" />
                <div id="profile-jot-email-end"></div>
                {{$jotnets}}
            </div>
        </div>

<div id="profile-jot-end"></div>
</form>
</div>
                {{if $content}}<script>initEditor();</script>{{/if}}
