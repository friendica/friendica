
<h3>{{$reply.header}}</h3>

<div id="prvmail-wrapper" >
<form id="prvmail-form" action="message" method="post" >

{{$reply.parent}}

<div id="prvmail-to-label">{{$reply.to}}</div>

{{if $reply.showinputs}}
<input type="text" id="recip" name="messagerecip" value="{{$reply.prefill}}" maxlength="255" size="64" tabindex="10" />
<input type="hidden" id="recip-complete" name="messageto" value="{{$reply.preid}}">
{{else}}
{{$reply.select}}
{{/if}}

<div id="prvmail-subject-label">{{$reply.subject}}</div>
<input type="text" size="64" maxlength="255" id="prvmail-subject" name="subject" value="{{$reply.subjtxt}}" {{$reply.readonly}} tabindex="11" />

<div id="prvmail-message-label">{{$reply.yourmessage}}</div>
<textarea rows="8" cols="72" class="prvmail-text" id="prvmail-text" name="body" tabindex="12">{{$reply.text}}</textarea>


<div id="prvmail-submit-wrapper" >
	<input type="submit" id="prvmail-submit" name="submit" value="{{$reply.submit}}" tabindex="13" />
	<div id="prvmail-upload-wrapper" >
		<div id="prvmail-upload" class="icon border camera" title="{{$reply.upload}}" ></div>
	</div> 
	<div id="prvmail-link-wrapper" >
		<div id="prvmail-link" class="icon border link" title="{{$reply.insert}}" onclick="jotGetLink();" ></div>
	</div> 
	<div id="prvmail-rotator-wrapper" >
		<img id="prvmail-rotator" src="images/rotator.gif" alt="{{$reply.wait}}" title="{{$reply.wait}}" style="display: none;" />
	</div> 
</div>
<div id="prvmail-end"></div>
</form>
</div>
