<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title }}


	<form action="settings/features" method="post" autocomplete="off">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			{{foreach $features as $g => $f}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="{{$g}}-settings-title">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#{{$g}}-settings-content" aria-expanded="true" aria-controls="{{$g}}-settings-content">
							{{$f.0}}
						</a>
					</h4>
				</div>
				<div id="{{$g}}-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="{{$g}}-settings-title">
					<div class="section-content-tools-wrapper">
						{{foreach $f.1 as $fcat}}
							{{include file="field_yesno.tpl" field=$fcat}}
						{{/foreach}}

						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
			{{/foreach}}
		</div>

	</form>
</div>
