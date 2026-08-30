{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1>{{$l10n.banner}}</h1>

<div id="profile-edit-links">
	<ul>
		<li><a class="btn" href="profile/{{$nickname}}/profile" id="profile-edit-view-link">{{$l10n.viewprof}}</a></li>
	</ul>
</div>
<div id="profile-edit-links-end"></div>

<div id="profile-edit-wrapper">
	<!-- Profile picture -->
	<details class="settings-section" {{if $change_profile_picture}}open{{/if}}>
		<summary class="settings-heading"><h2>{{$l10n.picture_section}}</h2></summary>
			<p id="profpic-intro-description">{{$l10n.profpic_intro}}</p>
			<div class="settings-row">
				<div id="profpic-left">
					<form enctype="multipart/form-data" action="settings/profile/photo" method="post">
					<input type="hidden" name="form_security_token" value="{{$form_security_token_photo}}">
					<div id="profile-photo-upload-wrapper">
						<label id="profile-photo-upload-label" for="profile-photo-upload">{{$l10n.profpic_upload_new_header}}:</label>
						<input name="userfile" type="file" id="profile-photo-upload" required/>
					</div>
					<div class="profile-edit-submit-wrapper">
						<button type="submit" name="submit" class="profile-edit-submit-button btn btn-default">{{$l10n.submit}}</button>
					</div>
					</form>
				</div>
				<div id="profpic-right">
					<h3>{{$l10n.profpic_existing_header}}</h3>
					<div class="spacer"></div>
					<div>
						<a class="btn btn-primary" href="{{$profpiclink}}">{{$l10n.yourphotos}}</a>
					</div>
				</div>
			</div>
	</details>

	<form id="profile-edit-form" name="form1" action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<!-- Basic information -->
		<details class="settings-section">
			<summary class="settings-heading"><h2>{{$l10n.personal_section}}</h2></summary>
            {{include file="field_input.tpl" field=$username}}

            {{include file="field_textarea.tpl" field=$about}}

             {{include file="field_input.tpl" field=$xmpp}}

            {{include file="field_input.tpl" field=$matrix}}

            {{include file="field_input.tpl" field=$homepage}}

			<div id="profile-edit-dob-wrapper">
                {{$dob nofilter}}
			</div>
			<div id="profile-edit-dob-end"></div>

            {{$hide_friends nofilter}}

            {{include file="field_input.tpl" field=$pub_keywords}}

            {{include file="field_input.tpl" field=$prv_keywords}}

			<div class="profile-edit-submit-wrapper">
				<button type="submit" name="submit" class="profile-edit-submit-button btn btn-default">{{$l10n.submit}}</button>
			</div>
			<div class="profile-edit-submit-end"></div>
		</details>
		<!-- About you -->
		<details class="settings-section">
			<summary class="settings-heading"><h2>{{$l10n.location_section}}</h2></summary>
            {{include file="field_input.tpl" field=$address}}

            {{include file="field_input.tpl" field=$locality}}

            {{include file="field_input.tpl" field=$postal_code}}

			<div id="profile-edit-country-name-wrapper">
				<label id="profile-edit-country-name-label" for="profile-edit-country-name">{{$country_name.1}} </label>
				<select name="country_name" id="profile-edit-country-name" onChange="Fill_States('{{$region.2}}');">
					<option selected="selected">{{$country_name.2}}</option>
				</select>
			</div>
			<div id="profile-edit-country-name-end"></div>

			<div id="profile-edit-region-wrapper">
				<label id="profile-edit-region-label" for="profile-edit-region">{{$region.1}} </label>
				<select name="region" id="profile-edit-region" onChange="Update_Globals();">
					<option selected="selected">{{$region.2}}</option>
				</select>
			</div>
			<div id="profile-edit-region-end"></div>

			<div class="profile-edit-submit-wrapper">
				<button type="submit" name="submit" class="profile-edit-submit-button btn btn-default">{{$l10n.submit}}</button>
			</div>
			<div class="profile-edit-submit-end"></div>
		</details>
		<!-- Interests -->
		<details class="settings-section">
			<summary class="settings-heading"><h2>{{$l10n.custom_fields_section}}</h2></summary>
            {{$custom_fields_description nofilter}}
			<div id="profile-custom-fields">
                {{foreach $custom_fields as $custom_field}}
                    {{include file="settings/profile/field/edit.tpl" profile_field=$custom_field}}
                {{/foreach}}
			</div>

			<div class="profile-edit-submit-wrapper">
				<button type="submit" name="submit" class="profile-edit-submit-button btn btn-default">{{$l10n.submit}}</button>
			</div>
			<div class="profile-edit-submit-end"></div>
		</details>
	</form>
</div>
<script type="text/javascript">
	Fill_Country('{{$country_name.2}}');
	Fill_States('{{$region.2}}');
</script>
