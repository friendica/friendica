{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$ptitle}}</h1>

	<div id="settings-nick-wrapper">
		<div id="settings-nickname-desc" class="info-message">{{$desc nofilter}}</div>
	</div>
	<div id="settings-nick-end"></div>

	<div id="settings-form">
		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			{{* The password setting section *}}
			<form action="settings/account/password" method="post" autocomplete="off" class="panel" >
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'password'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$h_pass}}</h2></summary>
					<div id="password-settings">
						<div class="panel-body">
							{{include file="field_password.tpl" field=$password1}}
							{{include file="field_password.tpl" field=$password2}}
							{{include file="field_password.tpl" field=$password3}}
						</div>
						<div class="panel-footer">
							<button type="submit" name="password-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			{{* The basic setting section *}}
			<form action="settings/account/basic" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'basic'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$h_basic}}</h2></summary>
					<div id="basic-settings">
						<div class="panel-body">
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
						</div>
						<div class="panel-footer">
							<button type="submit" name="basic-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			{{* The privacity setting section *}}
			<form action="settings/account/privacy" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'privacy'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$h_prv}}</h2></summary>
					<div id="privacy-settings">
						<div class="panel-body">
							{{include file="field_input.tpl" field=$maxreq}}

							{{$profile_in_dir nofilter}}

							{{include file="field_checkbox.tpl" field=$profile_in_net_dir}}
							{{if not $is_community}}{{include file="field_checkbox.tpl" field=$hide_friends}}{{/if}}
							{{include file="field_checkbox.tpl" field=$hide_wall}}
							{{if not $is_community}}
							{{include file="field_checkbox.tpl" field=$unlisted}}
							{{include file="field_checkbox.tpl" field=$prevent_relay}}
							{{/if}}
							{{include file="field_checkbox.tpl" field=$accessiblephotos}}
							{{if not $is_community}}
							{{include file="field_checkbox.tpl" field=$blockwall}}
							{{include file="field_checkbox.tpl" field=$blocktags}}
							{{/if}}

							{{$circle_select nofilter}}

							{{$circle_select_group nofilter}}

							{{if not $is_community}}
							<h3>{{$permissions}}</h3>

							{{$aclselect nofilter}}
							{{/if}}
						</div>
						<div class="panel-footer">
							<button type="submit" name="privacy-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			<form action="settings/account/expire" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'expire'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$expire.label}}</h2></summary>
					<div id="expire-settings">
						<div class="panel-body">
							{{include file="field_input.tpl" field=$expire.days}}

							{{include file="field_checkbox.tpl" field=$expire.items}}
							{{include file="field_checkbox.tpl" field=$expire.notes}}
							{{include file="field_checkbox.tpl" field=$expire.starred}}
							{{include file="field_checkbox.tpl" field=$expire.network_only}}
						</div>
						<div class="panel-footer">
							<button type="submit" name="expire-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			{{* The notification setting section *}}
			<form action="settings/account/notification" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'notification'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$h_not}}</h2></summary>
					<div id="notification-settings">
						<div id="settings-notifications" class="panel-body">

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

							{{* commented out because it was commented out in the original template
							<div class="field">
						 		<button type="button" onclick="javascript:Notification.requestPermission(function(perm){if(perm === 'granted')alert('{{$desktop_notifications_success_message}}');});">{{$desktop_notifications}}</button>
						 		<span class="field_help">{{$desktop_notifications_note}}</span>
							</div>
							*}}

							{{include file="field_checkbox.tpl" field=$desktop_notifications}}
							<script type="text/javascript">
								(function(){
									let $notificationField = $("#div_id_{{$desktop_notifications.0}}");
									let $notificationCheckbox = $("#id_{{$desktop_notifications.0}}");

									if (getNotificationPermission() === 'granted') {
										$notificationCheckbox.prop('checked', true);
									}
									if (getNotificationPermission() === null) {
										$notificationField.hide();
									}

									$notificationCheckbox.on('change', function(e){
										if (Notification.permission === 'granted') {
											localStorage.setItem('notification-permissions', $notificationCheckbox.prop('checked') ? 'granted' : 'denied');
										} else if (Notification.permission === 'denied') {
											localStorage.setItem('notification-permissions', 'denied');

											$notificationCheckbox.prop('checked', false);
										} else if (Notification.permission === 'default') {
											Notification.requestPermission(function(choice) {
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
						<div class="panel-footer">
							<button type="submit" name="notification-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			{{* The additional account setting section *}}
			<form action="settings/account/advanced" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel"{{if $open == 'advanced'}} open{{/if}}>
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$h_advn}}</h2></summary>
					<div id="advanced-account-settings">
						<div class="panel-body">
							<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

							{{$pagetype nofilter}}
						</div>
						<div class="panel-footer">
							<button type="submit" name="advanced-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</details>
			</form>

			{{* The relocate setting section *}}
			<form action="settings/account/relocate" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<details class="panel">
					<summary class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$relocate}}</h2></summary>
					<div id="relocate-settings">
						<div class="panel-body">
							<div id="settings-relocate-desc">{{$relocate_text}}</div>
						</div>
						<div class="panel-footer">
							<button type="submit" name="relocate-submit" class="btn btn-primary" value="{{$relocate_button}}">{{$relocate_button}}</button>
						</div>
					</div>
				</details>
			</form>
		</div>
	</div>
</div>
