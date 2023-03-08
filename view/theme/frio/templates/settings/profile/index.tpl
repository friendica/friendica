<div class="generic-page-wrapper">
	<h1>{{$banner}}</h1>

	{{* The actions dropdown which can performed to the current profile *}}
	<div id="profile-edit-links">
		<ul class="nav nav-pills preferences">
			<li class="dropdown pull-right">
				<button type="button" class="btn btn-link dropdown-toggle" id="profile-edit-links-dropdown" data-toggle="dropdown" aria-expanded="false">
					<i class="fa fa-angle-down" aria-hidden="true"></i>&nbsp;{{$profile_action}}
				</button>
				<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="profile-edit-links-dropdown">
					<li role="presentation"><a role="menuitem" href="{{$profpiclink}}" id="profile-photo_upload-link" title="{{$profpic}}"><i class="fa fa-user" aria-hidden="true"></i>&nbsp;{{$profpic}}</a></li>
					<li role="presentation"><button role="menuitem" type="button" class="btn-link" id="profile-photo_upload-link-new" title="{{$lbl_profile_photo}}" onclick="openClose('profile-photo-upload-section');"><i class="fa fa-user" aria-hidden="true"></i>&nbsp;{{$lbl_profile_photo}}</button></li>
					<li role="presentation" class="divider"></li>
					<li role="presentation"><a role="menuitem" href="profile/{{$nickname}}/profile" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a></li>
				</ul>
			</li>
		</ul>
	</div>

	<div id="profile-edit-links-end"></div>

	<form enctype="multipart/form-data" action="settings/profile/photo" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token_photo}}">

		<div id="profile-photo-upload-section" class="panel">
			<a id="profile-photo-upload-close" class="close pull-right" onclick="openClose('profile-photo-upload-section');"><i class="fa fa-times" aria-hidden="true"></i></a>
			<div id="profile-photo-upload-wrapper">
				<label id="profile-photo-upload-label" for="profile-photo-upload">{{$lbl_profile_photo}}:</label>
				<input name="userfile" type="file" id="profile-photo-upload" size="48" />
			</div>

			<div class="profile-edit-submit-wrapper pull-right">
				<button type="submit" name="submit" class="profile-edit-submit-button btn btn-primary" value="{{$submit}}">{{$submit}}</button>
			</div>
			<div class="clear"></div>
		</div>
	</form>

	{{* Most of the Variables used below are arrays in the following style
		0 => Some kind of identifier (e.g. for the ID)
		1 => The label description
		2 => The input values
		3 => The additional help text (if available)
	*}}

	<form id="profile-edit-form" name="form1" action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="panel-group panel-group-settings" id="profile-edit-wrapper" role="tablist" aria-multiselectable="true">
			{{* The personal settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="personal">
					<h2>
						<button class="btn-link accordion-toggle" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#personal-collapse" aria-expanded="true" aria-controls="personal-collapse">
							{{$lbl_personal_section}}
						</button>
					</h2>
				</div>
				{{* for the $detailed_profile we use bootstraps collapsable panel-groups to have expandable groups *}}
				<div id="personal-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="personal">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$name}}

						{{include file="field_textarea.tpl" field=$about}}

						{{$dob nofilter}}

						{{$hide_friends nofilter}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The location settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="location">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#location-collapse" aria-expanded="false" aria-controls="location-collapse">
							{{$lbl_location_section}}
						</button>
					</h2>
				</div>
				<div id="location-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="location">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$address}}

						{{include file="field_input.tpl" field=$locality}}

						{{include file="field_input.tpl" field=$postal_code}}

						<div id="profile-edit-country-name-wrapper" class="form-group field select">
							<label id="profile-edit-country-name-label" for="profile-edit-country-name">{{$country_name.1}} </label>
							<select name="country_name" id="profile-edit-country-name" class="form-control" onChange="Fill_States('{{$region.2}}');">
								<option selected="selected">{{$country_name.2}}</option>
								<option>temp</option>
							</select>
						</div>
						<div class="clear"></div>

						<div id="profile-edit-region-wrapper" class="form-group field select">
							<label id="profile-edit-region-label" for="profile-edit-region">{{$region.1}} </label>
							<select name="region" id="profile-edit-region" class="form-control" onChange="Update_Globals();">
								<option selected="selected">{{$region.2}}</option>
								<option>temp</option>
							</select>
						</div>
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The miscellanous other settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="miscellaneous">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#miscellaneous-collapse" aria-expanded="false" aria-controls="miscellaneous-collapse">
							{{$lbl_miscellaneous_section}}
						</button>
					</h2>
				</div>
				<div id="miscellaneous-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="miscellaneous">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$homepage}}

						{{include file="field_input.tpl" field=$xmpp}}

						{{include file="field_input.tpl" field=$matrix}}

						{{include file="field_input.tpl" field=$pub_keywords}}

						{{include file="field_input.tpl" field=$prv_keywords}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>

			{{* The miscellanous other settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="custom-fields">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#custom-fields-collapse" aria-expanded="false" aria-controls="custom-fields-collapse">
							{{$lbl_custom_fields_section}}
						</button>
					</h2>
				</div>
				<div id="custom-fields-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="custom-fields">
					<div class="panel-body">
						{{$custom_fields_description nofilter}}
						<div id="profile-custom-fields">
						{{foreach $custom_fields as $custom_field}}
							{{include file="settings/profile/field/edit.tpl" profile_field=$custom_field}}
						{{/foreach}}
						</div>
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	Fill_Country('{{$country_name.2}}');
	Fill_States('{{$region.2}}');

	// initiale autosize for the textareas
	autosize($("textarea.text-autosize"));
</script>
