<h1>{{$ptitle}}</h1>

<div id="settings-nick-wrapper">
	<div id="settings-nickname-desc" class="info-message">{{$desc nofilter}}</div>
</div>
<div id="settings-nick-end"></div>

<div id="settings-form">
	<h2 class="settings-heading"><a href="javascript:;">{{$h_pass}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{include file="field_password.tpl" field=$password1}}
		{{include file="field_password.tpl" field=$password2}}
		{{include file="field_password.tpl" field=$password3}}

		<div class="settings-submit-wrapper">
			<input type="submit" name="password-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$h_basic}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{include file="field_input.tpl" field=$username}}
		{{include file="field_input.tpl" field=$email}}
		{{include file="field_password.tpl" field=$password4}}

		{{if $oid_enable}}
			{{include file="field_input.tpl" field=$openid}}
			{{include file="field_checkbox.tpl" field=$delete_openid}}
		{{/if}}

		{{include file="field_custom.tpl" field=$timezone}}
		{{include file="field_select.tpl" field=$language}}
		{{include file="field_input.tpl" field=$default_location}}
		{{include file="field_checkbox.tpl" field=$allow_location}}

		<div class="settings-submit-wrapper">
			<input type="submit" name="basic-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$h_prv}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{include file="field_input.tpl" field=$maxreq}}

		{{$profile_in_dir nofilter}}

		{{include file="field_checkbox.tpl" field=$profile_in_net_dir}}
		{{if not $is_community}}{{include file="field_checkbox.tpl" field=$hide_friends}}{{/if}}
		{{include file="field_checkbox.tpl" field=$hide_wall}}
		{{if not $is_community}}{{include file="field_checkbox.tpl" field=$unlisted}}{{/if}}
		{{include file="field_checkbox.tpl" field=$accessiblephotos}}
		{{if not $is_community}}
		{{include file="field_checkbox.tpl" field=$blockwall}}
		{{include file="field_checkbox.tpl" field=$blocktags}}
		{{/if}}
		{{include file="field_checkbox.tpl" field=$unkmail}}
		{{include file="field_input.tpl" field=$cntunkmail}}

		{{$circle_select nofilter}}
		{{if not $is_community}}
		<h3>{{$permissions}}</h3>

		{{$aclselect nofilter}}
		{{/if}}
		<div class="settings-submit-wrapper">
			<input type="submit" name="privacy-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$expire.label}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<div id="settings-expiry">
			{{include file="field_input.tpl" field=$expire.days}}
			{{include file="field_checkbox.tpl" field=$expire.items}}
			{{include file="field_checkbox.tpl" field=$expire.notes}}
			{{include file="field_checkbox.tpl" field=$expire.starred}}
			{{include file="field_checkbox.tpl" field=$expire.network_only}}

			<div class="settings-submit-wrapper">
				<input type="submit" name="expire-submit" class="settings-submit" value="{{$submit}}"/>
			</div>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$h_not}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<div id="settings-notifications">

			<div id="settings-notification-desc">{{$lbl_not}}</div>

			<div class="group">
				{{include file="field_intcheckbox.tpl" field=$notify1}}
				{{include file="field_intcheckbox.tpl" field=$notify2}}
				{{include file="field_intcheckbox.tpl" field=$notify3}}
				{{include file="field_intcheckbox.tpl" field=$notify4}}
				{{include file="field_intcheckbox.tpl" field=$notify5}}
				{{include file="field_intcheckbox.tpl" field=$notify6}}
				{{include file="field_intcheckbox.tpl" field=$notify7}}
			</div>

			<div id="settings-notify-desc">{{$lbl_notify}}</div>

			<div class="group">
				{{include file="field_checkbox.tpl" field=$notify_tagged}}
				{{include file="field_checkbox.tpl" field=$notify_direct_comment}}
				{{include file="field_checkbox.tpl" field=$notify_like}}
				{{include file="field_checkbox.tpl" field=$notify_announce}}
				{{include file="field_checkbox.tpl" field=$notify_thread_comment}}
				{{include file="field_checkbox.tpl" field=$notify_comment_participation}}
				{{include file="field_checkbox.tpl" field=$notify_activity_participation}}
			</div>

			{{include file="field_checkbox.tpl" field=$email_textonly}}
			{{include file="field_checkbox.tpl" field=$detailed_notif}}

			{{include file="field_checkbox.tpl" field=$notify_ignored}}

			{{include file="field_checkbox.tpl" field=$desktop_notifications}}
			<script>
				(function () {
					let $notificationField = $("#div_id_{{$desktop_notifications.0}}");
					let $notificationCheckbox = $("#id_{{$desktop_notifications.0}}");

					if (getNotificationPermission() === 'granted') {
						$notificationCheckbox.prop('checked', true);
					}
					if (getNotificationPermission() === null) {
						$notificationField.hide();
					}

					$notificationCheckbox.on('change', function (e) {
						if (Notification.permission === 'granted') {
							localStorage.setItem('notification-permissions', $notificationCheckbox.prop('checked') ? 'granted' : 'denied');
						} else if (Notification.permission === 'denied') {
							localStorage.setItem('notification-permissions', 'denied');

							$notificationCheckbox.prop('checked', false);
						} else if (Notification.permission === 'default') {
							Notification.requestPermission(function (choice) {
								if (choice === 'granted') {
									localStorage.setItem('notification-permissions', $notificationCheckbox.prop('checked') ? 'granted' : 'denied');
								} else {
									localStorage.setItem('notification-permissions', 'denied');
									$notificationCheckbox.prop('checked', false);
								}
							});
						}
					})
				})();
			</script>

		</div>

		<div class="settings-submit-wrapper">
			<input type="submit" name="notification-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$h_advn}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<p id="settings-advanced-desc">{{$h_descadvn}}</p>

		{{$pagetype nofilter}}

		<div class="settings-submit-wrapper">
			<input type="submit" name="advanced-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$importcontact}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="MAX_FILE_SIZE" value="{{$importcontact_maxsize}}"/>
		<p id="settings-pagetype-desc">{{$importcontact_text}}</p>
		<p><input type="file" name="importcontact-filename"/></p>

		<div class="settings-submit-wrapper">
			<input type="submit" name="importcontact-submit" class="importcontact-submit" value="{{$importcontact_button}}"/>
		</div>
	</form>

	<h2 class="settings-heading"><a href="javascript:;">{{$relocate}}</a></h2>
	<form class="settings-content-block" action="settings" method="post" autocomplete="off" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<p id="settings-pagetype-desc">{{$relocate_text}}</p>

		<div class="settings-submit-wrapper">
			<input type="submit" name="relocate-submit" class="settings-submit" value="{{$relocate_button}}"/>
		</div>
	</form>
</div>
