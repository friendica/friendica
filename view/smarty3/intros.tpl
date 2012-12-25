
<div class="intro-wrapper" id="intro-{{$contact_id}}" >

<p class="intro-desc">{{$str_notifytype}} {{$notify_type}}</p>
<div class="intro-fullname" id="intro-fullname-{{$contact_id}}" >{{$fullname}}</div>
<a class="intro-url-link" id="intro-url-link-{{$contact_id}}" href="{{$url}}" ><img id="photo-{{$contact_id}}" class="intro-photo" src="{{$photo}}" width="175" height=175" title="{{$fullname}}" alt="{{$fullname}}" /></a>
<div class="intro-knowyou">{{$knowyou}}</div>
<div class="intro-note" id="intro-note-{{$contact_id}}">{{$note}}</div>
<div class="intro-wrapper-end" id="intro-wrapper-end-{{$contact_id}}"></div>
<form class="intro-form" action="notifications/{{$intro_id}}" method="post">
<input class="intro-submit-ignore" type="submit" name="submit" value="{{$ignore}}" />
<input class="intro-submit-discard" type="submit" name="submit" value="{{$discard}}" />
</form>
<div class="intro-form-end"></div>

<form class="intro-approve-form" action="dfrn_confirm" method="post">
{{include file="field_checkbox.tpl" field=$hidden}}
{{include file="field_checkbox.tpl" field=$activity}}
<input type="hidden" name="dfrn_id" value="{{$dfrn_id}}" >
<input type="hidden" name="intro_id" value="{{$intro_id}}" >
<input type="hidden" name="contact_id" value="{{$contact_id}}" >

{{$dfrn_text}}

<input class="intro-submit-approve" type="submit" name="submit" value="{{$approve}}" />
</form>
</div>
<div class="intro-end"></div>
