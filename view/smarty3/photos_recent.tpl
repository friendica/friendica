<h3>{{$title}}</h3>
{{if $can_post}}
<a id="photo-top-upload-link" href="{{$upload.1}}">{{$upload.0}}</a>
{{/if}}

<div class="photos">
{{foreach $photos as $ph}}
	{{include file="photo_top.tpl" photo=$ph}}
{{/foreach}}
</div>
<div class="photos-end"></div>
