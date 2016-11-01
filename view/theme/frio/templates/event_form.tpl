<script language="javascript" type="text/javascript"
	  src="{{$baseurl}}/view/theme/frio/js/event_edit.js"></script>
<div id="event-form-wrapper">
	<h3 class="heading">{{$title}}</h3>

	{{* The event edit navigation menu (text input, permissions, preview, filebrowser) *}}
	<ul id="event-nav" class="nav nav-tabs event-nav" role="menubar" data-tabs="tabs">
		{{* Mark the first list entry as active because it is the first which is active after opening
			the modal. Changing of the activity status is done by js in event_head.tpl *}}
		<li class="active" role="menuitem"><a id="event-edit-lnk" onclick="eventEditActive(); return false;">{{$basic}}</a></li>
		<li role="menuitem"><a id="event-desc-lnk" onclick="eventDescActive(); return false;">{{$advanced}}</a></li>
		{{if $acl}}<li role="menuitem" {{if !$sh_checked}} style="display: none"{{/if}}><a id="event-perms-lnk" onclick="eventAclActive();return false;">{{$permissions}}</a></li>{{/if}}
		{{if $preview}}<li role="menuitem"><a id="event-preview-lnk" onclick="eventPreviewActive();return false;">{{$preview}}</a></li>{{/if}}
		{{* commented out because it isn't implemented yet
		<li role="menuitem"><a id="event-preview-link" onclick="fbrowserActive(); return false;"> Browser </a></li>
		*}}
	</ul>

	<div id="event-edit-form-wrapper">
	<form id="event-edit-form" action="{{$post}}" method="post" >

		<input type="hidden" name="event_id" value="{{$eid}}" />
		<input type="hidden" name="cid" value="{{$cid}}" />
		<input type="hidden" name="uri" value="{{$uri}}" />
		<input type="hidden" name="preview" id="event-edit-preview" value="0" />

		{{* The tab conten with the necessary basic settings *}}
		<div id="event-edit-wrapper">

			{{* The event title *}}
			{{include file="field_input.tpl" field=$summary}}

			<div id="event-edit-time">
				{{* The field for event starting time *}}
				{{$s_dsel}}

				{{* The field for event finish time *}}
				{{$f_dsel}}

				{{* checkbox if the the event doesn't have a finish time *}}
				{{include file="field_checkbox.tpl" field=$nofinish}}

				{{* checkbox for adjusting the event time to the timezone of the user *}}
				{{include file="field_checkbox.tpl" field=$adjust}}
			</div>

			{{* checkbox to enable event sharing and the permissions tab *}}
			{{if ! $eid}}
			{{include file="field_checkbox.tpl" field=$share}}
			{{/if}}

			{{* The submit button - saves the event *}}
			<div class="pull-right" >
				<button id="event-submit" type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
			</div>
			<div class="clear"></div>
		</div>

		{{* The advanced tab *}}
		<div id="event-desc-wrapper" style="display: none">

			{{* The textarea for the event description *}}
			<div class="form-group">
				<div id="event-desc-text"><b>{{$d_text}}</b></div>
				<textarea id="comment-edit-text-desc" class="form-control text-autosize" name="desc" >{{$d_orig}}</textarea>
				<ul id="event-desc-text-edit-bb" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
					{{* commented out because it isn't implemented yet
					<li>
						<a class="icon" style="cursor: pointer;" title="{{$edimg|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="desc">
							<i class="fa fa-picture-o"></i>
						</a>
					</li>
					*}}
					<li>
						<a class="icon bb-url" style="cursor: pointer;" title="{{$edurl|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="desc">
							<i class="fa fa-link"></i>
						</a>
					</li>
					<li>
						<a class="icon bb-video" style="cursor: pointer;" title="{{$edvideo|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="desc">
							<i class="fa fa-video-camera"></i>
						</a>
					</li>

					<li>
						<a class="icon underline" style="cursor: pointer;" title="{{$eduline|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="desc">
							<i class="fa fa-underline"></i>
						</a>
					</li>
					<li>
						<a class="icon italic" style="cursor: pointer;" title="{{$editalic|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="desc">
							<i class="fa fa-italic"></i>
						</a>
					</li>
					<li>
						<a class="icon bold" style="cursor: pointer;"  title="{{$edbold|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="desc">
							<i class="fa fa-bold"></i>
						</a>
					</li>
					<li>
						<a class="icon quote" style="cursor: pointer;" title="{{$edquote|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="desc">
							<i class="fa fa-quote-left"></i>
						</a>
					</li>
				</ul>
				<div class="clear"></div>
			</div>

			{{* The textarea for the event location *}}
			<div class="form-group">
				<div id="event-location-text"><b>{{$l_text}}</b></div>
				<textarea id="comment-edit-text-loc" class="form-control text-autosize" name="location">{{$l_orig}}</textarea>
				<ul id="comment-tools-loc" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
					{{* commented out because it isn't implemented yet
					<li>
						<a class="icon" style="cursor: pointer;" title="{{$edimg|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="img" data-id="loc">
							<i class="fa fa-picture-o"></i>
						</a>
					</li>
					*}}
					<li>
						<a class="icon bb-url" style="cursor: pointer;" title="{{$edurl|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="loc">
							<i class="fa fa-link"></i>
						</a>
					</li>
					<li>
						<a class="icon bb-video" style="cursor: pointer;" title="{{$edvideo|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="loc">
							<i class="fa fa-video-camera"></i>
						</a>
					</li>

					<li>
						<a class="icon underline" style="cursor: pointer;" title="{{$eduline|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="loc">
							<i class="fa fa-underline"></i>
						</a>
					</li>
					<li>
						<a class="icon italic" style="cursor: pointer;" title="{{$editalic|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="loc">
							<i class="fa fa-italic"></i>
						</a>
					</li>
					<li>
						<a class="icon bold" style="cursor: pointer;"  title="{{$edbold|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="loc">
							<i class="fa fa-bold"></i>
						</a>
					</li>
					<li>
						<a class="icon quote" style="cursor: pointer;" title="{{$edquote|escape:'html'}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="loc">
							<i class="fa fa-quote-left"></i>
						</a>
					</li>
				</ul>
				<div class="clear"></div>
			</div>
		</div>

		{{* The tab for the permissions (if event sharing is enabled) *}}
		<div id="event-acl-wrapper" style="display: none">
			{{$acl}}
		</div>

		{{* The tab for the event preview (content is inserted by js) *}}
		<div id="event-preview" style="display: none"></div>

		<div class="clear"></div>

	</form>
	</div>
</div>

<script language="javascript" type="text/javascript">
	$(document).ready( function() {
		// disable finish date input if it isn't available
		enableDisableFinishDate();
		// load bbcode autocomplete for the description textarea
		$('#comment-edit-text-desc, #comment-edit-text-loc').bbco_autocomplete('bbcode');

		// initiale autosize for the textareas
		autosize($("textarea.text-autosize"));
	});
</script>