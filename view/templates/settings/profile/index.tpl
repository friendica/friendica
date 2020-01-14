<h1>{{$banner}}</h1>

{{$default nofilter}}

<div id="profile-edit-links">
	<ul>
		<li><a href="settings/profile/photo" id="profile-photo_upload-link" title="{{$profpic}}">{{$profpic}}</a></li>
		<li><a href="profile/{{$nickname}}" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a></li>
	</ul>
</div>

<div id="profile-edit-links-end"></div>

<div id="profile-edit-wrapper">
	<form id="profile-edit-form" name="form1" action="settings/profiles" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div id="profile-edit-name-wrapper">
			<label id="profile-edit-name-label" for="profile-edit-name">{{$name.1}} </label>
			<input type="text" size="32" name="name" id="profile-edit-name" value="{{$name.2}}"/>
		</div>
		<div id="profile-edit-name-end"></div>
		<div id="profile-edit-pdesc-wrapper">
			<label id="profile-edit-pdesc-label" for="profile-edit-pdesc">{{$pdesc.1}} </label>
			<input type="text" size="32" name="pdesc" id="profile-edit-pdesc" value="{{$pdesc.1}}"/>
		</div>
		<div id="profile-edit-pdesc-end"></div>
		<div id="profile-edit-dob-wrapper">
			{{$dob nofilter}}
		</div>
		<div id="profile-edit-dob-end"></div>
		{{$hide_friends nofilter}}
		<div class="profile-edit-submit-wrapper">
			<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
		</div>
		<div class="profile-edit-submit-end"></div>
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
				<option>temp</option>
			</select>
		</div>
		<div id="profile-edit-country-name-end"></div>
		<div id="profile-edit-region-wrapper">
			<label id="profile-edit-region-label" for="profile-edit-region">{{$region.1}} </label>
			<select name="region" id="profile-edit-region" onChange="Update_Globals();">
				<option selected="selected">{{$region.2}}</option>
				<option>temp</option>
			</select>
		</div>
		<div id="profile-edit-hometown-end"></div>
		<div class="profile-edit-submit-wrapper">
			<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
		</div>
		<div class="profile-edit-submit-end"></div>
		<div id="profile-edit-homepage-wrapper">
			<label id="profile-edit-homepage-label" for="profile-edit-homepage">{{$homepage.1}} </label>
			<input type="url" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage.2}}"/>
		</div>
		<div id="profile-edit-homepage-end"></div>
		<div id="profile-edit-xmpp-wrapper">
			<label id="profile-edit-xmpp-label" for="profile-edit-xmpp">{{$xmpp.1}} </label>
			<input type="text" size="32" name="xmpp" id="profile-edit-xmpp" title="{{$lbl_ex2}}" value="{{$xmpp.2}}"/>
		</div>
		<div id="profile-edit-xmpp-desc">{{$xmpp.3}}</div>
		<div id="profile-edit-xmpp-end"></div>
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

		<h3>{{$lbl_custom_fields_section}}</h3>

		{{foreach $custom_fields as $custom_field}}
			{{include file="settings/profile/field/edit.tpl" profile_field=$custom_field}}
		{{/foreach}}

		<div class="profile-edit-submit-wrapper">
			<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}"/>
		</div>
		<div class="profile-edit-submit-end"></div>
	</form>
</div>
<script type="text/javascript">
	Fill_Country('{{$country_name.2}}');
	Fill_States('{{$region.2}}');
</script>
