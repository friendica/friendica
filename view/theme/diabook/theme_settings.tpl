{{inc field_select.tpl with $field=$color}}{{endinc}}

{{inc field_select.tpl with $field=$font_size}}{{endinc}}

{{inc field_select.tpl with $field=$line_height}}{{endinc}}

{{inc field_select.tpl with $field=$resolution}}{{endinc}}

<div class="settings-submit-wrapper">
	<input type="submit" value="$submit" class="settings-submit" name="diabook-settings-submit" />
</div>
<br>
<h3>Show/hide boxes at right-hand column</h3>
{{inc field_select.tpl with $field=$close_pages}}{{endinc}}
{{inc field_select.tpl with $field=$close_profiles}}{{endinc}}
{{inc field_select.tpl with $field=$close_helpers}}{{endinc}}
{{inc field_select.tpl with $field=$close_services}}{{endinc}}
{{inc field_select.tpl with $field=$close_friends}}{{endinc}}
{{inc field_select.tpl with $field=$close_lastusers}}{{endinc}}
{{inc field_select.tpl with $field=$close_lastphotos}}{{endinc}}
{{inc field_select.tpl with $field=$close_lastlikes}}{{endinc}}
{{inc field_select.tpl with $field=$close_twitter}}{{endinc}}
{{inc field_input.tpl with $field=$TSearchTerm}}{{endinc}}
{{inc field_select.tpl with $field=$close_mapquery}}{{endinc}}

{{inc field_input.tpl with $field=$ELPosX}}{{endinc}}

{{inc field_input.tpl with $field=$ELPosY}}{{endinc}}

{{inc field_input.tpl with $field=$ELZoom}}{{endinc}}

<div class="settings-submit-wrapper">
	<input type="submit" value="$submit" class="settings-submit" name="diabook-settings-submit" />
</div>

<br>

<div class="field select">
<a onClick="restore_boxes()" title="Restore boxorder at right-hand column" style="cursor: pointer;">Restore boxorder at right-hand column</a>
</div>

