{{include file="file:{{$field_select}}" field=$color}}

{{include file="file:{{$field_select}}" field=$font_size}}

{{include file="file:{{$field_select}}" field=$line_height}}

{{include file="file:{{$field_select}}" field=$resolution}}

<div class="settings-submit-wrapper">
	<input type="submit" value="{{$submit}}" class="settings-submit" name="diabook-settings-submit" />
</div>
<br>
<h3>Show/hide boxes at right-hand column</h3>
{{include file="file:{{$field_select}}" field=$close_pages}}
{{include file="file:{{$field_select}}" field=$close_profiles}}
{{include file="file:{{$field_select}}" field=$close_helpers}}
{{include file="file:{{$field_select}}" field=$close_services}}
{{include file="file:{{$field_select}}" field=$close_friends}}
{{include file="file:{{$field_select}}" field=$close_lastusers}}
{{include file="file:{{$field_select}}" field=$close_lastphotos}}
{{include file="file:{{$field_select}}" field=$close_lastlikes}}
{{include file="file:{{$field_select}}" field=$close_twitter}}
{{include file="file:{{$field_input}}" field=$TSearchTerm}}
{{include file="file:{{$field_select}}" field=$close_mapquery}}

{{include file="file:{{$field_input}}" field=$ELPosX}}

{{include file="file:{{$field_input}}" field=$ELPosY}}

{{include file="file:{{$field_input}}" field=$ELZoom}}

<div class="settings-submit-wrapper">
	<input type="submit" value="{{$submit}}" class="settings-submit" name="diabook-settings-submit" />
</div>

<br>

<div class="field select">
<a onClick="restore_boxes()" title="Restore boxorder at right-hand column" style="cursor: pointer;">Restore boxorder at right-hand column</a>
</div>

