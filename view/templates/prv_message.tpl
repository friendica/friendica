{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}


<h3>{{$header}}</h3>

<div id="prvmail-wrapper">
<form id="prvmail-form" action="message" method="post">

{{$parent nofilter}}

<div id="prvmail-to-label">
{{$to}}<br/>
{{$select nofilter}}<br/>
<small>{{$to_desc}}</small>
</div>

<input type="text" maxlength="255" id="prvmail-subject" name="subject" placeholder="{{$subject}}" value="{{$subjtxt}}" {{$readonly}} tabindex="11" />

<div id="prvmail-message-label">{{$yourmessage}}</div>
<textarea rows="8" cols="72" class="prvmail-text" id="prvmail-text" name="body" placeholder="{{$yourmessage}}" tabindex="12">{{$text}}</textarea>


<div id="prvmail-submit-wrapper">
	<input type="submit" id="prvmail-submit" name="submit" value="{{$submit}}" tabindex="13" />
	<div id="prvmail-upload-wrapper">
		<div id="prvmail-upload" class="icon border camera" title="{{$upload}}"></div>
	</div>
	<div id="prvmail-link-wrapper">
		<div id="prvmail-link" class="icon border link" title="{{$insert}}" onclick="jotGetLink();"></div>
	</div>
	<div id="prvmail-rotator-wrapper">
		<img id="prvmail-rotator" src="images/rotator.gif" alt="{{$wait}}" title="{{$wait}}" style="display: none;" />
	</div>
</div>
<div id="prvmail-end"></div>
</form>
</div>
