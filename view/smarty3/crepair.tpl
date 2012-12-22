
<form id="crepair-form" action="crepair/{{$contact_id}}" method="post" >

<h4>{{$contact_name}}</h4>

<label id="crepair-name-label" class="crepair-label" for="crepair-name">{{$label_name}}</label>
<input type="text" id="crepair-name" class="crepair-input" name="name" value="{{$contact_name}}" />
<div class="clear"></div>

<label id="crepair-nick-label" class="crepair-label" for="crepair-nick">{{$label_nick}}</label>
<input type="text" id="crepair-nick" class="crepair-input" name="nick" value="{{$contact_nick}}" />
<div class="clear"></div>

<label id="crepair-attag-label" class="crepair-label" for="crepair-attag">{{$label_attag}}</label>
<input type="text" id="crepair-attag" class="crepair-input" name="attag" value="{{$contact_attag}}" />
<div class="clear"></div>

<label id="crepair-url-label" class="crepair-label" for="crepair-url">{{$label_url}}</label>
<input type="text" id="crepair-url" class="crepair-input" name="url" value="{{$contact_url}}" />
<div class="clear"></div>

<label id="crepair-request-label" class="crepair-label" for="crepair-request">{{$label_request}}</label>
<input type="text" id="crepair-request" class="crepair-input" name="request" value="{{$request}}" />
<div class="clear"></div>
 
<label id="crepair-confirm-label" class="crepair-label" for="crepair-confirm">{{$label_confirm}}</label>
<input type="text" id="crepair-confirm" class="crepair-input" name="confirm" value="{{$confirm}}" />
<div class="clear"></div>

<label id="crepair-notify-label" class="crepair-label" for="crepair-notify">{{$label_notify}}</label>
<input type="text" id="crepair-notify" class="crepair-input" name="notify" value="{{$notify}}" />
<div class="clear"></div>

<label id="crepair-poll-label" class="crepair-label" for="crepair-poll">{{$label_poll}}</label>
<input type="text" id="crepair-poll" class="crepair-input" name="poll" value="{{$poll}}" />
<div class="clear"></div>

<label id="crepair-photo-label" class="crepair-label" for="crepair-photo">{{$label_photo}}</label>
<input type="text" id="crepair-photo" class="crepair-input" name="photo" value="" />
<div class="clear"></div>

<input type="submit" name="submit" value="{{$lbl_submit}}" />

</form>


