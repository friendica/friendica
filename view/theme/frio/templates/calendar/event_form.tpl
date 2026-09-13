{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<header id="event-header">
		<div class="section-title-wrapper">
			<h2 class="heading">{{$title}}</h2>
		</div>

		<div class="event-actions">
			<a class="btn btn-primary photos-upload-link page-action" href="calendar">
				<i class="ri ri-reply-line"></i>
				{{$back_text}}
			</a>
		</div>
	</header>

	{{* The event edit navigation menu (text input, permissions, preview, filebrowser) *}}
	<ul id="event-nav" class="nav nav-tabs event-nav" role="menubar" data-tabs="tabs">
		{{* Mark the first list entry as active because it is the first which is active after opening
			the modal. Changing of the activity status is done by js in calendar_head.tpl *}}
		<li class="active" role="menuitem">
			<a id="event-edit-lnk" onclick="eventEditActive(); return false;">{{$basic}}</a>
		</li>
		{{if $acl}}
		<li role="menuitem" {{if !$sh_checked}} style="display: none"{{/if}}>
			<a id="event-perms-lnk" onclick="eventAclActive(); return false;">{{$permissions}}</a>
		</li>
		{{/if}}
		{{if $preview}}
		<li role="menuitem">
			<a id="event-preview-lnk" onclick="eventPreviewActive(); return false;">{{$preview}}</a>
		</li>
		{{/if}}
		{{* commented out because it isn't implemented yet
		<li role="menuitem"><a id="event-preview-link" onclick="fbrowserActive(); return false;"> Browser </a></li>
		*}}
	</ul>

	<div id="event-edit-form-wrapper">
	<form id="event-edit-form" action="{{$post}}" method="post">

		<input type="hidden" name="event_id" value="{{$eid}}" />
		<input type="hidden" name="cid" value="{{$cid}}" />
		<input type="hidden" name="uri" value="{{$uri}}" />
		<input type="hidden" name="preview" id="event-edit-preview" value="0" />

		{{* The tab content with the necessary basic settings *}}
		<div id="event-edit-wrapper">

			{{* The event title *}}
			{{include file="field_input.tpl" field=$summary}}

			<div id="event-edit-time">
				<h3>{{$section_time}}</h3>
				<p>{{$timezone_help nofilter}}</p>
				{{* checkbox if the event doesn't have a finish time *}}
				{{include file="field_checkbox.tpl" field=$nofinish}}
				<section>
					<div>
						{{* The field for event starting time *}}
						{{$s_dsel nofilter}}
					</div>
					<div>
						{{* The field for event finish time *}}
						{{$f_dsel nofilter}}
					</div>
				</section>
			</div>

				<h3>{{$section_additional}}</h3>

			{{* The textarea for the event location *}}
			<div class="form-group">
				<label for="comment-edit-text-loc">{{$l_text}}</label>
				<textarea id="comment-edit-text-loc" class="form-control text-autosize" name="location" placeholder="{{$l_placeholder}}" rows="1" dir="auto">{{$l_orig}}</textarea>
				<p><small class="help-block">{{$no_formatting}}</small></p>
			</div>

			{{* The textarea for the event description *}}
			<div class="form-group">
				<div id="event-desc-text"><b>{{$d_text}}</b></div>
				<div id="event-desc-text-edit-bb" class="comment-edit-bb comment-icon-list">
					<div class="btn-group">
						<button type="button" class="btn btn-default icon bb-img" style="cursor: pointer;" title="{{$edimg}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="desc">
							<i class="ri ri-image-line"></i>
						</button>
						<button type="button" class="btn btn-default emojis" style="cursor: pointer;" aria-label="{{$edemojis}}" title="{{$edemojis}}">
							<i class="ri ri-emotion-line"></i>
						</button>
					</div>

					<div class="btn-group">
						<button type="button" class="btn btn-default icon bb-url" style="cursor: pointer;" title="{{$edurl}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="desc">
							<i class="ri ri-link"></i>
						</button>
						<button type="button" class="btn btn-default icon underline" style="cursor: pointer;" title="{{$eduline}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="desc">
							<i class="ri ri-underline"></i>
						</button>
						<button type="button" class="btn btn-default icon italic" style="cursor: pointer;" title="{{$editalic}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="desc">
							<i class="ri ri-italic"></i>
						</button>
						<button type="button" class="btn btn-default icon bold" style="cursor: pointer;"  title="{{$edbold}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="desc">
							<i class="ri ri-bold"></i>
						</button>
						<button type="button" class="btn btn-default icon quote" style="cursor: pointer;" title="{{$edquote}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="desc">
							<i class="ri ri-double-quotes-l"></i>
						</button>
						<button type="button" class="btn btn-default icon code" style="cursor: pointer;" title="{{$edcode}}" data-role="insert-formatting" data-comment=" " data-bbcode="code" data-id="desc">
							<i class="ri ri-code-line"></i>
						</button>
					</div>
				</div>
				<textarea id="comment-edit-text-desc" class="form-control text-autosize emojis-target" placeholder="{{$d_placeholder}}" name="desc" rows="3" dir="auto">{{$d_orig}}</textarea>
			</div>

			{{* checkbox to enable event sharing and the permissions tab *}}
			<div class="pull-right share-checkbox">
			{{if ! $eid}}
			{{include file="field_checkbox.tpl" field=$share}}
			{{/if}}

			{{* The submit button - saves the event *}}
				<button id="event-submit" type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
			</div>
			<div class="clear"></div>
		</div>

		{{* The tab for the permissions (if event sharing is enabled) *}}
		<div id="event-acl-wrapper" style="display: none">
			{{$acl nofilter}}
		</div>

		{{* The tab for the event preview (content is inserted by js) *}}
		<div id="event-preview" style="display: none"></div>

		<div class="clear"></div>

	</form>
	</div>
</div>

<script type="text/javascript">
	window.onDocumentReady('body', function() {
		// disable finish date input if it isn't available
		enableDisableFinishDate();
		// load bbcode autocomplete for the description textarea
		$('#comment-edit-text-desc').bbco_autocomplete('bbcode');
	});
</script>
