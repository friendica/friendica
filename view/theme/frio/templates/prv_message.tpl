{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="prvmail-wrapper">
<form id="prvmail-form" action="message" method="post">

	{{$parent nofilter}}

	{{* The To: form-group which contains the message recipient *}}
	<div id="prvmail-to-label" class="form-group">
		<label for="recipient">{{$to}}</label><br>
		{{$select nofilter}}
	</div>

	{{* The subject input field *}}
	<div id="prvmail-subject-label" class="form-group">
		<label for="prvmail-subject">{{$subject}}</label>
		<input type="text" id="prvmail-subject" class="form-control" name="subject" value="{{$subjtxt}}" {{$readonly}} tabindex="11" />
	</div>

	{{* The message input field which contains the message text *}}
	<div id="prvmail-message-label" class="form-group">
		<label for="comment-edit-text-input">{{$yourmessage}}</label>
		<textarea class="prvmail-text form-control text-autosize" id="comment-edit-text-input" name="body" tabindex="12" dir="auto" onkeydown="sendOnCtrlEnter(event, 'prvmail-submit')">{{$text}}</textarea>
	</div>

	<ul id="prvmail-text-edit-bb" class="comment-edit-bb comment-icon-list nav nav-pills hidden-xs pull-left">
				<li>
					<button type="button" class="btn-link icon bb-img" style="cursor: pointer;" title="{{$edimg}}" data-role="insert-formatting" data-comment=" " data-bbcode="imgprv" data-id="input">
						<i class="fa fa-picture-o" aria-hidden="true"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-url" style="cursor: pointer;" title="{{$edurl}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="input">
						<i class="fa fa-link" aria-hidden="true"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bb-video" style="cursor: pointer;" title="{{$edvideo}}" data-role="insert-formatting" data-comment=" " data-bbcode="video" data-id="input">
						<i class="fa fa-video-camera" aria-hidden="true"></i>
					</button>
				</li>

				<li>
					<button type="button" class="btn-link icon underline" style="cursor: pointer;" title="{{$eduline}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="input">
						<i class="fa fa-underline" aria-hidden="true"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon italic" style="cursor: pointer;" title="{{$editalic}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="input">
						<i class="fa fa-italic" aria-hidden="true"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon bold" style="cursor: pointer;"  title="{{$edbold}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="input">
						<i class="fa fa-bold" aria-hidden="true"></i>
					</button>
				</li>
				<li>
					<button type="button" class="btn-link icon quote" style="cursor: pointer;" title="{{$edquote}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="input">
						<i class="fa fa-quote-left" aria-hidden="true"></i>
					</button>
				</li>
			</ul>

	<div id="prvmail-text-bb-end"></div>

	{{* The submit button *}}
	<div id="prvmail-submit-wrapper">
		<button type="submit" id="prvmail-submit" name="submit" value="{{$submit}}" class="btn btn-primary pull-right"  tabindex="13">
			<i class="fa fa-slideshare fa-fw" aria-hidden="true"></i>
			{{$submit}}
		</button>
	</div>

	<div id="prvmail-end"></div>

</form>
</div>
