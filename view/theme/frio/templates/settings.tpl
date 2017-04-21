<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$ptitle }}

	{{$nickname_block}}

	<form action="settings" id="settings-form" method="post" autocomplete="off" >
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			{{* The password setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="password-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#password-settings-collapse" aria-expanded="true" aria-controls="basic-settings-collapse">
							{{$h_pass}}
						</a>
					</h4>
				</div>
				<div id="password-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="password-settings">
					<div class="section-content-tools-wrapper">
						{{include file="field_password.tpl" field=$password1}}
						{{include file="field_password.tpl" field=$password2}}
						{{include file="field_password.tpl" field=$password3}}

						{{if $oid_enable}}
						{{include file="field_input.tpl" field=$openid}}
						{{/if}}

						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			{{* The basic setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="basic-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#basic-settings-collapse" aria-expanded="true" aria-controls="basic-settings-collapse">
							{{$h_basic}}
						</a>
					</h4>
				</div>
				<div id="basic-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="basic-settings">
					<div class="section-content-tools-wrapper">

						{{include file="field_input.tpl" field=$username}}
						{{include file="field_input.tpl" field=$email}}
						{{include file="field_password.tpl" field=$password4}}
						{{include file="field_custom.tpl" field=$timezone}}
						{{include file="field_select.tpl" field=$language}}
						{{include file="field_input.tpl" field=$defloc}}
						{{include file="field_checkbox.tpl" field=$allowloc}}

						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			{{* The privacity setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="privacy-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#privacy-settings-collapse" aria-expanded="true" aria-controls="privacy-settings-collapse">
							{{$h_prv}}
						</a>
					</h4>
				</div>
				<div id="privacy-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="privacy-settings">
					<div class="section-content-tools-wrapper">

						<input type="hidden" name="visibility" value="{{$visibility}}" />

						{{include file="field_input.tpl" field=$maxreq}}

						{{$profile_in_dir}}

						{{$profile_in_net_dir}}

						{{$hide_friends}}

						{{$hide_wall}}

						{{$blockwall}}

						{{$blocktags}}

						{{$suggestme}}

						{{$unkmail}}


						{{include file="field_input.tpl" field=$cntunkmail}}

						{{include file="field_input.tpl" field=$expire.days}}


						<div id="settings-advanced-expire">
							<a id="advanced-expire-menu" title="{{$expire.advanced}}" data-toggle="modal" data-target="#aeModal">{{$expire.label}}</a>

							{{* The modal for advanced-expire *}}
							<div class="modal" id="aeModal">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<h4 class="modal-title">{{$expire.advanced}}</h4>
										</div>
										<div class="modal-body">
											{{include file="field_yesno.tpl" field=$expire.items}}
											{{include file="field_yesno.tpl" field=$expire.notes}}
											{{include file="field_yesno.tpl" field=$expire.starred}}
											{{include file="field_yesno.tpl" field=$expire.network_only}}
										</div>
									</div>
								</div>
							</div>
						</div>

						{{* Block for setting default permissions *}}
						<div id="settings-default-perms" class="settings-default-perms">
							<a id="settings-default-perms-menu" class="settings-default-perms" data-toggle="modal" data-target="#aclModal">{{$permissions}} {{$permdesc}}</a>
							<div id="settings-default-perms-menu-end"></div>

							{{* We include the aclModal directly into the template since we cant use frio's default modal *}}
							<div class="modal" id="aclModal">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<h4 class="modal-title">{{$permissions}}</h4>
										</div>
										<div class="modal-body">
											{{$aclselect}}
										</div>
									</div>
								</div>
							</div>
						</div>
						<br/>
						<div class="clear"></div>

						{{$group_select}}


						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			{{* The notification setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="notification-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#notification-settings-collapse" aria-expanded="true" aria-controls="notification-settings-collapse">
							{{$h_not}}
						</a>
					</h4>
				</div>
				<div id="notification-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="notification-settings">
					<div class="section-content-tools-wrapper">
						<div id="settings-notifications">

							<div id="settings-activity-desc"><h4>{{$activity_options}}</h4></div>

							<div class="group">
								{{include file="field_checkbox.tpl" field=$post_newfriend}}
								{{include file="field_checkbox.tpl" field=$post_joingroup}}
								{{include file="field_checkbox.tpl" field=$post_profilechange}}
							</div>


							<div id="settings-notify-desc"><h4>{{$lbl_not}}</h4></div>

							<div class="group">
								{{include file="field_intcheckbox.tpl" field=$notify1}}
								{{include file="field_intcheckbox.tpl" field=$notify2}}
								{{include file="field_intcheckbox.tpl" field=$notify3}}
								{{include file="field_intcheckbox.tpl" field=$notify4}}
								{{include file="field_intcheckbox.tpl" field=$notify5}}
								{{include file="field_intcheckbox.tpl" field=$notify6}}
								{{include file="field_intcheckbox.tpl" field=$notify7}}
								{{include file="field_intcheckbox.tpl" field=$notify8}}
							</div>

							{{include file="field_checkbox.tpl" field=$email_textonly}}


							{{* commented out because it was commented out in the original template
							<div class="field">
							 <button type="button" onclick="javascript:Notification.requestPermission(function(perm){if(perm === 'granted')alert('{{$desktop_notifications_success_message}}');});">{{$desktop_notifications}}</button>
							 <span class="field_help">{{$desktop_notifications_note}}</span>
							</div>
							*}}

							{{include file="field_yesno.tpl" field=$desktop_notifications}}
							<script>
								(function(){
									var elm = $("#id_{{$desktop_notifications.0}}_onoff");
									var ckbox = $("#id_{{$desktop_notifications.0}}");

									if (getNotificationPermission() === 'granted') {
										ckbox.val(1);
										elm.find(".off").addClass("hidden");
										elm.find(".on").removeClass("hidden");
									}
									if (getNotificationPermission() === null) {
										elm.parent(".field.yesno").hide();
									}

									$("#id_{{$desktop_notifications.0}}_onoff").on("click", function(e){

										if (Notification.permission === 'granted') {
											localStorage.setItem('notification-permissions', ckbox.val()==1 ? 'granted' : 'denied');
										} else if (Notification.permission === 'denied') {
											localStorage.setItem('notification-permissions', 'denied');

											ckbox.val(0);
											elm.find(".on").addClass("hidden");
											elm.find(".off").removeClass("hidden");

										} else if (Notification.permission === 'default') {
											Notification.requestPermission(function(choice) {
												if (choice === 'granted') {
													localStorage.setItem('notification-permissions', ckbox.val()==1 ? 'granted' : 'denied');

												} else {
													localStorage.setItem('notification-permissions', 'denied');
													ckbox.val(0);
													elm.find(".on").addClass("hidden");
													elm.find(".off").removeClass("hidden");
												}
											});
										}

										//console.log(getNotificationPermission());
									})
								})();
							</script>
						</div>

						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			{{* The additional account setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="additional-account-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#additional-account-settings-collapse" aria-expanded="true" aria-controls="additional-account-settings-collapse">
							{{$h_advn}}
						</a>
					</h4>
				</div>
				<div id="additional-account-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="additional-account-settings">
					<div class="section-content-tools-wrapper">

						<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

						{{$pagetype}}

						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			{{* The relocate setting section *}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="relocate-settings">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#relocate-settings-collapse" aria-expanded="true" aria-controls="relocate-settings-collapse">
							{{$relocate}}
						</a>
					</h4>
				</div>
				<div id="relocate-settings-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="relocate-settings">
					<div class="section-content-tools-wrapper">

						<div id="settings-pagetype-desc">{{$relocate_text}}</div>

						<br/>
						<div class="form-group pull-right settings-submit-wrapper" >
							<button type="submit" name="submit" class="btn btn-primary" value="{{$relocate_button|escape:'html'}}">{{$relocate_button|escape:'html'}}</button>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

