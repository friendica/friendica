<form id="crepair-form" action="crepair/{{$contact_id}}" method="post" >

<h4>{{$contact_name}}</h4>

<div id="contact-update-profile-wrapper">
{{if $update_profile}}
	<span id="contact-update-profile-now" class="button"><a href="contacts/{{$contact_id}}/updateprofile" >{{$udprofilenow}}</a></span>
{{/if}}
</div>

<label id="crepair-name-label" class="crepair-label" for="crepair-name">{{$label_name}}</label>
<input type="text" id="crepair-name" class="crepair-input" name="name" value="{{$contact_name|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-nick-label" class="crepair-label" for="crepair-nick">{{$label_nick}}</label>
<input type="text" id="crepair-nick" class="crepair-input" name="nick" value="{{$contact_nick|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-attag-label" class="crepair-label" for="crepair-attag">{{$label_attag}}</label>
<input type="text" id="crepair-attag" class="crepair-input" name="attag" value="{{$contact_attag|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-url-label" class="crepair-label" for="crepair-url">{{$label_url}}</label>
<input type="text" id="crepair-url" class="crepair-input" name="url" value="{{$contact_url|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-request-label" class="crepair-label" for="crepair-request">{{$label_request}}</label>
<input type="text" id="crepair-request" class="crepair-input" name="request" value="{{$request|escape:'html'}}" />
<div class="clear"></div>
 
<label id="crepair-confirm-label" class="crepair-label" for="crepair-confirm">{{$label_confirm}}</label>
<input type="text" id="crepair-confirm" class="crepair-input" name="confirm" value="{{$confirm|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-notify-label" class="crepair-label" for="crepair-notify">{{$label_notify}}</label>
<input type="text" id="crepair-notify" class="crepair-input" name="notify" value="{{$notify|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-poll-label" class="crepair-label" for="crepair-poll">{{$label_poll}}</label>
<input type="text" id="crepair-poll" class="crepair-input" name="poll" value="{{$poll|escape:'html'}}" />
<div class="clear"></div>

<label id="crepair-photo-label" class="crepair-label" for="crepair-photo">{{$label_photo}}</label>
<input type="text" id="crepair-photo" class="crepair-input" name="photo" value="" />
<div class="clear"></div>
{{if $allow_remote_self eq 1}}
<h4>{{$label_remote_self}}</h4>
{{include file="field_select.tpl" field=$remote_self}}
{{/if}}

<input type="submit" name="submit" value="{{$lbl_submit|escape:'html'}}" />

</form>


