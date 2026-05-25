{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
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
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="password-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'password'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#password-settings-collapse" aria-expanded="false" aria-controls="password-settings-collapse">
							{{$h_pass}}
						</button>
					</h2>
				</div>
				<div id="password-settings-collapse" class="panel-collapse collapse{{if $open == 'password'}} in{{/if}}" role="tabpanel" aria-labelledby="password-settings">
					<div class="panel-body">
						{{include file="field_password.tpl" field=$password1}}
						{{include file="field_password.tpl" field=$password2}}
						{{include file="field_password.tpl" field=$password3}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="password-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</form>

			{{* The basic setting section *}}
			<form action="settings/account/basic" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="basic-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'basic'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#basic-settings-collapse" aria-expanded="false" aria-controls="basic-settings-collapse">
							{{$h_basic}}
						</button>
					</h2>
				</div>
				<div id="basic-settings-collapse" class="panel-collapse collapse{{if $open == 'basic'}} in{{/if}}" role="tabpanel" aria-labelledby="basic-settings">
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
			</form>

			{{* The privacity setting section *}}
			<form action="settings/account/privacy" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="privacy-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'privacy'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#privacy-settings-collapse" aria-expanded="false" aria-controls="privacy-settings-collapse">
							{{$h_prv}}
						</button>
					</h2>
				</div>
				<div id="privacy-settings-collapse" class="panel-collapse collapse{{if $open == 'privacy'}} in{{/if}}" role="tabpanel" aria-labelledby="privacy-settings">
					<div class="panel-body">
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
			</form>

			<form action="settings/account/expire" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="expire-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'expire'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#expire-settings-collapse" aria-expanded="false" aria-controls="expire-settings-collapse">
							{{$expire.label}}
						</button>
					</h2>
				</div>
				<div id="expire-settings-collapse" class="panel-collapse collapse{{if $open == 'expire'}} in{{/if}}" role="tabpanel" aria-labelledby="expire-settings">
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
			</form>

			{{* The notification setting section *}}
			<form action="settings/account/notification" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="notification-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'notification'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#notification-settings-collapse" aria-expanded="false" aria-controls="notification-settings-collapse">
							{{$h_not}}
						</button>
					</h2>
				</div>
				<div id="notification-settings-collapse" class="panel-collapse collapse{{if $open == 'notification'}} in{{/if}}" role="tabpanel" aria-labelledby="notification-settings">
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
			</form>

			{{* The additional account setting section *}}
			<form action="settings/account/advanced" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="advanced-account-settings">
					<h2>
						<button class="btn-link accordion-toggle{{if $open !== 'advanced'}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings" href="#advanced-account-settings-collapse" aria-expanded="false" aria-controls="advanced-account-settings-collapse">
							{{$h_advn}}
						</button>
					</h2>
				</div>
				<div id="advanced-account-settings-collapse" class="panel-collapse collapse{{if $open == 'advanced'}} in{{/if}}" role="tabpanel" aria-labelledby="advanced-account-settings">
					<div class="panel-body">
						<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

						{{$pagetype nofilter}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="advanced-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</form>

			{{* The relocate setting section *}}
			<form action="settings/account/relocate" method="post" autocomplete="off" class="panel">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<div class="section-subtitle-wrapper panel-heading" role="tab" id="relocate-settings">
					<h2>
						<button class="btn-link accordion-toggle" data-toggle="collapse" data-parent="#settings" href="#relocate-settings-collapse" aria-expanded="false" aria-controls="relocate-settings-collapse">
							{{$relocate}}
						</button>
					</h2>
				</div>
				<div id="relocate-settings-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="relocate-settings">
					<div class="panel-body">
						<div id="settings-relocate-desc">{{$relocate_text}}</div>
					</div>
					<div class="panel-footer">
						<button type="submit" name="relocate-submit" class="btn btn-primary" value="{{$relocate_button}}">{{$relocate_button}}</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
