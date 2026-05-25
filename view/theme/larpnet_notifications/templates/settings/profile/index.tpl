{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h2>{{$l10n.banner}}</h2>

	{{* The actions dropdown which can performed to the current profile *}}
	<div id="profile-edit-links">
		<ul class="nav nav-pills preferences">
			<li>
				<a class="btn btn-primary" href="profile/{{$nickname}}/profile"><i class="fa fa-eye" aria-hidden="true"></i>&nbsp;{{$l10n.viewprof}}</a>
			</li>
		</ul>
	</div>

	<div id="profile-edit-links-end"></div>

	{{* Most of the Variables used below are arrays in the following style
		0 => Some kind of identifier (e.g. for the ID)
		1 => The label description
		2 => The input values
		3 => The additional help text (if available)
	*}}

	<div class="panel-group panel-group-settings" id="profile-photo-edit-wrapper" role="tablist" aria-multiselectable="false">
		{{* Change profile picture *}}
		<div class="panel">
			<div class="section-subtitle-wrapper panel-heading" role="tab" id="photo-upload">
				<h2>
					<button class="btn-link accordion-toggle {{if !$change_profile_picture}}collapsed{{/if}}" data-toggle="collapse" data-parent="#profile-photo-edit-wrapper" href="#photo-upload-collapse" aria-expanded="true" aria-controls="photo-upload-collapse">
						{{$l10n.profpic_header}}
					</button>
				</h2>
			</div>
			<div id="photo-upload-collapse" class="panel-collapse collapse {{if $change_profile_picture}}in{{/if}}" role="tabpanel" aria-labelledby="photo-upload">
				<div class="panel-body">
					<p id="profpic-intro-description">{{$l10n.profpic_intro}}</p>
					<div class="row">
						<div id="profpic-left" class="col-md-6">
							<h3>{{$l10n.profpic_upload_new_header}}</h3>
							<form enctype="multipart/form-data" action="settings/profile/photo" method="post">
								<input type="hidden" name="form_security_token" value="{{$form_security_token_photo}}">
									<div id="profile-photo-upload-wrapper">
										<input name="userfile" type="file" id="profile-photo-upload" required/>
									</div>
									<div class="profile-edit-submit-wrapper">
										<button type="submit" name="submit" class="profile-edit-submit-button btn btn-primary">{{$l10n.profpic_upload_submit}}</button>
									</div>
								</form>
							</div>
							<div id="profpic-right" class="col-md-6">
								<h3>{{$l10n.profpic_existing_header}}</h3>
								<div class="spacer"></div>
								<div>
								<a class="btn btn-primary" href="{{$profpiclink}}">{{$l10n.yourphotos}}</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<form id="profile-edit-form" name="form1" action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="panel-group panel-group-settings" id="profile-edit-wrapper" role="tablist" aria-multiselectable="false">
			{{* The personal settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="personal">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#personal-collapse" aria-expanded="false" aria-controls="personal-collapse">
							{{$l10n.personal_section}}
						</button>
					</h2>
				</div>
				{{* for the $detailed_profile we use bootstraps collapsable panel-groups to have expandable groups *}}
				<div id="personal-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="personal">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$username}}

						{{include file="field_textarea.tpl" field=$about}}

						{{$dob nofilter}}

						{{$hide_friends nofilter}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary">{{$l10n.submit}}</button>
					</div>
				</div>
			</div>

			{{* The location settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="location">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#location-collapse" aria-expanded="false" aria-controls="location-collapse">
							{{$l10n.location_section}}
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
						<button type="submit" name="submit" class="btn btn-primary">{{$l10n.submit}}</button>
					</div>
				</div>
			</div>

			{{* The miscellaneous other settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="miscellaneous">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#miscellaneous-collapse" aria-expanded="false" aria-controls="miscellaneous-collapse">
							{{$l10n.miscellaneous_section}}
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
						<button type="submit" name="submit" class="btn btn-primary">{{$l10n.submit}}</button>
					</div>
				</div>
			</div>

			{{* The miscellaneous other settings *}}
			<div class="panel">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="custom-fields">
					<h2>
						<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#profile-edit-wrapper" href="#custom-fields-collapse" aria-expanded="false" aria-controls="custom-fields-collapse">
							{{$l10n.custom_fields_section}}
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
						<button type="submit" name="submit" class="btn btn-primary">{{$l10n.submit}}</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
	Fill_Country('{{$country_name.2}}');
	Fill_States('{{$region.2}}');
</script>
