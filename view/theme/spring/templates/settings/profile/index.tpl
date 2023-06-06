<script>
	$(document).ready(function () {
		//$('.toggle-section-content + .toggle-section-content').hide();
		$('.js-section-toggler').click(function () {
			$('.toggle-section-content').hide();
			$(this).parents('.toggle-section').find('.toggle-section-content').toggle();
		});
	});
</script>

<h1>{{$banner}}</h1>

<div id="profile-edit-links">
	<ul>
		<li><a class="btn" href="profile/{{$nickname}}/profile" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a></li>
	</ul>
</div>
<div id="profile-edit-links-end"></div>

<div id="profile-edit-wrapper">
	<form enctype="multipart/form-data" action="settings/profile/photo" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token_photo}}">

		<!-- Profile picture -->
		<div class="toggle-section js-toggle-section">
			<h2><a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_picture_section}} &raquo;</a></h2>
			<div class="js-section toggle-section-content hidden">

				<div id="profile-photo-upload-wrapper">
					<label id="profile-photo-upload-label" for="profile-photo-upload">{{$lbl_profile_photo}}:</label>
					<input name="userfile" type="file" id="profile-photo-upload" size="48"/>
				</div>

				<div class="profile-edit-submit-wrapper">
					<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
				</div>
				<div class="profile-edit-submit-end"></div>

			</div>
		</div>
	</form>

	<form id="profile-edit-form" name="form1" action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<!-- Basic information -->
		<div class="toggle-section js-toggle-section">
			<h2><a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_personal_section}} &raquo;</a></h2>
			<div class="js-section toggle-section-content hidden">

				<div id="profile-edit-name-wrapper">
					<label id="profile-edit-name-label" for="profile-edit-name">{{$name.1}} </label>
					<input type="text" size="32" name="name" id="profile-edit-name" value="{{$name.2}}"/>
				</div>
				<div id="profile-edit-name-end"></div>

				<div id="profile-edit-about-wrapper">
					<label id="profile-edit-about-label" for="profile-edit-about">{{$about.1}} </label>
					<input type="text" size="32" name="about" id="profile-edit-about" value="{{$about.2}}"/>
				</div>
				<div id="profile-edit-about-end"></div>

				<div id="profile-edit-xmpp-wrapper">
					<label id="profile-edit-xmpp-label" for="profile-edit-xmpp">{{$xmpp.1}} </label>
					<input type="text" size="32" name="xmpp" id="profile-edit-xmpp" value="{{$xmpp.2}}"/>
				</div>
				<div id="profile-edit-xmpp-desc">{{$xmpp.3}}</div>
				<div id="profile-edit-xmpp-end"></div>

				<div id="profile-edit-matrix-wrapper">
					<label id="profile-edit-matrix-label" for="profile-edit-matrix">{{$matrix.1}} </label>
					<input type="text" size="32" name="matrix" id="profile-edit-matrix" value="{{$matrix.2}}"/>
				</div>
				<div id="profile-edit-matrix-desc">{{$matrix.3}}</div>
				<div id="profile-edit-matrix-end"></div>

				<div id="profile-edit-homepage-wrapper">
					<label id="profile-edit-homepage-label" for="profile-edit-homepage">{{$homepage.1}} </label>
					<input type="text" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage.2}}"/>
				</div>
				<div id="profile-edit-homepage-desc">{{$homepage.3}}</div>
				<div id="profile-edit-homepage-end"></div>

				<div id="profile-edit-dob-wrapper">
					{{$dob nofilter}}
				</div>
				<div id="profile-edit-dob-end"></div>

				{{$hide_friends nofilter}}

				<div id="profile-edit-pubkeywords-wrapper">
					<label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords">{{$pub_keywords.1}} </label>
					<input type="text" size="32" name="pub_keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$pub_keywords.2}}"/>
				</div>
				<div id="profile-edit-pubkeywords-desc">{{$pub_keywords.3}}</div>
				<div id="profile-edit-pubkeywords-end"></div>

				<div id="profile-edit-prvkeywords-wrapper">
					<label id="profile-edit-prvkeywords-label" for="profile-edit-prvkeywords">{{$prv_keywords.1}} </label>
					<input type="text" size="32" name="prv_keywords" id="profile-edit-prvkeywords" title="{{$lbl_ex2}}" value="{{$prv_keywords.2}}"/>
				</div>
				<div id="profile-edit-prvkeywords-desc">{{$prv_keywords.3}}</div>
				<div id="profile-edit-prvkeywords-end"></div>

				<div class="profile-edit-submit-wrapper">
					<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
				</div>
				<div class="profile-edit-submit-end"></div>

			</div>
		</div>
		<!-- About you -->
		<div class="toggle-section js-toggle-section">
			<h2><a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_location_section}} &raquo;</a></h2>
			<div class="js-section toggle-section-content hidden">

				<div id="profile-edit-address-wrapper">
					<label id="profile-edit-address-label" for="profile-edit-address">{{$address.1}} </label>
					<input type="text" size="32" name="address" id="profile-edit-address" value="{{$address.2}}"/>
				</div>
				<div id="profile-edit-address-end"></div>

				<div id="profile-edit-locality-wrapper">
					<label id="profile-edit-locality-label" for="profile-edit-locality">{{$locality.1}} </label>
					<input type="text" size="32" name="locality" id="profile-edit-locality" value="{{$locality.2}}"/>
				</div>
				<div id="profile-edit-locality-end"></div>

				<div id="profile-edit-postal-code-wrapper">
					<label id="profile-edit-postal-code-label" for="profile-edit-postal-code">{{$postal_code.1}} </label>
					<input type="text" size="32" name="postal_code" id="profile-edit-postal-code" value="{{$postal_code.2}}"/>
				</div>
				<div id="profile-edit-postal-code-end"></div>

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
					<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
				</div>
				<div class="profile-edit-submit-end"></div>
			</div>
		</div>
		<!-- Interests -->
		<div class="toggle-section js-toggle-section">
			<h2><a class="section-caption js-section-toggler" href="javascript:;">{{$lbl_custom_fields_section}} &raquo;</a></h2>
			<div class="js-section toggle-section-content hidden">
				{{$custom_fields_description nofilter}}
				<div id="profile-custom-fields">
				{{foreach $custom_fields as $custom_field}}
					{{include file="settings/profile/field/edit.tpl" profile_field=$custom_field}}
				{{/foreach}}
				</div>

				<div class="profile-edit-submit-wrapper">
					<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
				</div>
				<div class="profile-edit-submit-end"></div>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	Fill_Country('{{$country_name.2}}');
	Fill_States('{{$region.2}}');
</script>
