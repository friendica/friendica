<div class="generic-page-wrapper">
	<h1>{{$ptitle}}</h1>
	<form action="settings/display" id="settings-form" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="theme-settings-title">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#theme-settings-content" aria-expanded="true" aria-controls="theme-settings-content">
							{{$d_tset}}
						</button>
					</h2>
				</div>

				<div id="theme-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="theme-settings">
					<div class="panel-body">
						{{include file="field_themeselect.tpl" field=$theme}}

						{{* Show the mobile theme selection only if mobile themes are available *}}
						{{if count($mobile_theme.4) > 1}}
						{{include file="field_themeselect.tpl" field=$mobile_theme}}
						{{/if}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="custom-settings-title">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#custom-settings-content" aria-expanded="false" aria-controls="custom-settings-content">
							{{$d_ctset}}
						</button>
					</h2>
				</div>
				<div id="custom-settings-content" class="panel-collapse collapse{{if !$theme && !$mobile_theme}} in{{/if}}" role="tabpanel" aria-labelledby="custom-settings">
					<div class="panel-body">

					{{if $theme_config}}
						{{$theme_config nofilter}}
					{{/if}}

					</div>
				</div>
			</div>

			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="content-settings-title">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#content-settings-content" aria-expanded="false" aria-controls="content-settings-content">
							{{$d_cset}}
						</button>
					</h2>
				</div>
				<div id="content-settings-content" class="panel-collapse collapse{{if !$theme && !$mobile_theme && !$theme_config}} in{{/if}}" role="tabpanel" aria-labelledby="content-settings">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$itemspage_network}}
						{{include file="field_input.tpl" field=$itemspage_mobile_network}}
						{{include file="field_input.tpl" field=$ajaxint}}
						{{include file="field_checkbox.tpl" field=$enable_smile}}
						{{include file="field_checkbox.tpl" field=$infinite_scroll}}
						{{include file="field_checkbox.tpl" field=$enable_smart_threading}}
						{{include file="field_checkbox.tpl" field=$enable_dislike}}
						{{include file="field_checkbox.tpl" field=$display_resharer}}
						{{include file="field_checkbox.tpl" field=$stay_local}}
						{{include file="field_select.tpl" field=$preview_mode}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="calendar-settings-title">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#calendar-settings-content" aria-expanded="false" aria-controls="calendar-settings-content">
							{{$calendar_title}}
						</button>
					</h2>
				</div>
				<div id="calendar-settings-content" class="panel-collapse collapse{{if !$theme && !$mobile_theme && !$theme_config}} in{{/if}}" role="tabpanel" aria-labelledby="calendar-settings">
					<div class="panel-body">
						{{include file="field_select.tpl" field=$first_day_of_week}}
						{{include file="field_select.tpl" field=$calendar_default_view}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
