
	<div class="field yesno">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<div class="onoff toggle btn btn-xs pull-right" id="id_{{$field.0}}_onoff">
			
			<div class="switchlabel toggle-group">
				<input  type="hidden" name="{{$field.0}}" id="id_{{$field.0}}" value="{{$field.2|escape:'html'}}" aria-describedby="{{$field.0}}_tip">
				<label class="btn btn-default btn-xs  active toggle-off">
					{{if $field.4}}{{$field.4.0}}{{else}}OFF{{/if}}
				</label>
				<label class="btn btn-primary btn-xs toggle-on">
					{{if $field.4}}{{$field.4.1}}{{else}}ON{{/if}}
				</label>
				<span class="toggle-handle btn btn-default btn-xs"></span>
			</div>
		</div>
		<span class="help-block" id="{{$field.0}}_tip" role="tooltip">{{$field.3}}</span>
	</div>
	<div class="clear"></div>
