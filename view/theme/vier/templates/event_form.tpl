

<h3>{{$title}}</h3>

<p>
{{$desc}}
</p>

<form id="event-edit-form" action="{{$post}}" method="post" >

<input type="hidden" name="event_id" value="{{$eid}}" />
<input type="hidden" name="cid" value="{{$cid}}" />
<input type="hidden" name="uri" value="{{$uri}}" />
<input type="hidden" name="preview" id="event-edit-preview" value="0" />

{{$s_dsel}}

{{$f_dsel}}

{{include file="field_checkbox.tpl" field=$nofinish}}

{{include file="field_checkbox.tpl" field=$adjust}}

{{include file="field_input.tpl" field=$summary}}


<div id="event-desc-text">{{$d_text}}</div>
<textarea id="comment-edit-text-desc" rows="8" cols="64" name="desc" autocomplete="off">{{$d_orig}}</textarea>
<div id="event-desc-text-edit-bb" class="comment-edit-bb">
	<a title="{{$edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="desc"><i class="icon-picture"></i></a>
	<a title="{{$edurl}}" data-role="insert-formatting" data-bbcode="url" data-id="desc"><i class="icon-link"></i></a>
	<a title="{{$edvideo}}" data-role="insert-formatting" data-bbcode="video" data-id="desc"><i class="icon-film"></i></a>

	<a title="{{$eduline}}" data-role="insert-formatting" data-bbcode="u" data-id="desc"><i class="icon-underline"></i></a>
	<a title="{{$editalic}}" data-role="insert-formatting" data-bbcode="i" data-id="desc"><i class="icon-italic"></i></a>
	<a title="{{$edbold}}" data-role="insert-formatting" data-bbcode="b" data-id="desc"><i class="icon-bold"></i></a>
	<a title="{{$edquote}}" data-role="insert-formatting" data-bbcode="quote" data-id="desc"><i class="icon-quote-left"></i></a>
</div>

<div id="event-location-text">{{$l_text}}</div>
<textarea id="comment-edit-text-location" rows="4" cols="64" name="location">{{$l_orig}}</textarea>
<div id="event-location-text-edit-bb" class="comment-edit-bb">
	<a title="{{$edimg}}" data-role="insert-formatting" data-bbcode="img" data-id="location"><i class="icon-picture"></i></a>
	<a title="{{$edurl}}" data-role="insert-formatting" data-bbcode="url" data-id="location"><i class="icon-link"></i></a>
	<a title="{{$edvideo}}" data-role="insert-formatting" data-bbcode="video" data-id="location"><i class="icon-film"></i></a>

	<a title="{{$eduline}}" data-role="insert-formatting" data-bbcode="u" data-id="location"><i class="icon-underline"></i></a>
	<a title="{{$editalic}}" data-role="insert-formatting" data-bbcode="i" data-id="location"><i class="icon-italic"></i></a>
	<a title="{{$edbold}}" data-role="insert-formatting" data-bbcode="b" data-id="location"><i class="icon-bold"></i></a>
	<a title="{{$edquote}}" data-role="insert-formatting" data-bbcode="quote" data-id="location"><i class="icon-quote-left"></i></a>
</div>

<div id="event-location-break"></div>

{{if ! $eid}}
{{include file="field_checkbox.tpl" field=$share}}
{{/if}}

{{$acl}}

<div class="clear"></div>
<input id="event-edit-preview" type="submit" name="preview" value="{{$preview|escape:'html'}}" onclick="doEventPreview(); return false;" />
<input id="event-submit" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
</form>


