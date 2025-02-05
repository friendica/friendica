{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{* The button to open the jot - in This theme we move the button with js to the second nav bar *}}
<a class="btn btn-sm btn-primary pull-right{{if !$always_open_compose}} modal-open{{/if}}" id="jotOpen" href="compose/{{$posttype}}{{if $content}}?body={{$content}}{{/if}}" aria-label="{{$new_post}}" title="{{$new_post}}"><i class="fa fa-pencil-square-o fa-2x"></i></a>

<div id="jot-content">
	<div id="jot-sections">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="float: right;">&times;</button>

			<a href="/compose" class="btn compose-link" title="{{$compose_link_title}}" aria-label="{{$compose_link_title}}">
				<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
			</a>

			{{* The Jot navigation menu for desktop user (text input, permissions, preview, filebrowser) *}}
			<ul class="nav nav-tabs hidden-xs jot-nav" role="tablist" data-tabs="tabs">
				{{* Mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
				<li class="active">
					<a href="#profile-jot-wrapper" class="jot-text-lnk jot-nav-lnk" id="jot-text-lnk" role="tab" aria-controls="profile-jot-wrapper">
						<i class="fa fa-file-text-o" aria-hidden="true"></i>
						{{$message}}
					</a>
				</li>
				{{if $acl}}
				<li>
					<a href="#profile-jot-acl-wrapper" class="jot-perms-lnk jot-nav-lnk" id="jot-perms-lnk" role="tab" aria-controls="profile-jot-acl-wrapper">
						<i class="fa fa-shield" aria-hidden="true"></i>
						{{$shortpermset}}
					</a>
				</li>
				{{/if}}
				{{if $preview}}
				<li>
					<a href="#jot-preview-content" class="jot-preview-lnk jot-nav-lnk" id="jot-preview-lnk" role="tab" aria-controls="jot-preview-content">
						<i class="fa fa-eye" aria-hidden="true"></i>
						{{$preview}}
					</a>
				</li>
				{{/if}}
				<li>
					<a href="#jot-fbrowser-wrapper" class="jot-browser-lnk jot-nav-lnk" id="jot-browser-link" role="tab" aria-controls="jot-fbrowser-wrapper">
						<i class="fa fa-picture-o" aria-hidden="true"></i>
						{{$browser}}
					</a>
				</li>
			</ul>

			{{* The Jot navigation menu for small displays (text input, permissions, preview, filebrowser) *}}
			<div class="dropdown dropdown-head dropdown-mobile-jot jot-nav hidden-lg hidden-md hidden-sm" role="menubar" data-tabs="tabs" style="float: left;">
				<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true">{{$message}}&nbsp;<span class="caret"></span></button>
				<ul class="dropdown-menu nav nav-pills" aria-label="submenu">
					{{* mark the first list entry as active because it is the first which is active after opening
					the modal. Changing of the activity status is done by js in jot.tpl-header *}}
					<li style="display: none;">
						<button class="jot-text-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-text-lnk-mobile" aria-controls="profile-jot-wrapper" role="menuitem">{{$message}}</button>
					</li>
					{{if $acl}}
					<li>
						<button class="jot-perms-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-perms-lnk-mobile" aria-controls="profile-jot-acl-wrapper" role="menuitem">{{$shortpermset}}</button>
					</li>
					{{/if}}
					{{if $preview}}
					<li>
						<button class="jot-preview-lnk btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-preview-lnk-mobile" aria-controls="jot-preview-content" role="menuitem">{{$preview}}</button>
					</li>
					{{/if}}
					<li>
						<button class="jot-browser-lnk-mobile btn-link jot-nav-lnk jot-nav-lnk-mobile" id="jot-browser-lnk-mobile" aria-controls="jot-fbrowser-wrapper" role="menuitem">{{$browser}}</button>
					</li>
				</ul>
			</div>
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
					<div id="jot-title-wrap"><input name="title" id="jot-title" class="jothidden jotforms form-control" type="text" placeholder="{{$placeholdertitle}}" title="{{$placeholdertitle}}" value="{{$title}}" style="display:block;" dir="auto" /></div>
					{{if $placeholdercategory}}
					<div id="jot-category-wrap"><input name="category" id="jot-category" class="jothidden jotforms form-control" type="text" placeholder="{{$placeholdercategory}}" title="{{$placeholdercategory}}" value="{{$category}}" dir="auto" /></div>
					{{/if}}

					{{* The jot text field in which the post text is inserted *}}
					<div id="jot-text-wrap" class="dropzone">
						<textarea rows="8" cols="64" class="profile-jot-text form-control text-autosize" id="profile-jot-text" name="body" placeholder="{{$share}}" onFocus="jotTextOpenUI(this);" onBlur="jotTextCloseUI(this);" style="min-width:100%; max-width:100%;" dir="auto" onkeydown="sendOnCtrlEnter(event, 'profile-jot-submit')">{{if $content}}{{$content nofilter}}{{/if}}</textarea>
					</div>

					<ul id="profile-jot-submit-wrapper" class="jothidden nav nav-pills">
						<li><button type="button" class="hidden-xs btn-link icon underline" style="cursor: pointer;" aria-label="{{$eduline}}" title="{{$eduline}}" onclick="insertFormattingToPost('u');"><i class="fa fa-underline"></i></button></li>
						<li><button type="button" class="hidden-xs btn-link icon italic" style="cursor: pointer;" aria-label="{{$editalic}}" title="{{$editalic}}" onclick="insertFormattingToPost('i');"><i class="fa fa-italic"></i></button></li>
						<li><button type="button" class="hidden-xs btn-link icon bold" style="cursor: pointer;" aria-label="{{$edbold}}" title="{{$edbold}}" onclick="insertFormattingToPost('b');"><i class="fa fa-bold"></i></button></li>
						<li><button type="button" class="hidden-xs btn-link icon quote" style="cursor: pointer;" aria-label="{{$edquote}}" title="{{$edquote}}" onclick="insertFormattingToPost('quote');"><i class="fa fa-quote-left"></i></button></li>
						<li><button type="button" class="hidden-xs btn-link icon emojis" style="cursor: pointer;" aria-label="{{$edemojis}}" title="{{$edemojis}}"><i class="fa fa-smile-o"></i></button></li>
						<li><button type="button" class="btn-link icon" style="cursor: pointer;" aria-label="{{$edurl}}" title="{{$edurl}}" onclick="insertFormattingToPost('url');"><i class="fa fa-link"></i></button></li>
						<li><button type="button" class="btn-link" id="profile-attach"  ondragenter="return linkDropper(event);" ondragover="return linkDropper(event);" ondrop="linkDrop(event);" onclick="jotGetLink();" title="{{$edattach}}"><i class="fa fa-paperclip"></i></button></li>
						<li><button type="button" class="btn-link" id="profile-location" onclick="jotGetLocation();" title="{{$setloc}}"><i class="fa fa-map-marker" aria-hidden="true"></i></button></li>
						<li><button type="button" class="hidden-xs btn-link icon underline" style="cursor: pointer;" aria-label="{{$contentwarn}}" title="{{$contentwarn}}" onclick="insertFormattingToPost('abstract');"><i class="fa fa-eye"></i></button></li>
						<!-- TODO: waiting for a better placement
						<li><button type="button" class="btn-link" id="profile-nolocation" onclick="jotClearLocation();" title="{{$noloc}}">{{$shortnoloc}}</button></li>
						-->

						<li class="pull-right">
							<button class="btn btn-primary" type="submit" id="profile-jot-submit" name="submit" data-loading-text="{{$loading}}">
								<i class="fa fa-paper-plane fa-fw" aria-hidden="true"></i> {{$share}}
							</button>
						</li>
						<li id="character-counter" class="grey jothidden text-info pull-right"></li>
						<li id="profile-rotator-wrapper" class="pull-right" style="display: {{$visitor}};">
							<img id="profile-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
						</li>
						<li id="profile-jot-plugin-wrapper">
							{{$jotplugins nofilter}}
						</li>
					</ul>

				</div>

				<div id="profile-jot-acl-wrapper" class="minimize" aria-labelledby="jot-perms-lnk" role="tabpanel" aria-hidden="true">
					{{$acl nofilter}}
					{{if $scheduled_at}}{{$scheduled_at nofilter}}{{/if}}
					{{if $created_at}}{{$created_at nofilter}}{{/if}}
				</div>

				<div id="jot-preview-content" class="minimize" aria-labelledby="jot-preview-lnk" role="tabpanel" aria-hidden="true"></div>

				<div id="jot-preview-share" class="minimize" aria-labelledby="jot-preview-lnk" role="tabpanel" aria-hidden="true">
					<ul id="profile-jot-preview-submit-wrapper" class="jothidden nav nav-pills">
						<li class="pull-right">
							<button class="btn btn-primary" type="submit" id="profile-jot-preview-submit" name="submit" data-loading-text="{{$loading}}">
								<i class="fa fa-paper-plane fa-fw" aria-hidden="true"></i> {{$share}}
							</button>
						</li>
					</ul>
				</div>

				<div id="jot-fbrowser-wrapper" class="minimize" aria-labelledby="jot-browser-link" role="tabpanel" aria-hidden="true"></div>

			</form>
			<div id="dz-preview-jot" class="dropzone-preview"></div>

			{{if $content}}<script type="text/javascript">initEditor();</script>{{/if}}
		</div>
	</div>
</div>


{{* The jot modal - We use a own modal for the jot and not the standard modal
from the page template. This is because the special structure of the jot
(e.g.jot navigation tabs in the modal title area).
Then in the frio theme the jot will loaded regularly and is hidden by default.)
The js function jotShow() loads the jot into the modal. With this structure we
can load different content into the jot modal (e.g. the item edit jot)
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

<script>
	dzFactory.setupDropzone('#jot-text-wrap', 'profile-jot-text'); 
</script>
