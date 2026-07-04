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
		 <small>{{$to_desc}}</small>
	</div>

	{{* The subject input field *}}
	<div id="prvmail-subject-label" class="form-group">
	  <input type="text" id="prvmail-subject" class="form-control" placeholder="{{$subject}}" name="subject" value="{{$subjtxt}}" {{$readonly}} tabindex="11" />
	</div>

	<div id="prvmail-text-edit-bb" class="comment-edit-bb comment-icon-list">
		<div class="btn-group">
			<button type="button" class="btn btn-default icon bb-img" style="cursor: pointer;" title="{{$edimg}}" data-role="insert-formatting" data-comment=" " data-bbcode="imgprv" data-id="input">
					<i class="ri ri-image-line" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default emojis" style="cursor: pointer;" aria-label="{{$edemojis}}" title="{{$edemojis}}">
				<i class="ri ri-emotion-line"></i>
			</button>
		</div>
	 <div class="btn-group">
			<button type="button" class="btn btn-default icon bb-url" style="cursor: pointer;" title="{{$edurl}}" data-role="insert-formatting" data-comment=" " data-bbcode="url" data-id="input">
					<i class="ri ri-link" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default icon underline" style="cursor: pointer;" title="{{$eduline}}" data-role="insert-formatting" data-comment=" " data-bbcode="u" data-id="input">
				<i class="ri ri-underline" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default icon italic" style="cursor: pointer;" title="{{$editalic}}" data-role="insert-formatting" data-comment=" " data-bbcode="i" data-id="input">
				<i class="ri ri-italic" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default icon bold" style="cursor: pointer;"  title="{{$edbold}}" data-role="insert-formatting" data-comment=" " data-bbcode="b" data-id="input">
				<i class="ri ri-bold" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default icon quote" style="cursor: pointer;" title="{{$edquote}}" data-role="insert-formatting" data-comment=" " data-bbcode="quote" data-id="input">
				<i class="ri ri-double-quotes-l" aria-hidden="true"></i>
			</button>
			<button type="button" class="btn btn-default icon code" style="cursor: pointer;" title="{{$edquote}}" data-role="insert-formatting" data-comment=" " data-bbcode="code" data-id="input">
				<i class="ri ri-code-line" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	{{* The message input field which contains the message text *}}
	<textarea class="prvmail-text form-control text-autosize" id="comment-edit-text-input" name="body" placeholder="{{$yourmessage}}" tabindex="12" dir="auto" onkeydown="sendOnCtrlEnter(event, 'prvmail-submit')">{{$text}}</textarea>

	{{* The submit button *}}
	<div id="prvmail-submit-wrapper">
		<button type="submit" id="prvmail-submit" name="submit" value="{{$submit}}" class="btn btn-primary pull-right"  tabindex="13">
			<i class="ri ri-mail-line ri-fw" aria-hidden="true"></i>
			{{$submit}}
		</button>
	</div>

	<div id="prvmail-end"></div>

</form>
</div>
