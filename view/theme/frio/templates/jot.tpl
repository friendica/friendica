
{{* The button to open the jot - in This theme we move the button with js to the second nav bar *}}
<button class="btn btn-sm btn-main pull-right" id="jotOpen" onclick="jotShow(); return false;"><i class="fa fa-pencil-square-o fa-2x"></i></button>


<div id="jot-content">
	<div id="jot-sections">
		<div class="modal-header">
			{{* Note: We need 2 modal close buttons here to bypass a bug in bootstrap.
			The second is for mobile view. The first one doesnt work with dropdowns. To get a working close button
			in with dropdows the close button needs to be inserted after the dropdown. *}}
			<button type="button" class="close hidden-xs" data-dismiss="modal" style="float: right;">&times;</button>

			{{* The Jot navigation menu (text input, permissions, preview, filebrowser) *}}
			<ul class="nav nav-tabs hidden-xs jot-nav" role="menubar" data-tabs="tabs">
				{{* Mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
				<li class="active" role="menuitem"><a id="jot-text-lnk" class="jot-text-lnk" onclick="jotActive(); return false;">{{$message}}</a></li>
				{{if $acl}}<li role="menuitem"><a id="jot-perms-lnk" class="jot-perms-lnk" onclick="aclActive();return false;">{{$shortpermset}}</a></li>{{/if}}
				{{if $preview}}<li role="menuitem"><a id="jot-preview-lnk" class="jot-preview-lnk" onclick="previewActive();return false;">{{$preview}}</a></li>{{/if}}
				<li role="menuitem"><a id="jot-browser-link" onclick="fbrowserActive(); return false;">{{$browser}}</a></li>
			</ul>

			<div class="dropdown dropdown-head dropdown-mobile-jot jot-nav hidden-lg hidden-md hidden-sm" role="menubar" data-tabs="tabs" style="float: left;">
				<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">{{$message}}&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu nav nav-pills">
					{{* mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
					<li role="menuitem" style="display: none;"><a id="jot-text-lnk-mobile" class="jot-text-lnk" onclick="jotActive(); return false;">{{$message}}</a></li>
					{{if $acl}}<li role="menuitem"><a id="jot-perms-lnk-mobile" class="jot-perms-lnk" onclick="aclActive();return false;">{{$shortpermset}}</a></li>{{/if}}
					{{if $preview}}<li role="menuitem"><a id="jot-preview-lnk-mobile" class="jot-preview-lnk" onclick="previewActive();return false;">{{$preview}}</a></li>{{/if}}
				</ul>
			</div>
			<button type="button" class="close hidden-lg hidden-md hidden-sm" data-dismiss="modal" style="float: right;">&times;</button>
		</div>

		<div id="jot-modal-body" class="modal-body">
			<form id="profile-jot-form" action="{{$action}}" method="post">
				<div id="profile-jot-wrapper">
					<div>
						<div id="character-counter" class="grey jothidden text-info pull-left"></div>
						<!--<div id="profile-jot-desc" class="jothidden pull-right">&nbsp;</div>-->
					</div>

					<div id="profile-jot-banner-end"></div>

					{{* The hidden input fields which submit important values with the post *}}
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
					<div id="jot-title-wrap"><input name="title" id="jot-title" class="jothidden jotforms form-control" type="text" placeholder="{{$placeholdertitle}}" title="{{$placeholdertitle}}" value="{{$title}}" style="display:block;" /></div>
					{{if $placeholdercategory}}
					<div id="jot-category-wrap"><input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$placeholdercategory}}" title="{{$placeholdercategory}}" value="{{$category}}" /></div>
					{{/if}}

					{{* The jot text field in which the post text is inserted *}}
					<div id="jot-text-wrap">
					<textarea rows="2" cols="64" class="profile-jot-text form-control text-autosize" id="profile-jot-text" name="body" onFocus="jotTextOpenUI(this);" onBlur="jotTextCloseUI(this);" style="min-width:100%; max-width:100%;">{{if $content}}{{$content}}{{else}}{{$share}}{{/if}}</textarea>
					</div>

					<ul id="profile-jot-submit-wrapper" class="jothidden nav nav-pills">
						{{* uncomment the button for "wall-immage-upload" because we have integrated it directly in the jot modal
						<li><a href="#" id="wall-image-upload" title="{{$upload}}"><i class="fa fa-picture-o"></i></a></li>
						*}}
						<li><a href="#" onclick="return false;" id="wall-file-upload"  title="{{$attach}}"><i class="fa fa-paperclip"></i></a></li>
						<li><a id="profile-link"  ondragenter="return linkdropper(event);" ondragover="return linkdropper(event);" ondrop="linkdrop(event);" onclick="jotGetLink(); return false;" title="{{$weblink}}"><i class="fa fa-link"></i></a></li>
						<li><a id="profile-video" onclick="jotVideoURL();return false;" title="{{$video}}"><i class="fa fa-film"></i></a></li>
						<li><a id="profile-audio" onclick="jotAudioURL();return false;" title="{{$audio}}"><i class="fa fa-music"></i></a></li>
						<li><a id="profile-location" onclick="jotGetLocation();return false;" title="{{$setloc}}"><i class="fa fa-map-marker"></i></a></li>
						<!-- TODO: waiting for a better placement 
						<li><a id="profile-nolocation" onclick="jotClearLocation();return false;" title="{{$noloc}}">{{$shortnoloc}}</a></li>
						-->

						<li class="pull-right"><button class="btn btn-primary" id="jot-submit" type="submit" id="profile-jot-submit" name="submit" ><i class="fa fa-slideshare fa-fw"></i> {{$share}}</button></li>
						<div id="profile-rotator-wrapper" style="display: {{$visitor}};" >
							<img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
						</div> 
						<div id="profile-jot-plugin-wrapper">
							{{$jotplugins}}
						</div>
					</ul>

				</div>

				<div id="profile-jot-acl-wrapper" style="display: none;">
					{{$acl}}
				</div>

				<div id="jot-preview-content" style="display:none;"></div>
			</form>

			<div id="jot-fbrowser-wrapper" style="display: none"></div>

			{{if $content}}<script>initEditor();</script>{{/if}}
		</div>
	</div>
</div>


{{* The jot modal - We use a own modal for the jot and not the standard modal
from the page template. This is because the special structure of the jot
(e.g.jot navigation tabs in the modal titel area).
The in the frio theme the jot will loaded regulary and is hidden by default.)
The js function jotShow() loads the jot into the modal. With this structure we
can load different content into the jot moadl (e.g. the item edit jot)
*}}
<div id="jot-modal" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div id="jot-modal-content" class="modal-content"></div>
	</div>
</div>


<script language="javascript" type="text/javascript">
	$('iframe').load(function() {
		this.style.height = this.contentWindow.document.body.offsetHeight + 'px';
	});

	// insert new object with value to aStr
	// function jotTextOpenUI does make use of it
	aStr.share = "{{$share}}";
</script>

