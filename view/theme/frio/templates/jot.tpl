{{* The button to open the jot - in This theme we move the button with js to the second nav bar *}}
<button class="btn btn-sm btn-main pull-right" id="jotOpen" aria-label="{{$new_post}}" title="{{$new_post}}" onclick="jotShow();"><i class="fa fa-pencil-square-o fa-2x"></i></button>
<a class="btn btn-sm btn-main pull-right" id="composeOpen" href="compose/{{$posttype}}{{if $content}}?body={{$content}}{{/if}}" aria-label="{{$new_post}}" title="{{$new_post}}"><i class="fa fa-pencil-square-o fa-2x"></i></a>

<div id="jot-content">
	<div id="jot-sections">
		<div class="modal-header">
			{{* Note: We need 2 modal close buttons here to bypass a bug in bootstrap.
			The second is for mobile view. The first one doesnt work with dropdowns. To get a working close button
			in with dropdows the close button needs to be inserted after the dropdown. *}}
			<button type="button" class="close hidden-xs" data-dismiss="modal" aria-label="Close" style="float: right;">&times;</button>

			{{* The Jot navigation menu for desktop user (text input, permissions, preview, filebrowser) *}}
			<ul class="nav nav-tabs hidden-xs jot-nav" role="tablist" data-tabs="tabs">
				{{* Mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
				<li class="active" role="presentation">
					<a href="#profile-jot-wrapper" class="jot-text-lnk jot-nav-lnk" id="jot-text-lnk" role="tab" aria-controls="profile-jot-wrapper">{{$message}}</a>
				</li>
				{{if $acl}}
				<li role="presentation">
					<a href="#profile-jot-acl-wrapper" class="jot-perms-lnk jot-nav-lnk" id="jot-perms-lnk" role="tab" aria-controls="profile-jot-acl-wrapper">{{$shortpermset}}</a>
				</li>
				{{/if}}
				{{if $preview}}
				<li role="presentation">
					<a href="#jot-preview-content" class="jot-preview-lnk jot-nav-lnk" id="jot-preview-lnk" role="tab" aria-controls="jot-preview-content">{{$preview}}</a>
				</li>
				{{/if}}
				<li role="presentation">
					<a href="#jot-fbrowser-wrapper" class="jot-browser-lnk jot-nav-lnk" id="jot-browser-link" role="tab" aria-controls="jot-fbrowser-wrapper">{{$browser}}</a>
				</li>
			</ul>

			{{* The Jot navigation menu for small displays (text input, permissions, preview, filebrowser) *}}
			<div class="dropdown dropdown-head dropdown-mobile-jot jot-nav hidden-lg hidden-md hidden-sm" role="menubar" data-tabs="tabs" style="float: left;">
				<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true">{{$message}}&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu nav nav-pills" aria-label="submenu">
					{{* mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
					<li role="presentation" style="display: none;">
						<button class="jot-text-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-text-lnk-mobile" aria-controls="profile-jot-wrapper" role="menuitem">{{$message}}</button>
					</li>
					{{if $acl}}
					<li role="presentation">
						<button class="jot-perms-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-perms-lnk-mobile" aria-controls="profile-jot-acl-wrapper" role="menuitem">{{$shortpermset}}</button>
					</li>
					{{/if}}
					{{if $preview}}
					<li role="presentation">
						<button class="jot-preview-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-preview-lnk-mobile" aria-controls="jot-preview-content" role="menuitem">{{$preview}}</button>
					</li>
					{{/if}}
					<li role="presentation">
						<button class="jot-browser-lnk-mobile btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-browser-lnk-mobile" aria-controls="jot-fbrowser-wrapper" role="menuitem">{{$browser}}</button>
					</li>
				</ul>
			</div>
			<button type="button" class="close hidden-lg hidden-md hidden-sm" data-dismiss="modal" style="float: right;">&times;</button>
		</div>

		<div id="jot-modal-body" class="modal-body">
			<form id="profile-jot-form" action="{{$action}}" method="post">
				<div id="profile-jot-wrapper" aria-labelledby="jot-text-lnk" role="tabpanel" aria-hidden="false">
					<div>
						<!--<div id="profile-jot-desc" class="jothidden pull-right">&nbsp;</div>-->
					</div>

					<div id="profile-jot-banner-end"></div>

					{{* The hidden input fields which submit important values with the post *}}
					<input type="hidden" name="jot" value="{{$jot}}" />
					<input type="hidden" name="wall" value="{{$wall}}" />
					<input type="hidden" name="post_type" value="{{$posttype}}" />
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
						<textarea rows="2" cols="64" class="profile-jot-text form-control text-autosize" id="profile-jot-text" name="body" placeholder="{{$share}}" onFocus="jotTextOpenUI(this);" onBlur="jotTextCloseUI(this);" style="min-width:100%; max-width:100%;">{{if $content}}{{$content nofilter}}{{/if}}</textarea>
					</div>

					<ul id="profile-jot-submit-wrapper" class="jothidden nav nav-pills">
						<li role="presentation"><button type="button" class="hidden-xs btn-link icon underline" style="cursor: pointer;" aria-label="{{$eduline}}" title="{{$eduline}}" onclick="insertFormattingToPost('u');"><i class="fa fa-underline"></i></button></li>
						<li role="presentation"><button type="button" class="hidden-xs btn-link icon italic" style="cursor: pointer;" aria-label="{{$editalic}}" title="{{$editalic}}" onclick="insertFormattingToPost('i');"><i class="fa fa-italic"></i></button></li>
						<li role="presentation"><button type="button" class="hidden-xs btn-link icon bold" style="cursor: pointer;" aria-label="{{$edbold}}" title="{{$edbold}}" onclick="insertFormattingToPost('b');"><i class="fa fa-bold"></i></button></li>
						<li role="presentation"><button type="button" class="hidden-xs btn-link icon quote" style="cursor: pointer;" aria-label="{{$edquote}}" title="{{$edquote}}" onclick="insertFormattingToPost('quote');"><i class="fa fa-quote-left"></i></button></li>
						<li role="presentation"><button type="button" class="btn-link icon" style="cursor: pointer;" aria-label="{{$edurl}}" title="{{$edurl}}" onclick="insertFormattingToPost('url');"><i class="fa fa-link"></i></button></li>
						<li role="presentation"><button type="button" class="btn-link" id="profile-attach"  ondragenter="return linkDropper(event);" ondragover="return linkDropper(event);" ondrop="linkDrop(event);" onclick="jotGetLink();" title="{{$edattach}}"><i class="fa fa-paperclip"></i></button></li>
						<li role="presentation"><button type="button" class="btn-link" id="profile-location" onclick="jotGetLocation();" title="{{$setloc}}"><i class="fa fa-map-marker" aria-hidden="true"></i></button></li>
						<!-- TODO: waiting for a better placement
						<li><button type="button" class="btn-link" id="profile-nolocation" onclick="jotClearLocation();" title="{{$noloc}}">{{$shortnoloc}}</button></li>
						-->

						<li role="presentation" class="pull-right"><button class="btn btn-primary" type="submit" id="profile-jot-submit" name="submit" ><i class="fa fa-slideshare fa-fw" aria-hidden="true"></i> {{$share}}</button></li>
						<li role="presentation" id="character-counter" class="grey jothidden text-info pull-right"></li>
						<li role="presentation" id="profile-rotator-wrapper" class="pull-right" style="display: {{$visitor}};" >
							<img role="presentation" id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
						</li>
						<li role="presentation" id="profile-jot-plugin-wrapper">
							{{$jotplugins nofilter}}
						</li>
					</ul>

				</div>

				<div id="profile-jot-acl-wrapper" class="minimize" aria-labelledby="jot-perms-lnk" role="tabpanel" aria-hidden="true">
					{{$acl nofilter}}
				</div>

				<div id="jot-preview-content" class="minimize" aria-labelledby="jot-preview-lnk" role="tabpanel" aria-hidden="true"></div>

				<div id="jot-preview-share" class="minimize" aria-labelledby="jot-preview-lnk" role="tabpanel" aria-hidden="true">
					<ul id="profile-jot-preview-submit-wrapper" class="jothidden nav nav-pills">
						<li role="presentation" class="pull-right"><button class="btn btn-primary" type="submit" id="profile-jot-peview-submit" name="submit" ><i class="fa fa-slideshare fa-fw" aria-hidden="true"></i> {{$share}}</button></li>
					</ul>
				</div>

			</form>

			<div id="jot-fbrowser-wrapper" class="minimize" aria-labelledby="jot-browser-link" role="tabpanel" aria-hidden="true"></div>

			{{if $content}}<script type="text/javascript">initEditor();</script>{{/if}}
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


<script type="text/javascript">
	$('iframe').load(function() {
		this.style.height = this.contentWindow.document.body.offsetHeight + 'px';
	});
</script>
