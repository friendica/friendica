<script>
	$(function () {
		$("#cnftheme").click(function () {
			document.location.assign("{{$baseurl}}/admin/themes/" + $("#id_theme :selected").val());
			return false;
		});
	});
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/site" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		<h2>{{$general_info}}</h2>
		{{include file="field_input.tpl" field=$sitename}}
		{{include file="field_input.tpl" field=$sender_email}}
		{{include file="field_input.tpl" field=$system_actor_name}}
		{{include file="field_textarea.tpl" field=$banner}}
		{{include file="field_input.tpl" field=$email_banner}}
		{{include file="field_input.tpl" field=$shortcut_icon}}
		{{include file="field_input.tpl" field=$touch_icon}}
		{{include file="field_textarea.tpl" field=$additional_info}}
		{{include file="field_select.tpl" field=$language}}
		{{include file="field_select.tpl" field=$theme}}
		{{include file="field_select.tpl" field=$theme_mobile}}
		{{include file="field_checkbox.tpl" field=$force_ssl}}
		{{include file="field_checkbox.tpl" field=$show_help}}
		{{include file="field_select.tpl" field=$singleuser}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$registration}}</h2>
		{{include file="field_textarea.tpl" field=$register_text}}
		{{include file="field_select.tpl" field=$register_policy}}
		{{include file="field_input.tpl" field=$daily_registrations}}
		{{include file="field_checkbox.tpl" field=$enable_multi_reg}}
		{{include file="field_checkbox.tpl" field=$enable_openid}}
		{{include file="field_checkbox.tpl" field=$enable_regfullname}}
		{{include file="field_checkbox.tpl" field=$register_notification}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$upload}}</h2>
		{{include file="field_input.tpl" field=$maximagesize}}
		{{include file="field_input.tpl" field=$maximagelength}}
		{{include file="field_input.tpl" field=$jpegimagequality}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$corporate}}</h2>
		{{include file="field_input.tpl" field=$allowed_sites}}
		{{include file="field_input.tpl" field=$allowed_email}}
		{{include file="field_input.tpl" field=$forbidden_nicknames}}
		{{include file="field_checkbox.tpl" field=$no_oembed_rich_content}}
		{{include file="field_input.tpl" field=$allowed_oembed}}
		{{include file="field_checkbox.tpl" field=$block_public}}
		{{include file="field_checkbox.tpl" field=$force_publish}}
		{{include file="field_select.tpl" field=$community_page_style}}
		{{include file="field_input.tpl" field=$max_author_posts_community_page}}

		{{if $mail_able}}
			{{include file="field_checkbox.tpl" field=$mail_enabled}}
		{{else}}
			<div class='field checkbox' id='div_id_{{$mail_enabled.0}}'>
				<label for='id_{{$mail_enabled.0}}'>{{$mail_enabled.1}}</label>
				<span class="field_help" role="tooltip" id="id_{{$mail_enabled.0}}">{{$mail_not_able}}</span>
			</div>
		{{/if}}

		{{include file="field_checkbox.tpl" field=$ostatus_enabled}}

		{{if $diaspora_able}}
			{{include file="field_checkbox.tpl" field=$diaspora_enabled}}
		{{else}}
			<div class='field checkbox' id='div_id_{{$diaspora_enabled.0}}'>
				<label for='id_{{$diaspora_enabled.0}}'>{{$diaspora_enabled.1}}</label>
				<span class="field_help" role="tooltip" id="id_{{$diaspora_enabled.0}}">{{$diaspora_not_able}}</span>
			</div>
		{{/if}}
		{{include file="field_input.tpl" field=$global_directory}}
		<div class="submit"><input type="submit" name="republish_directory" value="{{$republish}}"/></div>
		{{include file="field_checkbox.tpl" field=$newuser_private}}
		{{include file="field_checkbox.tpl" field=$enotify_no_content}}
		{{include file="field_checkbox.tpl" field=$private_addons}}
		{{include file="field_checkbox.tpl" field=$disable_embedded}}
		{{include file="field_checkbox.tpl" field=$allow_users_remote_self}}
		{{include file="field_checkbox.tpl" field=$explicit_content}}
		{{include file="field_checkbox.tpl" field=$proxify_content}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$advanced}}</h2>
		{{include file="field_checkbox.tpl" field=$verifyssl}}
		{{include file="field_input.tpl" field=$proxy}}
		{{include file="field_input.tpl" field=$proxyuser}}
		{{include file="field_input.tpl" field=$timeout}}
		{{include file="field_input.tpl" field=$abandon_days}}
		{{include file="field_input.tpl" field=$temppath}}
		{{include file="field_checkbox.tpl" field=$suppress_tags}}
		{{include file="field_checkbox.tpl" field=$nodeinfo}}
		{{include file="field_select.tpl" field=$check_new_version_url}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$portable_contacts}}</h2>
		{{include file="field_select.tpl" field=$contact_discovery}}
		{{include file="field_checkbox.tpl" field=$synchronize_directory}}
		{{include file="field_input.tpl" field=$poco_requery_days}}
		{{include file="field_checkbox.tpl" field=$poco_discovery}}
		{{include file="field_checkbox.tpl" field=$poco_local_search}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$performance}}</h2>
		{{include file="field_checkbox.tpl" field=$compute_group_counts}}
		{{include file="field_checkbox.tpl" field=$only_tag_search}}
		{{include file="field_input.tpl" field=$max_comments}}
		{{include file="field_input.tpl" field=$max_display_comments}}
		{{include file="field_checkbox.tpl" field=$dbclean}}
		{{include file="field_input.tpl" field=$dbclean_expire_days}}
		{{include file="field_input.tpl" field=$dbclean_unclaimed}}
		{{include file="field_input.tpl" field=$dbclean_expire_conv}}
		{{include file="field_checkbox.tpl" field=$optimize_tables}}
		{{include file="field_checkbox.tpl" field=$cache_contact_avatar}}
		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$worker_title}}</h2>
		{{include file="field_input.tpl" field=$maxloadavg}}
		{{include file="field_input.tpl" field=$min_memory}}
		{{include file="field_input.tpl" field=$worker_queues}}
		{{include file="field_checkbox.tpl" field=$worker_fastlane}}

		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

		<h2>{{$relay_title}}</h2>
		{{if $relay_list}}
			<p>{{$relay_list_title}}</p>
			<ul id="relay-list">
				{{foreach $relay_list as $relay}}
				<li>{{$relay.url}}</li>
				{{/foreach}}
			</ul>
		{{else}}
			<p>{{$no_relay_list}}</p>
		{{/if}}
		<p>{{$relay_description}}</p>
		{{include file="field_select.tpl" field=$relay_scope}}
		{{include file="field_input.tpl" field=$relay_server_tags}}
		{{include file="field_input.tpl" field=$relay_deny_tags}}
		{{include file="field_checkbox.tpl" field=$relay_user_tags}}
		{{include file="field_checkbox.tpl" field=$relay_directly}}

		<div class="submit"><input type="submit" name="page_site" value="{{$submit}}"/></div>

	</form>

	<div>
		<h2>{{$relocate}}</h2>
		<p>{{$relocate_msg}}</p>
		<p><code>{{$relocate_cmd}}</code></p>
	</div>

</div>
