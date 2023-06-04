
<div class="field custom">
<label for="circle-selection" id="circle-selection-lbl">{{$label}}</label>
<select name="circle-selection" id="circle-selection">
{{foreach $circles as $circle}}
<option value="{{$circle.id}}"{{if $circle.selected}} selected="selected"{{/if}}>{{$circle.name}}</option>
{{/foreach}}
</select>
</div>
