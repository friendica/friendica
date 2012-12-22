<form action="invite" method="post" id="invite-form" >
<div id="invite-wrapper">

<h3>{{$invite}}</h3>

<div id="invite-recipient-text">
{{$addr_text}}
</div>

<div id="invite-recipient-textarea">
<textarea id="invite-recipients" name="recipients" rows="8" cols="32" ></textarea>
</div>

<div id="invite-message-text">
{{$msg_text}}
</div>

<div id="invite-message-textarea">
<textarea id="invite-message" name="message" rows="10" cols="72" >{{$default_message}}</textarea>
</div>

<div id="invite-submit-wrapper">
<input type="submit" name="submit" value="{{$submit}}" />
</div>

</div>
</form>
